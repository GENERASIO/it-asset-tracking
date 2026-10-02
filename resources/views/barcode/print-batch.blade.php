<!DOCTYPE html>
<html>
<head>
    <title>Cetak Label Massal</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f5f7;
            color: #172b4d;
        }

        .toolbar {
            max-width: 900px;
            margin: 0 auto 20px;
            padding: 20px 24px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .toolbar .brand { display: flex; align-items: center; gap: 10px; }
        .toolbar .brand-icon {
            width: 36px; height: 36px; border-radius: 8px;
            background: #0c66e4; color: #fff;
            display: flex; align-items: center; justify-content: center;
        }
        .toolbar h1 { font-size: 15px; font-weight: 600; color: #172b4d; }
        .toolbar p { font-size: 12px; color: #6b778c; margin-top: 2px; }
        .toolbar .actions { display: flex; gap: 8px; align-items: center; }
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 13px; font-weight: 500;
            padding: 9px 16px; border-radius: 8px;
            border: none; cursor: pointer; text-decoration: none;
            transition: transform .1s;
        }
        .btn:active { transform: scale(0.96); }
        .btn-primary { background: #0c66e4; color: #fff; }
        .btn-primary:hover { background: #0052cc; }
        .btn-secondary { background: #f1f2f4; color: #44546f; }
        .btn-secondary:hover { background: #e4e6ea; }

        .preview-card {
            max-width: 900px;
            margin: 0 auto 20px;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        .labels {
            display: flex;
            flex-wrap: wrap;
            gap: 2mm;
        }
        .label {
            width: 35mm; height: 40mm;
            border: 1.5px dashed #666 !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3mm;
            text-align: center;
            overflow: hidden;
            color: #000 !important;
        }
        .label img { width: 100%; height: auto; }
        .label p { margin: 1mm 0 0; font-size: 8px; font-weight: bold; color: #000 !important; }
        .label .asset-name { font-weight: normal; font-size: 7px; }

        .history-card {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px 24px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .history-card h2 { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
        .history-card > p { font-size: 12px; color: #6b778c; margin-bottom: 14px; }
        .history-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .history-table th {
            text-align: left; padding: 8px 10px; background: #f4f5f7;
            color: #6b778c; font-weight: 600; font-size: 11px; text-transform: uppercase;
        }
        .history-table td { padding: 8px 10px; border-bottom: 1px solid #f1f2f4; vertical-align: top; }
        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 999px;
            font-size: 11px; font-weight: 600;
        }
        .history-table a { color: #0c66e4; text-decoration: none; font-weight: 500; }
        .history-table a:hover { text-decoration: underline; }

        @media print {
            body { background: #fff; }
            .no-print { display: none; }
            .preview-card, .history-card { box-shadow: none; padding: 0; border-radius: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <div class="brand">
            <div class="brand-icon">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h1>Cetak Label QR Code</h1>
                <p>{{ $assets->count() }} aset dipilih</p>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('assets.index') }}" class="btn btn-secondary">← Kembali</a>
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                </svg>
                Cetak Semua Label
            </button>
        </div>
    </div>

    <div class="preview-card">
        <div class="labels">
            @foreach ($assets as $asset)
                <div class="label">
                    <img src="data:image/png;base64,{{ $asset->qrcode }}" alt="barcode">
                    <p class="asset-code" style="font-family: 'Courier New', monospace; font-size: 9pt; margin-top: 2mm;">{{ $asset->asset_code }}</p>
                    <p class="asset-name" style="font-size: 7pt; margin-top: 1mm;">{{ $asset->name ?: '-' }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="history-card no-print">
        <h2>Ringkasan &amp; Riwayat Aset</h2>
        <p>Scan QR pada label untuk membuka info dasar aset ini (tanpa perlu login). Klik "Riwayat lengkap" di bawah untuk lihat detail & histori mutasi penuh (perlu login).</p>
        <table class="history-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Aset</th>
                    <th>Status</th>
                    <th>Lokasi</th>
                    <th>Aktivitas Terakhir</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php
                    $statusColors = [
                        'available' => 'background:#e3fcef;color:#006644',
                        'in_use' => 'background:#deebff;color:#0747a6',
                        'maintenance' => 'background:#fffae6;color:#974f0c',
                        'broken' => 'background:#ffebe6;color:#bf2600',
                        'retired' => 'background:#f4f5f7;color:#6b778c',
                    ];
                @endphp
                @foreach ($assets as $asset)
                    @php $lastLog = $asset->logs->first(); @endphp
                    <tr>
                        <td style="font-family: 'Courier New', monospace;">{{ $asset->asset_code }}</td>
                        <td>{{ $asset->name ?: '-' }}</td>
                        <td>
                            <span class="badge" style="{{ $statusColors[$asset->status] ?? 'background:#f4f5f7;color:#6b778c' }}">
                                {{ ucfirst(str_replace('_', ' ', $asset->status)) }}
                            </span>
                        </td>
                        <td>{{ $asset->location->name ?? '-' }}</td>
                        <td>
                            @if ($lastLog)
                                {{ ucfirst(str_replace('_', ' ', $lastLog->action)) }} · {{ $lastLog->created_at->diffForHumans() }}
                            @else
                                Belum ada aktivitas
                            @endif
                        </td>
                        <td><a href="{{ route('assets.show', $asset) }}" target="_blank">Riwayat lengkap →</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
