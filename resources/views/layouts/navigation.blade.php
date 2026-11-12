<?php
    $roleLabels = [
        'super_admin' => 'Super Admin',
        'it_staff' => 'IT Staff',
        'user' => 'User',
    ];
    $roleLabel = $roleLabels[Auth::user()->role] ?? ucfirst(str_replace('_', ' ', Auth::user()->role));
?>
<div x-data="{ open: false }" class="sticky top-0 z-40 lg:static lg:z-auto">
    <!-- Mobile Top Bar -->
    <div class="lg:hidden bg-gradient-to-br from-indigo-950 via-purple-900 to-violet-800 border-b border-indigo-900">
        <div class="flex justify-between items-center h-16 px-4">
            <a href="{{ route('dashboard') }}" class="flex items-center">
                <div class="bg-white dark:bg-white rounded-lg px-3 py-1.5 flex items-center mr-2">
                    <img src="{{ asset('images/logo.png') }}" alt="YAY Group" class="h-8 w-auto">
                </div>
                <div class="leading-tight">
                    <div class="font-semibold text-white text-sm">PT. YAY Enak Semua</div>
                    <div class="text-xs text-indigo-200">IT Asset Tracking</div>
                </div>
            </a>

            <div class="flex items-center gap-1">
                @include('layouts.partials.notification-bell')

                <button type="button" data-mobile-menu-btn @click="open = ! open" :aria-expanded="open" aria-label="Menu" class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] rounded-md text-indigo-200 hover:text-white hover:bg-white/10 focus:outline-none focus:bg-white/10 focus:text-white transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu Panel -->
        <div :class="{'block': open, 'hidden': ! open}" class="hidden bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 max-h-[calc(100dvh-4rem)] overflow-y-auto scrollbar-modern shadow-xl">
            <nav x-data="{ collapsed: false }" class="px-3 pt-3 pb-3 space-y-1">
                @include('layouts.partials.nav-links')
            </nav>

            <!-- Responsive Settings Options -->
            <div class="pt-4 pb-3 border-t border-gray-200 dark:border-gray-700">
                <div class="mx-3 px-3 py-3 rounded-lg bg-gray-50 dark:bg-gray-700 flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-brand-600 flex items-center justify-center text-white font-bold shrink-0">
                        {{ Auth::user()->initials }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-semibold text-base text-gray-800 dark:text-white truncate">{{ Auth::user()->name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email }}</div>
                        <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200">
                            {{ $roleLabel }}
                        </span>
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">
                        <span class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            {{ __('Profile') }}
                        </span>
                    </x-responsive-nav-link>

                    <button type="button" onclick="toggleDarkMode()"
                            class="w-full flex items-center justify-between gap-3 ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-800 dark:focus:text-white focus:bg-gray-50 dark:focus:bg-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                        <span class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M12 3a9 9 0 000 18 9 9 0 000-18z" fill="none" />
                                <path d="M12 3a9 9 0 000 18V3z" fill="currentColor" stroke="none" />
                            </svg>
                            {{ __('Theme') }}
                        </span>
                        <span class="relative inline-flex h-5 w-9 items-center rounded-full bg-gray-300 dark:bg-brand-500 transition-colors shrink-0">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-0.5 dark:translate-x-[18px]"></span>
                        </span>
                    </button>

                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            <span class="flex items-center gap-3">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H3" />
                                </svg>
                                {{ __('Log Out') }}
                            </span>
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Desktop Sidebar (icon rail + nav list) -->
    <div class="hidden lg:flex lg:sticky lg:top-0 lg:h-screen lg:shrink-0"
         x-data="{ collapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
         x-init="$watch('collapsed', value => localStorage.setItem('sidebarCollapsed', value))">
        <!-- Icon Rail -->
        <div class="flex flex-col items-center w-16 h-full shrink-0 bg-gradient-to-b from-indigo-950 via-purple-900 to-violet-800 border-r border-indigo-900 py-4 gap-3">
            <a href="{{ route('dashboard') }}" class="w-10 h-10 rounded-lg bg-white dark:bg-white flex items-center justify-center shrink-0">
                <img src="{{ asset('images/logo.png') }}" alt="YAY Group" class="h-6 w-auto">
            </a>

            <button type="button" @click="collapsed = !collapsed"
                    class="w-8 h-8 rounded-lg text-white/70 hover:text-white hover:bg-white/10 flex items-center justify-center transition shrink-0"
                    :title="collapsed ? 'Perluas menu' : 'Ciutkan menu'">
                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': collapsed }"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </button>

            @include('layouts.partials.notification-bell')

            <div class="mt-auto">
                <x-dropdown align="left" direction="up" width="w-72" contentClasses="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700 overflow-hidden">
                    <x-slot name="trigger">
                        <button type="button"
                                class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white font-bold text-sm hover:bg-white/30 focus:outline-none transition"
                                :class="{ 'ring-2 ring-white': open }">
                            {{ Auth::user()->initials }}
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        {{-- Header: avatar besar + nama + email + role --}}
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-14 h-14 rounded-full bg-brand-600 flex items-center justify-center text-white font-bold text-lg shrink-0">
                                    {{ Auth::user()->initials }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-white truncate">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email }}</p>
                                    <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200">
                                        {{ $roleLabel }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Menu items --}}
                        <div class="py-1">
                            <a href="{{ route('profile.edit') }}"
                               class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                                {{ __('Profile') }}
                            </a>

                            <button type="button" onclick="toggleDarkMode()"
                                    class="w-full flex items-center justify-between gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <span class="flex items-center gap-3">
                                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path d="M12 3a9 9 0 000 18 9 9 0 000-18z" fill="none" />
                                        <path d="M12 3a9 9 0 000 18V3z" fill="currentColor" stroke="none" />
                                    </svg>
                                    {{ __('Theme') }}
                                </span>
                                <span class="relative inline-flex h-5 w-9 items-center rounded-full bg-gray-300 dark:bg-brand-500 transition-colors shrink-0">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform translate-x-0.5 dark:translate-x-[18px]"></span>
                                </span>
                            </button>
                        </div>

                        {{-- Logout --}}
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H3" />
                                </svg>
                                {{ __('Log Out') }}
                            </button>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>

        <!-- Nav List Panel -->
        <aside class="flex flex-col h-full shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-all duration-200"
               :class="collapsed ? 'w-16' : 'w-52'">
        <div class="h-16 flex flex-col justify-center px-4 border-b border-gray-100 dark:border-gray-700 shrink-0" x-show="!collapsed">
            <div class="font-semibold text-gray-800 dark:text-white text-sm truncate">PT. YAY Enak Semua</div>
            <div class="text-xs text-gray-400 dark:text-gray-500 truncate">IT Asset Tracking</div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            @include('layouts.partials.nav-links')
        </nav>
        </aside>
    </div>
</div>
