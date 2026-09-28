<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetLogController extends Controller
{
    public function checkout(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $ok = DB::transaction(function () use ($asset, $validated) {
            // lockForUpdate menutup celah dua checkout barengan pada aset yang sama.
            $locked = Asset::whereKey($asset->id)->lockForUpdate()->first();

            if ($locked->status !== 'available') {
                return false;
            }

            AssetLog::create([
                'asset_id' => $locked->id,
                'action' => 'check_out',
                'from_user_id' => null,
                'to_user_id' => $validated['to_user_id'],
                'status_before' => $locked->status,
                'status_after' => 'in_use',
                'notes' => $validated['notes'] ?? null,
                'handled_by' => auth()->id(),
            ]);

            $locked->update([
                'assigned_to' => $validated['to_user_id'],
                'status' => 'in_use',
            ]);

            return true;
        });

        if (! $ok) {
            return back()->with('error', 'Aset ini tidak dalam status Available, tidak bisa di-checkout.');
        }

        return redirect()->route('assets.show', $asset)->with('success', 'Aset berhasil di-checkout.');
    }

    public function checkin(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'new_status' => 'required|in:available,maintenance,broken',
        ]);

        $ok = DB::transaction(function () use ($asset, $validated) {
            $locked = Asset::whereKey($asset->id)->lockForUpdate()->first();

            if ($locked->status !== 'in_use') {
                return false;
            }

            AssetLog::create([
                'asset_id' => $locked->id,
                'action' => 'check_in',
                'from_user_id' => $locked->assigned_to,
                'to_user_id' => null,
                'status_before' => $locked->status,
                'status_after' => $validated['new_status'],
                'notes' => $validated['notes'] ?? null,
                'handled_by' => auth()->id(),
            ]);

            $locked->update([
                'assigned_to' => null,
                'status' => $validated['new_status'],
            ]);

            return true;
        });

        if (! $ok) {
            return back()->with('error', 'Aset ini tidak sedang dipinjam, tidak bisa di-checkin.');
        }

        return redirect()->route('assets.show', $asset)->with('success', 'Aset berhasil di-checkin.');
    }
}
