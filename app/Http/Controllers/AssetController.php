<?php

namespace App\Http\Controllers;

use App\Exports\AssetsExport;
use App\Imports\AssetsImport;
use App\Models\Asset;
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
        $query = Asset::with(['category', 'location', 'assignedUser']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('asset_code', 'like', '%' . $request->search . '%')
                  ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $assets = $query->latest()->get()->groupBy('status');
        $categories = Category::orderBy('name')->get();

        return view('assets.index', compact('assets', 'categories'));
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
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $validated['asset_code'] = AssetCodeGenerator::generate(
            $validated['category_id'],
            $validated['location_id']
        );

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('assets', 'public');
        }

        Asset::create($validated);

        return redirect()->route('assets.index')->with('success', 'Aset berhasil ditambahkan.');
    }

    public function show(Asset $asset)
    {
        $asset->load([
            'category', 'location', 'assignedUser',
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
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($asset->photo) {
                Storage::disk('public')->delete($asset->photo);
            }
            $validated['photo'] = $request->file('photo')->store('assets', 'public');
        }

        $asset->update($validated);

        return redirect()->route('assets.index')->with('success', 'Aset berhasil diperbarui.');
    }

    public function destroy(Asset $asset)
    {
        if ($asset->photo) {
            Storage::disk('public')->delete($asset->photo);
        }
        $asset->delete();
        return redirect()->route('assets.index')->with('success', 'Aset berhasil dihapus.');
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