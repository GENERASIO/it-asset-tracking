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
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $requestedView = $request->query('view');

        if (in_array($requestedView, ['board', 'table'], true)) {
            session(['assets_view' => $requestedView]);
            $view = $requestedView;
        } else {
            $view = session('assets_view', 'board');
        }

        $query = Asset::with(['category', 'location', 'assignedUser']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('assets.asset_code', 'like', '%' . $request->search . '%')
                  ->orWhere('assets.name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('assets.status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('assets.category_id', $request->category_id);
        }

        if ($view === 'table') {
            $sortable = [
                'asset_code' => 'assets.asset_code',
                'name' => 'assets.name',
                'category' => 'categories.name',
                'location' => 'locations.name',
                'status' => 'assets.status',
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

        return view('assets.index', compact('assets', 'categories', 'view'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('assets.create', compact('categories', 'locations', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'location_id' => 'required|exists:locations,id',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'specification' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:available,in_use,maintenance,broken,retired',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric',
            'warranty_expired_at' => 'nullable|date',
            'photos' => 'required|array|min:1|max:5',
            'photos.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $validated['asset_code'] = AssetCodeGenerator::generate(
            $validated['category_id'],
            $validated['location_id']
        );

        $photos = $validated['photos'];
        unset($validated['photos']);

        $asset = Asset::create($validated);
        $this->storePhotos($asset, $photos);

        return redirect()->route('assets.index')->with('success', 'Aset berhasil ditambahkan.');
    }

    public function show(Asset $asset)
    {
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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'location_id' => 'required|exists:locations,id',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'specification' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:available,in_use,maintenance,broken,retired',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric',
            'warranty_expired_at' => 'nullable|date',
            'photos' => ['nullable', 'array', function ($attribute, $value, $fail) use ($existingCount) {
                if ($existingCount + count($value) > 5) {
                    $fail('Total foto maksimal 5. Hapus foto lama dulu kalau mau tambah yang baru.');
                }
            }],
            'photos.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $photos = $validated['photos'] ?? [];
        unset($validated['photos']);

        $asset->update($validated);
        $this->storePhotos($asset, $photos);

        return redirect()->route('assets.index')->with('success', 'Aset berhasil diperbarui.');
    }

    public function destroy(Asset $asset)
    {
        foreach ($asset->photos as $photo) {
            Storage::disk('assets')->delete($photo->path);
        }
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

    public function export()
    {
        return Excel::download(new AssetsExport, 'data-aset-' . now()->format('Ymd-His') . '.xlsx');
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
        $asset->update(['status' => $request->status]);

        \App\Models\AssetLog::create([
            'asset_id' => $asset->id,
            'action' => 'status_change',
            'status_before' => $oldStatus,
            'status_after' => $request->status,
            'handled_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true]);
    }
}