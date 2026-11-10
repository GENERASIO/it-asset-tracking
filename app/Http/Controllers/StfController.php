<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\Location;
use App\Models\StfDocument;
use App\Support\SignatureImage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StfController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q', ''));

        $documents = StfDocument::with(['fromLocation', 'toLocation'])
            ->when($search !== '', fn ($q) => $q->where('reason', 'like', "%{$search}%")
                ->orWhere('sender_name', 'like', "%{$search}%"))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('stf.index', compact('documents', 'search'));
    }

    public function create(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:30'],
            'ids.*' => ['integer', 'exists:assets,id'],
        ], [
            'ids.required' => 'Pilih minimal 1 aset untuk dibuatkan STF.',
        ]);

        $assets = Asset::with(['category', 'location'])
            ->whereIn('id', $request->ids)
            ->orderBy('asset_code')
            ->get();

        $fromIds = $assets->pluck('location_id')->unique()->filter();
        $fromLocationId = $fromIds->count() === 1 ? $fromIds->first() : null;

        $items = $assets->map(function ($a) {
            $specParts = array_filter([
                $a->cpu ? 'Processor: '.$a->cpu : null,
                $a->ram_gb ? 'RAM: '.$a->ram_gb.' GB' : null,
                $a->storage_gb ? 'Storage: '.$a->storage_gb.' GB' : null,
            ]);
            $spec = $specParts ? implode("\n", $specParts) : (string) $a->specification;

            return [
                'asset_id' => $a->id,
                'asset_no' => $a->asset_code,
                'description' => trim(($a->brand ?? '').' '.($a->model ?? '')) ?: $a->name,
                'spec' => $spec,
                'quantity' => 1,
                'notes' => '',
            ];
        });

        $senders = config('bast.first_parties', []);
        $defaultSender = in_array($request->user()->name, $senders, true) ? $request->user()->name : '';

        return view('stf.create', [
            'items' => $items,
            'locations' => Location::orderBy('name')->get(['id', 'name']),
            'senders' => $senders,
            'defaults' => [
                'transfer_date' => now('Asia/Jakarta')->toDateString(),
                'from_location_id' => $fromLocationId,
                'from_location_name' => $fromLocationId ? optional($assets->first()->location)->name : 'Beberapa lokasi',
                'sender_name' => $defaultSender,
                'supervisor_name' => 'Aris Munandar',
                'reason' => 'Perpindahan Asset',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transfer_date' => ['required', 'date'],
            'from_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'to_location_id' => ['required', 'integer', 'exists:locations,id'],
            'reason' => ['required', 'string', 'max:255'],
            'sender_name' => ['required', 'string', Rule::in(config('bast.first_parties', []))],
            'supervisor_name' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'items.*.asset_no' => ['required', 'string', 'max:255'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.spec' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'sender_name.in' => 'PIC Pengirim harus dipilih dari daftar Staff IT.',
            'to_location_id.required' => 'Outlet Tujuan wajib dipilih.',
        ]);

        $document = DB::transaction(function () use ($data, $request) {
            $toLocation = Location::findOrFail($data['to_location_id']);

            $document = StfDocument::create([
                'transfer_date' => $data['transfer_date'],
                'from_location_id' => $data['from_location_id'] ?? null,
                'to_location_id' => $toLocation->id,
                'reason' => $data['reason'],
                'sender_name' => $data['sender_name'],
                'supervisor_name' => $data['supervisor_name'],
                'created_by' => $request->user()->id,
            ]);

            foreach ($data['items'] as $item) {
                $document->items()->create([
                    'asset_id' => $item['asset_id'] ?? null,
                    'asset_no' => $item['asset_no'],
                    'description' => $item['description'],
                    'spec' => $item['spec'] ?? null,
                    'to_location_name' => $toLocation->name,
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);

                // Auto-pindah lokasi aset + catat di log aktivitas.
                if (! empty($item['asset_id'])) {
                    $asset = Asset::with('location')->find($item['asset_id']);
                    if ($asset) {
                        $fromName = optional($asset->location)->name ?? '-';
                        $asset->location_id = $toLocation->id;
                        $asset->save();

                        AssetLog::create([
                            'asset_id' => $asset->id,
                            'action' => 'transfer',
                            'status_before' => $asset->status,
                            'status_after' => $asset->status,
                            'notes' => "Mutasi {$fromName} → {$toLocation->name} ({$document->number})",
                            'handled_by' => $request->user()->id,
                        ]);
                    }
                }
            }

            return $document;
        });

        \App\Support\SlackNotifier::send(
            "🔁 *Mutasi aset (STF)* {$document->number}\n"
            .(optional($document->fromLocation)->name ?? 'Beberapa lokasi').' → '.optional($document->toLocation)->name
            ." · oleh {$document->sender_name}\n"
            .route('stf.show', $document)
        );

        return redirect()->route('stf.show', $document)
            ->with('success', 'STF berhasil dibuat & aset dipindahkan ke outlet tujuan. Tanda tangan di bawah, lalu kirim link TTD ke PIC Penerima via WhatsApp.');
    }

    public function show(StfDocument $stf)
    {
        $stf->load(['items', 'fromLocation', 'toLocation', 'creator']);

        return view('stf.show', compact('stf'));
    }

    public function updateItems(Request $request, StfDocument $stf)
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.spec' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($data['items'] as $row) {
            $stf->items()->where('id', $row['id'])->update([
                'description' => $row['description'],
                'spec' => $row['spec'] ?? null,
                'quantity' => $row['quantity'],
                'notes' => $row['notes'] ?? null,
            ]);
        }

        return redirect()->route('stf.show', $stf)->with('success', 'Rincian STF diperbarui.');
    }

    public function pdf(StfDocument $stf)
    {
        return $this->renderPdf($stf)->download('STF-'.$stf->id.'.pdf');
    }

    public function status(StfDocument $stf)
    {
        return response()->json([
            'sender_signed' => (bool) $stf->sender_signed_at,
            'receiver_signed' => (bool) $stf->receiver_signed_at,
        ]);
    }

    /** Tanda tangan PIC Pengirim (Staff IT) dari halaman detail STF. */
    public function signSender(Request $request, StfDocument $stf)
    {
        $data = $request->validate([
            'signature' => ['required', 'string', 'max:1200000'],
        ]);

        $path = SignatureImage::store($data['signature'], StfDocument::SIGN_DIR);
        if (! $path) {
            return back()->withErrors(['signature' => 'Tanda tangan kosong atau tidak terbaca. Mohon tanda tangan ulang.']);
        }

        SignatureImage::delete($stf->sender_signature);
        $stf->update(['sender_signature' => $path, 'sender_signed_at' => now()]);

        return back()->with('success', 'Tanda tangan PIC Pengirim tersimpan.');
    }

    /** Halaman publik (tanpa login) untuk PIC Penerima tanda tangan. */
    public function signShow(string $token)
    {
        $stf = StfDocument::with(['items', 'fromLocation', 'toLocation'])->where('sign_token', $token)->firstOrFail();

        return view('stf.sign', compact('stf'));
    }

    public function signStore(Request $request, string $token)
    {
        $stf = StfDocument::with('toLocation')->where('sign_token', $token)->firstOrFail();

        $data = $request->validate([
            'receiver_name' => ['required', 'string', 'max:255'],
            'signature' => ['required', 'string', 'max:1200000'],
        ], [
            'receiver_name.required' => 'Nama penerima wajib diisi.',
        ]);

        $path = SignatureImage::store($data['signature'], StfDocument::SIGN_DIR);
        if (! $path) {
            return back()->withInput()->withErrors(['signature' => 'Tanda tangan kosong atau tidak terbaca. Mohon tanda tangan ulang dengan jelas.']);
        }

        SignatureImage::delete($stf->receiver_signature);
        $stf->update([
            'receiver_name' => trim($data['receiver_name']),
            'receiver_signature' => $path,
            'receiver_signed_at' => now(),
        ]);

        \App\Support\SlackNotifier::send(
            "✍️ *STF {$stf->number} ditandatangani* oleh {$stf->receiver_name}"
            .' ('.optional($stf->toLocation)->name.")\n"
            .route('stf.show', $stf)
        );

        return redirect()->route('stf.sign.show', $token)->with('signed', true);
    }

    public function signPdf(string $token)
    {
        $stf = StfDocument::where('sign_token', $token)->firstOrFail();

        return $this->renderPdf($stf)->stream('STF-'.$stf->id.'.pdf');
    }

    private function renderPdf(StfDocument $stf)
    {
        $stf->load(['items', 'fromLocation', 'toLocation']);

        $logoPath = public_path('images/logo.png');
        $logo = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;

        // Tanda tangan atasan (Aris Munandar) ditempel otomatis di kolom Atasan.
        $arisSignPath = public_path('images/signature-aris.png');
        $arisSignature = is_file($arisSignPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($arisSignPath)) : null;

        $senderSignature = SignatureImage::dataUri($stf->sender_signature);
        $receiverSignature = SignatureImage::dataUri($stf->receiver_signature);

        return Pdf::loadView('stf.pdf', compact('stf', 'logo', 'arisSignature', 'senderSignature', 'receiverSignature'))
            ->setPaper('a4', 'landscape');
    }

    public function destroy(StfDocument $stf)
    {
        SignatureImage::delete($stf->sender_signature);
        SignatureImage::delete($stf->receiver_signature);
        $stf->delete();

        return redirect()->route('stf.index')
            ->with('success', 'STF dihapus. Lokasi aset yang sudah dipindahkan tidak berubah.');
    }
}
