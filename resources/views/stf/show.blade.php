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

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3 text-left">No</th>
                            <th class="px-4 py-3 text-left">Asset No</th>
                            <th class="px-4 py-3 text-left">Deskripsi</th>
                            <th class="px-4 py-3 text-center">Qty</th>
                            <th class="px-4 py-3 text-left">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-900 dark:text-white">
                        @foreach ($stf->items as $item)
                            <tr>
                                <td class="px-4 py-3">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 font-mono">{{ $item->asset_no }}</td>
                                <td class="px-4 py-3">{{ $item->description }}</td>
                                <td class="px-4 py-3 text-center">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $item->notes ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <a href="{{ route('stf.index') }}" class="text-sm text-brand-500 hover:underline">&larr; Kembali ke daftar STF</a>
        </div>
    </div>
</x-app-layout>
