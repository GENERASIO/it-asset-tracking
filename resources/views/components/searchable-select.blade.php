@props(['name', 'options', 'selected' => null, 'placeholder' => 'Semua'])

<div x-data="searchableSelect({
        options: @js(collect($options)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])->values()),
        selected: @js((string) $selected),
        placeholder: @js($placeholder),
    })"
    @click.outside="open = false"
    class="relative">
    <input type="hidden" name="{{ $name }}" :value="selectedValue">

    <button type="button" @click="toggle()"
            class="flex items-center justify-between gap-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg text-sm px-3 py-2 min-w-[9.5rem] bg-white dark:text-white">
        <span x-text="selectedLabel || placeholder" :class="!selectedLabel ? 'text-gray-400' : 'text-gray-800 dark:text-white'"></span>
        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-show="open" style="display:none" class="absolute z-20 mt-1 w-56 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg overflow-hidden">
        <input type="text" x-model="query" x-ref="search" @keydown.escape="open = false"
               placeholder="Cari..." autocomplete="off"
               class="w-full border-0 border-b border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm px-3 py-2 focus:ring-0">
        <div class="max-h-56 overflow-auto">
            <button type="button" @click="choose('')"
                    class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-500 dark:text-gray-300">
                <span x-text="placeholder"></span>
            </button>
            <template x-for="opt in filteredOptions" :key="opt.value">
                <button type="button" @click="choose(opt.value)"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-white"
                        :class="opt.value === selectedValue ? 'bg-brand-50 dark:bg-gray-600 font-medium' : ''"
                        x-text="opt.label"></button>
            </template>
            <p x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-400">Tidak ada hasil.</p>
        </div>
    </div>
</div>

<script>
    function searchableSelect(config) {
        return {
            options: config.options,
            selectedValue: config.selected || '',
            placeholder: config.placeholder,
            query: '',
            open: false,
            get selectedLabel() {
                const found = this.options.find((o) => o.value === this.selectedValue);
                return found ? found.label : '';
            },
            get filteredOptions() {
                if (!this.query) return this.options;
                const q = this.query.toLowerCase();
                return this.options.filter((o) => o.label.toLowerCase().includes(q));
            },
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    this.query = '';
                    this.$nextTick(() => this.$refs.search.focus());
                }
            },
            choose(value) {
                this.selectedValue = value;
                this.open = false;
                this.$el.closest('form').submit();
            },
        };
    }
</script>
