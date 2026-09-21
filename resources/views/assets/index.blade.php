<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Daftar Aset IT</h2>
    </x-slot>

    @php
        $statusColumns = [
            'available' => ['label' => 'Available', 'text' => 'text-green-700', 'badge' => 'bg-green-100 text-green-700', 'header' => 'bg-green-50'],
            'in_use' => ['label' => 'In Use', 'text' => 'text-blue-700', 'badge' => 'bg-blue-100 text-blue-700', 'header' => 'bg-blue-50'],
            'maintenance' => ['label' => 'Maintenance', 'text' => 'text-yellow-700', 'badge' => 'bg-yellow-100 text-yellow-700', 'header' => 'bg-yellow-50'],
            'broken' => ['label' => 'Broken', 'text' => 'text-red-700', 'badge' => 'bg-red-100 text-red-700', 'header' => 'bg-red-50'],
            'retired' => ['label' => 'Retired', 'text' => 'text-gray-600', 'badge' => 'bg-gray-100 text-gray-600', 'header' => 'bg-gray-50'],
        ];

        $currentSort = request('sort', 'created_at');
        $currentDirection = request('direction', 'desc');
        $nextDirection = fn ($col) => ($currentSort === $col && $currentDirection === 'asc') ? 'desc' : 'asc';
        $sortArrow = fn ($col) => $currentSort === $col ? ($currentDirection === 'asc' ? '↑' : '↓') : '';
        $sortUrl = fn ($col) => route('assets.index', array_merge(
            request()->except(['page']),
            ['view' => 'table', 'sort' => $col, 'direction' => $nextDirection($col)]
        ));
        $viewUrl = fn ($targetView) => route('assets.index', array_merge(
            request()->except(['view', 'page', 'sort', 'direction']),
            ['view' => $targetView]
        ));
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">

                <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <form method="GET" class="flex flex-wrap gap-2">
                            <input type="hidden" name="view" value="{{ $view }}">
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="Cari kode / nama aset..."
                                   class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">

                            <select name="category_id" class="border-gray-300 rounded-lg text-sm">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>

                            <select name="status" class="border-gray-300 rounded-lg text-sm">
                                <option value="">Semua Status</option>
                                @foreach ($statusColumns as $key => $meta)
                                    <option value="{{ $key }}" @selected(request('status') == $key)>
                                        {{ $meta['label'] }}
                                    </option>
                                @endforeach
                            </select>

                            <button type="submit" class="bg-gray-200 px-3 py-2 rounded-lg text-sm transition-all duration-150 hover:scale-105 active:scale-95">Filter</button>
                        </form>

                        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                            <a href="{{ $viewUrl('board') }}"
                               class="px-3 py-1.5 rounded-lg text-sm whitespace-nowrap transition-all duration-150 {{ $view === 'board' ? 'bg-brand-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                📋 Board
                            </a>
                            <a href="{{ $viewUrl('table') }}"
                               class="px-3 py-1.5 rounded-lg text-sm whitespace-nowrap transition-all duration-150 {{ $view === 'table' ? 'bg-brand-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                📊 Tabel
                            </a>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('assets.export') }}"
                           class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 whitespace-nowrap transition-all duration-150 hover:scale-105 active:scale-95">
                            Export Excel
                        </a>

                        <form action="{{ route('assets.import') }}" method="POST" enctype="multipart/form-data"
                              class="flex items-center gap-2">
                            @csrf
                            <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                                   class="border-gray-300 rounded-lg text-sm">
                            <button type="submit"
                                    class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 whitespace-nowrap transition-all duration-150 hover:scale-105 active:scale-95">
                                Import
                            </button>
                        </form>
                    </div>
                </div>

                <form id="bulk-print-form" action="{{ route('barcode.print-batch') }}" method="POST" target="_blank">
                    @csrf

                    <div class="flex flex-wrap justify-end items-center gap-2 mb-4">
                        <button type="submit"
                                class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-purple-700 whitespace-nowrap transition-all duration-150 hover:scale-105 active:scale-95">
                            🖨️ Cetak Label Terpilih
                        </button>

                        <a href="{{ route('assets.create') }}"
                           class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600 whitespace-nowrap transition-all duration-150 hover:scale-105 active:scale-95">
                            + Tambah Aset
                        </a>
                    </div>

                    @if ($view === 'board')
                        <div class="flex gap-4 overflow-x-auto scroll-smooth pb-4">
                            @foreach ($statusColumns as $key => $meta)
                                @php $columnAssets = $assets->get($key, collect()); @endphp
                                <div class="board-column bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 w-72 shrink-0" data-status="{{ $key }}">
                                    <div class="flex items-center justify-between mb-3 px-1 {{ $meta['header'] }} rounded-lg py-2">
                                        <span class="font-semibold text-sm {{ $meta['text'] }}">{{ $meta['label'] }}</span>
                                        <span class="text-xs px-2 py-0.5 rounded-full {{ $meta['badge'] }}">{{ $columnAssets->count() }}</span>
                                    </div>

                                    <div class="board-column-body space-y-3 min-h-[80px]">
                                        @forelse ($columnAssets as $asset)
                                            <div class="asset-card bg-white dark:bg-gray-700 shadow rounded-lg p-3 cursor-move transition-shadow duration-200 hover:shadow-md"
                                                 data-asset-id="{{ $asset->id }}"
                                                 onclick="handleAssetCardClick(event, '{{ route('assets.show', $asset) }}')">
                                                <div class="flex items-start justify-between gap-2 mb-1">
                                                    <input type="checkbox" name="ids[]" value="{{ $asset->id }}"
                                                           class="asset-checkbox mt-0.5" onclick="event.stopPropagation()">
                                                    @if ($asset->photo)
                                                        <img src="{{ Storage::url($asset->photo) }}"
                                                             class="w-10 h-10 object-cover rounded border">
                                                    @endif
                                                </div>
                                                <p class="font-mono font-bold text-sm text-gray-800 dark:text-white">{{ $asset->asset_code }}</p>
                                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $asset->name }}</p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    {{ $asset->category->name ?? '-' }} · {{ $asset->location->name ?? '-' }}
                                                </p>
                                                @if ($asset->assignedUser)
                                                    <p class="text-xs text-gray-400 mt-1">{{ $asset->assignedUser->name }}</p>
                                                @endif
                                            </div>
                                        @empty
                                            <p class="text-xs text-gray-400 text-center py-4">Tidak ada aset.</p>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- Mobile card-list (< sm) --}}
                        <div class="sm:hidden space-y-3">
                            @forelse ($assets as $asset)
                                <div data-asset-row="{{ $asset->id }}"
                                     class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 bg-white dark:bg-gray-800 transition-colors duration-300">
                                    <div class="flex items-center justify-between gap-2">
                                        <div>
                                            <p class="font-mono font-bold text-sm text-gray-800 dark:text-white">{{ $asset->asset_code }}</p>
                                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $asset->name }}</p>
                                        </div>
                                        @if ($asset->photo)
                                            <img src="{{ Storage::url($asset->photo) }}" class="w-12 h-12 object-cover rounded border shrink-0">
                                        @endif
                                    </div>

                                    <select class="status-select w-full mt-3 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm py-2"
                                            data-asset-id="{{ $asset->id }}" onchange="handleStatusChange(this)">
                                        @foreach ($statusColumns as $key => $meta)
                                            <option value="{{ $key }}" @selected($asset->status === $key)>{{ $meta['label'] }}</option>
                                        @endforeach
                                    </select>

                                    <div class="flex gap-2 mt-3">
                                        <a href="{{ route('assets.show', $asset) }}"
                                           class="flex-1 text-center bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 py-2 rounded-lg text-sm transition-all duration-150 active:scale-95">
                                            Detail
                                        </a>
                                        <a href="{{ route('assets.edit', $asset) }}"
                                           class="flex-1 text-center bg-brand-500 text-white py-2 rounded-lg text-sm transition-all duration-150 active:scale-95">
                                            Edit
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <p class="text-center text-gray-400 py-6">Belum ada aset. Klik "+ Tambah Aset" untuk mulai.</p>
                            @endforelse
                        </div>

                        {{-- Desktop table (>= sm) --}}
                        <div class="hidden sm:block overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-2">
                                            <input type="checkbox" id="select-all-assets" onclick="toggleAllAssetCheckboxes(this)">
                                        </th>
                                        <th class="px-4 py-2 hidden md:table-cell">Foto</th>
                                        <th class="px-4 py-2">
                                            <a href="{{ $sortUrl('asset_code') }}" class="hover:underline">Kode Aset {{ $sortArrow('asset_code') }}</a>
                                        </th>
                                        <th class="px-4 py-2">
                                            <a href="{{ $sortUrl('name') }}" class="hover:underline">Nama {{ $sortArrow('name') }}</a>
                                        </th>
                                        <th class="px-4 py-2 hidden md:table-cell">
                                            <a href="{{ $sortUrl('category') }}" class="hover:underline">Kategori {{ $sortArrow('category') }}</a>
                                        </th>
                                        <th class="px-4 py-2 hidden lg:table-cell">
                                            <a href="{{ $sortUrl('location') }}" class="hover:underline">Lokasi {{ $sortArrow('location') }}</a>
                                        </th>
                                        <th class="px-4 py-2 hidden lg:table-cell">Dipegang Oleh</th>
                                        <th class="px-4 py-2">
                                            <a href="{{ $sortUrl('status') }}" class="hover:underline">Status {{ $sortArrow('status') }}</a>
                                        </th>
                                        <th class="px-4 py-2 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($assets as $asset)
                                        <tr data-asset-row="{{ $asset->id }}" class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-300">
                                            <td class="px-4 py-2">
                                                <input type="checkbox" name="ids[]" value="{{ $asset->id }}" class="asset-checkbox">
                                            </td>
                                            <td class="px-4 py-2 hidden md:table-cell">
                                                @if ($asset->photo)
                                                    <img src="{{ Storage::url($asset->photo) }}" class="w-10 h-10 object-cover rounded border">
                                                @else
                                                    <div class="w-10 h-10 rounded border bg-gray-50 dark:bg-gray-600"></div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 font-mono font-semibold whitespace-nowrap">
                                                <a href="{{ route('assets.show', $asset) }}" class="text-brand-500 hover:underline">
                                                    {{ $asset->asset_code }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $asset->name }}</td>
                                            <td class="px-4 py-2 text-gray-900 dark:text-white hidden md:table-cell">{{ $asset->category->name ?? '-' }}</td>
                                            <td class="px-4 py-2 text-gray-900 dark:text-white hidden lg:table-cell">{{ $asset->location->name ?? '-' }}</td>
                                            <td class="px-4 py-2 text-gray-900 dark:text-white hidden lg:table-cell">{{ $asset->assignedUser->name ?? '-' }}</td>
                                            <td class="px-4 py-2">
                                                <select class="status-select border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-xs"
                                                        data-asset-id="{{ $asset->id }}" onchange="handleStatusChange(this)">
                                                    @foreach ($statusColumns as $key => $meta)
                                                        <option value="{{ $key }}" @selected($asset->status === $key)>{{ $meta['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                                                <a href="{{ route('assets.edit', $asset) }}" class="text-brand-500 hover:underline">✏️ Edit</a>
                                                <a href="{{ route('barcode.print', $asset) }}" target="_blank" class="text-gray-600 dark:text-gray-300 hover:underline">🏷️ Label</a>
                                                <button type="button" class="text-red-600 hover:underline"
                                                        onclick="confirmDeleteAsset('{{ route('assets.destroy', $asset) }}')">
                                                    🗑️ Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-4 py-6 text-center text-gray-400">
                                                Belum ada aset. Klik "+ Tambah Aset" untuk mulai.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $assets->links() }}
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <form id="delete-asset-form" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <script>
        function handleAssetCardClick(event, url) {
            if (event.target.closest('.asset-checkbox')) return;
            window.location = url;
        }

        function toggleAllAssetCheckboxes(source) {
            document.querySelectorAll('.asset-checkbox').forEach(function (checkbox) {
                checkbox.checked = source.checked;
            });
        }

        function confirmDeleteAsset(url) {
            if (confirm('Yakin hapus aset ini?')) {
                var form = document.getElementById('delete-asset-form');
                form.action = url;
                form.submit();
            }
        }

        function handleStatusChange(selectEl) {
            const assetId = selectEl.dataset.assetId;
            const newStatus = selectEl.value;
            const rows = document.querySelectorAll('[data-asset-row="' + assetId + '"]');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            fetch(`/assets/${assetId}/update-status`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status: newStatus }),
            }).then((response) => {
                if (! response.ok) {
                    throw new Error('Gagal memperbarui status aset.');
                }
                rows.forEach((row) => {
                    row.classList.add('bg-green-100', 'dark:bg-green-900/40');
                    setTimeout(() => row.classList.remove('bg-green-100', 'dark:bg-green-900/40'), 1000);
                });
            }).catch(() => {
                alert('Gagal memperbarui status aset. Halaman akan dimuat ulang.');
                window.location.reload();
            });
        }
    </script>

    @if ($view === 'board')
        @vite(['resources/js/asset-board.js'])
    @endif
</x-app-layout>
