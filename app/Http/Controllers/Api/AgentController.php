<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\Category;
use App\Models\Location;
use App\Models\PriceReference;
use App\Services\AgentInventorySync;
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
            'cpu' => ['nullable', 'string', 'max:191'],
            'brand' => ['nullable', 'string', 'max:191'],
            'model' => ['nullable', 'string', 'max:191'],
            'ram_gb' => ['nullable', 'integer', 'min:0', 'max:4096'],
            'storage_gb' => ['nullable', 'integer', 'min:0', 'max:1048576'],
            'mac_address' => ['nullable', 'string', 'max:64'],
            'username' => ['nullable', 'string', 'max:191'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'location_accuracy_m' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'location_status' => ['nullable', 'string', 'max:40'],
            // Isi tiap item divalidasi longgar di AgentInventorySync (dipotong/dilewati, bukan
            // ditolak), supaya satu nama aplikasi yang aneh tidak menggagalkan check-in.
            'software' => ['nullable', 'array', 'max:5000'],
            'licenses' => ['nullable', 'array', 'max:50'],
            'crack_indicators' => ['nullable', 'array', 'max:50'],
        ]);

        // Pelaku log = user pembuat token agent yang dipakai laptop ini.
        $handledBy = optional($request->attributes->get('agentToken'))->created_by;

        [$asset, $created] = DB::transaction(function () use ($data, $handledBy) {
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

            // Auto-isi harga beli dari buku harga per model, kalau masih kosong.
            // Harga yang sudah diisi manual tidak ditimpa.
            if (is_null($asset->purchase_price)) {
                $price = PriceReference::priceFor($asset->name)
                    ?? PriceReference::priceFor(trim(($asset->brand ?? '').' '.($asset->model ?? '')));
                if (! is_null($price)) {
                    $asset->purchase_price = $price;
                    $asset->purchase_price_estimated = false;
                }
            }

            $asset->hostname = $data['hostname'];
            $asset->os_name = $data['os_name'];
            $asset->os_version = $data['os_version'] ?? null;
            // Agent lama tidak mengirim cpu, jadi nilai yang sudah ada tidak ditimpa kosong.
            if (! empty($data['cpu'])) {
                $asset->cpu = trim(preg_replace('/\s+/', ' ', $data['cpu']));
            }
            $asset->ram_gb = $data['ram_gb'] ?? null;
            $asset->storage_gb = $data['storage_gb'] ?? null;
            $asset->mac_address = $data['mac_address'] ?? null;
            $asset->last_seen_at = now();
            if (! empty($data['location_status'])) {
                $asset->location_status = $data['location_status'];
            }

            // Lokasi GPS hanya ditimpa kalau agent berhasil mendapatkannya, supaya posisi
            // terakhir yang diketahui tidak hilang saat Location Services sedang mati.
            if (isset($data['latitude'], $data['longitude'])) {
                $asset->latitude = $data['latitude'];
                $asset->longitude = $data['longitude'];
                $asset->location_accuracy_m = $data['location_accuracy_m'] ?? null;
                $asset->geo_captured_at = now();
            }

            $asset->save();

            // Catat aktivitas saat perangkat baru pertama kali didaftarkan agent,
            // supaya muncul di notifikasi, aktivitas terbaru, dan tab Aktivitas aset.
            if ($created && $handledBy) {
                AssetLog::create([
                    'asset_id' => $asset->id,
                    'action' => 'agent_register',
                    'status_after' => $asset->status,
                    'notes' => 'Perangkat baru didaftarkan otomatis oleh agent',
                    'handled_by' => $handledBy,
                ]);
            }

            // Agent lama tidak mengirim key software/licenses, jadi data inventaris yang
            // sudah ada tidak ikut terhapus.
            app(AgentInventorySync::class)->sync($asset, $data);

            return [$asset, $created];
        });

        $scriptPath = self::scriptPath($data['os_name']);

        return response()->json([
            'status' => 'ok',
            'created' => $created,
            'asset_code' => $asset->asset_code,
            // Agent membandingkan ini dengan hash script check-in miliknya sendiri; kalau beda,
            // agent mengunduh versi terbaru dari endpoint script() di bawah (update otomatis).
            // null kalau file script tidak ada di server - check-in tetap jalan, hanya update dilewati.
            'script_sha256' => is_file($scriptPath) ? hash_file('sha256', $scriptPath) : null,
            // Kalau aset ditandai hilang, agent melacak lokasi di mana pun (abaikan batas Wi-Fi kantor).
            'track_location_always' => (bool) $asset->reported_lost_at,
        ], $created ? 201 : 200);
    }

    public function script(Request $request)
    {
        // Agent Windows versi awal memanggil tanpa ?os=, jadi default-nya windows.
        $path = self::scriptPath($request->query('os', 'windows'));
        abort_unless(is_file($path), 404, 'Script agent tidak tersedia di server.');

        return response()->file($path, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'X-Script-Sha256' => hash_file('sha256', $path),
        ]);
    }

    private static function scriptPath(string $os): string
    {
        return strtolower($os) === 'macos'
            ? base_path('agents/macos/checkin.sh')
            : base_path('agents/windows/checkin.ps1');
    }
}
