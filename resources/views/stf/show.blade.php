<x-app-layout title="STF {{ $stf->number }}">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">STF {{ $stf->number }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-3 bg-green-100 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-4">
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
                $input = 'block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm';
            @endphp
            <form method="POST" action="{{ route('stf.items.update', $stf) }}" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-4">
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
