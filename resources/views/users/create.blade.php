<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah User</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                        @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                        @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password"
                               class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                        @error('password') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation"
                               class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="role" class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">-- Pilih Role --</option>
                            @foreach (['super_admin', 'it_staff', 'user'] as $role)
                                <option value="{{ $role }}" @selected(old('role') == $role)>
                                    {{ ucfirst(str_replace('_', ' ', $role)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lokasi (opsional)</label>
                        <select name="location_id" class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">-- Tidak Ada --</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}" @selected(old('location_id') == $loc->id)>
                                    {{ $loc->name }} ({{ $loc->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('location_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Employee ID (opsional)</label>
                        <input type="text" name="employee_id" value="{{ old('employee_id') }}"
                               class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                        @error('employee_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg">
                            Simpan
                        </button>
                        <a href="{{ route('users.index') }}" class="px-4 py-2 text-gray-600">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
