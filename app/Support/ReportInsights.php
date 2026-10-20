<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Kesimpulan & rekomendasi otomatis untuk halaman Laporan / PDF, disusun dari data aset
 * (yang sudah difilter) dan hasil analisis di ReportController::buildAnalysis().
 *
 * Tiap temuan: level (danger | warning | info | good), judul, penjelasan, dan saran tindakan.
 */
class ReportInsights
{
    // Harga laptop pengganti kalau belum ada harga perangkat baru yang bisa dijadikan acuan.
    private const DEFAULT_REPLACEMENT_PRICE = 8000000;

    public static function build(Collection $assets, array $analysis): array
    {
        $total = $assets->count();
        if ($total === 0) {
            return [];
        }

        $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        $pct = fn ($n) => round($n / $total * 100);
        $ages = collect($analysis['ageGroups'])->keyBy('key');
        $insights = [];

        // 1. Peremajaan perangkat tua.
        $old = $ages['old']['count'] ?? 0;
        if ($old > 0) {
            $newGroup = $assets->filter(fn ($a) => DeviceAge::forAsset($a) === 'new' && $a->purchase_price);
            // Median, bukan rata-rata: beberapa laptop gaming/desain yang mahal tidak boleh
            // menaikkan perkiraan harga laptop pengganti untuk staf biasa.
            $unitPrice = $newGroup->isNotEmpty() ? (int) round($newGroup->median('purchase_price'), -5) : self::DEFAULT_REPLACEMENT_PRICE;

            $insights[] = [
                'level' => $pct($old) >= 20 ? 'danger' : 'warning',
                'title' => "{$old} perangkat ({$pct($old)}%) sudah tua (prosesor 2020 ke bawah)",
                'detail' => 'Perangkat tua lebih lambat, lebih sering rusak, dan sebagian tidak bisa menjalankan Windows 11 (dukungan keamanan Windows 10 sudah berakhir Oktober 2025).',
                'action' => "Anggarkan peremajaan bertahap sekitar {$rp($old * $unitPrice)} ({$old} unit × {$rp($unitPrice)}), dahulukan yang paling sering bermasalah.",
            ];
        }

        // 2. Stok cadangan.
        $spare = $analysis['spareCount'];
        $minSpare = (int) ceil($total * 0.03);
        if ($spare < $minSpare) {
            $insights[] = [
                'level' => $spare === 0 ? 'danger' : 'warning',
                'title' => $spare === 0 ? 'Tidak ada perangkat cadangan' : "Perangkat cadangan hanya {$spare} unit",
                'detail' => 'Kalau ada laptop rusak, tidak ada pengganti siap pakai sehingga karyawan tidak bisa bekerja selama perbaikan.',
                'action' => "Siapkan {$minSpare}–".((int) ceil($total * 0.05))." unit cadangan (3–5% dari {$total} aset), bisa dari perangkat lama yang masih layak.",
            ];
        }

        // 3. Aset yang tidak produktif.
        $idle = $assets->whereIn('status', ['maintenance', 'broken']);
        if ($idle->isNotEmpty()) {
            $insights[] = [
                'level' => 'warning',
                'title' => "{$idle->count()} aset sedang Maintenance / Broken",
                'detail' => "Nilai aset yang sedang tidak terpakai: {$rp($idle->sum('purchase_price'))}.",
                'action' => 'Pastikan ada tindak lanjut (perbaikan, klaim garansi, atau dijadikan suku cadang) supaya tidak menumpuk.',
            ];
        }

        $retired = $assets->where('status', 'retired')->count();
        if ($retired > 0) {
            $insights[] = [
                'level' => 'info',
                'title' => "{$retired} aset berstatus Retired",
                'detail' => 'Aset yang sudah tidak dipakai tetap tercatat dan memakan tempat penyimpanan.',
                'action' => 'Putuskan: dijual, didonasikan, atau dimusnahkan (hapus data di dalamnya lebih dulu), lalu catat di sistem.',
            ];
        }

        // 4. Keandalan nilai aset.
        $estimated = $assets->filter(fn ($a) => $a->purchase_price && $a->purchase_price_estimated)->count();
        if ($estimated > 0) {
            $insights[] = [
                'level' => $pct($estimated) >= 50 ? 'warning' : 'info',
                'title' => "{$pct($estimated)}% harga beli adalah estimasi",
                'detail' => "{$estimated} dari {$total} aset memakai harga pasar perkiraan karena data harga aslinya hilang, sehingga Total Nilai Aset hanya gambaran kasar.",
                'action' => 'Cocokkan dengan invoice / catatan Finance dan ganti dengan harga asli saat ditemukan.',
            ];
        }

        if ($analysis['bookValueCoverage'] < $total) {
            $missing = $total - $analysis['bookValueCoverage'];
            $insights[] = [
                'level' => $pct($missing) >= 50 ? 'warning' : 'info',
                'title' => "Nilai buku baru terhitung untuk {$analysis['bookValueCoverage']} dari {$total} aset",
                'detail' => "Perkiraan nilai buku saat ini {$rp($analysis['bookValue'])}; {$missing} aset belum punya tanggal beli sehingga belum ikut dihitung.",
                'action' => 'Isi tanggal beli (dari invoice) supaya nilai buku bisa dipakai untuk laporan Finance dan asuransi.',
            ];
        }

        // 5. Kelengkapan data.
        $weak = collect($analysis['completeness'])->where('percent', '<', 50);
        if ($weak->isNotEmpty()) {
            $insights[] = [
                'level' => 'warning',
                'title' => "Skor kelengkapan data {$analysis['dataScore']}%",
                'detail' => 'Data yang paling banyak kosong: '.$weak->map(fn ($r) => "{$r['label']} ({$r['percent']}%)")->implode(', ').'.',
                'action' => 'Lengkapi bertahap, mulai dari aset bernilai paling besar. Data garansi yang lengkap juga mengaktifkan pengingat garansi otomatis.',
            ];
        }

        // 6. Umur tidak diketahui -> agent.
        $unknown = $ages['unknown']['count'] ?? 0;
        $withAgent = $assets->whereNotNull('last_seen_at')->count();
        if ($unknown > 0 || $withAgent < $total) {
            $insights[] = [
                'level' => 'info',
                'title' => "Agent baru terpasang di {$withAgent} dari {$total} aset",
                'detail' => "{$unknown} aset belum diketahui umurnya karena spesifikasi prosesornya tidak tercatat; lisensi software dan lokasi laptop juga baru terpantau di perangkat yang ada agent-nya.",
                'action' => 'Pasang IT Asset Agent di semua laptop/PC supaya spesifikasi, lokasi, dan status lisensi terisi otomatis.',
            ];
        }

        // 7. Konsentrasi nilai di satu lokasi.
        $topLocation = $analysis['valueByLocation']->first();
        $totalValue = max((int) $assets->sum('purchase_price'), 1);
        if ($topLocation && $analysis['valueByLocation']->count() > 1 && $topLocation['value'] / $totalValue >= 0.5) {
            $share = round($topLocation['value'] / $totalValue * 100);
            $insights[] = [
                'level' => 'info',
                'title' => "{$share}% nilai aset ada di {$topLocation['name']}",
                'detail' => "{$topLocation['count']} aset senilai {$rp($topLocation['value'])} berada di satu lokasi.",
                'action' => 'Pastikan lokasi ini tercakup asuransi dan rutin diaudit (stock opname dengan scan QR label).',
            ];
        }

        // 8. Maintenance.
        $topModel = $analysis['maintenanceByModel']->first();
        if ($topModel) {
            $insights[] = [
                'level' => 'info',
                'title' => "Biaya perbaikan terbesar: {$topModel['model']}",
                'detail' => "{$topModel['count']} kali perbaikan pada {$topModel['assets']} unit, total {$rp($topModel['cost'])}.",
                'action' => 'Pertimbangkan tidak membeli model ini lagi atau mengganti unit yang paling sering rusak.',
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'level' => 'good',
                'title' => 'Tidak ada temuan penting',
                'detail' => 'Data aset lengkap dan kondisi perangkat baik.',
                'action' => 'Pertahankan dengan audit berkala.',
            ];
        }

        $order = ['danger' => 0, 'warning' => 1, 'info' => 2, 'good' => 3];

        return collect($insights)->sortBy(fn ($i) => $order[$i['level']])->values()->all();
    }
}
