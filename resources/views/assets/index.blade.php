<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daftar Aset IT</h2>
    </x-slot>

    @php
        $statusColumns = [
            'available' => ['label' => 'Available', 'text' => 'text-green-700', 'badge' => 'bg-green-100 text-green-700', 'header' => 'bg-green-50'],
            'in_use' => ['label' => 'In Use', 'text' => 'text-blue-700', 'badge' => 'bg-blue-100 text-blue-700', 'header' => 'bg-blue-50'],
            'maintenance' => ['label' => 'Maintenance', 'text' => 'text-yellow-700', 'badge' => 'bg-yellow-100 text-yellow-700', 'header' => 'bg-yellow-50'],
            'broken' => ['label' => 'Broken', 'text' => 'text-red-700', 'badge' => 'bg-red-100 text-red-700', 'header' => 'bg-red-50'],
            'retired' => ['label' => 'Retired', 'text' => 'text-gray-600', 'badge' => 'bg-gray-100 text-gray-600', 'header' => 'bg-gray-50'],
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6">

                <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                    <form method="GET" class="flex flex-wrap gap-2">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Cari kode / nama aset..."
                               class="border-gray-300 rounded-lg text-sm">

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

                        <button type="submit" class="bg-gray-200 px-3 py-2 rounded-lg text-sm">Filter</button>
                    </form>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('assets.export') }}"
                           class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 whitespace-nowrap">
                            Export Excel
                        </a>

                        <form action="{{ route('assets.import') }}" method="POST" enctype="multipart/form-data"
                              class="flex items-center gap-2">
                            @csrf
                            <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                                   class="border-gray-300 rounded-lg text-sm">
                            <button type="submit"
                                    class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 whitespace-nowrap">
                                Import
                            </button>
                        </form>
                    </div>
                </div>

                <form id="bulk-print-form" action="{{ route('barcode.print-batch') }}" method="POST" target="_blank">
                    @csrf

                    <div class="flex flex-wrap justify-end items-center gap-2 mb-4">
                        <button type="submit"
                                class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-purple-700 whitespace-nowrap">
                            🖨️ Cetak Label Terpilih
                        </button>

                        <a href="{{ route('assets.create') }}"
                           class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600 whitespace-nowrap">
                            + Tambah Aset
                        </a>
                    </div>

                    <div class="flex gap-4 overflow-x-auto pb-4">
                        @foreach ($statusColumns as $key => $meta)
                            @php $columnAssets = $assets->get($key, collect()); @endphp
                            <div class="board-column bg-gray-50 rounded-lg p-3 w-72 shrink-0" data-status="{{ $key }}">
                                <div class="flex items-center justify-between mb-3 px-1 {{ $meta['header'] }} rounded-lg py-2">
                                    <span class="font-semibold text-sm {{ $meta['text'] }}">{{ $meta['label'] }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $meta['badge'] }}">{{ $columnAssets->count() }}</span>
                                </div>

                                <div class="board-column-body space-y-3 min-h-[80px]">
                                    @forelse ($columnAssets as $asset)
                                        <div class="asset-card bg-white shadow rounded-lg p-3 cursor-move"
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
                                            <p class="font-mono font-bold text-sm text-gray-800">{{ $asset->asset_code }}</p>
                                            <p class="text-sm text-gray-700">{{ $asset->name }}</p>
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
                </form>
            </div>
        </div>
    </div>

    <script>
        function handleAssetCardClick(event, url) {
            if (event.target.closest('.asset-checkbox')) return;
            window.location = url;
        }
    </script>

    @vite(['resources/js/asset-board.js'])
</x-app-layout>
