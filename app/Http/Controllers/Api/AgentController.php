<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Services\AssetCodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'location_accuracy_m' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);

        [$asset, $created] = DB::transaction(function () use ($data) {
            // lockForUpdate juga menutup celah dua check-in dengan serial_number yang
            // sama datang bersamaan (salah satu akan menunggu transaksi lain selesai).
            $asset = Asset::where('serial_number', $data['serial_number'])->lockForUpdate()->first();
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

            // Lokasi GPS hanya ditimpa kalau agent berhasil mendapatkannya, supaya posisi
            // terakhir yang diketahui tidak hilang saat Location Services sedang mati.
            if (isset($data['latitude'], $data['longitude'])) {
                $asset->latitude = $data['latitude'];
                $asset->longitude = $data['longitude'];
                $asset->location_accuracy_m = $data['location_accuracy_m'] ?? null;
                $asset->geo_captured_at = now();
            }

            $asset->save();

            return [$asset, $created];
        });

        return response()->json([
            'status' => 'ok',
            'created' => $created,
            'asset_code' => $asset->asset_code,
            // Agent membandingkan ini dengan hash checkin.ps1 miliknya sendiri; kalau beda,
            // agent mengunduh versi terbaru dari endpoint script() di bawah (update otomatis).
            // null kalau file script tidak ada di server - check-in tetap jalan, hanya update dilewati.
            'script_sha256' => is_file(self::scriptPath()) ? hash_file('sha256', self::scriptPath()) : null,
        ], $created ? 201 : 200);
    }

    public function script()
    {
        $path = self::scriptPath();
        abort_unless(is_file($path), 404, 'Script agent tidak tersedia di server.');

        return response()->file($path, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'X-Script-Sha256' => hash_file('sha256', $path),
        ]);
    }

    private static function scriptPath(): string
    {
        return base_path('agents/windows/checkin.ps1');
    }
}
