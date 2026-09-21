<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Laporan &amp; Analitik</h2>
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

            <div class="flex flex-wrap justify-between items-center gap-3">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 flex-1">
                    <div class="bg-white rounded-xl shadow p-5">
                        <p class="text-sm text-gray-500">Total Aset</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalAssets }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow p-5">
                        <p class="text-sm text-gray-500">Total Nilai Aset</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">
                            Rp {{ number_format($totalAssetValue ?? 0, 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="bg-white rounded-xl shadow p-5">
                        <p class="text-sm text-gray-500">Jumlah Kategori</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalCategories }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow p-5">
                        <p class="text-sm text-gray-500">Jumlah Lokasi</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalLocations }}</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('reports.export-pdf') }}"
                   class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">
                    📄 Export ke PDF
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow p-5">
                    <h3 class="font-semibold text-gray-700 mb-3">Aset per Kategori</h3>
                    <div class="relative" style="height: 280px;">
                        <canvas id="chartCategory"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-5">
                    <h3 class="font-semibold text-gray-700 mb-3">Aset per Lokasi</h3>
                    <div class="relative" style="height: 280px;">
                        <canvas id="chartLocation"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-5">
                    <h3 class="font-semibold text-gray-700 mb-3">Aset per Status</h3>
                    <div class="relative" style="height: 280px;">
                        <canvas id="chartStatus"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-5">
                    <h3 class="font-semibold text-gray-700 mb-3">Trend Pembelian Aset (12 Bulan Terakhir)</h3>
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
