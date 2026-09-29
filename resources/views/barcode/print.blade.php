<!DOCTYPE html>
<html>
<head>
    <title>Label Aset - {{ $asset->asset_code }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @page { size: 35mm 40mm; margin: 0; }

        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f5f7; color: #172b4d; }

        .label {
            width: 35mm; height: 40mm; padding: 2mm;
            box-sizing: border-box; text-align: center;
            background: #fff;
        }
        .label img { width: 100%; height: auto; }
        .label p { margin: 1mm 0 0; font-size: 8px; font-weight: bold; color: #000 !important; }

        .page-wrap {
            max-width: 460px;
            margin: 0 auto;
            padding: 24px;
        }
        .toolbar {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; margin-bottom: 16px;
        }
        .toolbar .brand { display: flex; align-items: center; gap: 10px; }
        .toolbar .brand-icon {
            width: 36px; height: 36px; border-radius: 8px;
            background: #0c66e4; color: #fff;
            display: flex; align-items: center; justify-content: center;
        }
        .toolbar h1 { font-size: 15px; font-weight: 600; }
        .toolbar p { font-size: 12px; color: #6b778c; margin-top: 2px; }

        .label-card {
            background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 20px; display: flex; justify-content: center; margin-bottom: 16px;
        }
        .label-card .label { border: 1.5px dashed #666; }

        .actions { display: flex; gap: 8px; margin-bottom: 16px; }
        .btn {
            display: inline-flex; align-items: center; gap: 6px; flex: 1;
            justify-content: center;
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

        .info-card {
            background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 18px 20px; margin-bottom: 16px;
        }
        .info-card h2 { font-size: 13px; font-weight: 600; margin-bottom: 10px; }
        .info-row { display: flex; justify-content: space-between; font-size: 13px; padding: 5px 0; border-bottom: 1px solid #f4f5f7; }
        .info-row:last-child { border-bottom: none; }
        .info-row span:first-child { color: #6b778c; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }

        .history-card {
            background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 18px 20px;
        }
        .history-card h2 { font-size: 13px; font-weight: 600; margin-bottom: 10px; }
        .history-item { font-size: 12px; padding: 8px 0; border-bottom: 1px solid #f4f5f7; }
        .history-item:last-child { border-bottom: none; }
        .history-item .when { color: #6b778c; font-size: 11px; }
        .history-card a { color: #0c66e4; text-decoration: none; font-size: 12px; font-weight: 500; }

        @media print {
            body { background: #fff; }
            .no-print { display: none; }
            .page-wrap { max-width: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="page-wrap">
        <div class="toolbar no-print">
            <div class="brand">
                <div class="brand-icon">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h1>Cetak Label</h1>
                    <p>{{ $asset->asset_code }}</p>
                </div>
            </div>
        </div>

        <div class="label-card">
            <div class="label">
                <img src="data:image/png;base64,{{ $barcode }}" alt="barcode">
                <p>{{ $asset->asset_code }}</p>
            </div>
        </div>

        <div class="actions no-print">
            <a href="{{ route('assets.show', $asset) }}" class="btn btn-secondary">← Kembali</a>
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                </svg>
                Cetak Label
            </button>
        </div>

        @php
            $statusColors = [
                'available' => 'background:#e3fcef;color:#006644',
                'in_use' => 'background:#deebff;color:#0747a6',
                'maintenance' => 'background:#fffae6;color:#974f0c',
                'broken' => 'background:#ffebe6;color:#bf2600',
                'retired' => 'background:#f4f5f7;color:#6b778c',
            ];
        @endphp

        <div class="info-card no-print">
            <h2>Info Aset</h2>
            <div class="info-row"><span>Nama</span><span>{{ $asset->name ?: '-' }}</span></div>
            <div class="info-row">
                <span>Status</span>
                <span class="badge" style="{{ $statusColors[$asset->status] ?? 'background:#f4f5f7;color:#6b778c' }}">
                    {{ ucfirst(str_replace('_', ' ', $asset->status)) }}
                </span>
            </div>
            <div class="info-row"><span>Kategori</span><span>{{ $asset->category->name ?? '-' }}</span></div>
            <div class="info-row"><span>Lokasi</span><span>{{ $asset->location->name ?? '-' }}</span></div>
            <div class="info-row"><span>Dipegang Oleh</span><span>{{ $asset->assignedUser->name ?? '-' }}</span></div>
        </div>

        <div class="history-card no-print">
            <h2>Riwayat Terbaru</h2>
            @forelse ($asset->logs as $log)
                <div class="history-item">
                    <div>{{ ucfirst(str_replace('_', ' ', $log->action)) }}</div>
                    <div class="when">{{ $log->created_at->diffForHumans() }}</div>
                </div>
            @empty
                <p style="font-size:12px;color:#6b778c;">Belum ada riwayat.</p>
            @endforelse
            <div style="margin-top:10px;">
                <a href="{{ route('assets.show', $asset) }}">Lihat riwayat lengkap →</a>
            </div>
        </div>
    </div>
</body>
</html>
