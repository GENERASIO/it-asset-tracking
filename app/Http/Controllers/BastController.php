<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BastDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BastController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('q'));

        $documents = BastDocument::with('items.asset')
            ->when($status === 'unsigned', fn ($q) => $q->whereNull('signed_file')->whereNull('second_party_signed_at'))
            ->when($status === 'signed', fn ($q) => $q->where(fn ($q) => $q->whereNotNull('signed_file')->orWhereNotNull('second_party_signed_at')))
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

        $unsignedCount = BastDocument::whereNull('signed_file')->whereNull('second_party_signed_at')->count();

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

        $firstParties = config('bast.first_parties', []);

        // Jabatan terakhir tiap Pihak Pertama di BAST sebelumnya, untuk isian otomatis.
        $lastPositions = BastDocument::whereIn('first_party_name', $firstParties)
            ->latest('id')
            ->get(['first_party_name', 'first_party_position'])
            ->unique('first_party_name')
            ->pluck('first_party_position', 'first_party_name');

        $defaultFirstParty = in_array($request->user()->name, $firstParties, true) ? $request->user()->name : '';

        return view('bast.create', [
            'items' => $items,
            'firstParties' => $firstParties,
            'lastPositions' => $lastPositions,
            'defaults' => [
                'handover_date' => now('Asia/Jakarta')->toDateString(),
                'city' => 'Jakarta',
                'first_party_name' => $defaultFirstParty,
                'first_party_position' => $lastPositions[$defaultFirstParty] ?? '',
                'second_party_name' => $holders->count() === 1 ? (string) $holders->first() : '',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'handover_date' => ['required', 'date'],
            'city' => ['required', 'string', 'max:100'],
            'first_party_name' => ['required', 'string', Rule::in(config('bast.first_parties', []))],
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
        ], [
            'first_party_name.in' => 'Pihak Pertama harus dipilih dari daftar.',
        ], [
            'first_party_name' => 'nama Pihak Pertama',
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

    /** Dipakai halaman detail untuk auto-refresh saat penerima tanda tangan. */
    public function status(BastDocument $bast)
    {
        return response()->json([
            'first_signed' => (bool) $bast->first_party_signed_at,
            'second_signed' => (bool) $bast->second_party_signed_at,
        ]);
    }

    public function pdf(BastDocument $bast)
    {
        return $this->renderPdf($bast->load('items.asset'))->stream($bast->pdf_filename);
    }

    /** PDF dokumen lewat token (publik) - untuk dikirim ke penerima via WhatsApp. */
    public function signPdf(string $token)
    {
        $bast = BastDocument::with('items.asset')->where('sign_token', $token)->firstOrFail();

        return $this->renderPdf($bast)->stream($bast->pdf_filename);
    }

    private function renderPdf(BastDocument $bast)
    {
        $logoPath = public_path('images/logo.png');
        $logo = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
        $sig1 = $this->signatureDataUri($bast->first_party_signature);
        $sig2 = $this->signatureDataUri($bast->second_party_signature);

        return Pdf::loadView('bast.pdf', compact('bast', 'logo', 'sig1', 'sig2'))->setPaper('a4');
    }

    /** Halaman publik (tanpa login) untuk tanda tangan digital oleh penerima. */
    public function signShow(string $token)
    {
        $bast = BastDocument::with('items')->where('sign_token', $token)->firstOrFail();

        return view('bast.sign', compact('bast'));
    }

    public function signStore(Request $request, string $token)
    {
        $bast = BastDocument::where('sign_token', $token)->firstOrFail();

        $data = $request->validate([
            'signature' => ['required', 'string', 'max:1200000'],
        ]);

        $path = $this->saveSignatureDataUrl($data['signature']);
        if (! $path) {
            return back()->withErrors(['signature' => 'Tanda tangan kosong atau tidak terbaca. Mohon tanda tangan ulang dengan jelas.']);
        }

        if ($bast->second_party_signature) {
            Storage::disk(BastDocument::SIGNED_DISK)->delete($bast->second_party_signature);
        }

        $bast->update(['second_party_signature' => $path, 'second_party_signed_at' => now()]);

        return redirect()->route('bast.sign.show', $token)->with('signed', true);
    }

    /** Tanda tangan Pihak Pertama (IT) dari dalam aplikasi. */
    public function signFirst(Request $request, BastDocument $bast)
    {
        $data = $request->validate([
            'signature' => ['required', 'string', 'max:1200000'],
        ]);

        $path = $this->saveSignatureDataUrl($data['signature']);
        if (! $path) {
            return back()->withErrors(['signature' => 'Tanda tangan kosong atau tidak terbaca. Mohon tanda tangan ulang.']);
        }

        if ($bast->first_party_signature) {
            Storage::disk(BastDocument::SIGNED_DISK)->delete($bast->first_party_signature);
        }

        $bast->update(['first_party_signature' => $path, 'first_party_signed_at' => now()]);

        return back()->with('success', 'Tanda tangan Pihak Pertama tersimpan.');
    }

    private function signatureDataUri(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = Storage::disk(BastDocument::SIGNED_DISK);

        return $disk->exists($path) ? 'data:image/png;base64,'.base64_encode($disk->get($path)) : null;
    }

    private function saveSignatureDataUrl(string $dataUrl): ?string
    {
        if (! preg_match('#^data:image/png;base64,#', $dataUrl)) {
            return null;
        }

        $raw = base64_decode(substr($dataUrl, strlen('data:image/png;base64,')), true);
        if ($raw === false || strlen($raw) < 100 || strlen($raw) > 800000) {
            return null;
        }

        // Tolak tanda tangan kosong (hampir tanpa coretan) kalau GD tersedia.
        if (! $this->signatureHasInk($raw)) {
            return null;
        }

        $path = BastDocument::SIGN_DIR.'/'.Str::random(40).'.png';
        Storage::disk(BastDocument::SIGNED_DISK)->put($path, $raw);

        return $path;
    }

    /** true kalau gambar punya cukup piksel bertinta (bukan kosong). Lewati kalau GD tak ada. */
    private function signatureHasInk(string $png): bool
    {
        if (! function_exists('imagecreatefromstring')) {
            return true;
        }

        $img = @imagecreatefromstring($png);
        if (! $img) {
            return true;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $stepX = max(1, (int) ($w / 150));
        $stepY = max(1, (int) ($h / 80));
        $ink = 0;

        for ($x = 0; $x < $w; $x += $stepX) {
            for ($y = 0; $y < $h; $y += $stepY) {
                $alpha = (imagecolorat($img, $x, $y) & 0x7F000000) >> 24; // 0 = opaque, 127 = transparan
                if ($alpha < 100) {
                    $ink++;
                }
            }
        }

        imagedestroy($img);

        return $ink >= 12;
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
