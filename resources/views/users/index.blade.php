<x-app-layout title="Kelola User">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Kelola User</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-3 bg-green-50 text-green-700 ring-1 ring-green-200 dark:bg-green-900/30 dark:text-green-300 dark:ring-green-800 rounded-xl text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-3 bg-red-50 text-red-700 ring-1 ring-red-200 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800 rounded-xl text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-100 dark:ring-gray-700/60 p-6">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                    <form method="GET" class="flex flex-wrap gap-2">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Cari nama / email..."
                               class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                        <button type="submit" class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 px-4 py-2 rounded-lg text-sm font-medium">Cari</button>
                    </form>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('users.create') }}"
                           class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600 whitespace-nowrap transition-all duration-150 hover:scale-105 active:scale-95">
                            + Tambah User
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700/60 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3">Lokasi</th>
                                <th class="px-4 py-3">Status Aktif</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors">
                                    @php
                                        $initials = collect(explode(' ', trim($user->name)))->filter()->map(fn ($n) => mb_strtoupper(mb_substr($n, 0, 1)))->take(2)->join('');
                                        $roleStyle = [
                                            'super_admin' => ['Super Admin', 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'],
                                            'it_staff' => ['IT Staff', 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
                                            'user' => ['User', 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'],
                                        ][$user->role] ?? [ucfirst(str_replace('_', ' ', $user->role)), 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'];
                                    @endphp
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <span class="shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center text-xs font-semibold text-white">{{ $initials ?: '?' }}</span>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $user->email ?: '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap {{ $roleStyle[1] }}">{{ $roleStyle[0] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $user->location->name ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($user->is_active)
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
                                        @else
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        @if ($user->role === 'super_admin' && auth()->user()->role !== 'super_admin')
                                            <span class="text-xs text-gray-400" title="Hanya Super Admin yang bisa mengubah akun ini">🔒</span>
                                        @else
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('users.edit', $user) }}" title="Edit"
                                               class="inline-flex p-1.5 rounded-lg text-brand-500 hover:bg-brand-50 dark:hover:bg-gray-700">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zM19.5 7.125L16.875 4.5"/></svg>
                                            </a>
                                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline"
                                                  onsubmit="event.preventDefault(); confirmAction('User yang sudah dihapus tidak bisa dikembalikan.', () => this.submit(), 'Hapus user ini?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="Hapus"
                                                        class="inline-flex p-1.5 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-gray-700">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">
                                        Belum ada user.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
