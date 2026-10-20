<div class="overflow-x-auto">
    <table class="w-full text-sm text-left">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="px-4 py-2">Aset</th>
                <th class="px-4 py-2">Lisensi</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $license)
                <tr class="border-b dark:border-gray-700 align-top">
                    <td class="px-4 py-2 whitespace-nowrap">
                        <a href="{{ route('assets.show', $license->asset) }}" class="font-mono text-blue-600 dark:text-blue-400 hover:underline">{{ $license->asset->asset_code }}</a>
                        <p class="text-xs text-gray-500">{{ $license->asset->assignedUser?->name ?? 'Belum ada pemegang' }}</p>
                    </td>
                    <td class="px-4 py-2">
                        <span class="text-xs uppercase text-gray-500">{{ $license->product }}</span>
                        <p class="text-gray-900 dark:text-white break-all">{{ $license->name }}</p>
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $statusClasses[$license->status] ?? 'bg-gray-100 text-gray-600' }}">{{ $license->status_label }}</span>
                        @if ($license->expires_at)
                            <p class="text-xs text-gray-500 mt-1">s/d {{ $license->expires_at->translatedFormat('d M Y') }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-300">
                        {{ $license->suspected_reason ?? ($license->channel ?? '-') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-gray-400">{{ $empty }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
