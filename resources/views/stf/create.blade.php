<x-app-layout title="Buat STF">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Buat STF (Transfer / Mutasi Aset)</h2>
    </x-slot>

    @php
        $input = 'mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm text-sm';
        $label = 'text-sm font-medium text-gray-700 dark:text-gray-300';
        $rows = old('items', $items->all());
        $oldTo = old('to_location_id');
        $oldToName = $oldTo ? optional($locations->firstWhere('id', (int) $oldTo))->name : '';
        $oldFrom = old('from_location_id', $defaults['from_location_id']);
        $oldFromName = $oldFrom ? optional($locations->firstWhere('id', (int) $oldFrom))->name : '';
    @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('stf.store') }}" class="space-y-6">
                @csrf

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
                            <div x-data="{
                                    open: false, q: '',
                                    id: @js((string) ($oldFrom ?? '')), label: @js($oldFromName),
                                    options: @js($locations->map(fn ($l) => ['id' => (string) $l->id, 'name' => $l->name])->values()),
                                    get filtered() { return this.q === '' ? this.options : this.options.filter(o => o.name.toLowerCase().includes(this.q.toLowerCase())); },
                                    pick(o) { this.id = o.id; this.label = o.name; this.open = false; this.q = ''; }
                                 }" @click.outside="open = false" class="relative">
                                <input type="hidden" name="from_location_id" :value="id">
                                <input type="text" readonly placeholder="-- Pilih outlet asal --"
                                       @click="open = !open; if (open) $nextTick(() => $refs.searchFrom.focus())"
                                       :value="label" class="{{ $input }} cursor-pointer">
                                <div x-show="open" x-cloak class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg overflow-hidden">
                                    <input type="text" x-ref="searchFrom" x-model="q" placeholder="Cari outlet..." autocomplete="off"
                                           @keydown.escape="open = false"
                                           class="w-full border-0 border-b border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm px-3 py-2 focus:ring-0">
                                    <div class="max-h-60 overflow-auto">
                                        <template x-for="o in filtered" :key="o.id">
                                            <button type="button" @click="pick(o)" x-text="o.name"
                                                    class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-white"
                                                    :class="o.id === id ? 'bg-brand-50 dark:bg-gray-600 font-medium' : ''"></button>
                                        </template>
                                        <p x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400">Tidak ada hasil.</p>
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Terisi otomatis dari lokasi aset, bisa diubah.</p>
                        </div>
                        <div>
                            <label class="{{ $label }}">Outlet Tujuan</label>
                            <div x-data="{
                                    open: false, q: '',
                                    id: @js((string) ($oldTo ?? '')), label: @js($oldToName),
                                    options: @js($locations->map(fn ($l) => ['id' => (string) $l->id, 'name' => $l->name])->values()),
                                    get filtered() { return this.q === '' ? this.options : this.options.filter(o => o.name.toLowerCase().includes(this.q.toLowerCase())); },
                                    pick(o) { this.id = o.id; this.label = o.name; this.open = false; this.q = ''; }
                                 }" @click.outside="open = false" class="relative">
                                <input type="hidden" name="to_location_id" :value="id">
                                <input type="text" readonly placeholder="-- Pilih outlet tujuan --"
                                       @click="open = !open; if (open) $nextTick(() => $refs.search.focus())"
                                       :value="label" class="{{ $input }} cursor-pointer">
                                <div x-show="open" x-cloak class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg overflow-hidden">
                                    <input type="text" x-ref="search" x-model="q" placeholder="Cari outlet..." autocomplete="off"
                                           @keydown.escape="open = false"
                                           class="w-full border-0 border-b border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm px-3 py-2 focus:ring-0">
                                    <div class="max-h-60 overflow-auto">
                                        <template x-for="o in filtered" :key="o.id">
                                            <button type="button" @click="pick(o)" x-text="o.name"
                                                    class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-white"
                                                    :class="o.id === id ? 'bg-brand-50 dark:bg-gray-600 font-medium' : ''"></button>
                                        </template>
                                        <p x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400">Tidak ada hasil.</p>
                                    </div>
                                </div>
                            </div>
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
                                        <label class="{{ $label }}">Deskripsi</label>
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
                                    <div class="md:col-span-6">
                                        <label class="{{ $label }}">Spesifikasi (Processor, RAM, SSD)</label>
                                        <textarea name="items[{{ $i }}][spec]" rows="2" class="{{ $input }}" placeholder="Otomatis dari data agent, bisa diubah">{{ $row['spec'] ?? '' }}</textarea>
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
