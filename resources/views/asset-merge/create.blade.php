<x-app-layout title="Gabungkan Aset">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Gabungkan {{ $asset->asset_code }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($errors->any())
                <div class="p-3 bg-red-100 text-red-700 rounded-lg text-sm">{{ $errors->first() }}</div>
            @endif

            {{-- Data agent dari record ini --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Record dari agent (akan dipindahkan)</h3>
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-gray-500">Model</dt><dd class="text-gray-900 dark:text-white">{{ $asset->name }}</dd></div>
                    <div><dt class="text-gray-500">Serial (Service Tag)</dt><dd class="font-mono text-gray-900 dark:text-white">{{ $asset->serial_number ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500">Hostname</dt><dd class="text-gray-900 dark:text-white">{{ $asset->hostname ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500">Prosesor</dt><dd class="text-gray-900 dark:text-white">{{ $asset->cpu ?? '-' }}</dd></div>
                </dl>
            </div>

            {{-- Pilih aset tujuan --}}
            <form method="POST" action="{{ route('asset-merge.store', $asset) }}"
                  x-data="{ q: '', target: '' }" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 space-y-3">
                @csrf
                <h3 class="font-semibold text-gray-900 dark:text-white">Pilih aset lama tujuan</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Nama pemegang, lokasi, kode aset, dan histori milik aset tujuan <strong>tetap</strong>.
                    Serial & data hardware diambil dari record agent di atas.
                </p>

                <input type="text" x-model="q" placeholder="Cari kode / nama / pemegang / serial…"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">

                <div class="max-h-80 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($targets as $t)
                        @php
                            $search = strtolower(implode(' ', array_filter([
                                $t->asset_code, $t->name, $t->serial_number,
                                optional($t->assignedUser)->name, optional($t->location)->name,
                            ])));
                        @endphp
                        <label data-search="{{ $search }}"
                               x-show="q === '' || $el.dataset.search.includes(q.toLowerCase())"
                               class="flex items-start gap-3 px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50"
                               :class="target === '{{ $t->id }}' ? 'bg-brand-50 dark:bg-brand-900/30' : ''">
                            <input type="radio" name="target_id" value="{{ $t->id }}" x-model="target" class="mt-1">
                            <div class="text-sm">
                                <span class="font-mono font-semibold text-brand-500">{{ $t->asset_code }}</span>
                                <span class="text-gray-900 dark:text-white">· {{ $t->name }}</span>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Pemegang: {{ optional($t->assignedUser)->name ?? '-' }} ·
                                    Lokasi: {{ optional($t->location)->name ?? '-' }} ·
                                    Serial: <span class="font-mono">{{ $t->serial_number ?? '-' }}</span>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" x-bind:disabled="target === ''"
                            class="bg-brand-500 hover:bg-brand-600 disabled:opacity-40 disabled:cursor-not-allowed text-white px-6 py-2 rounded-lg text-sm"
                            onclick="return confirm('Gabungkan record agent ini ke aset tujuan? Record agent akan dihapus.')">
                        Gabungkan
                    </button>
                    <a href="{{ route('asset-merge.index') }}" class="text-gray-500 text-sm hover:underline">Batal</a>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
