<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Aset - {{ date('d-m-Y') }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #222; margin: 20px; }
        h1 { font-size: 18px; margin-bottom: 0; }
        p.subtitle { color: #666; margin-top: 4px; margin-bottom: 20px; }
        h2 { font-size: 14px; margin-top: 24px; margin-bottom: 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        td.number, th.number { text-align: right; }
        .summary-box { background-color: #f9f9f9; border: 1px solid #ddd; padding: 10px 14px; margin-bottom: 10px; }
        .summary-box p { margin: 4px 0; }
        .footer { margin-top: 30px; font-size: 10px; color: #888; }
    </style>
</head>
<body>
    <h1>Laporan &amp; Analitik Aset IT</h1>
    <p class="subtitle">Dicetak pada {{ now()->translatedFormat('d F Y H:i') }}</p>

    @php
        $filterParts = [];
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $dateFieldLabel = $filters['date_field'] === 'purchase_date' ? 'Tanggal Pembelian' : 'Tanggal Dicatat';
            $filterParts[] = 'Periode ('.$dateFieldLabel.'): '.($filters['date_from'] ?: '...').' s/d '.($filters['date_to'] ?: '...');
        }
        if (!empty($filters['category_id'])) {
            $filterParts[] = 'Kategori: '.optional($categories->firstWhere('id', $filters['category_id']))->name;
        }
        if (!empty($filters['location_id'])) {
            $filterParts[] = 'Lokasi: '.optional($locations->firstWhere('id', $filters['location_id']))->name;
        }
        if (!empty($filters['status'])) {
            $filterParts[] = 'Status: '.ucfirst(str_replace('_', ' ', $filters['status']));
        }
    @endphp

    @if (count($filterParts))
        <p class="subtitle"><strong>Filter aktif:</strong> {{ implode(' · ', $filterParts) }}</p>
    @endif

    <div class="summary-box">
        <p><strong>Total Aset:</strong> {{ $totalAssets }}</p>
        <p><strong>Total Nilai Aset:</strong> Rp {{ number_format($totalAssetValue ?? 0, 0, ',', '.') }}</p>
        <p><strong>Jumlah Kategori:</strong> {{ $totalCategories }}</p>
        <p><strong>Jumlah Lokasi:</strong> {{ $totalLocations }}</p>
    </div>

    <h2>Aset per Kategori</h2>
    <table>
        <thead>
            <tr>
                <th>Kategori</th>
                <th class="number">Jumlah Aset</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assetsPerCategory as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td class="number">{{ $category->assets_count }}</td>
                </tr>
            @empty
                <tr><td colspan="2">Belum ada data kategori.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Aset per Lokasi</h2>
    <table>
        <thead>
            <tr>
                <th>Lokasi</th>
                <th class="number">Jumlah Aset</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assetsPerLocation as $location)
                <tr>
                    <td>{{ $location->name }}</td>
                    <td class="number">{{ $location->assets_count }}</td>
                </tr>
            @empty
                <tr><td colspan="2">Belum ada data lokasi.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Aset per Status</h2>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th class="number">Jumlah Aset</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assetsPerStatus as $row)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td>
                    <td class="number">{{ $row->total }}</td>
                </tr>
            @empty
                <tr><td colspan="2">Belum ada data status.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Trend Pembelian Aset (12 Bulan Terakhir)</h2>
    <table>
        <thead>
            <tr>
                <th>Bulan</th>
                <th class="number">Jumlah Aset Dibeli</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($monthlyPurchases as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="number">{{ $row['total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Laporan ini dibuat otomatis oleh sistem IT Asset Tracking — PT. YAY Enak Semua.
    </div>
</body>
</html>
