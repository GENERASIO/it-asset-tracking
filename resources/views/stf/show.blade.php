<x-app-layout title="STF {{ $stf->number }}">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">STF {{ $stf->number }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-3 bg-green-50 text-green-700 ring-1 ring-green-200 dark:bg-green-900/30 dark:text-green-300 dark:ring-green-800 rounded-xl text-sm">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="p-3 bg-red-50 text-red-700 ring-1 ring-red-200 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800 rounded-xl text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-100 dark:ring-gray-700/60 p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Detail Transfer</h3>
                    <div class="flex gap-2">
                        <a href="{{ route('stf.pdf', $stf) }}" class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600">Cetak PDF</a>
                        <form method="POST" action="{{ route('stf.destroy', $stf) }}"
                              onsubmit="event.preventDefault(); confirmAction('Dokumen STF ini akan dihapus. Lokasi aset yang sudah pindah tidak berubah.', () => this.submit(), 'Hapus STF?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm border border-red-300 text-red-600 hover:bg-red-50 dark:hover:bg-gray-700">Hapus</button>
                        </form>
                    </div>
                </div>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-500">Tanggal</dt><dd class="text-gray-900 dark:text-white font-medium">{{ $stf->transfer_date_long }}</dd></div>
                    <div><dt class="text-gray-500">Alasan Mutasi</dt><dd class="text-gray-900 dark:text-white font-medium">{{ $stf->reason }}</dd></div>
                    <div><dt class="text-gray-500">Outlet Asal</dt><dd class="text-gray-900 dark:text-white font-medium">{{ optional($stf->fromLocation)->name ?? 'Beberapa lokasi' }}</dd></div>
                    <div><dt class="text-gray-500">Outlet Tujuan</dt><dd class="text-gray-900 dark:text-white font-medium">{{ optional($stf->toLocation)->name }}</dd></div>
                    <div><dt class="text-gray-500">PIC Pengirim</dt><dd class="text-gray-900 dark:text-white font-medium">{{ $stf->sender_name }}</dd></div>
                    <div><dt class="text-gray-500">Atasan</dt><dd class="text-gray-900 dark:text-white font-medium">{{ $stf->supervisor_name }}</dd></div>
                </dl>
            </div>

            @php
                $waIcon = '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';
                $toName = optional($stf->toLocation)->name;
                if ($stf->isReceiverSigned()) {
                    $shareUrl = route('stf.sign.pdf', $stf->sign_token);
                    $waText = rawurlencode("Berikut dokumen Surat Transfer Aset (STF) {$stf->number} ke {$toName} yang sudah ditandatangani.\nBuka/unduh di sini:\n".$shareUrl);
                } else {
                    $shareUrl = $stf->sign_url;
                    $waText = rawurlencode("Halo, mohon konfirmasi penerimaan aset & tanda tangan Surat Transfer Aset (STF) {$stf->number} ke {$toName}.\nSilakan buka & tanda tangan langsung di link ini:\n".$shareUrl);
                }
                $copyLabel = $stf->isReceiverSigned() ? 'Salin link dokumen' : 'Salin link TTD';
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-100 dark:ring-gray-700/60 p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Tanda Tangan Digital</h3>
                    @if ($stf->isReceiverSigned())
                        <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700">Diterima &amp; ditandatangani</span>
                    @else
                        <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">Menunggu TTD penerima</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kirim link ke PIC Penerima di outlet tujuan lewat WhatsApp. Penerima mengisi nama &amp; tanda tangan di HP-nya, hasilnya otomatis tersimpan di sini &amp; masuk ke PDF.</p>

                <div class="flex flex-wrap gap-2">
                    <a href="https://wa.me/?text={{ $waText }}" target="_blank"
                       class="inline-flex items-center gap-1.5 bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition-all duration-150 hover:scale-105 active:scale-95">
                        {!! $waIcon !!}
                        {{ $stf->isReceiverSigned() ? 'Kirim Dokumen (WA)' : 'Minta Tanda Tangan (WA)' }}
                    </a>
                    <button type="button" onclick="navigator.clipboard.writeText(@js($shareUrl)).then(()=>{this.textContent='Link tersalin ✓'; setTimeout(()=>this.textContent=@js($copyLabel),1500)})"
                            class="px-4 py-2 rounded-lg text-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">{{ $copyLabel }}</button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- PIC Pengirim --}}
                    <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">PIC Pengirim — {{ $stf->sender_name }}</p>
                        @if ($stf->sender_signed_at)
                            <p class="text-xs text-green-600 dark:text-green-400 mt-1">✓ Sudah tanda tangan · {{ $stf->sender_signed_at->diffForHumans() }}</p>
                        @else
                            <form method="POST" action="{{ route('stf.sign-sender', $stf) }}" id="senderSignForm" class="mt-2">
                                @csrf
                                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700">
                                    <canvas id="senderPad" class="w-full rounded-lg" style="height:140px; touch-action:none;"></canvas>
                                </div>
                                <input type="hidden" name="signature" id="senderSig">
                                <div class="flex items-center justify-between mt-2">
                                    <button type="button" id="senderClear" class="text-xs text-gray-500 hover:text-gray-700">Hapus</button>
                                    <button type="submit" class="bg-brand-500 text-white px-4 py-1.5 rounded-lg text-xs hover:bg-brand-600">Simpan TTD Pengirim</button>
                                </div>
                            </form>
                        @endif
                    </div>
                    {{-- PIC Penerima --}}
                    <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">PIC Penerima{{ $stf->receiver_name ? ' — '.$stf->receiver_name : '' }}</p>
                        @if ($stf->receiver_signed_at)
                            <p class="text-xs text-green-600 dark:text-green-400 mt-1">✓ Sudah tanda tangan · {{ $stf->receiver_signed_at->diffForHumans() }}</p>
                        @else
                            <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">Menunggu tanda tangan penerima — kirim link via WhatsApp.</p>
                        @endif
                    </div>
                </div>
                <p class="text-xs text-gray-400">Kolom Atasan terisi otomatis; kolom Mengetahui tetap ditandatangani manual bila diperlukan.</p>
            </div>

            @if (! $stf->sender_signed_at)
                <script>
                    (function () {
                        var canvas = document.getElementById('senderPad');
                        if (!canvas) return;
                        var ctx = canvas.getContext('2d'); var drawing = false;
                        function resize() { var r = window.devicePixelRatio || 1, rect = canvas.getBoundingClientRect(); canvas.width = rect.width * r; canvas.height = rect.height * r; ctx.scale(r, r); ctx.lineWidth = 2.5; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#111827'; }
                        function pt(e) { var rect = canvas.getBoundingClientRect(); return { x: e.clientX - rect.left, y: e.clientY - rect.top }; }
                        function ink() { try { var d = ctx.getImageData(0, 0, canvas.width, canvas.height).data, n = 0; for (var i = 3; i < d.length; i += 4) { if (d[i] > 10) n++; } return n; } catch (e) { return 9999; } }
                        canvas.addEventListener('pointerdown', function (e) { drawing = true; var p = pt(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); });
                        canvas.addEventListener('pointermove', function (e) { if (!drawing) return; var p = pt(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); });
                        window.addEventListener('pointerup', function () { drawing = false; });
                        window.addEventListener('load', resize);
                        document.getElementById('senderClear').addEventListener('click', function () { ctx.clearRect(0, 0, canvas.width, canvas.height); });
                        document.getElementById('senderSignForm').addEventListener('submit', function (e) { if (ink() < 200) { e.preventDefault(); alert('Mohon tanda tangan dulu dengan jelas.'); return; } document.getElementById('senderSig').value = canvas.toDataURL('image/png'); });
                    })();
                </script>
            @endif

            @if (! $stf->receiver_signed_at)
                <script>
                    (function () {
                        var url = @js(route('stf.status', $stf));
                        var tries = 0;
                        var timer = setInterval(function () {
                            if (++tries > 120) { clearInterval(timer); return; } // berhenti setelah ~20 menit
                            fetch(url, { headers: { 'Accept': 'application/json' } })
                                .then(function (r) { return r.ok ? r.json() : null; })
                                .then(function (d) { if (d && d.receiver_signed) { clearInterval(timer); location.reload(); } })
                                .catch(function () {});
                        }, 10000);
                    })();
                </script>
            @endif

            @php
                $input = 'block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm';
            @endphp
            <form method="POST" action="{{ route('stf.items.update', $stf) }}" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-100 dark:ring-gray-700/60 p-6 space-y-4">
                @csrf @method('PUT')
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Rincian Aset</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Bisa diedit, mis. isi spesifikasi manual.</span>
                </div>
                @foreach ($stf->items as $i => $item)
                    <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4 space-y-3">
                        <p class="text-xs text-gray-500">{{ $loop->iteration }}. <span class="font-mono text-brand-500">{{ $item->asset_no }}</span></p>
                        <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                            <div class="md:col-span-3">
                                <label class="text-xs text-gray-500">Deskripsi</label>
                                <input type="text" name="items[{{ $i }}][description]" required class="{{ $input }}" value="{{ $item->description }}">
                            </div>
                            <div class="md:col-span-1">
                                <label class="text-xs text-gray-500">Qty</label>
                                <input type="number" min="1" name="items[{{ $i }}][quantity]" required class="{{ $input }}" value="{{ $item->quantity }}">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500">Notes</label>
                                <input type="text" name="items[{{ $i }}][notes]" class="{{ $input }}" value="{{ $item->notes }}">
                            </div>
                            <div class="md:col-span-6">
                                <label class="text-xs text-gray-500">Spesifikasi (Processor, RAM, SSD)</label>
                                <textarea name="items[{{ $i }}][spec]" rows="2" class="{{ $input }}" placeholder="mis. Processor: Intel i5 | RAM: 8 GB | SSD: 256 GB">{{ $item->spec }}</textarea>
                            </div>
                        </div>
                    </div>
                @endforeach
                <button type="submit" class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600">Simpan Perubahan</button>
            </form>

            <a href="{{ route('stf.index') }}" class="text-sm text-brand-500 hover:underline">&larr; Kembali ke daftar STF</a>
        </div>
    </div>
</x-app-layout>
