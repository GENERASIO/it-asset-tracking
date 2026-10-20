<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetLicense;
use App\Models\SoftwareEntitlement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SoftwareLicenseController extends Controller
{
    public function index()
    {
        $reminderDays = config('licenses.reminder_days');

        $entitlements = SoftwareEntitlement::orderBy('name')->get()->map(function ($entitlement) use ($reminderDays) {
            $entitlement->used = $entitlement->usedCount();
            $entitlement->over = $entitlement->used > $entitlement->quantity;
            $entitlement->expiring = $entitlement->expires_at && $entitlement->expires_at->lte(now()->addDays($reminderDays));

            return $entitlement;
        });

        $licenses = AssetLicense::with('asset.assignedUser')->get()->filter(fn ($license) => $license->asset);
        $office = $licenses->where('product', 'office');

        $summary = [
            'assets_synced' => Asset::whereNotNull('inventory_synced_at')->count(),
            'office_official' => $office->where('suspected', false)->whereIn('status', ['licensed', 'subscription'])->pluck('asset_id')->unique()->count(),
            'office_suspected' => $office->where('suspected', true)->pluck('asset_id')->unique()->count(),
            'windows_suspected' => $licenses->where('product', 'windows')->where('suspected', true)->pluck('asset_id')->unique()->count(),
            'needs_attention' => AssetLicense::needsAttention($reminderDays)->distinct('asset_id')->count('asset_id'),
        ];

        $suspected = $licenses->where('suspected', true)->sortBy(fn ($license) => $license->asset->asset_code)->values();

        $attention = AssetLicense::needsAttention($reminderDays)
            ->with('asset.assignedUser')
            ->orderByRaw('expires_at IS NULL, expires_at')
            ->get()
            ->filter(fn ($license) => $license->asset);

        // Daftar edisi yang terdeteksi, untuk membantu mengisi kata kunci pencocokan lisensi.
        $editions = $licenses->groupBy('name')->map(fn ($group, $name) => [
            'name' => $name,
            'product' => $group->first()->product,
            'total' => $group->pluck('asset_id')->unique()->count(),
            'suspected' => $group->where('suspected', true)->pluck('asset_id')->unique()->count(),
        ])->sortByDesc('total')->values();

        return view('software-licenses.index', compact('entitlements', 'summary', 'suspected', 'attention', 'editions', 'reminderDays'));
    }

    public function store(Request $request)
    {
        SoftwareEntitlement::create($this->validated($request));

        return redirect()->route('software-licenses.index')->with('success', 'Lisensi yang dibeli berhasil ditambahkan.');
    }

    public function edit(SoftwareEntitlement $entitlement)
    {
        return view('software-licenses.edit', compact('entitlement'));
    }

    public function update(Request $request, SoftwareEntitlement $entitlement)
    {
        $entitlement->update($this->validated($request));

        return redirect()->route('software-licenses.index')->with('success', 'Lisensi berhasil diperbarui.');
    }

    public function destroy(SoftwareEntitlement $entitlement)
    {
        $entitlement->delete();

        return redirect()->route('software-licenses.index')->with('success', 'Lisensi berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'match' => ['required', 'string', 'max:255'],
            'license_type' => ['required', Rule::in(array_keys(SoftwareEntitlement::TYPES))],
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'expires_at' => ['nullable', 'date', 'required_if:license_type,subscription'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'expires_at.required_if' => 'Tanggal perpanjangan wajib diisi untuk lisensi langganan.',
        ]);
    }
}
