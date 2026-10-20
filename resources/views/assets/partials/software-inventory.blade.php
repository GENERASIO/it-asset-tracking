@php
    $statusClasses = [
        'licensed' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
        'subscription' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'grace' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
        'notification' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        'unlicensed' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    ];
    $software = $asset->software;
@endphp

<div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <h3 class="font-semibold text-gray-700 dark:text-gray-300">Lisensi Software</h3>
        <span class="text-xs text-gray-500">Diperbarui {{ $asset->inventory_synced_at->diffForHumans() }}</span>
    </div>

    @if ($asset->crack_indicators)
        <div class="mb-3 p-3 rounded-lg bg-red-50 dark:bg-red-900/30 text-sm text-red-700 dark:text-red-300">
            <p class="font-medium">Ditemukan jejak tool aktivasi ilegal:</p>
            <ul class="list-disc ml-5 mt-1 font-mono text-xs break-all">
                @foreach ($asset->crack_indicators as $indicator)
                    <li>{{ $indicator }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @forelse ($asset->licenses as $license)
        <div class="py-2 border-b last:border-b-0 border-gray-100 dark:border-gray-700 text-sm">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs uppercase tracking-wide text-gray-500 w-16 shrink-0">{{ $license->product }}</span>
                <span class="font-medium text-gray-900 dark:text-white break-all">{{ $license->name }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $statusClasses[$license->status] ?? 'bg-gray-100 text-gray-600' }}">{{ $license->status_label }}</span>
                @if ($license->suspected)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-red-600 text-white">Terindikasi tidak resmi</span>
                @endif
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 sm:ml-[4.5rem]">
                {{ $license->channel ?? '-' }}
                @if ($license->partial_key) · key ...{{ $license->partial_key }} @endif
                @if ($license->kms_host) · KMS {{ $license->kms_host }} @endif
                @if ($license->expires_at) · berlaku sampai {{ $license->expires_at->translatedFormat('d M Y') }} @endif
            </p>
            @if ($license->suspected_reason)
                <p class="text-xs text-red-600 dark:text-red-400 mt-1 sm:ml-[4.5rem]">{{ $license->suspected_reason }}</p>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500">Tidak ada data lisensi Office/Windows.</p>
    @endforelse

    <div class="mt-4" x-data="{ open: false, q: '' }">
        <button type="button" @click="open = !open" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
            <span x-text="open ? 'Sembunyikan' : 'Lihat'"></span> aplikasi terpasang ({{ $software->count() }})
        </button>

        <div x-show="open" style="display: none" class="mt-3">
            <input type="search" x-model="q" placeholder="Cari aplikasi / publisher..."
                   class="w-full mb-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
            <div class="max-h-96 overflow-y-auto border border-gray-100 dark:border-gray-700 rounded-lg">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 sticky top-0">
                        <tr>
                            <th class="px-3 py-2">Aplikasi</th>
                            <th class="px-3 py-2 hidden sm:table-cell">Versi</th>
                            <th class="px-3 py-2 hidden md:table-cell">Publisher</th>
                            <th class="px-3 py-2 hidden md:table-cell">Dipasang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($software as $app)
                            <tr class="border-b dark:border-gray-700"
                                x-show="!q || {{ Js::from(mb_strtolower($app->name.' '.$app->publisher)) }}.includes(q.toLowerCase())">
                                <td class="px-3 py-1.5 text-gray-900 dark:text-white break-all">{{ $app->name }}</td>
                                <td class="px-3 py-1.5 text-gray-600 dark:text-gray-300 hidden sm:table-cell">{{ $app->version ?? '-' }}</td>
                                <td class="px-3 py-1.5 text-gray-600 dark:text-gray-300 hidden md:table-cell">{{ $app->publisher ?? '-' }}</td>
                                <td class="px-3 py-1.5 text-gray-600 dark:text-gray-300 hidden md:table-cell whitespace-nowrap">{{ $app->installed_on?->format('d/m/Y') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
