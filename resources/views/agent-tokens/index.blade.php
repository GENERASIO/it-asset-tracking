<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Kelola Agent Token</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-3 bg-green-100 text-green-700 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('newToken'))
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-sm space-y-3">
                    <p class="font-medium text-yellow-800">Token baru (salin sekarang, tidak akan ditampilkan lagi):</p>
                    <code class="block bg-white border border-yellow-300 rounded px-3 py-2 font-mono text-xs break-all select-all">{{ session('newToken') }}</code>

                    <div>
                        <p class="text-yellow-800 mb-2">Atau langsung download paket agent yang sudah berisi token ini (tidak perlu edit config manual):</p>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('agent-tokens.download', 'windows') }}"
                               class="bg-yellow-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-900 transition-all duration-150 hover:scale-105 active:scale-95">
                                ⬇️ Download Agent Windows (.zip)
                            </a>
                            <a href="{{ route('agent-tokens.download', 'macos') }}"
                               class="bg-yellow-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-900 transition-all duration-150 hover:scale-105 active:scale-95">
                                ⬇️ Download Agent macOS (.zip)
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Buat Token Baru</h3>
                <form method="POST" action="{{ route('agent-tokens.store') }}" class="flex flex-wrap gap-2">
                    @csrf
                    <input type="text" name="name" required placeholder="Nama token, mis. Agent Windows Batch 1"
                           class="flex-1 min-w-[240px] border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <button type="submit" class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600 transition-all duration-150 hover:scale-105 active:scale-95">
                        Buat Token
                    </button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Daftar Token</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-2">Nama</th>
                                <th class="px-4 py-2">Dibuat Oleh</th>
                                <th class="px-4 py-2">Terakhir Dipakai</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tokens as $token)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $token->name }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $token->creator->name ?? '-' }}</td>
                                    <td class="px-4 py-2 text-gray-500">
                                        {{ $token->last_used_at?->diffForHumans() ?? 'Belum pernah' }}
                                    </td>
                                    <td class="px-4 py-2">
                                        @if ($token->revoked_at)
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Dicabut</span>
                                        @else
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">Aktif</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        @unless ($token->revoked_at)
                                            <form method="POST" action="{{ route('agent-tokens.destroy', $token) }}"
                                                  onsubmit="return confirm('Cabut token ini? Agent yang memakainya akan berhenti bisa check-in.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Cabut</button>
                                            </form>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">Belum ada token.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Aktivitas Agent Terbaru</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-2">Kode Aset</th>
                                <th class="px-4 py-2">Hostname</th>
                                <th class="px-4 py-2">OS</th>
                                <th class="px-4 py-2">Terakhir Check-in</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentCheckins as $checkin)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-2 font-mono">
                                        <a href="{{ route('assets.show', $checkin) }}" class="text-brand-500 hover:underline">{{ $checkin->asset_code }}</a>
                                    </td>
                                    <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $checkin->hostname ?? '-' }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $checkin->os_label ?? '-' }}</td>
                                    <td class="px-4 py-2">
                                        <span class="text-xs px-2 py-1 rounded-full {{ $checkin->last_seen_at->gt(now()->subDays(2)) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                            {{ $checkin->last_seen_at->diffForHumans() }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-400">Belum ada laptop yang check-in.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-sm text-gray-600 dark:text-gray-400 space-y-2">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Cara Pasang di Laptop</h3>
                <p>
                    1. Klik <strong>Buat Token</strong> di atas, lalu langsung download paket agent (sudah berisi token, tinggal pakai) dari tombol yang muncul.
                </p>
                <p>
                    2. Extract zip-nya di laptop tujuan, lalu jalankan <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">install.ps1</code> (klik kanan → Run with PowerShell) untuk Windows, atau <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">install.sh</code> untuk macOS.
                </p>
                <p>
                    3. Selesai — laptop otomatis check-in saat login dan setiap hari ke <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{{ url('/api/agent/checkin') }}</code>, muncul otomatis di daftar Aset.
                </p>
                <p class="text-xs text-gray-400 pt-2 border-t dark:border-gray-700">
                    Tombol download hanya tersedia sesaat setelah token dibuat (sama seperti tampilan token-nya). Kalau sudah tertutup, buat token baru untuk mendapat paket download lagi.
                </p>
            </div>

        </div>
    </div>
</x-app-layout>
