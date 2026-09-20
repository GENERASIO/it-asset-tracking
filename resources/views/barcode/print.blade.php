<!DOCTYPE html>
<html>
<head>
    <title>Label Aset - {{ $asset->asset_code }}</title>
    <style>
        @page { size: 40mm 25mm; margin: 0; }
        body { margin: 0; font-family: Arial, sans-serif; }
        .label {
            width: 40mm; height: 25mm; padding: 2mm;
            box-sizing: border-box; text-align: center;
        }
        .label img { width: 100%; height: auto; }
        .label p { margin: 1mm 0 0; font-size: 8px; font-weight: bold; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="label">
        <img src="data:image/png;base64,{{ $barcode }}" alt="barcode">
        <p>{{ $asset->asset_code }}</p>
    </div>

    <div class="no-print" style="margin-top:20px; text-align:center;">
        <button onclick="window.print()">Cetak Label</button>
    </div>
</body>
</html>