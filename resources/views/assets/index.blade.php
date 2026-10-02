<x-app-layout title="Daftar Aset">
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

    <div class="py-8" x-data="assetsPage()">
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

                            <div class="relative" x-data="assetSearchSuggest(@js(route('assets.search-suggestions')), @js(request('search', '')))" @click.outside="open = false">
                                <input type="text" name="search" x-model="query" @input.debounce.300ms="fetchSuggestions()"
                                       @focus="if (results.length) open = true" @keydown.escape="open = false"
                                       autocomplete="off"
                                       placeholder="Cari kode / nama / serial number / pemegang..."
                                       class="w-72 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">

                                <div x-show="open && results.length" style="display: none;"
                                     class="absolute z-20 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-64 overflow-auto">
                                    <template x-for="item in results" :key="item.id">
                                        <button type="button" @click="select(item)"
                                                class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center gap-2">
                                            <span class="font-mono font-semibold text-gray-800 dark:text-white" x-text="item.asset_code"></span>
                                            <span class="text-gray-500 dark:text-gray-400 truncate" x-text="item.name"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <x-searchable-select name="category_id" placeholder="Semua Kategori"
                                :options="$categories->pluck('name', 'id')" :selected="request('category_id')" />

                            <x-searchable-select name="status" placeholder="Semua Status"
                                :options="collect($statusColumns)->map(fn ($m) => $m['label'])" :selected="request('status')" />

                            <button type="submit" class="bg-gray-200 px-3 py-2 rounded-lg text-sm transition-all duration-150 hover:scale-105 active:scale-95">Filter</button>
                        </form>

                        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                            <a href="{{ $viewUrl('board') }}"
                               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm whitespace-nowrap transition-all duration-150 {{ $view === 'board' ? 'bg-brand-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                </svg>
                                Board
                            </a>
                            <a href="{{ $viewUrl('table') }}"
                               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm whitespace-nowrap transition-all duration-150 {{ $view === 'table' ? 'bg-brand-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                                </svg>
                                Tabel
                            </a>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('assets.export') }}"
                           class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-600 whitespace-nowrap transition-all duration-150 hover:scale-105 active:scale-95">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            Export Excel
                        </a>

                        <form action="{{ route('assets.import') }}" method="POST" enctype="multipart/form-data"
                              class="flex items-center gap-2">
                            @csrf
                            <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                                   class="border-gray-300 rounded-lg text-sm">
                            <button type="submit"
                                    class="flex items-center gap-1.5 bg-gray-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 whitespace-nowrap transition-all duration-150 hover:scale-105 active:scale-95">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 00-2.25 2.25v9a2.25 2.25 0 002.25 2.25h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25H15m-6 3l3-3m0 0l3 3m-3-3V15" />
                                </svg>
                                Import
                            </button>
                        </form>
                    </div>
                </div>

                <form id="bulk-print-form" action="{{ route('barcode.print-batch') }}" method="POST" target="_blank">
                    @csrf

                    <div class="flex flex-wrap justify-end items-center gap-2 mb-4">
                        <button type="submit" id="bulk-print-btn" disabled title="Pilih minimal 1 aset dulu"
                                class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-600 whitespace-nowrap transition-all duration-150 hover:scale-105 active:scale-95 disabled:opacity-40 disabled:pointer-events-none disabled:hover:scale-100">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                            </svg>
                            Cetak Label Terpilih
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
                                <div class="board-column bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 w-72 shrink-0 transition-colors duration-200" data-status="{{ $key }}">
                                    <div class="flex items-center justify-between mb-3 px-1 {{ $meta['header'] }} rounded-lg py-2">
                                        <span class="font-semibold text-sm {{ $meta['text'] }}">{{ $meta['label'] }}</span>
                                        <span class="text-xs px-2 py-0.5 rounded-full {{ $meta['badge'] }}">{{ $columnAssets->count() }}</span>
                                    </div>

                                    <div class="board-column-body space-y-3 min-h-[80px]">
                                        @forelse ($columnAssets as $asset)
                                            <div class="asset-card relative bg-white dark:bg-gray-700 shadow rounded-lg p-3 cursor-move transition-shadow duration-200 hover:shadow-md"
                                                 data-asset-id="{{ $asset->id }}"
                                                 onclick="handleAssetCardClick(event, '{{ route('assets.show', $asset) }}')">
                                                <button type="button"
                                                        class="asset-delete-btn absolute top-2 right-2 p-1 rounded text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition"
                                                        onclick="event.stopPropagation(); confirmDeleteAsset('{{ route('assets.destroy', $asset) }}')"
                                                        title="Hapus aset">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                    </svg>
                                                </button>
                                                <div class="flex items-start justify-between gap-2 mb-1 pr-6">
                                                    <input type="checkbox" name="ids[]" value="{{ $asset->id }}"
                                                           class="asset-checkbox mt-0.5" onclick="event.stopPropagation()">
                                                    @if ($asset->photo)
                                                        <img src="{{ asset('uploads/assets/' . $asset->photo) }}"
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
                                            <img src="{{ asset('uploads/assets/' . $asset->photo) }}" class="w-12 h-12 object-cover rounded border shrink-0">
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
                                        <th class="px-4 py-2 text-right sticky right-0 z-10 bg-gray-50 dark:bg-gray-700 border-l border-gray-200 dark:border-gray-600">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($assets as $asset)
                                        <tr data-asset-row="{{ $asset->id }}"
                                            data-asset="{{ json_encode([
                                                'code' => $asset->asset_code,
                                                'name' => $asset->name,
                                                'category' => $asset->category->name ?? '-',
                                                'location' => $asset->location->name ?? '-',
                                                'statusLabel' => $statusColumns[$asset->status]['label'] ?? ucfirst($asset->status),
                                                'statusBadge' => $statusColumns[$asset->status]['badge'] ?? 'bg-gray-100 text-gray-600',
                                                'brandModel' => trim(($asset->brand ?? '').' '.($asset->model ?? '')) ?: '-',
                                                'serial' => $asset->serial_number ?? '-',
                                                'assignedTo' => $asset->assignedUser->name ?? '-',
                                                'warranty' => optional($asset->warranty_expired_at)->format('d M Y') ?? '-',
                                                'photo' => $asset->photo ? asset('uploads/assets/'.$asset->photo) : null,
                                                'showUrl' => route('assets.show', $asset),
                                                'editUrl' => route('assets.edit', $asset),
                                            ]) }}"
                                            @click="if (!$event.target.closest('a, button, .asset-checkbox, .status-select')) { openQuickView(JSON.parse($el.dataset.asset)) }"
                                            class="cursor-pointer border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-300">
                                            <td class="px-4 py-2">
                                                <input type="checkbox" name="ids[]" value="{{ $asset->id }}" class="asset-checkbox">
                                            </td>
                                            <td class="px-4 py-2 hidden md:table-cell">
                                                @if ($asset->photo)
                                                    <img src="{{ asset('uploads/assets/' . $asset->photo) }}" class="w-10 h-10 object-cover rounded border">
                                                @else
                                                    <div class="w-10 h-10 rounded border bg-gray-50 dark:bg-gray-600"></div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 font-mono font-semibold whitespace-nowrap">
                                                <span class="text-brand-500">
                                                    {{ $asset->asset_code }}
                                                </span>
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
                                            <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap sticky right-0 z-10 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700">
                                                <a href="{{ route('assets.edit', $asset) }}" class="text-brand-500 hover:underline">Edit</a>
                                                <a href="{{ route('barcode.print', $asset) }}" target="_blank" class="text-gray-600 dark:text-gray-300 hover:underline">Label</a>
                                                <button type="button" class="text-red-600 hover:underline"
                                                        onclick="confirmDeleteAsset('{{ route('assets.destroy', $asset) }}')">
                                                    Hapus
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

        <!-- Quick View Drawer -->
        <div x-show="quickView" style="display: none;" class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-black/30" @click="closeQuickView()"></div>

            <div x-show="quickView"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="absolute right-0 top-0 h-full w-full sm:w-96 bg-white dark:bg-gray-800 shadow-xl flex flex-col">
                <template x-if="quickView">
                    <div class="flex flex-col h-full">
                        <!-- Header -->
                        <div class="bg-gradient-to-br from-indigo-950 via-purple-900 to-violet-800 px-5 py-4 flex items-center justify-between shrink-0">
                            <p class="font-mono font-bold text-white text-lg truncate" x-text="quickView.code"></p>
                            <button type="button" @click="closeQuickView()" class="text-white/80 hover:text-white transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body: mode lihat -->
                        <div x-show="!editing" class="flex-1 overflow-y-auto p-5 space-y-4">
                            <template x-if="quickView.photo">
                                <img :src="quickView.photo" class="w-full h-40 object-cover rounded-lg border dark:border-gray-700">
                            </template>

                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Nama</p>
                                <p class="font-medium text-gray-900 dark:text-white" x-text="quickView.name"></p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Status</p>
                                <span class="inline-block mt-0.5 px-2 py-1 rounded-full text-xs font-medium" :class="quickView.statusBadge" x-text="quickView.statusLabel"></span>
                            </div>

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Kategori</p>
                                    <p class="font-medium text-gray-900 dark:text-white" x-text="quickView.category"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Lokasi</p>
                                    <p class="font-medium text-gray-900 dark:text-white" x-text="quickView.location"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Merk / Model</p>
                                    <p class="font-medium text-gray-900 dark:text-white" x-text="quickView.brandModel"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Serial Number</p>
                                    <p class="font-medium text-gray-900 dark:text-white" x-text="quickView.serial"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Dipegang Oleh</p>
                                    <p class="font-medium text-gray-900 dark:text-white" x-text="quickView.assignedTo"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Garansi Sampai</p>
                                    <p class="font-medium text-gray-900 dark:text-white" x-text="quickView.warranty"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Body: mode edit (form disuntik via fetch, tanpa pindah halaman) -->
                        <div x-show="editing" class="flex-1 overflow-y-auto p-5">
                            <template x-if="editLoading">
                                <p class="text-sm text-gray-400">Memuat form edit...</p>
                            </template>
                            <template x-if="editError">
                                <div class="mb-3 p-2 bg-red-50 text-red-600 text-xs rounded" x-text="editError"></div>
                            </template>
                            <div id="quickview-edit-container" class="quickview-edit-form"></div>
                        </div>

                        <!-- Footer: mode lihat -->
                        <div x-show="!editing" class="border-t border-gray-100 dark:border-gray-700 p-4 flex gap-2 shrink-0">
                            <a :href="quickView.showUrl" class="flex-1 text-center bg-gray-100 dark:bg-gray-700 dark:text-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition-all duration-150 hover:scale-105 active:scale-95">
                                Lihat Detail Lengkap
                            </a>
                            <button type="button" @click="startEdit()" class="flex-1 text-center bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg text-sm transition-all duration-150 hover:scale-105 active:scale-95">
                                Edit
                            </button>
                        </div>

                        <!-- Footer: mode edit -->
                        <div x-show="editing" class="border-t border-gray-100 dark:border-gray-700 p-4 flex gap-2 shrink-0">
                            <button type="button" @click="cancelEdit()" class="flex-1 text-center bg-gray-100 dark:bg-gray-700 dark:text-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition-all duration-150 hover:scale-105 active:scale-95">
                                Batal
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <form id="delete-asset-form" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <!-- Dipakai tombol hapus foto di form edit yang disuntik ke Quick View drawer. -->
    <form id="delete-photo-form" action="" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <script>
        function confirmDeletePhoto(url) {
            confirmAction('Foto ini akan dihapus permanen.', function () {
                const form = document.getElementById('delete-photo-form');
                form.action = url;
                form.submit();
            }, 'Hapus foto ini?');
        }

        function assetsPage() {
            return {
                quickView: null,
                editing: false,
                editLoading: false,
                editError: null,

                openQuickView(data) {
                    this.quickView = data;
                    this.editing = false;
                    this.editError = null;
                },

                closeQuickView() {
                    this.quickView = null;
                    this.editing = false;
                },

                startEdit() {
                    this.editing = true;
                    this.editLoading = true;
                    this.editError = null;

                    const container = document.getElementById('quickview-edit-container');
                    container.innerHTML = '';

                    fetch(this.quickView.editUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then((res) => res.text())
                        .then((html) => {
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            // Bukan sekadar "form pertama di halaman" - sidebar navigasi juga
                            // punya <form> sendiri (logout) yang muncul lebih dulu di DOM.
                            const form = doc.querySelector('form[enctype="multipart/form-data"]');
                            if (!form) throw new Error('Form edit tidak ditemukan di halaman.');

                            // Pakai createContextualFragment (bukan innerHTML) supaya <script> di
                            // dalam form (tombol "+ Tambah Kategori/Lokasi/User Baru") ikut jalan.
                            const range = document.createRange();
                            range.selectNode(container);
                            container.appendChild(range.createContextualFragment(form.outerHTML));

                            if (window.Alpine) {
                                window.Alpine.initTree(container);
                            }

                            const injectedForm = container.querySelector('form');
                            injectedForm.addEventListener('submit', (event) => this.submitEdit(event, injectedForm));

                            // Link "Batal" bawaan form (navigasi ke halaman daftar aset) dilepas -
                            // drawer sudah punya tombol Batal sendiri yang cukup tutup mode edit.
                            injectedForm.querySelector('#edit-form-cancel-link')?.remove();

                            if (window.setupPhotoCompression) {
                                window.setupPhotoCompression(
                                    '#quickview-edit-container input[name="photos[]"]',
                                    '#quickview-edit-container #photo-preview-list'
                                );
                            }
                        })
                        .catch(() => {
                            this.editError = 'Gagal memuat form edit. Coba lagi.';
                        })
                        .finally(() => {
                            this.editLoading = false;
                        });
                },

                cancelEdit() {
                    this.editing = false;
                    this.editError = null;
                },

                submitEdit(event, form) {
                    event.preventDefault();
                    this.editError = null;

                    const btn = form.querySelector('button[type="submit"]');
                    const originalHtml = btn ? btn.innerHTML : '';
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = 'Menyimpan...';
                    }

                    fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'Accept': 'application/json' },
                    })
                        .then(async (res) => {
                            if (res.status === 422) {
                                const data = await res.json();
                                this.editError = data.errors ? Object.values(data.errors)[0][0] : 'Data belum valid.';
                                return;
                            }
                            if (!res.ok) {
                                throw new Error('Gagal menyimpan perubahan.');
                            }
                            window.location.reload();
                        })
                        .catch((err) => {
                            this.editError = err.message || 'Gagal menyimpan perubahan.';
                        })
                        .finally(() => {
                            if (btn && this.editing) {
                                btn.disabled = false;
                                btn.innerHTML = originalHtml;
                            }
                        });
                },
            };
        }

        function assetSearchSuggest(suggestUrl, initialQuery) {
            return {
                query: initialQuery || '',
                results: [],
                open: false,
                fetchSuggestions() {
                    if (!this.query || this.query.trim().length < 2) {
                        this.results = [];
                        this.open = false;
                        return;
                    }
                    fetch(suggestUrl + '?q=' + encodeURIComponent(this.query))
                        .then((res) => res.json())
                        .then((data) => {
                            this.results = data;
                            this.open = data.length > 0;
                        });
                },
                select(item) {
                    this.query = item.asset_code;
                    this.open = false;
                    this.$el.closest('form').submit();
                },
            };
        }

        function handleAssetCardClick(event, url) {
            if (event.target.closest('.asset-checkbox, .asset-delete-btn')) return;
            window.location = url;
        }

        function toggleAllAssetCheckboxes(source) {
            document.querySelectorAll('.asset-checkbox').forEach(function (checkbox) {
                checkbox.checked = source.checked;
            });
            updateBulkPrintButtonState();
        }

        function updateBulkPrintButtonState() {
            var btn = document.getElementById('bulk-print-btn');
            if (!btn) return;
            var checkedCount = document.querySelectorAll('.asset-checkbox:checked').length;
            btn.disabled = checkedCount === 0;
            btn.title = checkedCount === 0 ? 'Pilih minimal 1 aset dulu' : '';
        }

        document.addEventListener('change', function (event) {
            if (event.target.classList.contains('asset-checkbox')) {
                updateBulkPrintButtonState();
            }
        });

        function confirmDeleteAsset(url) {
            confirmAction('Aset yang sudah dihapus tidak bisa dikembalikan.', function () {
                var form = document.getElementById('delete-asset-form');
                form.action = url;
                form.submit();
            }, 'Hapus aset ini?');
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
    @vite(['resources/js/photo-compress.js'])
</x-app-layout>
