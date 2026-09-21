<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Lokasi / Outlet</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-300">Daftar Lokasi</h3>
                    <a href="{{ route('locations.create') }}"
                       class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600">
                        + Tambah Lokasi
                    </a>
                </div>

                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-2">Nama</th>
                            <th class="px-4 py-2">Kode</th>
                            <th class="px-4 py-2">Alamat</th>
                            <th class="px-4 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($locations as $location)
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $location->name }}</td>
                                <td class="px-4 py-2 font-mono text-gray-900 dark:text-white">{{ $location->code }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $location->address ?? '-' }}</td>
                                <td class="px-4 py-2 text-right space-x-2">
                                    <a href="{{ route('locations.edit', $location) }}"
                                       class="text-brand-500 hover:underline">Edit</a>
                                    <form action="{{ route('locations.destroy', $location) }}"
                                          method="POST" class="inline"
                                          onsubmit="return confirm('Yakin hapus lokasi ini?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-400">
                                    Belum ada lokasi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $locations->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>