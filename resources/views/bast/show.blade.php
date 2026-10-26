<x-app-layout title="BAST {{ $bast->handover_date_label }}">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Berita Acara Serah Terima</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-3 bg-green-100 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="p-3 bg-red-100 text-red-700 rounded-lg text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $bast->city }}, {{ $bast->handover_date_label }}</p>
                        <p class="text-xs text-gray-400">Dibuat oleh {{ $bast->creator->name ?? '-' }} · {{ $bast->created_at->diffForHumans() }}</p>
                    </div>
                    @if ($bast->isSigned())
                        <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700">Sudah ditandatangani (scan)</span>
                    @elseif ($bast->isDigitallySigned())
                        <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700">Ditandatangani digital</span>
                    @else
                        <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">Belum ditandatangani</span>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Pihak Pertama</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $bast->first_party_name }}</p>
                        <p class="text-gray-600 dark:text-gray-300">{{ $bast->first_party_position }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Pihak Kedua</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $bast->second_party_name }}</p>
                        <p class="text-gray-600 dark:text-gray-300">{{ $bast->second_party_position }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-3 py-2">No.</th>
                                <th class="px-3 py-2">Jenis</th>
                                <th class="px-3 py-2">Detail</th>
                                <th class="px-3 py-2">Serial Number</th>
                                <th class="px-3 py-2">Jml</th>
                                <th class="px-3 py-2">Kondisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bast->items as $item)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ $item->item_type }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">
                                        {{ $item->detail }}
                                        @if ($item->spec)
                                            <span class="block text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ $item->spec }}</span>
                                        @endif
                                        @if ($item->asset)
                                            <a href="{{ route('assets.show', $item->asset) }}" class="block text-xs font-mono text-blue-600 dark:text-blue-400 hover:underline">{{ $item->asset->asset_code }}</a>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ $item->serial_number ?: '-' }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ $item->quantity }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ $item->condition }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @php
                    $waText = rawurlencode("Halo, mohon tanda tangan Berita Acara Serah Terima (BAST) untuk ".$bast->second_party_name.".\nSilakan buka & tanda tangan langsung di link ini:\n".$bast->sign_url);
                @endphp
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('bast.pdf', $bast) }}" target="_blank"
                       class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600 transition-all duration-150 hover:scale-105 active:scale-95">
                        Cetak / Unduh PDF
                    </a>
                    @if (! $bast->isDigitallySigned())
                        <a href="https://wa.me/?text={{ $waText }}" target="_blank"
                           class="inline-flex items-center gap-1.5 bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition-all duration-150 hover:scale-105 active:scale-95">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            Minta Tanda Tangan (WA)
                        </a>
                        <button type="button" onclick="navigator.clipboard.writeText(@js($bast->sign_url)).then(()=>{this.textContent='Link tersalin ✓'; setTimeout(()=>this.textContent='Salin link TTD',1500)})"
                                class="px-4 py-2 rounded-lg text-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">Salin link TTD</button>
                    @else
                        @php $waDoc = rawurlencode("Berikut dokumen Berita Acara Serah Terima (BAST) yang sudah ditandatangani untuk ".$bast->second_party_name.".\nBuka/unduh di sini:\n".route('bast.sign.pdf', $bast->sign_token)); @endphp
                        <a href="https://wa.me/?text={{ $waDoc }}" target="_blank"
                           class="inline-flex items-center gap-1.5 bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition-all duration-150 hover:scale-105 active:scale-95">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            Kirim Dokumen (WA)
                        </a>
                        <button type="button" onclick="navigator.clipboard.writeText(@js(route('bast.sign.pdf', $bast->sign_token))).then(()=>{this.textContent='Link tersalin ✓'; setTimeout(()=>this.textContent='Salin link dokumen',1500)})"
                                class="px-4 py-2 rounded-lg text-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">Salin link dokumen</button>
                    @endif
                    <form action="{{ route('bast.destroy', $bast) }}" method="POST"
                          onsubmit="event.preventDefault(); confirmAction('BAST ini beserta file scan tanda tangannya akan dihapus.', () => this.submit(), 'Hapus BAST?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30">Hapus</button>
                    </form>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-4">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300">Tanda Tangan Digital</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kirim link ke penerima lewat tombol <b>Kirim via WhatsApp</b> di atas. Begitu penerima tanda tangan di HP-nya, hasilnya otomatis tersimpan di sini &amp; masuk ke PDF.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Pihak Pertama --}}
                    <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Pihak Pertama — {{ $bast->first_party_name }}</p>
                        @if ($bast->first_party_signed_at)
                            <p class="text-xs text-green-600 dark:text-green-400 mt-1">✓ Sudah tanda tangan · {{ $bast->first_party_signed_at->diffForHumans() }}</p>
                        @else
                            <form method="POST" action="{{ route('bast.sign-first', $bast) }}" id="firstSignForm" class="mt-2">
                                @csrf
                                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700">
                                    <canvas id="firstPad" class="w-full rounded-lg" style="height:140px; touch-action:none;"></canvas>
                                </div>
                                <input type="hidden" name="signature" id="firstSig">
                                <div class="flex items-center justify-between mt-2">
                                    <button type="button" id="firstClear" class="text-xs text-gray-500 hover:text-gray-700">Hapus</button>
                                    <button type="submit" class="bg-brand-500 text-white px-4 py-1.5 rounded-lg text-xs hover:bg-brand-600">Simpan TTD saya</button>
                                </div>
                            </form>
                        @endif
                    </div>
                    {{-- Pihak Kedua --}}
                    <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Pihak Kedua — {{ $bast->second_party_name }}</p>
                        @if ($bast->second_party_signed_at)
                            <p class="text-xs text-green-600 dark:text-green-400 mt-1">✓ Sudah tanda tangan · {{ $bast->second_party_signed_at->diffForHumans() }}</p>
                        @else
                            <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">Menunggu tanda tangan penerima — kirim link via WhatsApp.</p>
                        @endif
                    </div>
                </div>
            </div>

            @if (! $bast->first_party_signed_at)
                <script>
                    (function () {
                        var canvas = document.getElementById('firstPad');
                        if (!canvas) return;
                        var ctx = canvas.getContext('2d'); var drawing = false;
                        function resize() { var r = window.devicePixelRatio || 1, rect = canvas.getBoundingClientRect(); canvas.width = rect.width * r; canvas.height = rect.height * r; ctx.scale(r, r); ctx.lineWidth = 2.5; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#111827'; }
                        function pt(e) { var rect = canvas.getBoundingClientRect(); return { x: e.clientX - rect.left, y: e.clientY - rect.top }; }
                        function ink() { try { var d = ctx.getImageData(0, 0, canvas.width, canvas.height).data, n = 0; for (var i = 3; i < d.length; i += 4) { if (d[i] > 10) n++; } return n; } catch (e) { return 9999; } }
                        canvas.addEventListener('pointerdown', function (e) { drawing = true; var p = pt(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); });
                        canvas.addEventListener('pointermove', function (e) { if (!drawing) return; var p = pt(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); });
                        window.addEventListener('pointerup', function () { drawing = false; });
                        window.addEventListener('load', resize);
                        document.getElementById('firstClear').addEventListener('click', function () { ctx.clearRect(0, 0, canvas.width, canvas.height); });
                        document.getElementById('firstSignForm').addEventListener('submit', function (e) { if (ink() < 200) { e.preventDefault(); alert('Mohon tanda tangan dulu dengan jelas.'); return; } document.getElementById('firstSig').value = canvas.toDataURL('image/png'); });
                    })();
                </script>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-1">Arsip Dokumen Ditandatangani</h3>

                @if ($bast->isDigitallySigned() && ! $bast->isSigned())
                    <p class="text-sm text-green-600 dark:text-green-400">
                        ✓ Sudah bertanda tangan digital. PDF final (tombol <b>Cetak / Unduh PDF</b> di atas) otomatis lengkap dengan tanda tangan — <b>upload scan tidak diperlukan</b>.
                    </p>
                    <details class="mt-3">
                        <summary class="text-sm text-gray-500 dark:text-gray-400 cursor-pointer">Upload scan manual (opsional)</summary>
                        <form action="{{ route('bast.signed.upload', $bast) }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3 mt-3">
                            @csrf
                            <input type="file" name="signed_file" required accept=".pdf,.jpg,.jpeg,.png" class="text-sm text-gray-700 dark:text-gray-300">
                            <button type="submit" class="bg-gray-800 dark:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 dark:hover:bg-gray-500">Upload</button>
                            <span class="text-xs text-gray-400">PDF, JPG, atau PNG · maks 10 MB</span>
                        </form>
                    </details>
                @else
                    @if ($bast->isSigned())
                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                            <a href="{{ route('bast.signed.show', $bast) }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $bast->signed_file_original_name ?: 'Lihat file' }}</a>
                            <span class="text-gray-400">· di-upload {{ $bast->signed_uploaded_at?->diffForHumans() }}</span>
                        </p>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Kalau tanda tangan dilakukan di kertas, cetak PDF di atas, tandatangani, lalu upload hasil scan / foto di sini sebagai arsip. (Atau pakai tanda tangan digital di atas — lebih cepat.)
                        </p>
                    @endif
                    <form action="{{ route('bast.signed.upload', $bast) }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                        @csrf
                        <input type="file" name="signed_file" required accept=".pdf,.jpg,.jpeg,.png" class="text-sm text-gray-700 dark:text-gray-300">
                        <button type="submit" class="bg-gray-800 dark:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 dark:hover:bg-gray-500">
                            {{ $bast->isSigned() ? 'Ganti File' : 'Upload' }}
                        </button>
                        <span class="text-xs text-gray-400">PDF, JPG, atau PNG · maks 10 MB</span>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if (! $bast->second_party_signed_at)
        <script>
            (function () {
                var url = @js(route('bast.status', $bast));
                var tries = 0;
                var timer = setInterval(function () {
                    if (++tries > 120) { clearInterval(timer); return; } // berhenti setelah ~20 menit
                    fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.ok ? r.json() : null; })
                        .then(function (d) { if (d && d.second_signed) { clearInterval(timer); location.reload(); } })
                        .catch(function () {});
                }, 10000);
            })();
        </script>
    @endif
</x-app-layout>
