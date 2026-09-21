<!DOCTYPE html>
<html>
<head>
    <title>Cetak Label Massal</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; }
        .labels {
            display: flex;
            flex-wrap: wrap;
            gap: 2mm;
        }
        .label {
            width: 35mm; height: 40mm; padding: 2mm;
            box-sizing: border-box; text-align: center;
            border: 1px solid #eee;
        }
        .label img { width: 100%; height: auto; }
        .label p { margin: 1mm 0 0; font-size: 8px; font-weight: bold; }
        @media print {
            .no-print { display: none; }
            .label { border: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:20px; text-align:center;">
        <button onclick="window.print()">Cetak Semua Label</button>
    </div>

    <div class="labels">
        @foreach ($assets as $asset)
            <div class="label">
                <img src="data:image/png;base64,{{ $asset->qrcode }}" alt="barcode">
                <p>{{ $asset->asset_code }}</p>
            </div>
        @endforeach
    </div>
</body>
</html>
