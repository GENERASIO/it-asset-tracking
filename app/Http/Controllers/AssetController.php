<?php

namespace App\Http\Controllers;

use App\Exports\AssetsExport;
use App\Imports\AssetsImport;
use App\Models\Asset;
use App\Models\AssetPhoto;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use App\Services\AssetCodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AssetController extends Controller
{
    /**
     * Halaman publik (tanpa login) yang dibuka waktu scan QR di label aset.
     * Sengaja cuma tampilkan info dasar - tanpa harga beli, pemegang aset,
     * atau riwayat mutasi, karena halaman ini bisa diakses siapa saja.
     */
    public function publicInfo(Asset $asset)
    {
        $asset->load(['category', 'location']);

        return view('assets.public-info', compact('asset'));
    }

    public function searchSuggestions(Request $request)
    {
        $search = trim((string) $request->query('q'));

        if ($search === '') {
            return response()->json([]);
        }

        $assets = Asset::query()
            ->where(function ($q) use ($search) {
                $q->where('asset_code', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%')
                  ->orWhere('serial_number', 'like', '%' . $search . '%')
                  ->orWhereHas('assignedUser', function ($uq) use ($search) {
                      $uq->where('name', 'like', '%' . $search . '%');
                  });
            })
            ->limit(8)
            ->get(['id', 'asset_code', 'name', 'status'])
            ->map(fn ($asset) => [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
                'status' => $asset->status,
            ]);

        return response()->json($assets);
    }

    public function index(Request $request)
    {
        $requestedView = $request->query('view');

        if (in_array($requestedView, ['board', 'table'], true)) {
            session(['assets_view' => $requestedView]);
            $view = $requestedView;
        } else {
            $view = session('assets_view', 'board');
        }

        $query = $this->filteredAssets($request)->with(['category', 'location', 'assignedUser']);

        if ($view === 'table') {
            $sortable = [
                'asset_code' => 'assets.asset_code',
                'name' => 'assets.name',
                'category' => 'categories.name',
                'location' => 'locations.name',
                'status' => 'assets.status',
                'price' => 'assets.purchase_price',
                'created_at' => 'assets.created_at',
            ];

            $sort = $request->query('sort', 'created_at');
            $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
            $sortColumn = $sortable[$sort] ?? 'assets.created_at';

            $assets = $query->select('assets.*')
                ->leftJoin('categories', 'categories.id', '=', 'assets.category_id')
                ->leftJoin('locations', 'locations.id', '=', 'assets.location_id')
                ->orderBy($sortColumn, $direction)
                ->paginate(25)
                ->withQueryString();
        } else {
            $assets = $query->latest()->get()->groupBy('status');
        }

        $categories = Category::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();

        return view('assets.index', compact('assets', 'categories', 'locations', 'view'));
    }

    /**
     * Filter daftar aset dari query string - dipakai halaman daftar aset dan export Excel,
     * supaya yang diexport sama dengan yang sedang ditampilkan.
     */
    private function filteredAssets(Request $request)
    {
        $query = Asset::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('assets.asset_code', 'like', '%' . $search . '%')
                  ->orWhere('assets.name', 'like', '%' . $search . '%')
                  ->orWhere('assets.serial_number', 'like', '%' . $search . '%')
                  ->orWhere('assets.brand', 'like', '%' . $search . '%')
                  ->orWhere('assets.model', 'like', '%' . $search . '%')
                  ->orWhereHas('assignedUser', fn ($uq) => $uq->where('name', 'like', '%' . $search . '%'))
                  ->orWhereHas('location', fn ($lq) => $lq->where('name', 'like', '%' . $search . '%'));
            });
        }

        if ($request->filled('status')) {
            $query->where('assets.status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('assets.category_id', $request->category_id);
        }

        if ($request->filled('location_id')) {
            $query->where('assets.location_id', $request->location_id);
        }

        // Harga dari input berformat ("Rp 5.000.000") - ambil angkanya saja.
        $priceMin = preg_replace('/\D/', '', (string) $request->query('price_min'));
        $priceMax = preg_replace('/\D/', '', (string) $request->query('price_max'));

        if ($priceMin !== '') {
            $query->where('assets.purchase_price', '>=', (int) $priceMin);
        }

        if ($priceMax !== '') {
            $query->where('assets.purchase_price', '<=', (int) $priceMax);
        }

        if ($request->query('price') === 'estimated') {
            $query->whereNotNull('assets.purchase_price')->where('assets.purchase_price_estimated', true);
        } elseif ($request->query('price') === 'actual') {
            $query->whereNotNull('assets.purchase_price')->where('assets.purchase_price_estimated', false);
        } elseif ($request->query('price') === 'empty') {
            $query->whereNull('assets.purchase_price');
        }

        return $query;
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('assets.create', compact('categories', 'locations', 'users'));
    }

    // Harga beli disimpan dalam Rupiah penuh (bilangan bulat). Minimal Rp 1.000 supaya input
    // dalam satuan juta (mis. "6,8" untuk 6,8 juta) tidak lolos lagi.
    private const PRICE_RULE = 'nullable|integer|min:1000|max:999999999999';

    private const PRICE_MESSAGES = [
        'purchase_price.min' => 'Harga beli terlalu kecil. Isi dalam Rupiah penuh, mis. 6800000 untuk Rp 6.800.000.',
        'purchase_price.integer' => 'Harga beli hanya boleh berisi angka.',
    ];

    /** "Rp 10.000.000" (dari input berformat) -> "10000000"; kosong -> null. */
    private function normalizeRupiah(Request $request): void
    {
        if (! $request->has('purchase_price')) {
            return;
        }

        $digits = preg_replace('/\D/', '', (string) $request->input('purchase_price'));
        $request->merge(['purchase_price' => $digits === '' ? null : $digits]);
    }

    public function store(Request $request)
    {
        $this->normalizeRupiah($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'location_id' => 'required|exists:locations,id',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => ['nullable', 'string', 'max:255', Rule::unique('assets', 'serial_number')->whereNull('deleted_at')],
            'specification' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:available,in_use,maintenance,broken,retired',
            'purchase_date' => 'nullable|date',
            'purchase_price' => self::PRICE_RULE,
            'warranty_expired_at' => 'nullable|date',
            'photos' => 'required|array|min:1|max:5',
            'photos.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ], self::PRICE_MESSAGES);

        $photos = $validated['photos'];
        unset($validated['photos']);

        $asset = DB::transaction(function () use ($validated) {
            $validated['asset_code'] = AssetCodeGenerator::generate(
                $validated['category_id'],
                $validated['location_id']
            );

            return Asset::create($validated);
        });
        $this->storePhotos($asset, $photos);

        return redirect()->route('assets.index')->with('success', 'Aset berhasil ditambahkan.');
    }

    public function show(Request $request, Asset $asset)
    {
        $user = $request->user();
        $isStaff = in_array($user->role, ['super_admin', 'it_staff']);

        abort_unless($isStaff || $asset->assigned_to === $user->id, 403, 'Anda tidak punya akses ke aset ini.');

        $asset->load([
            'category', 'location', 'assignedUser', 'photos',
            'logs.fromUser', 'logs.toUser', 'logs.handledBy',
            'maintenanceLogs.createdBy',
        ]);

        return view('assets.show', compact('asset'));
    }

    public function edit(Asset $asset)
    {
        $categories = Category::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('assets.edit', compact('asset', 'categories', 'locations', 'users'));
    }

    public function update(Request $request, Asset $asset)
    {
        $existingCount = $asset->photos()->count();

        $this->normalizeRupiah($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'location_id' => 'required|exists:locations,id',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => ['nullable', 'string', 'max:255', Rule::unique('assets', 'serial_number')->ignore($asset->id)->whereNull('deleted_at')],
            'specification' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:available,in_use,maintenance,broken,retired',
            'purchase_date' => 'nullable|date',
            'purchase_price' => self::PRICE_RULE,
            'warranty_expired_at' => 'nullable|date',
            'photos' => ['nullable', 'array', function ($attribute, $value, $fail) use ($existingCount) {
                if ($existingCount + count($value) > 5) {
                    $fail('Total foto maksimal 5. Hapus foto lama dulu kalau mau tambah yang baru.');
                }
            }],
            'photos.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ], self::PRICE_MESSAGES);

        $photos = $validated['photos'] ?? [];
        unset($validated['photos']);

        // Harga yang diubah manual dianggap harga asli, jadi tanda "estimasi" dilepas.
        if ($asset->purchase_price_estimated
            && ($validated['purchase_price'] ?? null) !== null && (int) $validated['purchase_price'] !== (int) $asset->purchase_price) {
            $validated['purchase_price_estimated'] = false;
        }
        if (($validated['purchase_price'] ?? null) === null) {
            $validated['purchase_price_estimated'] = false;
        }

        $asset->update($validated);
        $this->storePhotos($asset, $photos);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Aset berhasil diperbarui.']);
        }

        return redirect()->route('assets.index')->with('success', 'Aset berhasil diperbarui.');
    }

    public function destroy(Asset $asset)
    {
        foreach ($asset->photos as $photo) {
            Storage::disk('assets')->delete($photo->path);
        }
        $asset->photos()->delete();
        $asset->delete();
        return redirect()->route('assets.index')->with('success', 'Aset berhasil dihapus.');
    }

    public function destroyPhoto(Asset $asset, AssetPhoto $photo)
    {
        abort_unless($photo->asset_id === $asset->id, 404);

        Storage::disk('assets')->delete($photo->path);
        $photo->delete();
        $asset->syncPrimaryPhoto();

        return back()->with('success', 'Foto berhasil dihapus.');
    }

    protected function storePhotos(Asset $asset, array $photos): void
    {
        if (empty($photos)) {
            return;
        }

        $nextOrder = $asset->photos()->max('sort_order');
        $nextOrder = is_null($nextOrder) ? 0 : $nextOrder + 1;

        foreach ($photos as $photo) {
            $path = $photo->store('', 'assets');

            $asset->photos()->create([
                'path' => $path,
                'sort_order' => $nextOrder++,
            ]);
        }

        $asset->syncPrimaryPhoto();
    }

    public function export(Request $request)
    {
        $assets = $this->filteredAssets($request)
            ->with(['category', 'location', 'assignedUser'])
            ->latest()
            ->get();

        return Excel::download(new AssetsExport($assets), 'data-aset-' . now()->format('Ymd-His') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new AssetsImport, $request->file('file'));

        return redirect()->route('assets.index')->with('success', 'Data aset berhasil diimpor.');
    }

    public function updateStatus(Request $request, Asset $asset)
    {
        $request->validate(['status' => 'required|in:available,in_use,maintenance,broken,retired']);

        $oldStatus = $asset->status;

        // Samakan dengan alur check-in resmi: begitu aset tidak lagi berstatus "in_use",
        // data "dipegang oleh" ikut dikosongkan supaya tidak nyangkut ke orang yang lama.
        $newAttributes = ['status' => $request->status];
        if ($request->status !== 'in_use') {
            $newAttributes['assigned_to'] = null;
        }

        $asset->update($newAttributes);

        \App\Models\AssetLog::create([
            'asset_id' => $asset->id,
            'action' => 'status_change',
            'status_before' => $oldStatus,
            'status_after' => $request->status,
            'handled_by' => $request->user()->id,
        ]);

        if ($oldStatus !== $request->status) {
            $by = $request->user()->name;
            $new = $request->status;
            $url = route('assets.show', $asset);
            // Dikirim setelah response (terminating) supaya dropdown status tetap cepat.
            app()->terminating(function () use ($asset, $oldStatus, $new, $by, $url) {
                $labels = ['available' => 'Available', 'in_use' => 'In Use', 'maintenance' => 'Maintenance', 'broken' => 'Broken', 'retired' => 'Retired'];
                \App\Support\SlackNotifier::send(
                    "🔧 *Status aset diubah* oleh {$by}\n"
                    ."`{$asset->asset_code}` — {$asset->name}\n"
                    .($labels[$oldStatus] ?? $oldStatus).' → *'.($labels[$new] ?? $new)."*\n".$url
                );
            });
        }

        return response()->json(['success' => true]);
    }

    public function toggleLost(Request $request, Asset $asset)
    {
        // Tandai hilang / ditemukan. Saat hilang, agent melacak lokasi di mana pun (bukan hanya
        // Wi-Fi kantor) sampai ditandai ditemukan lagi.
        $lost = ! $asset->reported_lost_at;
        $asset->update(['reported_lost_at' => $lost ? now() : null]);

        $message = $lost
            ? 'Aset ditandai HILANG. Agent akan melacak lokasi di mana pun pada check-in berikutnya.'
            : 'Aset ditandai sudah ditemukan. Lokasi kembali hanya dicatat di Wi-Fi kantor.';

        $by = $request->user()->name;
        $url = route('assets.show', $asset);
        app()->terminating(function () use ($asset, $lost, $by, $url) {
            \App\Support\SlackNotifier::send(
                ($lost ? '🚨 *Aset DITANDAI HILANG*' : '✅ *Aset ditandai DITEMUKAN*')." oleh {$by}\n"
                ."`{$asset->asset_code}` — {$asset->name}\n".$url
            );
        });

        return back()->with('success', $message);
    }
}