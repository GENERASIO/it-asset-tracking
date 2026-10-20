<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BastDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BastController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('q'));

        $documents = BastDocument::with('items.asset')
            ->when($status === 'unsigned', fn ($q) => $q->whereNull('signed_file'))
            ->when($status === 'signed', fn ($q) => $q->whereNotNull('signed_file'))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('second_party_name', 'like', "%{$search}%")
                      ->orWhere('first_party_name', 'like', "%{$search}%")
                      ->orWhereHas('items', fn ($q) => $q->where('detail', 'like', "%{$search}%")
                          ->orWhere('serial_number', 'like', "%{$search}%")
                          ->orWhereHas('asset', fn ($q) => $q->where('asset_code', 'like', "%{$search}%")));
                });
            })
            ->latest('handover_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $unsignedCount = BastDocument::whereNull('signed_file')->count();

        return view('bast.index', compact('documents', 'status', 'search', 'unsignedCount'));
    }

    /**
     * Form BAST, diisi otomatis dari aset yang dipilih (?ids[]=..). Semua isian tetap bisa
     * diedit karena penerima bisa outlet/orang yang tidak terdaftar sebagai user.
     */
    public function create(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:30'],
            'ids.*' => ['integer', 'exists:assets,id'],
        ], [
            'ids.required' => 'Pilih minimal 1 aset untuk dibuatkan BAST.',
        ]);

        $assets = Asset::with(['category', 'assignedUser', 'logs' => fn ($q) => $q->where('action', 'check_out')])
            ->whereIn('id', $request->ids)
            ->orderBy('asset_code')
            ->get();

        $holders = $assets->pluck('assignedUser.name')->unique();

        $items = $assets->map(fn ($asset) => [
            'asset_id' => $asset->id,
            'asset_code' => $asset->asset_code,
            'item_type' => $asset->category->name ?? '',
            'detail' => trim(($asset->brand ?? '').' '.($asset->model ?? '')) ?: $asset->name,
            'serial_number' => $asset->serial_number,
            'quantity' => 1,
            'condition' => $asset->logs->first()?->condition ?? '',
        ]);

        return view('bast.create', [
            'items' => $items,
            'defaults' => [
                'handover_date' => now('Asia/Jakarta')->toDateString(),
                'city' => 'Jakarta',
                'first_party_name' => $request->user()->name,
                'second_party_name' => $holders->count() === 1 ? (string) $holders->first() : '',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'handover_date' => ['required', 'date'],
            'city' => ['required', 'string', 'max:100'],
            'first_party_name' => ['required', 'string', 'max:255'],
            'first_party_position' => ['required', 'string', 'max:255'],
            'second_party_name' => ['required', 'string', 'max:255'],
            'second_party_position' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'items.*.item_type' => ['required', 'string', 'max:255'],
            'items.*.detail' => ['required', 'string', 'max:255'],
            'items.*.serial_number' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.condition' => ['required', 'string', 'max:50'],
        ], [], [
            'first_party_position' => 'jabatan Pihak Pertama',
            'second_party_position' => 'jabatan Pihak Kedua',
            'items.*.item_type' => 'jenis barang',
            'items.*.detail' => 'detail barang',
            'items.*.condition' => 'kondisi barang',
        ]);

        $document = DB::transaction(function () use ($data, $request) {
            $document = BastDocument::create([
                ...collect($data)->except('items')->all(),
                'created_by' => $request->user()->id,
            ]);

            foreach ($data['items'] as $item) {
                $document->items()->create($item);
            }

            return $document;
        });

        return redirect()->route('bast.show', $document)
            ->with('success', 'BAST berhasil dibuat. Cetak PDF-nya, tandatangani, lalu upload hasil scan di halaman ini.');
    }

    public function show(BastDocument $bast)
    {
        $bast->load(['items.asset', 'creator']);

        return view('bast.show', compact('bast'));
    }

    public function pdf(BastDocument $bast)
    {
        $bast->load('items.asset');

        $logoPath = public_path('images/logo.png');
        $logo = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;

        return Pdf::loadView('bast.pdf', compact('bast', 'logo'))
            ->setPaper('a4')
            ->stream($bast->pdf_filename);
    }

    public function uploadSigned(Request $request, BastDocument $bast)
    {
        $request->validate([
            'signed_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'signed_file.mimes' => 'File harus PDF, JPG, atau PNG.',
            'signed_file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $file = $request->file('signed_file');
        $path = $file->store(BastDocument::SIGNED_DIR, BastDocument::SIGNED_DISK);

        if ($bast->signed_file) {
            Storage::disk(BastDocument::SIGNED_DISK)->delete($bast->signed_file);
        }

        $bast->update([
            'signed_file' => $path,
            'signed_file_original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'signed_uploaded_at' => now(),
        ]);

        return redirect()->route('bast.show', $bast)->with('success', 'Scan BAST yang sudah ditandatangani berhasil di-upload.');
    }

    public function signedFile(BastDocument $bast)
    {
        abort_unless($bast->signed_file && Storage::disk(BastDocument::SIGNED_DISK)->exists($bast->signed_file), 404);

        return Storage::disk(BastDocument::SIGNED_DISK)->response(
            $bast->signed_file,
            $bast->signed_file_original_name ?: basename($bast->signed_file)
        );
    }

    public function destroy(BastDocument $bast)
    {
        if ($bast->signed_file) {
            Storage::disk(BastDocument::SIGNED_DISK)->delete($bast->signed_file);
        }

        $bast->delete();

        return redirect()->route('bast.index')->with('success', 'BAST berhasil dihapus.');
    }
}
