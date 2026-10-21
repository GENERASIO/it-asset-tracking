<x-app-layout title="Harga Model">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Harga Model</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-3 bg-green-100 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="p-3 bg-red-100 text-red-700 rounded-lg text-sm">{{ $errors->first() }}</div>
            @endif

            <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 text-sm">
                Daftar harga beli per model. Saat agent mendaftarkan laptop baru, harga belinya
                <strong>terisi otomatis</strong> dari daftar ini (hanya kalau harganya masih kosong — yang sudah diisi manual tidak ditimpa).
            </div>

            {{-- Form tambah --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Tambah / Perbarui Harga Model</h3>
                <form method="POST" action="{{ route('price-references.store') }}" class="flex flex-col sm:flex-row sm:items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Nama model <span class="text-gray-400">(sesuai yang dibaca agent)</span></label>
                        <input type="text" name="match_name" required value="{{ old('match_name') }}" list="model-suggestions"
                               placeholder="mis. Dell Inc. Vostro 3400"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <datalist id="model-suggestions">
                            @foreach ($missing as $row)
                                <option value="{{ $row->name }}">{{ $row->unit }} unit tanpa harga</option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="sm:w-48">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Harga beli / unit</label>
                        <x-rupiah-input name="purchase_price" :value="old('purchase_price')"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                    </div>
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-6 py-2 rounded-lg text-sm whitespace-nowrap">Simpan</button>
                </form>
                @if ($missing->isNotEmpty())
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Model terdeteksi agent yang belum ada harga:
                        {{ $missing->take(8)->map(fn ($r) => $r->name." ({$r->unit})")->implode(', ') }}{{ $missing->count() > 8 ? ', …' : '' }}
                    </p>
                @endif
            </div>

            {{-- Daftar harga --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3 text-left">Nama Model</th>
                            <th class="px-4 py-3 text-right">Harga Beli</th>
                            <th class="px-4 py-3 text-center">Unit Cocok</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    @forelse ($references as $ref)
                        <tbody x-data="{ edit: false }" class="border-t border-gray-100 dark:border-gray-700">
                            {{-- tampil --}}
                            <tr x-show="!edit" class="text-gray-900 dark:text-white">
                                <td class="px-4 py-3 font-medium">{{ $ref->match_name }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">Rp {{ number_format($ref->purchase_price, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">{{ $counts[$ref->match_name] ?? 0 }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button type="button" @click="edit = true" class="text-brand-500 hover:underline">Edit</button>
                                    <form method="POST" action="{{ route('price-references.destroy', $ref) }}" class="inline"
                                          onsubmit="return confirm('Hapus harga model ini? Harga aset yang sudah terisi tidak berubah.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline ml-2">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            {{-- edit inline --}}
                            <tr x-show="edit" x-cloak>
                                <td colspan="4" class="px-4 py-3">
                                    <form method="POST" action="{{ route('price-references.update', $ref) }}" class="flex flex-wrap gap-2 items-center">
                                        @csrf @method('PUT')
                                        <input type="text" name="match_name" value="{{ $ref->match_name }}" required
                                               class="flex-1 min-w-[12rem] rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                        <x-rupiah-input name="purchase_price" :value="$ref->purchase_price"
                                            class="w-40 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                                        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-3 py-2 rounded-lg text-sm">Simpan</button>
                                        <button type="button" @click="edit = false" class="text-gray-500 px-2 text-sm">Batal</button>
                                    </form>
                                </td>
                            </tr>
                        </tbody>
                    @empty
                        <tbody>
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Belum ada harga model.</td></tr>
                        </tbody>
                    @endforelse
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
