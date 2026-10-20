<x-app-layout title="Laporan & Analitik">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Laporan &amp; Analitik</h2>
    </x-slot>

    @php
        $statusColors = [
            'available' => '#22c55e',
            'in_use' => '#3b82f6',
            'maintenance' => '#eab308',
            'broken' => '#ef4444',
            'retired' => '#9ca3af',
        ];
        $statusLabels = [
            'available' => 'Available',
            'in_use' => 'In Use',
            'maintenance' => 'Maintenance',
            'broken' => 'Broken',
            'retired' => 'Retired',
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @php
                $card = 'bg-white dark:bg-gray-800 rounded-xl shadow p-5 flex flex-col';
                $field = 'mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm';
                $label = 'block text-xs font-medium text-gray-500 dark:text-gray-400';
                $stats = [
                    ['Total Aset', number_format($totalAssets, 0, ',', '.'), 'unit tercatat', 'bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300',
                        'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'],
                    ['Jumlah Kategori', number_format($totalCategories, 0, ',', '.'), 'jenis aset', 'bg-purple-50 text-purple-600 dark:bg-purple-900/40 dark:text-purple-300',
                        'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z M6 6h.008v.008H6V6z'],
                    ['Jumlah Lokasi', number_format($totalLocations, 0, ',', '.'), 'kantor & outlet', 'bg-orange-50 text-orange-600 dark:bg-orange-900/40 dark:text-orange-300',
                        'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z'],
                ];
            @endphp

            {{-- Ringkasan --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                {{-- Nilai aset paling penting & angkanya paling panjang, jadi dapat 2 kolom di layar sedang. --}}
                <div class="{{ $card }} sm:col-span-2 xl:col-span-1">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Nilai Aset</p>
                        <span class="w-9 h-9 rounded-lg flex items-center justify-center bg-green-50 text-green-600 dark:bg-green-900/40 dark:text-green-300">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-2 font-bold text-gray-900 dark:text-white whitespace-nowrap tabular-nums">
                        <span class="text-base font-semibold text-gray-500 dark:text-gray-400">Rp</span>
                        <span class="text-2xl">{{ number_format($totalAssetValue ?? 0, 0, ',', '.') }}</span>
                    </p>
                    <p class="mt-auto pt-2 text-xs">
                        @if ($estimatedPriceCount)
                            <span class="inline-block px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300">
                                {{ $estimatedPriceCount }} aset harga estimasi
                            </span>
                        @else
                            <span class="text-gray-400">dari harga beli</span>
                        @endif
                    </p>
                </div>

                @foreach ($stats as [$title, $value, $hint, $chip, $icon])
                    <div class="{{ $card }}">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $title }}</p>
                            <span class="w-9 h-9 rounded-lg flex items-center justify-center {{ $chip }}">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white tabular-nums">{{ $value }}</p>
                        <p class="mt-auto pt-2 text-xs text-gray-400">{{ $hint }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Filter --}}
            <form method="GET" class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-4">Filter Laporan</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                    <div>
                        <label class="{{ $label }}">Filter Berdasarkan</label>
                        <select name="date_field" class="{{ $field }}">
                            <option value="created_at" @selected($filters['date_field'] === 'created_at')>Tanggal Dicatat</option>
                            <option value="purchase_date" @selected($filters['date_field'] === 'purchase_date')>Tanggal Pembelian</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="{{ $field }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="{{ $field }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Kategori</label>
                        <select name="category_id" class="{{ $field }}">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected($filters['category_id'] == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Lokasi</label>
                        <select name="location_id" class="{{ $field }}">
                            <option value="">Semua Lokasi</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}" @selected($filters['location_id'] == $loc->id)>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Status</label>
                        <select name="status" class="{{ $field }}">
                            <option value="">Semua Status</option>
                            @foreach ($statusLabels as $key => $statusLabel)
                                <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center gap-2">
                    <button type="submit"
                            class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600 transition-all duration-150 active:scale-95">
                        Terapkan Filter
                    </button>
                    @if (array_filter($filters))
                        <a href="{{ route('reports.index') }}"
                           class="bg-gray-100 dark:bg-gray-700 dark:text-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-600">
                            Reset
                        </a>
                    @endif

                    <a href="{{ route('reports.export-pdf', $filters) }}"
                       class="sm:ml-auto flex items-center gap-1.5 bg-gray-700 dark:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800 dark:hover:bg-gray-500 whitespace-nowrap transition-all duration-150 active:scale-95">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Export ke PDF
                    </a>
                </div>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="{{ $card }}">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Aset per Kategori</h3>
                    <div class="relative" style="height: 280px;">
                        <canvas id="chartCategory"></canvas>
                    </div>
                </div>

                <div class="{{ $card }}">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Aset per Lokasi</h3>
                    <div class="relative" style="height: 280px;">
                        <canvas id="chartLocation"></canvas>
                    </div>
                </div>

                <div class="{{ $card }}">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Aset per Status</h3>
                    <div class="relative" style="height: 280px;">
                        <canvas id="chartStatus"></canvas>
                    </div>
                </div>

                <div class="{{ $card }}">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Trend Pembelian Aset (12 Bulan Terakhir)</h3>
                    <div class="relative" style="height: 280px;">
                        <canvas id="chartTrend"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const categoryLabels = @json($assetsPerCategory->pluck('name'));
        const categoryData = @json($assetsPerCategory->pluck('assets_count'));

        const locationLabels = @json($assetsPerLocation->pluck('name'));
        const locationData = @json($assetsPerLocation->pluck('assets_count'));

        const statusLabels = @json($assetsPerStatus->map(fn($s) => $statusLabels[$s->status] ?? ucfirst($s->status)));
        const statusData = @json($assetsPerStatus->pluck('total'));
        const statusColors = @json($assetsPerStatus->map(fn($s) => $statusColors[$s->status] ?? '#9ca3af'));

        const trendLabels = @json($monthlyPurchases->pluck('label'));
        const trendData = @json($monthlyPurchases->pluck('total'));

        new Chart(document.getElementById('chartCategory'), {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryData,
                    backgroundColor: ['#6366f1', '#22c55e', '#eab308', '#ef4444', '#3b82f6', '#a855f7', '#f97316', '#14b8a6'],
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
            },
        });

        new Chart(document.getElementById('chartLocation'), {
            type: 'bar',
            data: {
                labels: locationLabels,
                datasets: [{
                    label: 'Jumlah Aset',
                    data: locationData,
                    backgroundColor: '#6366f1',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });

        new Chart(document.getElementById('chartStatus'), {
            type: 'bar',
            data: {
                labels: statusLabels,
                datasets: [{
                    label: 'Jumlah Aset',
                    data: statusData,
                    backgroundColor: statusColors,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });

        new Chart(document.getElementById('chartTrend'), {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Aset Dibeli',
                    data: trendData,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.3,
                    fill: true,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    </script>
</x-app-layout>
