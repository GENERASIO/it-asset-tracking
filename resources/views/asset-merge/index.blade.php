<x-app-layout title="Gabung Aset">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Tinjau Aset dari Agent</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-3 bg-green-100 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
            @endif

            <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 text-sm">
                Aset di bawah ini kemungkinan <strong>dibuat otomatis oleh agent</strong> (ada data agent, belum ada pemegang,
                lokasi masih "Tidak Diketahui"). Kalau salah satunya sebenarnya laptop yang <strong>sudah ada datanya</strong>
                (cuma serial-nya beda), <strong>gabungkan</strong> ke aset lama supaya nama pemegang & histori tidak kembar.
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3 text-left">Kode</th>
                            <th class="px-4 py-3 text-left">Model</th>
                            <th class="px-4 py-3 text-left">Serial</th>
                            <th class="px-4 py-3 text-left hidden sm:table-cell">Terakhir Check-in</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($candidates as $c)
                            <tr class="text-gray-900 dark:text-white">
                                <td class="px-4 py-3 font-mono font-semibold text-brand-500">
                                    <a href="{{ route('assets.show', $c) }}" class="hover:underline">{{ $c->asset_code }}</a>
                                </td>
                                <td class="px-4 py-3">{{ $c->name }}</td>
                                <td class="px-4 py-3 font-mono">{{ $c->serial_number ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 hidden sm:table-cell">{{ $c->last_seen_at?->diffForHumans() ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('asset-merge.create', $c) }}"
                                       class="inline-block bg-brand-500 hover:bg-brand-600 text-white px-4 py-1.5 rounded-lg text-sm">Gabungkan</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Tidak ada aset yang perlu ditinjau. 👍</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
