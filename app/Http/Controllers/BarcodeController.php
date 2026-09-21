<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Milon\Barcode\Facades\DNS2DFacade as DNS2D;

class BarcodeController extends Controller
{
    public function print(Asset $asset)
    {
        $url = route('assets.show', $asset, true);
        $barcode = DNS2D::getBarcodePNG($url, 'QRCODE', 4, 4);

        return view('barcode.print', compact('asset', 'barcode'));
    }

    public function printBatch(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:assets,id']);

        $assets = Asset::whereIn('id', $request->ids)->get();

        $assets->transform(function ($asset) {
            $url = route('assets.show', $asset, true);
            $asset->qrcode = DNS2D::getBarcodePNG($url, 'QRCODE', 4, 4);
            return $asset;
        });

        return view('barcode.print-batch', compact('assets'));
    }
}