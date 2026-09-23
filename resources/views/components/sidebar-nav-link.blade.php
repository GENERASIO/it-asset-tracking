@props(['active' => false])

@php
$classes = ($active ?? false)
            ? 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/40'
            : 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} :class="{ 'justify-center': collapsed }" :title="collapsed ? '{{ trim($slot) }}' : ''">
    @isset($icon)
        <span class="w-5 h-5 shrink-0">{{ $icon }}</span>
    @endisset
    <span class="truncate" x-show="!collapsed">{{ $slot }}</span>
</a>
