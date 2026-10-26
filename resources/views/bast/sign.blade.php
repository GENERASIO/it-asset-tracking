<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tanda Tangan BAST · PT. YAY Enak Semua</title>
    @vite(['resources/css/app.css'])
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
</head>
<body class="font-sans antialiased bg-gray-100 min-h-screen py-6 px-4">
    <div class="w-full max-w-lg mx-auto">
        <div class="flex items-center justify-center gap-2 mb-4">
            <img src="{{ asset('images/logo.png') }}" alt="YAY Group" class="h-9 w-auto">
            <div>
                <p class="font-semibold text-gray-800 text-sm leading-tight">PT. YAY Enak Semua</p>
                <p class="text-xs text-gray-500 leading-tight">Berita Acara Serah Terima</p>
            </div>
        </div>

        @if (session('signed') || $bast->isDigitallySigned())
            <div class="bg-white rounded-xl shadow p-6 text-center">
                <div class="mx-auto w-14 h-14 flex items-center justify-center rounded-full bg-green-100 text-green-600 mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </div>
                <h1 class="text-lg font-semibold text-gray-900">Terima kasih, {{ $bast->second_party_name }}!</h1>
                <p class="text-sm text-gray-500 mt-1">Tanda tangan Anda sudah tersimpan @if ($bast->second_party_signed_at) pada {{ $bast->second_party_signed_at->translatedFormat('d M Y H:i') }} @endif. Dokumen BAST otomatis terupdate di sistem IT.</p>
            </div>
        @else
            <div class="bg-white rounded-xl shadow p-6">
                <h1 class="text-lg font-semibold text-gray-900">Konfirmasi &amp; Tanda Tangan</h1>
                <p class="text-sm text-gray-500 mt-1 mb-4">Mohon periksa barang yang diterima, lalu tanda tangan di bawah.</p>

                <dl class="text-sm space-y-1.5 mb-4">
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">Tanggal</dt><dd class="text-gray-900 font-medium text-right">{{ $bast->handover_date_label }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">Diserahkan oleh</dt><dd class="text-gray-900 font-medium text-right">{{ $bast->first_party_name }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">Penerima</dt><dd class="text-gray-900 font-medium text-right">{{ $bast->second_party_name }}</dd></div>
                </dl>

                <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 mb-5">
                    @foreach ($bast->items as $item)
                        <div class="px-3 py-2 text-sm">
                            <p class="text-gray-900 font-medium">{{ $item->detail }}</p>
                            <p class="text-xs text-gray-500">{{ $item->item_type }} · SN: {{ $item->serial_number ?: '-' }} · Jml: {{ $item->quantity }} · {{ $item->condition }}</p>
                        </div>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('bast.sign.store', $bast->sign_token) }}" id="signForm">
                    @csrf
                    <label class="text-sm font-medium text-gray-700">Tanda tangan ({{ $bast->second_party_name }})</label>
                    <div class="mt-1 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50">
                        <canvas id="pad" class="w-full rounded-lg" style="height:200px; touch-action:none;"></canvas>
                    </div>
                    <input type="hidden" name="signature" id="sigInput">
                    <div class="flex items-center justify-between mt-3">
                        <button type="button" id="clear" class="text-sm text-gray-500 hover:text-gray-700">Hapus &amp; ulangi</button>
                        <button type="submit" class="bg-brand-500 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-brand-600">Kirim Tanda Tangan</button>
                    </div>
                    @error('signature')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
                </form>
            </div>
            <p class="text-center text-xs text-gray-400 mt-4">Link ini bersifat rahasia — jangan dibagikan ke pihak lain.</p>
        @endif
    </div>

    <script>
        (function () {
            var canvas = document.getElementById('pad');
            if (!canvas) return;
            var ctx = canvas.getContext('2d');
            var drawing = false, empty = true;

            function resize() {
                var ratio = window.devicePixelRatio || 1;
                var rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                ctx.scale(ratio, ratio);
                ctx.lineWidth = 2.5; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#111827';
            }
            function point(e) {
                var rect = canvas.getBoundingClientRect();
                return { x: e.clientX - rect.left, y: e.clientY - rect.top };
            }
            function start(e) { drawing = true; var p = point(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
            function move(e) { if (!drawing) return; var p = point(e); ctx.lineTo(p.x, p.y); ctx.stroke(); empty = false; e.preventDefault(); }
            function stop() { drawing = false; }

            window.addEventListener('load', resize);
            canvas.addEventListener('pointerdown', start);
            canvas.addEventListener('pointermove', move);
            window.addEventListener('pointerup', stop);
            document.getElementById('clear').addEventListener('click', function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height); empty = true;
            });
            document.getElementById('signForm').addEventListener('submit', function (e) {
                if (empty) { e.preventDefault(); alert('Mohon tanda tangan dulu di kotak.'); return; }
                document.getElementById('sigInput').value = canvas.toDataURL('image/png');
            });
        })();
    </script>
</body>
</html>
