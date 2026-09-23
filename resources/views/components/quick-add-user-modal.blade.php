@props(['targetSelectId', 'locations' => collect()])

<div x-data="quickAddUser('{{ $targetSelectId }}')" class="inline-block">
    <button type="button" @click="open = true" class="text-xs text-brand-500 hover:underline mt-1">
        + Tambah User Baru
    </button>

    <div x-show="open" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="open = false"></div>

        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6" @click.stop>
            <h3 class="font-semibold text-gray-800 dark:text-white mb-4">Tambah User Baru</h3>

            <template x-if="error">
                <div class="mb-3 p-2 bg-red-50 text-red-600 text-xs rounded" x-text="error"></div>
            </template>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Nama</label>
                    <input type="text" x-model="form.name" class="mt-1 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input type="email" x-model="form.email" class="mt-1 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm text-sm">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Password</label>
                        <input type="password" x-model="form.password" class="mt-1 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Konfirmasi</label>
                        <input type="password" x-model="form.password_confirmation" class="mt-1 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Role</label>
                    <select x-model="form.role" class="mt-1 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm text-sm">
                        <option value="user">User</option>
                        <option value="it_staff">IT Staff</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Lokasi (opsional)</label>
                    <select x-model="form.location_id" class="mt-1 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm text-sm">
                        <option value="">-- Tidak Ada --</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Employee ID (opsional)</label>
                    <input type="text" x-model="form.employee_id" class="mt-1 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm text-sm">
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-5">
                <button type="button" @click="open = false" class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300">Batal</button>
                <button type="button" @click="submit()" :disabled="loading"
                        class="px-4 py-1.5 bg-brand-500 hover:bg-brand-600 text-white rounded-lg text-sm disabled:opacity-50 transition">
                    <span x-show="!loading">Simpan</span>
                    <span x-show="loading">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function quickAddUser(targetSelectId) {
        return {
            open: false,
            loading: false,
            error: null,
            form: { name: '', email: '', password: '', password_confirmation: '', role: 'user', location_id: '', employee_id: '' },
            submit() {
                this.loading = true;
                this.error = null;

                fetch('{{ route('users.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.form),
                })
                .then(async (res) => {
                    const data = await res.json();
                    if (!res.ok) {
                        const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Gagal menyimpan user.');
                        throw new Error(firstError);
                    }

                    const select = document.getElementById(targetSelectId);
                    const option = document.createElement('option');
                    option.value = data.user.id;
                    option.textContent = data.user.name;
                    select.appendChild(option);
                    select.value = data.user.id;

                    this.open = false;
                    this.form = { name: '', email: '', password: '', password_confirmation: '', role: 'user', location_id: '', employee_id: '' };
                })
                .catch((err) => {
                    this.error = err.message;
                })
                .finally(() => {
                    this.loading = false;
                });
            },
        };
    }
</script>
