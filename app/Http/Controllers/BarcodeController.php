<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Milon\Barcode\Facades\DNS1DFacade as DNS1D;

class BarcodeController extends Controller
{
    public function print(Asset $asset)
    {
        $barcode = DNS1D::getBarcodePNG($asset->asset_code, 'C128', 3, 60);

        return view('barcode.print', compact('asset', 'barcode'));
    }
}