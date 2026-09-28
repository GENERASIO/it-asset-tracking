<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (isset($stats))
                {{-- DASHBOARD SUPER ADMIN / IT STAFF --}}

                @if ($warrantyAlerts->count() > 0)
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded-lg p-4">
                        <h3 class="flex items-center gap-2 font-semibold text-yellow-800 dark:text-yellow-300 mb-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            Garansi Akan Habis
                        </h3>
                        <div class="space-y-1">
                            @foreach ($warrantyAlerts as $asset)
                                <div class="flex justify-between items-center text-sm">
                                    <a href="{{ route('assets.show', $asset) }}" class="text-yellow-900 dark:text-yellow-200 hover:underline">
                                        <span class="font-mono font-semibold">{{ $asset->asset_code }}</span>
                                        — {{ $asset->name }}
                                    </a>
                                    <span class="text-xs text-yellow-700 font-medium whitespace-nowrap">
                                        {{ $asset->warranty_expired_at->translatedFormat('d F Y') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($overdueMaintenances->count() > 0)
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 rounded-lg p-4">
                        <h3 class="flex items-center gap-2 font-semibold text-red-800 dark:text-red-300 mb-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                            </svg>
                            Maintenance Menggantung
                        </h3>
                        <div class="space-y-1">
                            @foreach ($overdueMaintenances as $log)
                                <div class="flex justify-between items-center text-sm">
                                    <div>
                                        @if ($log->asset)
                                            <a href="{{ route('assets.show', $log->asset) }}" class="text-red-900 dark:text-red-200 hover:underline">
                                                <span class="font-mono font-semibold">{{ $log->asset->asset_code }}</span>
                                                — {{ $log->asset->name }}
                                            </a>
                                        @endif
                                        <p class="text-red-700 text-xs">{{ $log->issue }}</p>
                                    </div>
                                    <span class="text-xs text-red-700 font-medium whitespace-nowrap">
                                        {{ $log->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="{{ route('assets.index', ['view' => 'table']) }}"
                       class="block bg-white dark:bg-gray-800 rounded-xl shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 p-5 cursor-pointer">
                        <p class="text-sm text-gray-500">Total Aset</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">{{ $stats['total'] }}</p>
                    </a>
                    <a href="{{ route('assets.index', ['status' => 'available', 'view' => 'table']) }}"
                       class="block bg-white dark:bg-gray-800 rounded-xl shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 p-5 border-l-4 border-green-500 cursor-pointer">
                        <p class="text-sm text-gray-500">Available</p>
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $stats['available'] }}</p>
                    </a>
                    <a href="{{ route('assets.index', ['status' => 'in_use', 'view' => 'table']) }}"
                       class="block bg-white dark:bg-gray-800 rounded-xl shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 p-5 border-l-4 border-blue-500 cursor-pointer">
                        <p class="text-sm text-gray-500">In Use</p>
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $stats['in_use'] }}</p>
                    </a>
                    <a href="{{ route('assets.index', ['status' => 'maintenance', 'view' => 'table']) }}"
                       class="block bg-white dark:bg-gray-800 rounded-xl shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 p-5 border-l-4 border-yellow-500 cursor-pointer">
                        <p class="text-sm text-gray-500">Maintenance</p>
                        <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ $stats['maintenance'] }}</p>
                    </a>
                    <a href="{{ route('assets.index', ['status' => 'broken', 'view' => 'table']) }}"
                       class="block bg-white dark:bg-gray-800 rounded-xl shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 p-5 border-l-4 border-red-500 cursor-pointer">
                        <p class="text-sm text-gray-500">Broken</p>
                        <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $stats['broken'] }}</p>
                    </a>
                    <a href="{{ route('assets.index', ['status' => 'retired', 'view' => 'table']) }}"
                       class="block bg-white dark:bg-gray-800 rounded-xl shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 p-5 border-l-4 border-gray-400 cursor-pointer">
                        <p class="text-sm text-gray-500">Retired</p>
                        <p class="text-3xl font-bold text-gray-500 dark:text-gray-400 mt-1">{{ $stats['retired'] }}</p>
                    </a>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 p-5">
                        <p class="text-sm text-gray-500">Kategori</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">{{ $stats['categories'] }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 p-5">
                        <p class="text-sm text-gray-500">Lokasi</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-white mt-1">{{ $stats['locations'] }}</p>
                    </div>
                </div>

                {{-- QUICK ACTIONS --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 p-5">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Aksi Cepat</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('assets.create') }}"
                           class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600 transition-all duration-150 hover:scale-105 active:scale-95">
                            + Tambah Aset
                        </a>
                        <a href="{{ route('assets.index') }}"
                           class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-150 hover:scale-105 active:scale-95">
                            Lihat Semua Aset
                        </a>
                        @if (Auth::user()->role === 'super_admin')
                            <a href="{{ route('categories.index') }}"
                               class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-150 hover:scale-105 active:scale-95">
                                Kelola Kategori
                            </a>
                            <a href="{{ route('locations.index') }}"
                               class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-150 hover:scale-105 active:scale-95">
                                Kelola Lokasi
                            </a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- RECENT ASSETS --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 p-5">
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Aset Terbaru</h3>
                        @forelse ($recentAssets as $asset)
                            <div class="flex justify-between items-center border-b dark:border-gray-700 py-2 text-sm">
                                <div>
                                    <a href="{{ route('assets.show', $asset) }}" class="font-mono font-semibold text-brand-500">
                                        {{ $asset->asset_code }}
                                    </a>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $asset->name }}</p>
                                </div>
                                <span class="text-xs text-gray-400">{{ $asset->created_at->diffForHumans() }}</span>
                            </div>
                        @empty
                            <p class="text-gray-400 text-sm">Belum ada aset.</p>
                        @endforelse
                    </div>

                    {{-- WARRANTY ALERT --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 p-5">
                        <h3 class="flex items-center gap-2 font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            <svg class="w-5 h-5 shrink-0 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            Garansi Segera Habis (30 hari)
                        </h3>
                        @forelse ($warrantySoon as $asset)
                            <div class="flex justify-between items-center border-b dark:border-gray-700 py-2 text-sm">
                                <div>
                                    <a href="{{ route('assets.show', $asset) }}" class="font-mono font-semibold text-brand-500">
                                        {{ $asset->asset_code }}
                                    </a>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $asset->name }}</p>
                                </div>
                                <span class="text-xs text-red-500 font-medium">
                                    {{ $asset->warranty_expired_at->format('d M Y') }}
                                </span>
                            </div>
                        @empty
                            <p class="text-gray-400 text-sm">Tidak ada aset yang garansinya akan habis.</p>
                        @endforelse
                    </div>
                </div>

                {{-- RECENT ACTIVITIES --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                    <p class="font-medium text-sm mb-4 dark:text-white">Aktivitas Terbaru</p>

                    @forelse($recentActivities as $activity)
                        <div class="flex gap-3 py-2.5 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                            <div class="w-7 h-7 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center text-xs font-medium text-purple-700 dark:text-purple-300 flex-shrink-0">
                                {{ collect(explode(' ', $activity['user_name']))->map(fn($n) => strtoupper(substr($n, 0, 1)))->take(2)->join('') }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm dark:text-gray-200">
                                    <span class="font-medium">{{ $activity['user_name'] }}</span>
                                    {{ $activity['description'] }}
                                    <span class="font-mono text-xs bg-gray-50 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ $activity['asset_code'] }}</span>
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $activity['created_at']->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4">Belum ada aktivitas.</p>
                    @endforelse
                </div>

            @elseif (isset($myAssets))
                {{-- DASHBOARD USER BIASA --}}

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 p-5">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Aset yang Anda Pegang</h3>
                    @forelse ($myAssets as $asset)
                        <div class="flex justify-between items-center border-b dark:border-gray-700 py-3 text-sm">
                            <div>
                                <p class="font-mono font-semibold text-gray-800 dark:text-white">{{ $asset->asset_code }}</p>
                                <p class="text-gray-600 dark:text-gray-400">{{ $asset->name }} — {{ $asset->category->name }}</p>
                                <p class="text-gray-400 text-xs">Lokasi: {{ $asset->location->name }}</p>
                            </div>
                            <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700">
                                {{ ucfirst(str_replace('_',' ',$asset->status)) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-400 text-sm">Anda belum memegang aset apapun.</p>
                    @endforelse
                </div>

            @endif

        </div>
    </div>
</x-app-layout>