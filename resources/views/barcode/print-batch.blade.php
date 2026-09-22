<!DOCTYPE html>
<html>
<head>
    <title>Cetak Label Massal</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            color: #000 !important;
            text-decoration: none !important;
        }

        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        body { font-family: Arial, sans-serif; }
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
        }
        .label img { width: 100%; height: auto; }
        .label p { margin: 1mm 0 0; font-size: 8px; font-weight: bold; }
        .label .asset-name { font-weight: normal; font-size: 7px; }
        @media print {
            .no-print { display: none; }
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
                <p class="asset-name">{{ $asset->name ?: '-' }}</p>
            </div>
        @endforeach
    </div>
</body>
</html>
