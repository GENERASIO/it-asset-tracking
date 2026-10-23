<x-app-layout title="Buat STF">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Buat STF (Transfer / Mutasi Aset)</h2>
    </x-slot>

    @php
        $input = 'mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm text-sm';
        $label = 'text-sm font-medium text-gray-700 dark:text-gray-300';
        $rows = old('items', $items->all());
    @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('stf.store') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="from_location_id" value="{{ old('from_location_id', $defaults['from_location_id']) }}">

                @if ($errors->any())
                    <div class="p-3 bg-red-100 text-red-700 rounded-lg text-sm">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 text-sm">
                    Saat disimpan, lokasi aset di bawah <strong>otomatis dipindahkan</strong> ke Outlet Tujuan dan tercatat di log aktivitas.
                </div>

                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="{{ $label }}">Tanggal</label>
                            <input type="date" name="transfer_date" required class="{{ $input }}"
                                   value="{{ old('transfer_date', $defaults['transfer_date']) }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Outlet Asal</label>
                            <input type="text" class="{{ $input }} bg-gray-100 dark:bg-gray-600" value="{{ $defaults['from_location_name'] }}" readonly>
                            <p class="text-xs text-gray-500 mt-1">Otomatis dari lokasi aset yang dipilih.</p>
                        </div>
                        <div>
                            <label class="{{ $label }}">Outlet Tujuan</label>
                            <select name="to_location_id" required class="{{ $input }}">
                                <option value="">-- Pilih outlet tujuan --</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}" @selected((int) old('to_location_id') === $loc->id)>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Alasan Mutasi</label>
                            <input type="text" name="reason" required class="{{ $input }}"
                                   value="{{ old('reason', $defaults['reason']) }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">PIC Pengirim (Staff IT)</label>
                            <select name="sender_name" required class="{{ $input }}">
                                <option value="">-- Pilih --</option>
                                @foreach ($senders as $person)
                                    <option value="{{ $person }}" @selected(old('sender_name', $defaults['sender_name']) === $person)>{{ $person }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Atasan</label>
                            <input type="text" name="supervisor_name" required class="{{ $input }}"
                                   value="{{ old('supervisor_name', $defaults['supervisor_name']) }}">
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Aset yang Dipindahkan</h3>
                    <div class="space-y-4">
                        @foreach ($rows as $i => $row)
                            <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4">
                                <p class="text-xs text-gray-500 mb-2">{{ $i + 1 }}. <span class="font-mono">{{ $row['asset_no'] ?? '' }}</span></p>
                                <input type="hidden" name="items[{{ $i }}][asset_id]" value="{{ $row['asset_id'] ?? '' }}">
                                <input type="hidden" name="items[{{ $i }}][asset_no]" value="{{ $row['asset_no'] ?? '' }}">
                                <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                                    <div class="md:col-span-3">
                                        <label class="{{ $label }}">Deskripsi / Spesifikasi</label>
                                        <input type="text" name="items[{{ $i }}][description]" required class="{{ $input }}" value="{{ $row['description'] ?? '' }}">
                                    </div>
                                    <div class="md:col-span-1">
                                        <label class="{{ $label }}">Qty</label>
                                        <input type="number" min="1" name="items[{{ $i }}][quantity]" required class="{{ $input }}" value="{{ $row['quantity'] ?? 1 }}">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="{{ $label }}">Notes</label>
                                        <input type="text" name="items[{{ $i }}][notes]" class="{{ $input }}" value="{{ $row['notes'] ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600 transition-all duration-150 hover:scale-105 active:scale-95">
                        Simpan STF &amp; Pindahkan Aset
                    </button>
                    <a href="{{ url()->previous() }}" class="px-4 py-2 rounded-lg text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
