<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Aset</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-3 bg-green-100 text-green-700 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-3 bg-red-100 text-red-700 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @php
                $statusColor = match($asset->status) {
                    'available' => 'bg-green-100 text-green-700',
                    'in_use' => 'bg-blue-100 text-blue-700',
                    'maintenance' => 'bg-yellow-100 text-yellow-700',
                    'broken' => 'bg-red-100 text-red-700',
                    'retired' => 'bg-gray-100 text-gray-500',
                    default => 'bg-gray-100 text-gray-500',
                };
            @endphp

            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-start gap-4">
                    <div class="flex gap-4">
                        @if ($asset->photo)
                            <img src="{{ Storage::url($asset->photo) }}" class="w-20 h-20 object-cover rounded-lg border">
                        @else
                            <div class="w-20 h-20 rounded-lg border bg-gray-50 flex items-center justify-center text-gray-300 text-xs">
                                No Photo
                            </div>
                        @endif
                        <div>
                            <p class="font-mono font-bold text-2xl text-gray-800">{{ $asset->asset_code }}</p>
                            <h3 class="text-lg text-gray-700 mt-1">{{ $asset->name }}</h3>
                            <span class="inline-block mt-2 px-2 py-1 rounded-full text-xs font-medium {{ $statusColor }}">
                                {{ ucfirst(str_replace('_',' ',$asset->status)) }}
                            </span>
                        </div>
                    </div>
                    <div class="space-x-2 whitespace-nowrap">
                        <a href="{{ route('barcode.print', $asset) }}" target="_blank"
                           class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm">Cetak Label</a>
                        <a href="{{ route('assets.edit', $asset) }}"
                           class="bg-indigo-600 text-white px-3 py-2 rounded-lg text-sm">Edit</a>
                        <a href="{{ route('assets.index') }}"
                           class="text-gray-500 px-3 py-2 text-sm">Kembali</a>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-6 text-sm">
                    <div>
                        <span class="text-gray-500">Kategori</span>
                        <p class="font-medium">{{ $asset->category->name }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Lokasi</span>
                        <p class="font-medium">{{ $asset->location->name }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Merk / Model</span>
                        <p class="font-medium">{{ $asset->brand ?? '-' }} {{ $asset->model ?? '' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Serial Number</span>
                        <p class="font-medium">{{ $asset->serial_number ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Dipegang Oleh</span>
                        <p class="font-medium">{{ $asset->assignedUser->name ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Tanggal Pembelian</span>
                        <p class="font-medium">{{ optional($asset->purchase_date)->format('d M Y') ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Harga Beli</span>
                        <p class="font-medium">
                            {{ $asset->purchase_price ? 'Rp ' . number_format($asset->purchase_price, 0, ',', '.') : '-' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-gray-500">Garansi Sampai</span>
                        <p class="font-medium">{{ optional($asset->warranty_expired_at)->format('d M Y') ?? '-' }}</p>
                    </div>
                    <div class="col-span-2">
                        <span class="text-gray-500">Spesifikasi</span>
                        <p class="font-medium whitespace-pre-line">{{ $asset->specification ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-gray-700 mb-3">Check-in / Check-out</h3>

                @if ($asset->status === 'available')
                    <form action="{{ route('assets.checkout', $asset) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="text-sm font-medium text-gray-700">Karyawan Peminjam</label>
                            <select name="to_user_id" required
                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm">
                                <option value="">-- Pilih Karyawan --</option>
                                @foreach (\App\Models\User::orderBy('name')->get() as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Catatan (opsional)</label>
                            <textarea name="notes" rows="2"
                                      class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm"></textarea>
                        </div>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">
                            Check-out Aset
                        </button>
                    </form>
                @elseif ($asset->status === 'in_use')
                    <p class="text-sm text-gray-600 mb-3">
                        Sedang dipegang oleh: <span class="font-medium">{{ $asset->assignedUser->name ?? '-' }}</span>
                    </p>
                    <form action="{{ route('assets.checkin', $asset) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="text-sm font-medium text-gray-700">Status Baru</label>
                            <select name="new_status" required
                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm">
                                <option value="available">Available (siap dipakai lagi)</option>
                                <option value="maintenance">Maintenance (perlu perbaikan)</option>
                                <option value="broken">Broken (rusak)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Catatan (opsional)</label>
                            <textarea name="notes" rows="2"
                                      class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm"></textarea>
                        </div>
                        <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-700">
                            Check-in Aset
                        </button>
                    </form>
                @else
                    <p class="text-sm text-gray-400">
                        Aset tidak bisa di-checkout/checkin pada status "{{ ucfirst(str_replace('_',' ',$asset->status)) }}".
                    </p>
                @endif
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-gray-700 mb-3">Riwayat Mutasi / Check-in-Check-out</h3>
                @forelse ($asset->logs as $log)
                    <div class="border-b py-2 text-sm">
                        <span class="font-medium">{{ ucfirst(str_replace('_',' ',$log->action)) }}</span>
                        — {{ $log->fromUser->name ?? 'Gudang' }} → {{ $log->toUser->name ?? 'Gudang' }}
                        <span class="text-gray-400 block text-xs">{{ $log->created_at->format('d M Y H:i') }}</span>
                        @if ($log->notes)
                            <p class="text-gray-500 text-xs mt-1">{{ $log->notes }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-400 text-sm">Belum ada riwayat mutasi.</p>
                @endforelse
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-gray-700 mb-3">Riwayat Maintenance</h3>

                <form action="{{ route('maintenance-logs.store', $asset) }}" method="POST" class="space-y-3 mb-4 pb-4 border-b">
                    @csrf
                    <div>
                        <label class="text-sm font-medium text-gray-700">Masalah/Keluhan</label>
                        <textarea name="issue" rows="2" required
                                  class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm"></textarea>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Teknisi (opsional)</label>
                        <input type="text" name="technician"
                               class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Tanggal Lapor</label>
                        <input type="date" name="reported_at" required
                               value="{{ old('reported_at', now()->format('Y-m-d')) }}"
                               class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm text-sm">
                    </div>
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">
                        Laporkan Maintenance
                    </button>
                </form>

                @forelse ($asset->maintenanceLogs as $log)
                    <div class="border-b py-2 text-sm">
                        <span class="font-medium">{{ $log->issue }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 ml-2">{{ ucfirst(str_replace('_',' ',$log->status)) }}</span>
                        <p class="text-gray-500 text-xs mt-1">Dilaporkan: {{ $log->reported_at->format('d M Y') }}</p>
                        @if ($log->action_taken)
                            <p class="text-gray-500 text-xs">Tindakan: {{ $log->action_taken }}</p>
                        @endif

                        @if ($log->status !== 'done')
                            <form action="{{ route('maintenance-logs.update', $log) }}" method="POST"
                                  class="border-t pt-2 mt-2 text-xs space-y-1">
                                @csrf
                                @method('PUT')
                                <select name="status" class="block w-full border-gray-300 rounded-lg shadow-sm text-xs">
                                    <option value="open" @selected($log->status === 'open')>Open</option>
                                    <option value="in_progress" @selected($log->status === 'in_progress')>In Progress</option>
                                    <option value="done" @selected($log->status === 'done')>Done</option>
                                </select>
                                <textarea name="action_taken" rows="1" placeholder="Tindakan yang dilakukan..."
                                          class="block w-full border-gray-300 rounded-lg shadow-sm text-xs"></textarea>
                                <input type="number" name="cost" step="0.01" placeholder="Biaya (Rp)"
                                       class="block w-full border-gray-300 rounded-lg shadow-sm text-xs">
                                <div>
                                    <label class="text-xs text-gray-500">Tanggal Selesai (isi kalau status Done)</label>
                                    <input type="date" name="resolved_at"
                                           class="block w-full border-gray-300 rounded-lg shadow-sm text-xs">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Status Aset Jika Done</label>
                                    <select name="new_asset_status" class="block w-full border-gray-300 rounded-lg shadow-sm text-xs">
                                        <option value="available">Available</option>
                                        <option value="broken">Broken</option>
                                        <option value="retired">Retired</option>
                                    </select>
                                </div>
                                <button type="submit" class="bg-gray-700 text-white text-xs px-3 py-1 rounded">
                                    Update
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-400 text-sm">Belum ada riwayat maintenance.</p>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>