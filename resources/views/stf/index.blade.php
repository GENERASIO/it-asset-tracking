<x-app-layout title="Mutasi Aset (STF)">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Mutasi Aset (STF)</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-3 bg-green-100 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
            @endif

            <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 text-sm">
                Untuk membuat STF baru: buka <strong>Aset</strong>, centang aset yang dipindah, lalu klik <strong>Buat STF</strong>.
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3 text-left">No. STF</th>
                            <th class="px-4 py-3 text-left">Tanggal</th>
                            <th class="px-4 py-3 text-left">Asal &rarr; Tujuan</th>
                            <th class="px-4 py-3 text-left">PIC Pengirim</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-900 dark:text-white">
                        @forelse ($documents as $doc)
                            <tr>
                                <td class="px-4 py-3 font-mono font-semibold text-brand-500">
                                    <a href="{{ route('stf.show', $doc) }}" class="hover:underline">{{ $doc->number }}</a>
                                </td>
                                <td class="px-4 py-3">{{ $doc->transfer_date_long }}</td>
                                <td class="px-4 py-3">{{ optional($doc->fromLocation)->name ?? 'Beberapa lokasi' }} &rarr; {{ optional($doc->toLocation)->name }}</td>
                                <td class="px-4 py-3">{{ $doc->sender_name }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('stf.show', $doc) }}" title="Lihat"
                                           class="inline-flex p-1.5 rounded-lg text-brand-500 hover:bg-brand-50 dark:hover:bg-gray-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </a>
                                        <a href="{{ route('stf.pdf', $doc) }}" title="Cetak PDF"
                                           class="inline-flex p-1.5 rounded-lg text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247"/></svg>
                                        </a>
                                        <form method="POST" action="{{ route('stf.destroy', $doc) }}" class="inline"
                                              onsubmit="event.preventDefault(); confirmAction('Dokumen STF ini akan dihapus. Lokasi aset yang sudah pindah tidak berubah.', () => this.submit(), 'Hapus STF?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Hapus"
                                                    class="inline-flex p-1.5 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-gray-700">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Belum ada STF. Buat dari halaman Aset.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $documents->links() }}
        </div>
    </div>
</x-app-layout>
