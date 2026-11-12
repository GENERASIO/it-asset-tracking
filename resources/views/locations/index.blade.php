<x-app-layout title="Lokasi / Outlet">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Lokasi / Outlet</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-3 bg-green-50 text-green-700 ring-1 ring-green-200 dark:bg-green-900/30 dark:text-green-300 dark:ring-green-800 rounded-xl text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-100 dark:ring-gray-700/60 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-300">Daftar Lokasi</h3>
                    <a href="{{ route('locations.create') }}"
                       class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600 transition-all duration-150 hover:scale-105 active:scale-95">
                        + Tambah Lokasi
                    </a>
                </div>

                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/60 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Alamat</th>
                            <th class="px-4 py-3 text-right">Aset</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($locations as $location)
                            <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="shrink-0 flex items-center justify-center w-9 h-9 rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                        </span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $location->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><span class="font-mono text-xs px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">{{ $location->code }}</span></td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $location->address ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('assets.index', ['view' => 'table', 'location_id' => $location->id]) }}" class="font-semibold tabular-nums text-gray-900 dark:text-white hover:text-brand-600 dark:hover:text-blue-300">{{ $location->assets_count }}</a>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('locations.edit', $location) }}" title="Edit"
                                           class="inline-flex p-1.5 rounded-lg text-brand-500 hover:bg-brand-50 dark:hover:bg-gray-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zM19.5 7.125L16.875 4.5"/></svg>
                                        </a>
                                        <form action="{{ route('locations.destroy', $location) }}"
                                              method="POST" class="inline"
                                              onsubmit="event.preventDefault(); confirmAction('Lokasi yang sudah dihapus tidak bisa dikembalikan.', () => this.submit(), 'Hapus lokasi ini?');">
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
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                                    Belum ada lokasi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $locations->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>