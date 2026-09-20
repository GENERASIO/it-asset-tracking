<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function mobile()
    {
        return view('scan.mobile');
    }

    public function desktop()
    {
        return view('scan.desktop');
    }

    public function lookup(Request $request)
    {
        $request->validate(['asset_code' => 'required|string']);

        $asset = Asset::with(['category', 'location', 'assignedUser'])
            ->where('asset_code', $request->asset_code)
            ->first();

        if (!$asset) {
            return response()->json([
                'found' => false,
                'message' => 'Aset dengan kode "' . $request->asset_code . '" tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'found' => true,
            'asset' => $asset,
        ]);
    }
}