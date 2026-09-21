<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Milon\Barcode\Facades\DNS2DFacade as DNS2D;

class BarcodeController extends Controller
{
    public function print(Asset $asset)
    {
        $url = route('assets.show', $asset, true);
        $barcode = DNS2D::getBarcodePNG($url, 'QRCODE', 4, 4);

        return view('barcode.print', compact('asset', 'barcode'));
    }
}