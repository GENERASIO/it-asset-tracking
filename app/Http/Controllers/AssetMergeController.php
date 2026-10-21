<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetMergeController extends Controller
{
    /** Field hardware/agent yang dianggap "milik" record buatan agent. */
    private const AGENT_FIELDS = [
        'serial_number', 'hostname', 'os_name', 'os_version', 'cpu', 'ram_gb', 'storage_gb',
        'mac_address', 'last_seen_at', 'latitude', 'longitude', 'location_accuracy_m',
        'geo_captured_at', 'inventory_synced_at', 'crack_indicators', 'reported_lost_at',
    ];

    public function index()
    {
        $candidates = $this->candidateQuery()->with(['category', 'location'])->orderByDesc('last_seen_at')->get();

        // Indikator: untuk tiap kandidat, berapa aset lama dgn MODEL SAMA tapi BELUM punya
        // serial & bukan buatan agent (ciri record hasil migrasi GLPI yang kemungkinan cocok).
        $names = $candidates->pluck('name')->filter()->unique()->all();
        $glpiMatches = empty($names) ? collect() : Asset::whereIn('name', $names)
            ->where(fn ($q) => $q->whereNull('serial_number')->orWhere('serial_number', ''))
            ->whereNull('last_seen_at')
            ->selectRaw('name, COUNT(*) as c')
            ->groupBy('name')
            ->pluck('c', 'name');

        return view('asset-merge.index', compact('candidates', 'glpiMatches'));
    }

    public function create(Asset $asset)
    {
        // Calon aset tujuan: semua aset lain (yang kemungkinan record lama).
        $targets = Asset::with(['assignedUser', 'location'])
            ->where('id', '!=', $asset->id)
            ->orderBy('asset_code')
            ->get()
            // Kandidat yang kemungkinan cocok (model sama + belum ada serial) ditaruh paling atas.
            ->sortByDesc(fn ($t) => $t->name === $asset->name && empty($t->serial_number) ? 1 : 0)
            ->values();

        return view('asset-merge.create', compact('asset', 'targets'));
    }

    public function store(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'target_id' => 'required|integer|exists:assets,id|not_in:'.$asset->id,
        ]);

        $target = Asset::findOrFail($data['target_id']);

        $this->merge($asset, $target);

        return redirect()->route('assets.show', $target)
            ->with('success', "Aset {$asset->asset_code} digabungkan ke {$target->asset_code}. Data agent dipindahkan, kembar dihapus.");
    }

    /**
     * Gabungkan $source (record buatan agent) ke $target (record lama yang benar).
     * Histori & kepemilikan $target dipertahankan; data hardware/agent diambil dari $source.
     */
    private function merge(Asset $source, Asset $target): void
    {
        DB::transaction(function () use ($source, $target) {
            // 1. Pindahkan baris anak ke target.
            DB::table('asset_logs')->where('asset_id', $source->id)->update(['asset_id' => $target->id]);
            DB::table('maintenance_logs')->where('asset_id', $source->id)->update(['asset_id' => $target->id]);
            DB::table('asset_photos')->where('asset_id', $source->id)->update(['asset_id' => $target->id]);
            DB::table('bast_items')->where('asset_id', $source->id)->update(['asset_id' => $target->id]);

            // Inventori software/lisensi: target pakai hasil agent terbaru dari source.
            DB::table('asset_software')->where('asset_id', $target->id)->delete();
            DB::table('asset_licenses')->where('asset_id', $target->id)->delete();
            DB::table('asset_software')->where('asset_id', $source->id)->update(['asset_id' => $target->id]);
            DB::table('asset_licenses')->where('asset_id', $source->id)->update(['asset_id' => $target->id]);

            // 2. Salin field hardware/agent dari source -> target.
            foreach (self::AGENT_FIELDS as $field) {
                $target->{$field} = $source->{$field};
            }

            // 3. Harga: isi kalau target masih kosong (mis. harga auto-fill nempel di source).
            if (is_null($target->purchase_price) && ! is_null($source->purchase_price)) {
                $target->purchase_price = $source->purchase_price;
                $target->current_value = $source->current_value;
                $target->purchase_price_estimated = $source->purchase_price_estimated;
            }

            $target->save();

            // 4. Hapus record kembar. Kosongkan dulu field anak yang cascade (sudah dipindah).
            $source->delete();
        });
    }

    private function candidateQuery()
    {
        // Record yang kemungkinan besar dibuat otomatis agent: ada data agent (last_seen),
        // belum ada pemegang, dan lokasinya masih "Tidak Diketahui".
        return Asset::whereNotNull('last_seen_at')
            ->whereNull('assigned_to')
            ->whereHas('location', fn ($q) => $q->where('name', 'Tidak Diketahui'));
    }
}
