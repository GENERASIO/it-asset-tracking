<x-app-layout title="Edit User">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit User</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6 transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-4">
                    @csrf @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                               class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                        @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                               class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                        @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Password <span class="text-xs text-gray-400">(kosongkan jika tidak ingin ganti password)</span>
                        </label>
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
                            @foreach (['super_admin', 'it_staff', 'user'] as $role)
                                <option value="{{ $role }}" @selected(old('role', $user->role) == $role)>
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
                                <option value="{{ $loc->id }}" @selected(old('location_id', $user->location_id) == $loc->id)>
                                    {{ $loc->name }} ({{ $loc->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('location_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Employee ID (opsional)</label>
                        <input type="text" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}"
                               class="mt-1 w-full border-gray-300 rounded-lg shadow-sm">
                        @error('employee_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="is_active" type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $user->is_active))
                               @disabled($user->id === auth()->id())
                               class="rounded border-gray-300 text-brand-600 shadow-sm disabled:opacity-50">
                        <label for="is_active" class="text-sm font-medium text-gray-700">User Aktif</label>
                        @if ($user->id === auth()->id())
                            <span class="text-xs text-gray-400">(tidak bisa nonaktifkan akun sendiri)</span>
                        @endif
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-lg transition-all duration-150 hover:scale-105 active:scale-95">
                            Perbarui
                        </button>
                        <a href="{{ route('users.index') }}" class="px-4 py-2 text-gray-600">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                if (btn) {
                    // Disable ditunda satu tick: kalau langsung disabled di sini, Safari/WebKit
                    // membatalkan submit form yang sedang berjalan (tombolnya sendiri jadi disabled).
                    setTimeout(() => {
                        btn.disabled = true;
                        btn.innerHTML = '<svg class="inline-block w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Memproses...';
                    }, 0);
                }
            });
        });
    </script>
</x-app-layout>
