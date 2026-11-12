<!DOCTYPE html>
<html lang="id">
<head>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Masuk · {{ config('app.name', 'IT Asset Tracking') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
</head>
<body class="font-sans antialiased text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-950">
    @php
        $features = [
            ['title' => 'Lacak semua aset', 'desc' => 'Lokasi, pemegang, status & label QR dalam satu tempat.',
             'icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z'],
            ['title' => 'BAST & Mutasi digital', 'desc' => 'Serah terima & transfer aset ditandatangani lewat WhatsApp.',
             'icon' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125'],
            ['title' => 'Agent otomatis', 'desc' => 'Spesifikasi, software & lokasi laptop terbarui sendiri.',
             'icon' => 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25'],
            ['title' => 'Laporan & analisis', 'desc' => 'Umur, nilai & penyusutan aset per lokasi, siap export.',
             'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
        ];
    @endphp

    <button type="button" onclick="toggleDarkMode()" title="Ganti tema"
            class="fixed top-4 right-4 z-20 p-2 rounded-lg text-gray-500 dark:text-gray-300 hover:bg-gray-200/70 dark:hover:bg-gray-800 transition">
        <svg class="w-5 h-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
        </svg>
        <svg class="w-5 h-5 hidden dark:inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
        </svg>
    </button>

    <div class="min-h-screen flex">
        {{-- Panel brand (desktop) --}}
        <aside class="hidden lg:flex lg:w-1/2 xl:w-[55%] relative overflow-hidden bg-gradient-to-br from-indigo-950 via-purple-900 to-violet-800 text-white">
            <div class="absolute -top-32 -left-24 w-96 h-96 rounded-full bg-violet-500/30 blur-3xl"></div>
            <div class="absolute -bottom-40 -right-20 w-[28rem] h-[28rem] rounded-full bg-fuchsia-500/20 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[0.07]"
                 style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 22px 22px;"></div>

            <div class="relative z-10 flex flex-col justify-between w-full p-12 xl:p-16">
                <div class="flex items-center gap-3">
                    <div class="bg-white rounded-xl p-2 shadow-lg">
                        <img src="{{ asset('images/logo.png') }}" alt="YAY Group" class="h-9 w-auto">
                    </div>
                    <div>
                        <p class="font-semibold leading-tight">PT. YAY Enak Semua</p>
                        <p class="text-xs text-violet-200 leading-tight">IT Asset Tracking</p>
                    </div>
                </div>

                <div class="max-w-lg">
                    <h1 class="text-4xl xl:text-5xl font-bold leading-tight tracking-tight">
                        Kelola aset IT<br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-200 to-fuchsia-200">lebih rapi &amp; terukur.</span>
                    </h1>
                    <p class="mt-4 text-violet-100/80 text-base leading-relaxed">
                        Satu sistem untuk seluruh outlet &amp; Head Office — dari pencatatan, serah terima, mutasi, hingga laporan.
                    </p>

                    <ul class="mt-10 grid grid-cols-1 xl:grid-cols-2 gap-4">
                        @foreach ($features as $f)
                            <li class="flex items-start gap-3 rounded-xl bg-white/5 ring-1 ring-white/10 p-4 backdrop-blur-sm">
                                <span class="shrink-0 flex items-center justify-center w-9 h-9 rounded-lg bg-white/10 text-violet-100">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $f['icon'] }}" />
                                    </svg>
                                </span>
                                <span>
                                    <span class="block text-sm font-semibold">{{ $f['title'] }}</span>
                                    <span class="block text-xs text-violet-100/70 mt-0.5 leading-relaxed">{{ $f['desc'] }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="text-xs text-violet-200/60">&copy; {{ date('Y') }} PT. YAY Enak Semua · Tim IT</p>
            </div>
        </aside>

        {{-- Form login --}}
        <main class="flex-1 flex flex-col items-center justify-center px-6 py-12 relative">
            <div class="lg:hidden absolute inset-x-0 top-0 h-48 bg-gradient-to-br from-indigo-950 via-purple-900 to-violet-800"></div>

            <div class="relative w-full max-w-md">
                {{-- Logo (mobile) --}}
                <div class="lg:hidden flex flex-col items-center text-white mb-6">
                    <div class="bg-white rounded-2xl p-2.5 shadow-lg">
                        <img src="{{ asset('images/logo.png') }}" alt="YAY Group" class="h-12 w-auto">
                    </div>
                    <p class="mt-3 font-semibold">PT. YAY Enak Semua</p>
                    <p class="text-xs text-violet-200">IT Asset Tracking</p>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl shadow-gray-200/60 dark:shadow-none ring-1 ring-gray-100 dark:ring-gray-800 p-8 sm:p-10">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Selamat datang 👋</h2>
                        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">Masuk untuk mengelola aset IT perusahaan.</p>
                    </div>

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}" id="loginForm" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <div class="relative mt-1.5">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                    </svg>
                                </span>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                       placeholder="nama@yaygroup.id"
                                       class="block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white pl-11 py-2.5 text-sm placeholder-gray-400 focus:border-violet-500 focus:ring-violet-500">
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                            <div class="relative mt-1.5">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                </span>
                                <input id="password" type="password" name="password" required autocomplete="current-password"
                                       placeholder="••••••••"
                                       class="block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white pl-11 pr-11 py-2.5 text-sm placeholder-gray-400 focus:border-violet-500 focus:ring-violet-500">
                                <button type="button" id="togglePassword" title="Tampilkan password"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                    <svg id="eyeOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <svg id="eyeClosed" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input id="remember_me" type="checkbox" name="remember"
                                   class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-violet-600 focus:ring-violet-500">
                            <span class="text-sm text-gray-600 dark:text-gray-300">Ingat saya</span>
                        </label>

                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-violet-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-purple-500/25 hover:from-indigo-500 hover:via-purple-500 hover:to-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition disabled:opacity-70">
                            Masuk
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </button>
                    </form>

                    <div class="mt-8 flex items-start gap-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/60 p-3.5 text-xs text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4 shrink-0 mt-0.5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        <span>Belum punya akun atau lupa password? Hubungi <b class="font-semibold text-gray-700 dark:text-gray-200">Tim IT</b> untuk dibuatkan atau direset.</span>
                    </div>
                </div>

                <p class="lg:hidden text-center text-xs text-gray-400 mt-6">&copy; {{ date('Y') }} PT. YAY Enak Semua · Tim IT</p>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            var input = document.getElementById('password');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            document.getElementById('eyeOpen').classList.toggle('hidden', show);
            document.getElementById('eyeClosed').classList.toggle('hidden', !show);
            this.title = show ? 'Sembunyikan password' : 'Tampilkan password';
        });

        document.getElementById('loginForm').addEventListener('submit', function () {
            var btn = this.querySelector('button[type="submit"]');
            // Disable ditunda satu tick: kalau langsung disabled di sini, Safari/WebKit
            // membatalkan submit form yang sedang berjalan (tombolnya sendiri jadi disabled).
            setTimeout(function () {
                btn.disabled = true;
                btn.innerHTML = '<svg class="inline-block w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Memproses...';
            }, 0);
        });
    </script>

    @vite(['resources/js/dark-mode.js'])
</body>
</html>
