<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetLog;
use Illuminate\Http\Request;

class AssetLogController extends Controller
{
    public function checkout(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        if ($asset->status !== 'available') {
            return back()->with('error', 'Aset ini tidak dalam status Available, tidak bisa di-checkout.');
        }

        AssetLog::create([
            'asset_id' => $asset->id,
            'action' => 'check_out',
            'from_user_id' => null,
            'to_user_id' => $validated['to_user_id'],
            'status_before' => $asset->status,
            'status_after' => 'in_use',
            'notes' => $validated['notes'] ?? null,
            'handled_by' => auth()->id(),
        ]);

        $asset->update([
            'assigned_to' => $validated['to_user_id'],
            'status' => 'in_use',
        ]);

        return redirect()->route('assets.show', $asset)->with('success', 'Aset berhasil di-checkout.');
    }

    public function checkin(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'new_status' => 'required|in:available,maintenance,broken',
        ]);

        if ($asset->status !== 'in_use') {
            return back()->with('error', 'Aset ini tidak sedang dipinjam, tidak bisa di-checkin.');
        }

        AssetLog::create([
            'asset_id' => $asset->id,
            'action' => 'check_in',
            'from_user_id' => $asset->assigned_to,
            'to_user_id' => null,
            'status_before' => $asset->status,
            'status_after' => $validated['new_status'],
            'notes' => $validated['notes'] ?? null,
            'handled_by' => auth()->id(),
        ]);

        $asset->update([
            'assigned_to' => null,
            'status' => $validated['new_status'],
        ]);

        return redirect()->route('assets.show', $asset)->with('success', 'Aset berhasil di-checkin.');
    }
}
