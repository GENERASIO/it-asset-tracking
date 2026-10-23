<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Transfer Aset (STF)</title>
    <style>
        @page { margin: 1.4cm 1.6cm; }
        body { font-family: "Times New Roman", Times, serif; font-size: 11pt; color: #000; line-height: 1.3; }
        .logo { height: 46px; margin-bottom: 6px; }
        h1 { font-size: 14pt; text-align: center; margin: 2px 0 2px; }
        .sub { text-align: center; font-size: 10pt; margin: 0 0 16px; }
        table.asal { border-collapse: collapse; margin-bottom: 16px; width: 60%; }
        table.asal td { border: 1px solid #000; padding: 4px 8px; vertical-align: top; }
        table.asal td.label { width: 140px; font-weight: bold; background: #f0f0ec; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th, table.items td { border: 1px solid #000; padding: 4px 6px; vertical-align: top; }
        table.items th { font-weight: bold; text-align: center; background: #f0f0ec; }
        table.items td.center { text-align: center; }
        .desc { font-family: Helvetica, Arial, sans-serif; font-size: 10pt; }
        .desc .spec { font-size: 8.5pt; color: #555; margin-top: 2px; line-height: 1.25; }
        table.sign { width: 100%; margin-top: 34px; border-collapse: collapse; }
        table.sign td { width: 25%; text-align: center; vertical-align: top; font-size: 10.5pt; }
        table.sign .space { height: 80px; }
    </style>
</head>
<body>
    @if ($logo)
        <img src="{{ $logo }}" class="logo" alt="Logo">
    @endif

    <h1>SURAT TRANSFER ASET (STF)</h1>
    <p class="sub">No: {{ $stf->number }}</p>

    <table class="asal">
        <tr><td class="label">TANGGAL</td><td>{{ $stf->transfer_date_long }}</td></tr>
        <tr><td class="label">PT</td><td>PT. YAY Enak Semua</td></tr>
        <tr><td class="label">Outlet Asal</td><td>{{ optional($stf->fromLocation)->name ?? 'Beberapa lokasi' }}</td></tr>
        <tr><td class="label">Alasan Mutasi</td><td>{{ $stf->reason }}</td></tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 15%">Asset No</th>
                <th>Asset Description / Spesifikasi</th>
                <th style="width: 20%">Outlet Tujuan</th>
                <th style="width: 7%">Qty</th>
                <th style="width: 22%">Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($stf->items as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">{{ $item->asset_no }}</td>
                    <td class="desc">
                        {{ $item->description }}
                        @if ($item->spec)
                            <div class="spec">{!! nl2br(e($item->spec)) !!}</div>
                        @endif
                    </td>
                    <td class="center">{{ $item->to_location_name }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="desc">{{ $item->notes }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="sign">
        <tr>
            <td>PIC Pengirim</td>
            <td>PIC Penerima</td>
            <td>Atasan</td>
            <td>Mengetahui,</td>
        </tr>
        <tr>
            <td class="space"></td>
            <td class="space"></td>
            <td class="space">@if (($arisSignature ?? null) && $stf->supervisor_name === 'Aris Munandar')<img src="{{ $arisSignature }}" style="height:72px" alt="Tanda tangan">@endif</td>
            <td class="space"></td>
        </tr>
        <tr>
            <td>( {{ $stf->sender_name }} )</td>
            <td>( ................... )</td>
            <td>( {{ $stf->supervisor_name }} )</td>
            <td>( ................... )</td>
        </tr>
    </table>
</body>
</html>
