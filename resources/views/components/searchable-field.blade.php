@props(['id', 'name', 'placeholder' => '-- Pilih --'])

{{-- Combobox bisa diketik untuk mencari. Native <select> tetap ada (disembunyikan) supaya
     form submit normal DAN tombol "+ Tambah ..." (quick-add) tetap jalan tanpa diubah.
     Opsi slot = tag <option> dari form pemanggil. MutationObserver menyinkronkan opsi baru
     yang ditambahkan quick-add secara otomatis. --}}
<div x-data="searchableField('{{ $id }}', @js($placeholder))" @click.outside="open = false" class="relative mt-1">
    <select id="{{ $id }}" name="{{ $name }}" x-ref="native" tabindex="-1" aria-hidden="true" class="sr-only">
        {{ $slot }}
    </select>

    <button type="button" @click="toggle()"
            class="w-full flex items-center justify-between gap-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg shadow-sm text-left px-3 py-2 bg-white dark:text-white">
        <span x-text="label || placeholder" :class="label ? 'text-gray-800 dark:text-white' : 'text-gray-400'"></span>
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-show="open" style="display:none" x-transition.opacity.duration.100ms
         class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg overflow-hidden">
        <input type="text" x-model="query" x-ref="search" @keydown.escape="open = false"
               placeholder="Ketik untuk mencari..." autocomplete="off"
               class="w-full border-0 border-b border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm px-3 py-2 focus:ring-0">
        <div class="max-h-60 overflow-auto">
            <template x-for="opt in filtered" :key="opt.value + '|' + opt.label">
                <button type="button" @click="choose(opt.value)"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-white"
                        :class="opt.value === value ? 'bg-brand-50 dark:bg-gray-600 font-medium' : ''"
                        x-text="opt.label"></button>
            </template>
            <p x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400">Tidak ada hasil.</p>
        </div>
    </div>
</div>

<script>
    window.searchableField = window.searchableField || function (id, placeholder) {
        return {
            open: false,
            query: '',
            value: '',
            placeholder: placeholder,
            options: [],
            native: null,
            init() {
                this.native = this.$refs.native;
                this.sync();
                // Opsi yang ditambahkan tombol "+ Tambah ..." (quick-add) langsung ikut tampil.
                new MutationObserver(() => this.sync()).observe(this.native, { childList: true });
                this.native.addEventListener('change', () => { this.value = this.native.value; });
            },
            sync() {
                this.options = Array.from(this.native.options).map((o) => ({ value: o.value, label: o.textContent.trim() }));
                this.value = this.native.value;
            },
            get label() {
                if (this.value === '') return '';
                const o = this.options.find((o) => o.value === this.value);
                return o ? o.label : '';
            },
            get filtered() {
                if (!this.query) return this.options;
                const q = this.query.toLowerCase();
                return this.options.filter((o) => o.label.toLowerCase().includes(q));
            },
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    this.query = '';
                    this.$nextTick(() => this.$refs.search && this.$refs.search.focus());
                }
            },
            choose(v) {
                this.native.value = v;
                this.value = v;
                this.native.dispatchEvent(new Event('change'));
                this.open = false;
            },
        };
    };
</script>
