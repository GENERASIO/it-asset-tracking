<?php
    $roleLabels = [
        'super_admin' => 'Super Admin',
        'it_staff' => 'IT Staff',
        'user' => 'User',
    ];
    $roleLabel = $roleLabels[Auth::user()->role] ?? ucfirst(str_replace('_', ' ', Auth::user()->role));
?>
<div x-data="{ open: false }">
    <!-- Mobile Top Bar -->
    <div class="lg:hidden bg-gradient-to-br from-indigo-950 via-purple-900 to-violet-800 border-b border-indigo-900">
        <div class="flex justify-between items-center h-16 px-4">
            <a href="{{ route('dashboard') }}" class="flex items-center">
                <div class="bg-white rounded-lg px-3 py-1.5 flex items-center mr-2">
                    <img src="{{ asset('images/logo.png') }}" alt="YAY Group" class="h-8 w-auto">
                </div>
                <div class="leading-tight">
                    <div class="font-semibold text-white text-sm">PT. YAY Enak Semua</div>
                    <div class="text-xs text-indigo-200">IT Asset Tracking</div>
                </div>
            </a>

            <div class="flex items-center gap-1">
                @include('layouts.partials.notification-bell')

                <button @click="open = ! open" class="inline-flex items-center justify-center min-w-[44px] min-h-[44px] rounded-md text-indigo-200 hover:text-white hover:bg-white/10 focus:outline-none focus:bg-white/10 focus:text-white transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu Panel -->
        <div :class="{'block': open, 'hidden': ! open}" class="hidden bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            <div class="pt-2 pb-3 space-y-1">
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>

                @if (in_array(Auth::user()->role, ['super_admin', 'it_staff']))
                    <x-responsive-nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')">
                        {{ __('Aset') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                        {{ __('Laporan') }}
                    </x-responsive-nav-link>
                @endif

                @if (Auth::user()->role === 'super_admin')
                    <x-responsive-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.*')">
                        {{ __('Kategori') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('locations.index')" :active="request()->routeIs('locations.*')">
                        {{ __('Lokasi') }}
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                        {{ __('Kelola User') }}
                    </x-responsive-nav-link>
                @endif
            </div>

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
    <div class="hidden lg:flex lg:sticky lg:top-0 lg:h-screen lg:shrink-0">
        <!-- Icon Rail -->
        <div class="flex flex-col items-center w-16 h-full shrink-0 bg-gradient-to-b from-indigo-950 via-purple-900 to-violet-800 border-r border-indigo-900 py-4 gap-3">
            <a href="{{ route('dashboard') }}" class="w-10 h-10 rounded-lg bg-white flex items-center justify-center shrink-0">
                <img src="{{ asset('images/logo.png') }}" alt="YAY Group" class="h-6 w-auto">
            </a>

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
        <aside class="flex flex-col w-52 h-full shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
        <div class="h-16 flex flex-col justify-center px-4 border-b border-gray-100 dark:border-gray-700 shrink-0">
            <div class="font-semibold text-gray-800 dark:text-white text-sm truncate">PT. YAY Enak Semua</div>
            <div class="text-xs text-gray-400 dark:text-gray-500 truncate">IT Asset Tracking</div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            <x-sidebar-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <x-slot name="icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
                    </svg>
                </x-slot>
                {{ __('Dashboard') }}
            </x-sidebar-nav-link>

            @if (in_array(Auth::user()->role, ['super_admin', 'it_staff']))
                <x-sidebar-nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                        </svg>
                    </x-slot>
                    {{ __('Aset') }}
                </x-sidebar-nav-link>
            @endif

            @if (Auth::user()->role === 'super_admin')
                <x-sidebar-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.*')">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                        </svg>
                    </x-slot>
                    {{ __('Kategori') }}
                </x-sidebar-nav-link>

                <x-sidebar-nav-link :href="route('locations.index')" :active="request()->routeIs('locations.*')">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                    </x-slot>
                    {{ __('Lokasi') }}
                </x-sidebar-nav-link>

                <x-sidebar-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </x-slot>
                    {{ __('Kelola User') }}
                </x-sidebar-nav-link>
            @endif

            @if (in_array(Auth::user()->role, ['super_admin', 'it_staff']))
                <x-sidebar-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                    <x-slot name="icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </x-slot>
                    {{ __('Laporan') }}
                </x-sidebar-nav-link>
            @endif
        </nav>
        </aside>
    </div>
</div>
