<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\MaintenanceLog;
use Illuminate\Http\Request;

class MaintenanceLogController extends Controller
{
    public function store(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'issue' => 'required|string',
            'technician' => 'nullable|string',
            'reported_at' => 'required|date',
        ]);

        MaintenanceLog::create([
            'asset_id' => $asset->id,
            'issue' => $validated['issue'],
            'technician' => $validated['technician'] ?? null,
            'reported_at' => $validated['reported_at'],
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);

        if (in_array($asset->status, ['available', 'in_use'])) {
            $asset->status = 'maintenance';
            $asset->save();
        }

        return redirect()->route('assets.show', $asset)->with('success', 'Laporan maintenance berhasil ditambahkan.');
    }

    public function update(Request $request, MaintenanceLog $maintenanceLog)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,done',
            'action_taken' => 'nullable|string',
            'cost' => 'nullable|numeric',
            'resolved_at' => 'required_if:status,done|nullable|date',
            'new_asset_status' => 'required_if:status,done|nullable|in:available,broken,retired',
        ]);

        $maintenanceLog->update([
            'status' => $validated['status'],
            'action_taken' => $validated['action_taken'] ?? null,
            'cost' => $validated['cost'] ?? null,
            'resolved_at' => $validated['resolved_at'] ?? null,
        ]);

        if ($validated['status'] === 'done') {
            $maintenanceLog->asset->update([
                'status' => $validated['new_asset_status'],
            ]);
        }

        return redirect()->route('assets.show', $maintenanceLog->asset)->with('success', 'Maintenance log berhasil diperbarui.');
    }
}
