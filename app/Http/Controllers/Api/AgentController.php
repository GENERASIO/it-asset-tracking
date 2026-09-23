<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Services\AssetCodeGenerator;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function checkin(Request $request)
    {
        $data = $request->validate([
            'serial_number' => ['required', 'string', 'max:191'],
            'hostname' => ['required', 'string', 'max:191'],
            'os_name' => ['required', 'string', 'max:100'],
            'os_version' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:191'],
            'model' => ['nullable', 'string', 'max:191'],
            'ram_gb' => ['nullable', 'integer', 'min:0', 'max:4096'],
            'storage_gb' => ['nullable', 'integer', 'min:0', 'max:1048576'],
            'mac_address' => ['nullable', 'string', 'max:64'],
            'username' => ['nullable', 'string', 'max:191'],
        ]);

        $asset = Asset::where('serial_number', $data['serial_number'])->first();
        $created = false;

        if (! $asset) {
            $category = Category::where('name', 'Lainnya')->first() ?? Category::orderBy('id')->firstOrFail();
            $location = Location::where('name', 'Tidak Diketahui')->first() ?? Location::orderBy('id')->firstOrFail();

            $name = trim(($data['brand'] ?? '').' '.($data['model'] ?? '')) ?: $data['hostname'];

            $asset = new Asset([
                'asset_code' => AssetCodeGenerator::generate($category->id, $location->id),
                'name' => $name,
                'category_id' => $category->id,
                'location_id' => $location->id,
                'status' => 'in_use',
                'serial_number' => $data['serial_number'],
                'specification' => 'Didaftarkan otomatis via agent. Mohon lengkapi kategori, lokasi, dan penanggung jawab yang sesuai.',
            ]);
            $created = true;
        }

        if (empty($asset->brand) && ! empty($data['brand'])) {
            $asset->brand = $data['brand'];
        }
        if (empty($asset->model) && ! empty($data['model'])) {
            $asset->model = $data['model'];
        }

        $asset->hostname = $data['hostname'];
        $asset->os_name = $data['os_name'];
        $asset->os_version = $data['os_version'] ?? null;
        $asset->ram_gb = $data['ram_gb'] ?? null;
        $asset->storage_gb = $data['storage_gb'] ?? null;
        $asset->mac_address = $data['mac_address'] ?? null;
        $asset->last_seen_at = now();
        $asset->save();

        return response()->json([
            'status' => 'ok',
            'created' => $created,
            'asset_code' => $asset->asset_code,
        ], $created ? 201 : 200);
    }
}
