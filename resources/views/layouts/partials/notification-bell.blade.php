<x-dropdown align="left" width="w-80" contentClasses="bg-white dark:bg-gray-800">
    <x-slot name="trigger">
        <button type="button"
                class="relative w-9 h-9 flex items-center justify-center rounded-lg text-white/90 hover:text-white hover:bg-white/10 focus:outline-none transition"
                :class="{ 'bg-white/10 text-white': open }">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
            </svg>
            @if ($navNotifications->count() > 0)
                <span class="absolute top-1 right-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white leading-none">
                    {{ $navNotifications->count() > 9 ? '9+' : $navNotifications->count() }}
                </span>
            @endif
        </button>
    </x-slot>

    <x-slot name="content">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ __('Notifikasi') }}</p>
            @if ($navNotifications->count() > 0)
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $navNotifications->count() }} peringatan</span>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($navNotifications as $notif)
                <a href="{{ $notif['url'] }}"
                   class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                    <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $notif['severity'] === 'danger' ? 'bg-red-500' : 'bg-yellow-500' }}"></span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $notif['title'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $notif['subtitle'] }}</p>
                    </div>
                </a>
            @empty
                <div class="flex flex-col items-center justify-center py-10 px-4 text-center">
                    <svg class="w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('Tidak ada notifikasi saat ini.') }}</p>
                </div>
            @endforelse
        </div>
    </x-slot>
</x-dropdown>
