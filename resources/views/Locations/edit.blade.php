<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Lokasi</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <form action="{{ route('locations.update', $location) }}" method="POST" class="space-y-4">
                    @csrf @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Lokasi/Outlet</label>
                        <input type="text" name="name" value="{{ old('name', $location->name) }}"
                               class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                        @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kode Singkat</label>
                        <input type="text" name="code" value="{{ old('code', $location->code) }}"
                               class="mt-1 w-full border-gray-300 rounded-lg shadow-sm uppercase">
                        @error('code') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Alamat (opsional)</label>
                        <input type="text" name="address" value="{{ old('address', $location->address) }}"
                               class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                        @error('address') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg">
                            Perbarui
                        </button>
                        <a href="{{ route('locations.index') }}" class="px-4 py-2 text-gray-600">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout> 