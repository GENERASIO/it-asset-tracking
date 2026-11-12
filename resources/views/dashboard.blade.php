<x-app-layout title="Dashboard">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Dashboard</h2>
    </x-slot>

    @php
        $user = Auth::user();
        $now = now('Asia/Jakarta');
        $hour = (int) $now->format('G');
        $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
        $firstName = explode(' ', trim($user->name))[0];
        $card = 'bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-100 dark:ring-gray-700/60';

        // Warna status sama dengan badge status di seluruh aplikasi; selalu tampil bersama label.
        $statusMeta = [
            'available' => ['label' => 'Available', 'bar' => 'bg-green-500', 'text' => 'text-green-600 dark:text-green-400', 'soft' => 'bg-green-50 dark:bg-green-900/30'],
            'in_use' => ['label' => 'In Use', 'bar' => 'bg-blue-500', 'text' => 'text-blue-600 dark:text-blue-400', 'soft' => 'bg-blue-50 dark:bg-blue-900/30'],
            'maintenance' => ['label' => 'Maintenance', 'bar' => 'bg-amber-400', 'text' => 'text-amber-600 dark:text-amber-400', 'soft' => 'bg-amber-50 dark:bg-amber-900/30'],
            'broken' => ['label' => 'Broken', 'bar' => 'bg-red-500', 'text' => 'text-red-600 dark:text-red-400', 'soft' => 'bg-red-50 dark:bg-red-900/30'],
            'retired' => ['label' => 'Retired', 'bar' => 'bg-gray-400', 'text' => 'text-gray-500 dark:text-gray-400', 'soft' => 'bg-gray-100 dark:bg-gray-700/50'],
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (isset($stats))
                @php
                    $total = max(1, $stats['total']);
                    $pct = fn ($n) => round($n / $total * 100);
                    $attention = $stats['maintenance'] + $stats['broken'];
                    $alertCount = $warrantyAlerts->count() + $overdueMaintenances->count();
                    $maxCat = max(1, (int) $byCategory->max('assets_count'));
                    $maxLoc = max(1, (int) $topLocations->max('assets_count'));
                @endphp

                {{-- HERO --}}
                <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-950 via-purple-900 to-violet-800 text-white shadow-lg">
                    <div class="absolute -top-24 -right-16 w-72 h-72 rounded-full bg-fuchsia-500/20 blur-3xl"></div>
                    <div class="absolute -bottom-24 left-1/3 w-72 h-72 rounded-full bg-violet-400/20 blur-3xl"></div>
                    <div class="absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 22px 22px;"></div>

                    <div class="relative p-6 sm:p-8 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                        <div>
                            <p class="text-sm text-violet-200">{{ $now->translatedFormat('l, d F Y') }}</p>
                            <h1 class="mt-1 text-2xl sm:text-3xl font-bold tracking-tight">{{ $greeting }}, {{ $firstName }} 👋</h1>
                            <p class="mt-2 text-violet-100/80 text-sm max-w-xl">
                                Saat ini ada <b class="text-white">{{ number_format($stats['total']) }} aset</b> tercatat di
                                <b class="text-white">{{ $stats['locations'] }} lokasi</b> dan <b class="text-white">{{ $stats['categories'] }} kategori</b>.
                                @if ($alertCount)
                                    <span class="text-amber-200">{{ $alertCount }} hal perlu ditindaklanjuti.</span>
                                @else
                                    Semua aman, tidak ada peringatan.
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('assets.create') }}"
                               class="inline-flex items-center gap-1.5 rounded-xl bg-white text-purple-900 px-4 py-2.5 text-sm font-semibold shadow hover:bg-violet-50 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Tambah Aset
                            </a>
                            <a href="{{ route('assets.index', ['view' => 'table']) }}"
                               class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 ring-1 ring-white/20 px-4 py-2.5 text-sm font-medium hover:bg-white/20 transition">
                                Lihat Semua Aset
                            </a>
                            <a href="{{ route('stf.index') }}"
                               class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 ring-1 ring-white/20 px-4 py-2.5 text-sm font-medium hover:bg-white/20 transition">
                                Mutasi Asset
                            </a>
                        </div>
                    </div>
                </section>

                {{-- KPI --}}
                @php
                    $kpis = [
                        ['label' => 'Total Aset', 'value' => $stats['total'], 'sub' => $stats['categories'].' kategori · '.$stats['locations'].' lokasi',
                         'url' => route('assets.index', ['view' => 'table']), 'chip' => 'bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-300',
                         'icon' => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'],
                        ['label' => 'Sedang Dipakai', 'value' => $stats['in_use'], 'sub' => $pct($stats['in_use']).'% dari total aset',
                         'url' => route('assets.index', ['view' => 'table', 'status' => 'in_use']), 'chip' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300',
                         'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z'],
                        ['label' => 'Tersedia', 'value' => $stats['available'], 'sub' => 'Siap dipinjamkan / dipasang',
                         'url' => route('assets.index', ['view' => 'table', 'status' => 'available']), 'chip' => 'bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-300',
                         'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['label' => 'Perlu Perhatian', 'value' => $attention, 'sub' => $stats['maintenance'].' maintenance · '.$stats['broken'].' rusak',
                         'url' => route('assets.index', ['view' => 'table', 'status' => $stats['broken'] ? 'broken' : 'maintenance']), 'chip' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300',
                         'icon' => 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z'],
                    ];
                @endphp
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($kpis as $k)
                        <a href="{{ $k['url'] }}" class="{{ $card }} group p-5 transition hover:shadow-md hover:-translate-y-0.5">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $k['label'] }}</p>
                                <span class="shrink-0 flex items-center justify-center w-9 h-9 rounded-xl {{ $k['chip'] }}">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $k['icon'] }}"/></svg>
                                </span>
                            </div>
                            <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white tabular-nums">{{ number_format($k['value']) }}</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 truncate">{{ $k['sub'] }}</p>
                        </a>
                    @endforeach
                </div>

                {{-- KOMPOSISI STATUS + PERLU PERHATIAN --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <section class="{{ $card }} p-6 lg:col-span-2">
                        <div class="flex items-baseline justify-between gap-3">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Komposisi Status Aset</h3>
                            <span class="text-xs text-gray-400">{{ number_format($stats['total']) }} aset</span>
                        </div>

                        <div class="mt-5 flex h-4 w-full gap-0.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                            @foreach ($statusMeta as $key => $m)
                                @if ($stats[$key] > 0)
                                    <a href="{{ route('assets.index', ['view' => 'table', 'status' => $key]) }}"
                                       class="{{ $m['bar'] }} h-full hover:opacity-80 transition first:rounded-l-full last:rounded-r-full"
                                       style="width: {{ $stats[$key] / $total * 100 }}%; min-width: 6px;"
                                       title="{{ $m['label'] }}: {{ $stats[$key] }} aset ({{ $pct($stats[$key]) }}%)"></a>
                                @endif
                            @endforeach
                        </div>

                        <div class="mt-6 grid grid-cols-2 sm:grid-cols-5 gap-3">
                            @foreach ($statusMeta as $key => $m)
                                <a href="{{ route('assets.index', ['view' => 'table', 'status' => $key]) }}"
                                   class="rounded-xl p-3 {{ $m['soft'] }} hover:ring-1 hover:ring-gray-200 dark:hover:ring-gray-600 transition">
                                    <span class="flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-300">
                                        <span class="w-2.5 h-2.5 rounded-full {{ $m['bar'] }}"></span>{{ $m['label'] }}
                                    </span>
                                    <span class="mt-1.5 flex items-baseline gap-1.5">
                                        <span class="text-xl font-bold text-gray-900 dark:text-white tabular-nums">{{ $stats[$key] }}</span>
                                        <span class="text-xs text-gray-400">{{ $pct($stats[$key]) }}%</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </section>

                    <section class="{{ $card }} p-6 flex flex-col">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Perlu Perhatian</h3>
                            @if ($alertCount)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">{{ $alertCount }}</span>
                            @endif
                        </div>
                        <div class="mt-4 space-y-2 flex-1 overflow-y-auto max-h-72 pr-1">
                            @foreach ($overdueMaintenances as $log)
                                <a href="{{ $log->asset ? route('assets.show', $log->asset) : '#' }}"
                                   class="flex items-start gap-3 rounded-xl p-3 bg-red-50 dark:bg-red-900/20 hover:bg-red-100/70 dark:hover:bg-red-900/30 transition">
                                    <span class="shrink-0 mt-0.5 flex items-center justify-center w-7 h-7 rounded-lg bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-300">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-gray-900 dark:text-white truncate">
                                            <span class="font-mono">{{ optional($log->asset)->asset_code ?? '-' }}</span> · Maintenance menggantung
                                        </span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400 truncate">{{ $log->issue }} · {{ $log->created_at->diffForHumans() }}</span>
                                    </span>
                                </a>
                            @endforeach
                            @foreach ($warrantyAlerts as $asset)
                                <a href="{{ route('assets.show', $asset) }}"
                                   class="flex items-start gap-3 rounded-xl p-3 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100/70 dark:hover:bg-amber-900/30 transition">
                                    <span class="shrink-0 mt-0.5 flex items-center justify-center w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-300">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-gray-900 dark:text-white truncate">
                                            <span class="font-mono">{{ $asset->asset_code }}</span> · Garansi habis
                                        </span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400 truncate">{{ $asset->name }} · {{ $asset->warranty_expired_at->translatedFormat('d M Y') }}</span>
                                    </span>
                                </a>
                            @endforeach
                            @if (! $alertCount)
                                <div class="h-full flex flex-col items-center justify-center text-center py-8">
                                    <span class="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-300">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    </span>
                                    <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-200">Semua aman</p>
                                    <p class="text-xs text-gray-400">Tidak ada garansi yang akan habis atau maintenance menggantung.</p>
                                </div>
                            @endif
                        </div>
                    </section>
                </div>

                {{-- PER KATEGORI + PER LOKASI --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <section class="{{ $card }} p-6">
                        <div class="flex items-baseline justify-between gap-3">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Aset per Kategori</h3>
                            <span class="text-xs text-gray-400">Warna = status</span>
                        </div>
                        <div class="mt-5 space-y-4">
                            @forelse ($byCategory as $cat)
                                <a href="{{ route('assets.index', ['view' => 'table', 'category_id' => $cat->id]) }}" class="block group">
                                    <div class="flex items-baseline justify-between gap-3 text-sm">
                                        <span class="text-gray-700 dark:text-gray-200 group-hover:text-brand-600 dark:group-hover:text-white">{{ $cat->name }}</span>
                                        <span class="font-semibold text-gray-900 dark:text-white tabular-nums">{{ $cat->assets_count }}</span>
                                    </div>
                                    <div class="mt-1.5 h-2.5 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                                        <div class="flex h-full gap-0.5" style="width: {{ $cat->assets_count / $maxCat * 100 }}%">
                                            @foreach ($statusMeta as $key => $m)
                                                @php $n = $cat->{$key.'_count'}; @endphp
                                                @if ($n > 0)
                                                    <span class="{{ $m['bar'] }} h-full first:rounded-l-full last:rounded-r-full"
                                                          style="width: {{ $n / $cat->assets_count * 100 }}%; min-width: 4px;"
                                                          title="{{ $cat->name }} · {{ $m['label'] }}: {{ $n }}"></span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <p class="text-sm text-gray-400 text-center py-4">Belum ada kategori.</p>
                            @endforelse
                        </div>
                        <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700 flex flex-wrap gap-x-4 gap-y-1.5">
                            @foreach ($statusMeta as $m)
                                <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400"><span class="w-2.5 h-2.5 rounded-full {{ $m['bar'] }}"></span>{{ $m['label'] }}</span>
                            @endforeach
                        </div>
                    </section>

                    <section class="{{ $card }} p-6">
                        <div class="flex items-baseline justify-between gap-3">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Lokasi dengan Aset Terbanyak</h3>
                            <a href="{{ route('locations.index') }}" class="text-xs text-brand-500 hover:underline">Semua lokasi</a>
                        </div>
                        <div class="mt-5 space-y-4">
                            @forelse ($topLocations as $loc)
                                <a href="{{ route('assets.index', ['view' => 'table', 'location_id' => $loc->id]) }}" class="block group">
                                    <div class="flex items-baseline justify-between gap-3 text-sm">
                                        <span class="text-gray-700 dark:text-gray-200 truncate group-hover:text-brand-600 dark:group-hover:text-white">{{ $loc->name }}</span>
                                        <span class="font-semibold text-gray-900 dark:text-white tabular-nums">{{ $loc->assets_count }}</span>
                                    </div>
                                    <div class="mt-1.5 h-2.5 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500" style="width: {{ $loc->assets_count / $maxLoc * 100 }}%; min-width: 6px;"
                                             title="{{ $loc->name }}: {{ $loc->assets_count }} aset"></div>
                                    </div>
                                </a>
                            @empty
                                <p class="text-sm text-gray-400 text-center py-4">Belum ada aset di lokasi mana pun.</p>
                            @endforelse
                        </div>
                    </section>
                </div>

                {{-- AKTIVITAS --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <section class="{{ $card }} p-6 lg:col-span-2">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Aktivitas Terbaru</h3>
                        <ol class="mt-5 relative">
                            @forelse ($recentActivities as $activity)
                                <li class="relative flex gap-3 pb-5 last:pb-0">
                                    @if (! $loop->last)
                                        <span class="absolute left-4 top-9 -bottom-0 w-px bg-gray-200 dark:bg-gray-700"></span>
                                    @endif
                                    <span class="relative shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center text-[11px] font-semibold text-white ring-4 ring-white dark:ring-gray-800">
                                        {{ collect(explode(' ', $activity['user_name']))->map(fn ($n) => strtoupper(substr($n, 0, 1)))->take(2)->join('') }}
                                    </span>
                                    <div class="min-w-0 flex-1 pt-1">
                                        <p class="text-sm text-gray-700 dark:text-gray-200">
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ $activity['user_name'] }}</span>
                                            {{ $activity['description'] }}
                                            <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ $activity['asset_code'] }}</span>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $activity['created_at']->diffForHumans() }}</p>
                                    </div>
                                </li>
                            @empty
                                <p class="text-sm text-gray-400 text-center py-4">Belum ada aktivitas.</p>
                            @endforelse
                        </ol>
                    </section>

                    <div class="space-y-6">
                        <section class="{{ $card }} p-6">
                            <div class="flex items-baseline justify-between gap-3">
                                <h3 class="font-semibold text-gray-900 dark:text-white">Aktivitas Agent</h3>
                                <span class="text-xs text-gray-400">Perangkat &amp; GPS</span>
                            </div>
                            <div class="mt-4 space-y-1">
                                @forelse ($agentActivities ?? [] as $act)
                                    <a href="{{ route('assets.show', $act['asset_id']) }}" class="flex items-center gap-3 rounded-xl p-2 -mx-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                        <span class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg {{ $act['kind'] === 'agent_register' ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-300' }}">
                                            @if ($act['kind'] === 'agent_register')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                            @endif
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm text-gray-900 dark:text-white truncate"><span class="font-mono font-semibold">{{ $act['asset_code'] }}</span> · {{ $act['label'] }}</span>
                                            <span class="block text-xs text-gray-400 truncate">{{ $act['name'] }} · {{ $act['created_at']->diffForHumans() }}</span>
                                        </span>
                                    </a>
                                @empty
                                    <p class="py-4 text-sm text-gray-400 text-center">Belum ada aktivitas agent.</p>
                                @endforelse
                            </div>
                        </section>

                        <section class="{{ $card }} p-6">
                            <div class="flex items-baseline justify-between gap-3">
                                <h3 class="font-semibold text-gray-900 dark:text-white">Aset Terbaru</h3>
                                <a href="{{ route('assets.index', ['view' => 'table']) }}" class="text-xs text-brand-500 hover:underline">Lihat semua</a>
                            </div>
                            <div class="mt-4 space-y-1">
                                @forelse ($recentAssets as $asset)
                                    <a href="{{ route('assets.show', $asset) }}" class="flex items-center justify-between gap-3 rounded-xl p-2 -mx-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                        <span class="min-w-0">
                                            <span class="block font-mono text-sm font-semibold text-brand-500">{{ $asset->asset_code }}</span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400 truncate">{{ $asset->name }}</span>
                                        </span>
                                        <span class="shrink-0 text-xs text-gray-400">{{ $asset->created_at->diffForHumans() }}</span>
                                    </a>
                                @empty
                                    <p class="text-gray-400 text-sm">Belum ada aset.</p>
                                @endforelse
                            </div>
                        </section>
                    </div>
                </div>

            @elseif (isset($myAssets))
                {{-- DASHBOARD USER BIASA --}}
                <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-950 via-purple-900 to-violet-800 text-white shadow-lg p-6 sm:p-8">
                    <div class="absolute -top-24 -right-16 w-72 h-72 rounded-full bg-fuchsia-500/20 blur-3xl"></div>
                    <div class="relative">
                        <p class="text-sm text-violet-200">{{ $now->translatedFormat('l, d F Y') }}</p>
                        <h1 class="mt-1 text-2xl sm:text-3xl font-bold tracking-tight">{{ $greeting }}, {{ $firstName }} 👋</h1>
                        <p class="mt-2 text-violet-100/80 text-sm">Anda memegang <b class="text-white">{{ $myAssets->count() }} aset</b> perusahaan.</p>
                    </div>
                </section>

                <section class="{{ $card }} p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Aset yang Anda Pegang</h3>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @forelse ($myAssets as $asset)
                            @php $m = $statusMeta[$asset->status] ?? $statusMeta['retired']; @endphp
                            <div class="rounded-xl ring-1 ring-gray-100 dark:ring-gray-700 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-mono font-semibold text-gray-900 dark:text-white">{{ $asset->asset_code }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 truncate">{{ $asset->name }} — {{ optional($asset->category)->name }}</p>
                                        <p class="text-xs text-gray-400 mt-1">Lokasi: {{ optional($asset->location)->name ?? '-' }}</p>
                                    </div>
                                    <span class="shrink-0 inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs {{ $m['soft'] }} text-gray-700 dark:text-gray-200">
                                        <span class="w-2 h-2 rounded-full {{ $m['bar'] }}"></span>{{ $m['label'] }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-400 text-sm">Anda belum memegang aset apapun.</p>
                        @endforelse
                    </div>
                </section>
            @endif

        </div>
    </div>
</x-app-layout>
