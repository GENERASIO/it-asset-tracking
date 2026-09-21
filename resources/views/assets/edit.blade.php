<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Aset</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6 transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                <form action="{{ route('assets.update', $asset) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="sm:col-span-2 mb-2">
                            <span class="text-xs text-gray-500">Kode Aset</span>
                            <p class="font-mono font-bold text-lg text-gray-800">{{ $asset->asset_code }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Nama Aset</label>
                            <input type="text" name="name" value="{{ old('name', $asset->name) }}"
                                   placeholder="Contoh: Laptop Dell Latitude 5420"
                                   class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                            @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Foto Aset</label>
                            @if ($asset->photo)
                                <img src="{{ Storage::url($asset->photo) }}" class="w-24 h-24 object-cover rounded-lg mb-2 mt-1">
                            @endif
                            <input type="file" name="photo" accept="image/*"
                                   class="mt-1 w-full border-gray-300 rounded-lg shadow-sm text-sm">
                            <p class="text-xs text-gray-400 mt-1">Kosongkan kalau tidak ingin ganti foto.</p>
                            @error('photo') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kategori</label>
                            <select name="category_id" class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(old('category_id', $asset->category_id) == $cat->id)>
                                        {{ $cat->name }} ({{ $cat->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Lokasi</label>
                            <select name="location_id" class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                                <option value="">-- Pilih Lokasi --</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}" @selected(old('location_id', $asset->location_id) == $loc->id)>
                                        {{ $loc->name }} ({{ $loc->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('location_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Merk</label>
                            <input type="text" name="brand" value="{{ old('brand', $asset->brand) }}"
                                   class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Model</label>
                            <input type="text" name="model" value="{{ old('model', $asset->model) }}"
                                   class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Serial Number</label>
                            <input type="text" name="serial_number" value="{{ old('serial_number', $asset->serial_number) }}"
                                   class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Spesifikasi</label>
                            <textarea name="specification" rows="3"
                                      placeholder="Contoh: Intel i5, RAM 8GB, SSD 256GB"
                                      class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">{{ old('specification', $asset->specification) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Dipegang Oleh (opsional)</label>
                            <select name="assigned_to" class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                                <option value="">-- Tidak Ada --</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('assigned_to', $asset->assigned_to) == $user->id)>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                                @foreach (['available','in_use','maintenance','broken','retired'] as $s)
                                    <option value="{{ $s }}" @selected(old('status', $asset->status) == $s)>
                                        {{ ucfirst(str_replace('_',' ',$s)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal Pembelian</label>
                            <input type="date" name="purchase_date"
                                   value="{{ old('purchase_date', optional($asset->purchase_date)->format('Y-m-d')) }}"
                                   class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Harga Beli (Rp)</label>
                            <input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', $asset->purchase_price) }}"
                                   class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Garansi Sampai</label>
                            <input type="date" name="warranty_expired_at"
                                   value="{{ old('warranty_expired_at', optional($asset->warranty_expired_at)->format('Y-m-d')) }}"
                                   class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2 pt-2">
                        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg transition-all duration-150 hover:scale-105 active:scale-95">
                            Perbarui Aset
                        </button>
                        <a href="{{ route('assets.index') }}" class="px-4 py-2 text-center text-gray-600">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="inline-block animate-spin mr-2">⏳</span> Memproses...';
                }
            });
        });
    </script>
</x-app-layout>