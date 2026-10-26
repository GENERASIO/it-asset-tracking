<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Form Serah Terima Barang Inventaris Kantor</title>
    <style>
        @page { margin: 2cm 2.2cm; }
        body { font-family: "Times New Roman", Times, serif; font-size: 11pt; color: #000; line-height: 1.35; }
        .logo { height: 46px; }
        h1 { font-size: 11pt; font-weight: normal; text-align: center; margin: 4px 0 40px; }
        .party { margin-bottom: 22px; }
        .party p { margin: 0 0 8px; }
        .party td { padding: 0 0 8px; vertical-align: top; }
        .party .label { width: 80px; }
        .intro { text-align: justify; margin: 38px 0 4px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th, table.items td { border: 1px solid #000; padding: 3px 5px; vertical-align: top; }
        table.items th { font-weight: normal; text-align: center; }
        table.items td.center { text-align: center; }
        table.items td.detail { font-family: Helvetica, Arial, sans-serif; font-size: 10pt; }
        table.items td.condition { font-family: Helvetica, Arial, sans-serif; font-size: 9pt; }
        .closing { text-align: justify; margin: 24px 0 28px; }
        .signatures { width: 100%; margin-top: 30px; }
        .signatures td { width: 50%; text-align: center; vertical-align: top; }
        .signatures .space { height: 95px; }
    </style>
</head>
<body>
    @if ($logo)
        <img src="{{ $logo }}" class="logo" alt="Logo">
    @endif

    <h1>FORM SERAH TERIMA BARANG INVENTARIS KANTOR</h1>

    <div class="party">
        <p>Yang bertanda tangan di bawah ini disebut Pihak Pertama</p>
        <table>
            <tr><td class="label">Nama</td><td>: {{ $bast->first_party_name }}</td></tr>
            <tr><td class="label">Jabatan</td><td>: {{ $bast->first_party_position }}</td></tr>
        </table>
    </div>

    <div class="party">
        <p>Selanjutnya disebut Pihak Kedua</p>
        <table>
            <tr><td class="label">Nama</td><td>: {{ $bast->second_party_name }}</td></tr>
            <tr><td class="label">Jabatan</td><td>: {{ $bast->second_party_position }}</td></tr>
        </table>
    </div>

    <p class="intro">
        PIHAK PERTAMA menyerahkan dan meminjamkan inventaris kantor untuk menunjang kinerja PIHAK KEDUA di PT. Yay Enak Semua berupa :
    </p>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 6%">No.</th>
                <th style="width: 13%">Jenis barang</th>
                <th>Detail barang</th>
                <th style="width: 19%">Serial Number</th>
                <th style="width: 10%">Jumlah barang</th>
                <th style="width: 19%">Kondisi barang</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bast->items as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">{{ $item->item_type }}</td>
                    <td class="detail">{{ $item->detail }}</td>
                    <td class="center detail">{{ $item->serial_number ?: '-' }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="condition">{{ mb_strtoupper($item->condition) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="closing">
        Demikian surat peminjaman barang ini dibuat oleh kedua pihak, adapun barang - barang tersebut diterima dalam keadaan baik dan cukup, maka sejak ditandatangani berita acara ini oleh PIHAK KEDUA maka barang tersebut menjadi tanggung jawab PIHAK KEDUA sepenuhnya. Bahwa sudah senilai barang yang telah diberikan atas ganti rugi kejadian pada PIHAK PERTAMA.
    </p>

    <p>{{ $bast->city }}, {{ $bast->handover_date_label }}</p>

    <table class="signatures">
        <tr>
            <td>PIHAK PERTAMA</td>
            <td>PIHAK KEDUA</td>
        </tr>
        <tr>
            <td class="space">@if (($sig1 ?? null))<img src="{{ $sig1 }}" style="height:78px" alt="TTD">@endif</td>
            <td class="space">@if (($sig2 ?? null))<img src="{{ $sig2 }}" style="height:78px" alt="TTD">@endif</td>
        </tr>
        <tr>
            <td>( {{ $bast->first_party_name }} )</td>
            <td>( {{ $bast->second_party_name }} )</td>
        </tr>
    </table>
</body>
</html>
