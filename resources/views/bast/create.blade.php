<x-app-layout title="Buat BAST">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Buat BAST</h2>
    </x-slot>

    @php
        $input = 'mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm text-sm';
        $label = 'text-sm font-medium text-gray-700 dark:text-gray-300';
        $rows = old('items', $items->all());
    @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('bast.store') }}" class="space-y-6">
                @csrf

                @if ($errors->any())
                    <div class="p-3 bg-red-100 text-red-700 rounded-lg text-sm">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Semua isian bisa diubah. Penerima boleh nama outlet / orang yang tidak terdaftar sebagai user.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-3"
                             x-data="{
                                 positions: {{ Js::from($lastPositions) }},
                                 name: {{ Js::from(old('first_party_name', $defaults['first_party_name'])) }},
                                 position: {{ Js::from(old('first_party_position', $defaults['first_party_position'])) }},
                                 pick() { if (this.positions[this.name]) this.position = this.positions[this.name]; }
                             }">
                            <h3 class="font-semibold text-gray-700 dark:text-gray-300">Pihak Pertama (yang menyerahkan)</h3>
                            <div>
                                <label class="{{ $label }}">Nama</label>
                                <select name="first_party_name" required class="{{ $input }}" x-model="name" @change="pick()">
                                    <option value="">-- Pilih --</option>
                                    @foreach ($firstParties as $person)
                                        <option value="{{ $person }}" @selected(old('first_party_name', $defaults['first_party_name']) === $person)>{{ $person }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $label }}">Jabatan</label>
                                <input type="text" name="first_party_position" required class="{{ $input }}" placeholder="mis. IT Staff"
                                       x-model="position" value="{{ old('first_party_position', $defaults['first_party_position']) }}">
                                <p class="text-xs text-gray-500 mt-1">Terisi otomatis dari BAST terakhir orang ini, tetap bisa diubah.</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <h3 class="font-semibold text-gray-700 dark:text-gray-300">Pihak Kedua (yang menerima)</h3>
                            <div>
                                <label class="{{ $label }}">Nama</label>
                                <input type="text" name="second_party_name" required class="{{ $input }}" placeholder="Nama orang / outlet"
                                       value="{{ old('second_party_name', $defaults['second_party_name']) }}">
                            </div>
                            <div>
                                <label class="{{ $label }}">Jabatan</label>
                                <input type="text" name="second_party_position" required class="{{ $input }}" placeholder="mis. Staff Finance / Kepala Outlet"
                                       value="{{ old('second_party_position') }}">
                            </div>
                        </div>

                        <div>
                            <label class="{{ $label }}">Kota</label>
                            <input type="text" name="city" required class="{{ $input }}"
                                   value="{{ old('city', $defaults['city']) }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Tanggal</label>
                            <input type="date" name="handover_date" required class="{{ $input }}"
                                   value="{{ old('handover_date', $defaults['handover_date']) }}">
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Barang yang Diserahkan</h3>

                    <div class="space-y-4">
                        @foreach ($rows as $i => $row)
                            <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4">
                                <p class="text-xs text-gray-500 mb-2">
                                    {{ $i + 1 }}. @if (! empty($row['asset_code'])) <span class="font-mono">{{ $row['asset_code'] }}</span> @endif
                                </p>
                                <input type="hidden" name="items[{{ $i }}][asset_id]" value="{{ $row['asset_id'] ?? '' }}">
                                <input type="hidden" name="items[{{ $i }}][asset_code]" value="{{ $row['asset_code'] ?? '' }}">
                                <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                                    <div class="col-span-1">
                                        <label class="{{ $label }}">Jenis barang</label>
                                        <input type="text" name="items[{{ $i }}][item_type]" required class="{{ $input }}" value="{{ $row['item_type'] ?? '' }}">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="{{ $label }}">Detail barang</label>
                                        <input type="text" name="items[{{ $i }}][detail]" required class="{{ $input }}" value="{{ $row['detail'] ?? '' }}">
                                    </div>
                                    <div class="col-span-1">
                                        <label class="{{ $label }}">Serial Number</label>
                                        <input type="text" name="items[{{ $i }}][serial_number]" class="{{ $input }}" value="{{ $row['serial_number'] ?? '' }}">
                                    </div>
                                    <div class="col-span-1">
                                        <label class="{{ $label }}">Jumlah</label>
                                        <input type="number" min="1" name="items[{{ $i }}][quantity]" required class="{{ $input }}" value="{{ $row['quantity'] ?? 1 }}">
                                    </div>
                                    <div class="col-span-1">
                                        <label class="{{ $label }}">Kondisi</label>
                                        <input type="text" name="items[{{ $i }}][condition]" required list="bast-conditions" class="{{ $input }}" value="{{ $row['condition'] ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <datalist id="bast-conditions">
                        @foreach (\App\Models\AssetLog::CONDITIONS as $condition)
                            <option value="{{ $condition }}">
                        @endforeach
                    </datalist>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600 transition-all duration-150 hover:scale-105 active:scale-95">
                        Simpan BAST
                    </button>
                    <a href="{{ url()->previous() }}" class="px-4 py-2 rounded-lg text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
