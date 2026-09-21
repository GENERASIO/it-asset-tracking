<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (isset($stats))
                {{-- DASHBOARD SUPER ADMIN / IT STAFF --}}

                @if ($warrantyAlerts->count() > 0)
                    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4">
                        <h3 class="font-semibold text-yellow-800 mb-2">⚠️ Garansi Akan Habis</h3>
                        <div class="space-y-1">
                            @foreach ($warrantyAlerts as $asset)
                                <div class="flex justify-between items-center text-sm">
                                    <a href="{{ route('assets.show', $asset) }}" class="text-yellow-900 hover:underline">
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
                    <div class="bg-red-50 border border-red-300 rounded-lg p-4">
                        <h3 class="font-semibold text-red-800 mb-2">🔧 Maintenance Menggantung</h3>
                        <div class="space-y-1">
                            @foreach ($overdueMaintenances as $log)
                                <div class="flex justify-between items-center text-sm">
                                    <div>
                                        @if ($log->asset)
                                            <a href="{{ route('assets.show', $log->asset) }}" class="text-red-900 hover:underline">
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
                    <div class="bg-white rounded-xl shadow p-5">
                        <p class="text-sm text-gray-500">Total Aset</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1">{{ $stats['total'] }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow p-5 border-l-4 border-green-500">
                        <p class="text-sm text-gray-500">Available</p>
                        <p class="text-3xl font-bold text-green-600 mt-1">{{ $stats['available'] }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow p-5 border-l-4 border-blue-500">
                        <p class="text-sm text-gray-500">In Use</p>
                        <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['in_use'] }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow p-5 border-l-4 border-yellow-500">
                        <p class="text-sm text-gray-500">Maintenance</p>
                        <p class="text-3xl font-bold text-yellow-600 mt-1">{{ $stats['maintenance'] }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow p-5 border-l-4 border-red-500">
                        <p class="text-sm text-gray-500">Broken</p>
                        <p class="text-3xl font-bold text-red-600 mt-1">{{ $stats['broken'] }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow p-5 border-l-4 border-gray-400">
                        <p class="text-sm text-gray-500">Retired</p>
                        <p class="text-3xl font-bold text-gray-500 mt-1">{{ $stats['retired'] }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow p-5">
                        <p class="text-sm text-gray-500">Kategori</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1">{{ $stats['categories'] }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow p-5">
                        <p class="text-sm text-gray-500">Lokasi</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1">{{ $stats['locations'] }}</p>
                    </div>
                </div>

                {{-- QUICK ACTIONS --}}
                <div class="bg-white rounded-xl shadow p-5">
                    <h3 class="font-semibold text-gray-700 mb-3">Aksi Cepat</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('assets.create') }}"
                           class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600">
                            + Tambah Aset
                        </a>
                        <a href="{{ route('scan.mobile') }}"
                           class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600">
                            📷 Scan Aset
                        </a>
                        <a href="{{ route('assets.index') }}"
                           class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200">
                            Lihat Semua Aset
                        </a>
                        @if (Auth::user()->role === 'super_admin')
                            <a href="{{ route('categories.index') }}"
                               class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200">
                                Kelola Kategori
                            </a>
                            <a href="{{ route('locations.index') }}"
                               class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200">
                                Kelola Lokasi
                            </a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- RECENT ASSETS --}}
                    <div class="bg-white rounded-xl shadow p-5">
                        <h3 class="font-semibold text-gray-700 mb-3">Aset Terbaru</h3>
                        @forelse ($recentAssets as $asset)
                            <div class="flex justify-between items-center border-b py-2 text-sm">
                                <div>
                                    <a href="{{ route('assets.show', $asset) }}" class="font-mono font-semibold text-brand-500">
                                        {{ $asset->asset_code }}
                                    </a>
                                    <p class="text-gray-600">{{ $asset->name }}</p>
                                </div>
                                <span class="text-xs text-gray-400">{{ $asset->created_at->diffForHumans() }}</span>
                            </div>
                        @empty
                            <p class="text-gray-400 text-sm">Belum ada aset.</p>
                        @endforelse
                    </div>

                    {{-- WARRANTY ALERT --}}
                    <div class="bg-white rounded-xl shadow p-5">
                        <h3 class="font-semibold text-gray-700 mb-3">⚠️ Garansi Segera Habis (30 hari)</h3>
                        @forelse ($warrantySoon as $asset)
                            <div class="flex justify-between items-center border-b py-2 text-sm">
                                <div>
                                    <a href="{{ route('assets.show', $asset) }}" class="font-mono font-semibold text-brand-500">
                                        {{ $asset->asset_code }}
                                    </a>
                                    <p class="text-gray-600">{{ $asset->name }}</p>
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

            @elseif (isset($myAssets))
                {{-- DASHBOARD USER BIASA --}}

                <div class="bg-white rounded-xl shadow p-5">
                    <h3 class="font-semibold text-gray-700 mb-3">Aset yang Anda Pegang</h3>
                    @forelse ($myAssets as $asset)
                        <div class="flex justify-between items-center border-b py-3 text-sm">
                            <div>
                                <p class="font-mono font-semibold text-gray-800">{{ $asset->asset_code }}</p>
                                <p class="text-gray-600">{{ $asset->name }} — {{ $asset->category->name }}</p>
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