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
                            <label class="block text-sm font-medium text-gray-700">Foto Aset ({{ $asset->photos->count() }}/5)</label>

                            @if ($asset->photos->isNotEmpty())
                                <div class="flex flex-wrap gap-2 mt-1 mb-2">
                                    @foreach ($asset->photos as $photo)
                                        <div class="relative group">
                                            <img src="{{ $photo->url }}" class="w-20 h-20 object-cover rounded-lg border">
                                            <form action="{{ route('assets.photos.destroy', [$asset, $photo]) }}" method="POST"
                                                  onsubmit="return confirm('Hapus foto ini?');"
                                                  class="absolute -top-1.5 -right-1.5">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="w-5 h-5 rounded-full bg-red-600 text-white text-xs flex items-center justify-center leading-none">
                                                    ×
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if ($asset->photos->count() < 5)
                                <input type="file" name="photos[]" accept="image/*" capture="environment" multiple
                                       class="mt-1 w-full border-gray-300 rounded-lg shadow-sm text-sm">
                                <div id="photo-preview-list" class="flex flex-wrap gap-2 mt-2"></div>
                                <p class="text-xs text-gray-400 mt-1">Tambah foto baru (sisa slot: {{ 5 - $asset->photos->count() }}).</p>
                            @else
                                <p class="text-xs text-gray-400 mt-1">Sudah mencapai maksimal 5 foto. Hapus salah satu dulu untuk menambah.</p>
                            @endif
                            @error('photos') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                            @error('photos.*') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kategori</label>
                            <select id="category_id" name="category_id" class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(old('category_id', $asset->category_id) == $cat->id)>
                                        {{ $cat->name }} ({{ $cat->code }})
                                    </option>
                                @endforeach
                            </select>
                            @if (auth()->user()->role === 'super_admin')
                                <x-quick-add-category-modal target-select-id="category_id" />
                            @endif
                            @error('category_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Lokasi</label>
                            <select id="location_id" name="location_id" class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                                <option value="">-- Pilih Lokasi --</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}" @selected(old('location_id', $asset->location_id) == $loc->id)>
                                        {{ $loc->name }} ({{ $loc->code }})
                                    </option>
                                @endforeach
                            </select>
                            @if (auth()->user()->role === 'super_admin')
                                <x-quick-add-location-modal target-select-id="location_id" />
                            @endif
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
                            <select id="assigned_to" name="assigned_to" class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                                <option value="">-- Tidak Ada --</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('assigned_to', $asset->assigned_to) == $user->id)>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if (auth()->user()->role === 'super_admin')
                                <x-quick-add-user-modal target-select-id="assigned_to" :locations="$locations" />
                            @endif
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

        function compressImage(file) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = new Image();
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        const maxDimension = 1600;
                        let width = img.width;
                        let height = img.height;

                        if (width > height && width > maxDimension) {
                            height = Math.round(height * (maxDimension / width));
                            width = maxDimension;
                        } else if (height > maxDimension) {
                            width = Math.round(width * (maxDimension / height));
                            height = maxDimension;
                        }

                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        canvas.toBlob(function(blob) {
                            const compressedFile = new File([blob], file.name, {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            console.log('Ukuran asli:', (file.size / 1024).toFixed(0) + 'KB', '→ setelah kompresi:', (compressedFile.size / 1024).toFixed(0) + 'KB');
                            resolve(compressedFile);
                        }, 'image/jpeg', 0.75);
                    };
                    img.src = event.target.result;
                };
                reader.readAsDataURL(file);
            });
        }

        const photoInput = document.querySelector('input[name="photos[]"]');
        if (photoInput) {
            photoInput.addEventListener('change', async function(e) {
                const files = Array.from(e.target.files);
                if (!files.length) return;

                const compressedFiles = await Promise.all(files.map(compressImage));

                const dataTransfer = new DataTransfer();
                compressedFiles.forEach((f) => dataTransfer.items.add(f));
                photoInput.files = dataTransfer.files;

                const list = document.getElementById('photo-preview-list');
                list.innerHTML = '';
                compressedFiles.forEach((f) => {
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(f);
                    img.className = 'h-20 w-20 object-cover rounded-lg border';
                    list.appendChild(img);
                });
            });
        }
    </script>
</x-app-layout>