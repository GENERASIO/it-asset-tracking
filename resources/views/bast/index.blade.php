<x-app-layout title="Serah Terima (BAST)">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Serah Terima (BAST)</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="p-3 bg-green-100 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <form method="GET" class="flex flex-wrap gap-2">
                        <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama / kode aset / serial..."
                               class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm min-w-[220px]">
                        <select name="status" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                            <option value="">Semua status</option>
                            <option value="unsigned" @selected($status === 'unsigned')>Belum upload tanda tangan ({{ $unsignedCount }})</option>
                            <option value="signed" @selected($status === 'signed')>Sudah ditandatangani</option>
                        </select>
                        <button type="submit" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-lg text-sm">Cari</button>
                    </form>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Buat BAST dari halaman detail aset, atau centang beberapa aset di daftar Aset.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-2">Tanggal</th>
                                <th class="px-4 py-2">Diserahkan ke</th>
                                <th class="px-4 py-2">Barang</th>
                                <th class="px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($documents as $bast)
                                <tr class="border-b dark:border-gray-700 align-top hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                                    onclick="window.location='{{ route('bast.show', $bast) }}'">
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-900 dark:text-white">{{ $bast->handover_date_label }}</td>
                                    <td class="px-4 py-2">
                                        <a href="{{ route('bast.show', $bast) }}" class="text-gray-900 dark:text-white hover:underline">{{ $bast->second_party_name }}</a>
                                        <p class="text-xs text-gray-500">{{ $bast->second_party_position }}</p>
                                    </td>
                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">
                                        @foreach ($bast->items as $item)
                                            <p>
                                                @if ($item->asset)<span class="font-mono text-xs">{{ $item->asset->asset_code }}</span> · @endif
                                                {{ $item->detail }}
                                            </p>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        @if ($bast->isSigned())
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">Sudah ditandatangani</span>
                                        @else
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800">Belum upload</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-400">Belum ada BAST.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $documents->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
