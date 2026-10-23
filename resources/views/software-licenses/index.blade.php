<x-app-layout title="Lisensi Software">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Lisensi Software</h2>
    </x-slot>

    @php
        $statusClasses = [
            'licensed' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
            'subscription' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
            'grace' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
            'notification' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
            'unlicensed' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-3 bg-green-100 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
            @endif

            {{-- Ringkasan --}}
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach ([
                    ['Laptop terdata', $summary['assets_synced'], 'text-gray-900 dark:text-white'],
                    ['Office resmi', $summary['office_official'], 'text-green-600 dark:text-green-400'],
                    ['Office terindikasi tidak resmi', $summary['office_suspected'], 'text-red-600 dark:text-red-400'],
                    ['Windows terindikasi tidak resmi', $summary['windows_suspected'], 'text-red-600 dark:text-red-400'],
                    ['Perlu perhatian', $summary['needs_attention'], 'text-yellow-600 dark:text-yellow-400'],
                ] as [$label, $value, $color])
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                        <p class="text-2xl font-semibold {{ $color }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Lisensi yang dibeli vs dipakai --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-1">Lisensi yang Dibeli</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    "Terpakai" dihitung dari laptop yang lisensinya resmi dan nama edisinya mengandung kata kunci. Laptop yang terindikasi tidak resmi tidak ikut dihitung.
                </p>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-2">Nama</th>
                                <th class="px-4 py-2">Jenis</th>
                                <th class="px-4 py-2 text-right">Terpakai / Dibeli</th>
                                <th class="px-4 py-2">Berlaku Sampai</th>
                                <th class="px-4 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($entitlements as $entitlement)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-2">
                                        <p class="text-gray-900 dark:text-white">{{ $entitlement->name }}</p>
                                        <p class="text-xs text-gray-500 font-mono">cocok: "{{ $entitlement->match }}"</p>
                                    </td>
                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ \App\Models\SoftwareEntitlement::TYPES[$entitlement->license_type] ?? $entitlement->license_type }}</td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">
                                        <span class="font-semibold {{ $entitlement->over ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $entitlement->used }} / {{ $entitlement->quantity }}</span>
                                        @if ($entitlement->over)
                                            <p class="text-xs text-red-600 dark:text-red-400">Melebihi {{ $entitlement->used - $entitlement->quantity }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        @if ($entitlement->expires_at)
                                            <span class="{{ $entitlement->expiring ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-600 dark:text-gray-300' }}">
                                                {{ $entitlement->expires_at->translatedFormat('d M Y') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">Permanen</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('software-licenses.edit', $entitlement) }}" title="Edit"
                                               class="inline-flex p-1.5 rounded-lg text-brand-500 hover:bg-brand-50 dark:hover:bg-gray-700">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zM19.5 7.125L16.875 4.5"/></svg>
                                            </a>
                                            <form action="{{ route('software-licenses.destroy', $entitlement) }}" method="POST" class="inline"
                                                  onsubmit="event.preventDefault(); confirmAction('Data lisensi ini akan dihapus.', () => this.submit(), 'Hapus lisensi?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="Hapus"
                                                        class="inline-flex p-1.5 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-gray-700">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">Belum ada lisensi yang diinput.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="{{ route('software-licenses.store') }}" class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                    @csrf
                    <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">Tambah Lisensi yang Dibeli</h4>
                    @include('software-licenses.partials.form', ['entitlement' => null])
                    <button type="submit" class="mt-4 bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600 transition-all duration-150 hover:scale-105 active:scale-95">
                        Simpan
                    </button>
                </form>
            </div>

            {{-- Perlu perhatian (lisensi resmi) --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-1">Perlu Perhatian</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Lisensi resmi yang tidak aktif, dalam masa tenggang, atau habis dalam {{ $reminderDays }} hari. Juga muncul di lonceng notifikasi.
                </p>
                @include('software-licenses.partials.license-table', ['rows' => $attention, 'empty' => 'Tidak ada lisensi resmi yang perlu perhatian.'])
            </div>

            {{-- Terindikasi tidak resmi --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-1">Terindikasi Tidak Resmi</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Laptop yang perlu diganti ke lisensi resmi. Tidak semua jenis crack bisa terdeteksi, jadi daftar ini bisa lebih sedikit dari kenyataannya.
                </p>
                @include('software-licenses.partials.license-table', ['rows' => $suspected, 'empty' => 'Tidak ada lisensi yang terindikasi tidak resmi.'])
            </div>

            {{-- Edisi terdeteksi --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-1">Edisi Terdeteksi</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Nama edisi persis seperti yang dilaporkan Windows. Pakai sebagian nama ini (mis. <span class="font-mono">HomeBusiness2021</span>) sebagai kata kunci saat menambah lisensi.
                </p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-2">Produk</th>
                                <th class="px-4 py-2">Edisi</th>
                                <th class="px-4 py-2 text-right">Laptop</th>
                                <th class="px-4 py-2 text-right">Terindikasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($editions as $edition)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-2 uppercase text-xs text-gray-500">{{ $edition['product'] }}</td>
                                    <td class="px-4 py-2 font-mono text-xs text-gray-900 dark:text-white break-all">{{ $edition['name'] }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900 dark:text-white">{{ $edition['total'] }}</td>
                                    <td class="px-4 py-2 text-right {{ $edition['suspected'] ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-400' }}">{{ $edition['suspected'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-400">Belum ada laptop yang mengirim data lisensi. Data muncul setelah agent versi terbaru check-in.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
