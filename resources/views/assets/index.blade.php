<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daftar Aset IT</h2>
    </x-slot>

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
                            @foreach (['available','in_use','maintenance','broken','retired'] as $s)
                                <option value="{{ $s }}" @selected(request('status') == $s)>
                                    {{ ucfirst(str_replace('_',' ',$s)) }}
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
                           class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 whitespace-nowrap">
                            + Tambah Aset
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-600">
                                <tr>
                                    <th class="px-4 py-2">
                                        <input type="checkbox" id="select-all-assets" onclick="toggleAllAssetCheckboxes(this)">
                                    </th>
                                    <th class="px-4 py-2">Kode Aset</th>
                                    <th class="px-4 py-2">Nama</th>
                                    <th class="px-4 py-2">Kategori</th>
                                    <th class="px-4 py-2">Lokasi</th>
                                    <th class="px-4 py-2">Dipegang Oleh</th>
                                    <th class="px-4 py-2">Status</th>
                                    <th class="px-4 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($assets as $asset)
                                    @php
                                        $statusColor = match($asset->status) {
                                            'available' => 'bg-green-100 text-green-700',
                                            'in_use' => 'bg-blue-100 text-blue-700',
                                            'maintenance' => 'bg-yellow-100 text-yellow-700',
                                            'broken' => 'bg-red-100 text-red-700',
                                            'retired' => 'bg-gray-100 text-gray-500',
                                            default => 'bg-gray-100 text-gray-500',
                                        };
                                    @endphp
                                    <tr class="border-b">
                                        <td class="px-4 py-2">
                                            <input type="checkbox" name="ids[]" value="{{ $asset->id }}" class="asset-checkbox">
                                        </td>
                                        <td class="px-4 py-2 font-mono font-semibold">
                                            <a href="{{ route('assets.show', $asset) }}" class="text-indigo-600 hover:underline">
                                                {{ $asset->asset_code }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-2">{{ $asset->name }}</td>
                                        <td class="px-4 py-2">{{ $asset->category->name }}</td>
                                        <td class="px-4 py-2">{{ $asset->location->name }}</td>
                                        <td class="px-4 py-2">{{ $asset->assignedUser->name ?? '-' }}</td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColor }}">
                                                {{ ucfirst(str_replace('_',' ',$asset->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-right space-x-2 whitespace-nowrap">
                                            <a href="{{ route('barcode.print', $asset) }}" target="_blank"
                                               class="text-gray-600 hover:underline">Label</a>
                                            <a href="{{ route('assets.edit', $asset) }}"
                                               class="text-indigo-600 hover:underline">Edit</a>
                                            <button type="button" class="text-red-600 hover:underline"
                                                    onclick="confirmDeleteAsset('{{ route('assets.destroy', $asset) }}')">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-6 text-center text-gray-400">
                                            Belum ada aset. Klik "+ Tambah Aset" untuk mulai.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>

                <div class="mt-4">
                    {{ $assets->links() }}
                </div>
            </div>
        </div>
    </div>

    <form id="delete-asset-form" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <script>
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
    </script>
</x-app-layout>