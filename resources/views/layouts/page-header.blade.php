{{-- Header halaman: banner ungu dengan ikon, breadcrumb & deskripsi sesuai menu yang sedang dibuka. --}}
@php
    $menus = [
        'assets' => ['Aset', 'Semua perangkat IT perusahaan — lokasi, pemegang, status & label QR.',
            ['M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9']],
        'asset-merge' => ['Aset', 'Tinjau perangkat dari agent yang kemungkinan kembar dengan aset lama.',
            ['M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5']],
        'bast' => ['Serah Terima', 'Berita acara serah terima aset, lengkap dengan tanda tangan digital.',
            ['M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z']],
        'stf' => ['Mutasi Asset (STF)', 'Surat transfer aset antar outlet & Head Office — lokasi aset pindah otomatis.',
            ['M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5']],
        'categories' => ['Kategori', 'Kelompok jenis aset, dipakai untuk kode aset & laporan.',
            ['M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z', 'M6 6h.008v.008H6V6z']],
        'locations' => ['Lokasi', 'Outlet & kantor tempat aset berada.',
            ['M15 10.5a3 3 0 11-6 0 3 3 0 016 0z', 'M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z']],
        'users' => ['Kelola User', 'Akun login & pemegang aset beserta perannya.',
            ['M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z']],
        'software-licenses' => ['Lisensi Software', 'Lisensi resmi, pemakaian & pengingat masa berlaku.',
            ['M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z']],
        'price-references' => ['Harga Model', 'Harga acuan per model perangkat untuk nilai & penyusutan aset.',
            ['M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z']],
        'agent-tokens' => ['Agent', 'Token & installer agent yang mengirim data perangkat secara otomatis.',
            ['M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25']],
        'reports' => ['Laporan', 'Analisis umur, nilai & penyusutan aset — siap export.',
            ['M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z']],
        'profile' => ['Profil', 'Data akun & password Anda.',
            ['M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z']],
    ];

    $routeName = (string) optional(request()->route())->getName();
    $prefix = explode('.', $routeName)[0];
    [$menuLabel, $menuDesc, $menuIcon] = $menus[$prefix] ?? [null, null, ['M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6z']];
    $isIndex = str_ends_with($routeName, '.index');
    $menuUrl = Route::has($prefix.'.index') ? route($prefix.'.index') : null;
@endphp

<header class="px-4 sm:px-6 lg:px-8 pt-6">
    <div class="max-w-7xl mx-auto">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-950 via-purple-900 to-violet-800 text-white shadow-lg">
            <div class="absolute -top-20 -right-10 w-64 h-64 rounded-full bg-fuchsia-500/20 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 22px 22px;"></div>

            <div class="relative flex items-center gap-4 px-5 py-5 sm:px-7 sm:py-6">
                <span class="hidden sm:flex shrink-0 items-center justify-center w-12 h-12 rounded-xl bg-white/10 ring-1 ring-white/20">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        @foreach ($menuIcon as $d)
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}" />
                        @endforeach
                    </svg>
                </span>
                <div class="min-w-0">
                    <nav class="flex items-center gap-1.5 text-xs text-violet-200">
                        <a href="{{ route('dashboard') }}" class="hover:text-white">Dashboard</a>
                        @if ($menuLabel && ! $isIndex)
                            <span>/</span>
                            @if ($menuUrl)<a href="{{ $menuUrl }}" class="hover:text-white">{{ $menuLabel }}</a>@else<span>{{ $menuLabel }}</span>@endif
                        @endif
                    </nav>
                    <div class="mt-0.5 truncate [&_h2]:!text-white [&_h2]:!text-xl sm:[&_h2]:!text-2xl [&_h2]:!font-bold [&_h2]:tracking-tight [&_h2]:truncate">
                        {{ $header }}
                    </div>
                    @if ($menuDesc && $isIndex)
                        <p class="mt-1 text-sm text-violet-100/80 truncate">{{ $menuDesc }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
