<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Detail Aset</h2>
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

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                <div class="flex flex-col sm:flex-row sm:justify-between items-start gap-4">
                    <div class="flex gap-4">
                        @if ($asset->photo)
                            <img src="{{ Storage::url($asset->photo) }}" class="w-20 h-20 object-cover rounded-lg border shrink-0">
                        @else
                            <div class="w-20 h-20 rounded-lg border bg-gray-50 flex items-center justify-center text-gray-300 text-xs shrink-0">
                                No Photo
                            </div>
                        @endif
                        <div>
                            <p class="font-mono font-bold text-2xl text-gray-800 dark:text-white">{{ $asset->asset_code }}</p>
                            <h3 class="text-lg text-gray-700 dark:text-gray-300 mt-1">{{ $asset->name }}</h3>
                            <span class="inline-block mt-2 px-2 py-1 rounded-full text-xs font-medium {{ $statusColor }}">
                                {{ ucfirst(str_replace('_',' ',$asset->status)) }}
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto whitespace-nowrap">
                        @if(auth()->user()->role === 'super_admin' || auth()->user()->role === 'it_staff')
                            <a href="{{ route('barcode.print', $asset) }}" target="_blank"
                               class="text-center bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm transition-all duration-150 hover:scale-105 active:scale-95">Cetak Label</a>
                            <a href="{{ route('assets.edit', $asset) }}"
                               class="text-center bg-brand-500 hover:bg-brand-600 text-white px-3 py-2 rounded-lg text-sm transition-all duration-150 hover:scale-105 active:scale-95">Edit</a>
                        @endif
                        <a href="{{ auth()->user()->role === 'super_admin' || auth()->user()->role === 'it_staff' ? route('assets.index') : route('dashboard') }}"
                           class="text-center text-gray-500 px-3 py-2 text-sm">Kembali</a>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6 text-sm">
                    <div>
                        <span class="text-gray-500">Kategori</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $asset->category->name }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Lokasi</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $asset->location->name }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Merk / Model</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $asset->brand ?? '-' }} {{ $asset->model ?? '' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Serial Number</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $asset->serial_number ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Dipegang Oleh</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $asset->assignedUser->name ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Tanggal Pembelian</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ optional($asset->purchase_date)->format('d M Y') ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Harga Beli</span>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $asset->purchase_price ? 'Rp ' . number_format($asset->purchase_price, 0, ',', '.') : '-' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-gray-500">Garansi Sampai</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ optional($asset->warranty_expired_at)->format('d M Y') ?? '-' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="text-gray-500">Spesifikasi</span>
                        <p class="font-medium text-gray-900 dark:text-white whitespace-pre-line">{{ $asset->specification ?? '-' }}</p>
                    </div>
                </div>
            </div>

            @if(auth()->user()->role === 'super_admin' || auth()->user()->role === 'it_staff')
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Check-in / Check-out</h3>

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
                        <button type="submit" class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600 transition-all duration-150 hover:scale-105 active:scale-95">
                            Check-out Aset
                        </button>
                    </form>
                @elseif ($asset->status === 'in_use')
                    <p class="text-sm text-gray-600 mb-3">
                        Sedang dipegang oleh: <span class="font-medium text-gray-900 dark:text-white">{{ $asset->assignedUser->name ?? '-' }}</span>
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
                        <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-700 transition-all duration-150 hover:scale-105 active:scale-95">
                            Check-in Aset
                        </button>
                    </form>
                @else
                    <p class="text-sm text-gray-400">
                        Aset tidak bisa di-checkout/checkin pada status "{{ ucfirst(str_replace('_',' ',$asset->status)) }}".
                    </p>
                @endif
            </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Riwayat Mutasi / Check-in-Check-out</h3>
                @forelse ($asset->logs as $log)
                    <div class="border-b dark:border-gray-700 py-2 text-sm">
                        <span class="font-medium text-gray-900 dark:text-white">{{ ucfirst(str_replace('_',' ',$log->action)) }}</span>
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

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Riwayat Maintenance</h3>

                @if(auth()->user()->role === 'super_admin' || auth()->user()->role === 'it_staff')
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
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700 transition-all duration-150 hover:scale-105 active:scale-95">
                        Laporkan Maintenance
                    </button>
                </form>
                @endif

                @forelse ($asset->maintenanceLogs as $log)
                    <div class="border-b dark:border-gray-700 py-2 text-sm">
                        <span class="font-medium text-gray-900 dark:text-white">{{ $log->issue }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 ml-2">{{ ucfirst(str_replace('_',' ',$log->status)) }}</span>
                        <p class="text-gray-500 text-xs mt-1">Dilaporkan: {{ $log->reported_at->format('d M Y') }}</p>
                        @if ($log->action_taken)
                            <p class="text-gray-500 text-xs">Tindakan: {{ $log->action_taken }}</p>
                        @endif

                        @if ($log->status !== 'done' && (auth()->user()->role === 'super_admin' || auth()->user()->role === 'it_staff'))
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
                                <button type="submit" class="bg-gray-700 text-white text-xs px-3 py-1 rounded transition-all duration-150 hover:scale-105 active:scale-95">
                                    Update
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-400 text-sm">Belum ada riwayat maintenance.</p>
                @endforelse
            </div>

            @php
                $historyItems = collect();

                foreach ($asset->logs as $log) {
                    $description = match ($log->action) {
                        'check_out' => 'Check-out ke ' . ($log->toUser->name ?? '-'),
                        'check_in' => 'Check-in dari ' . ($log->fromUser->name ?? '-') . ', status jadi ' . ucfirst(str_replace('_', ' ', $log->status_after)),
                        'transfer' => 'Transfer dari ' . ($log->fromUser->name ?? 'Gudang') . ' ke ' . ($log->toUser->name ?? 'Gudang'),
                        'status_change' => 'Status berubah dari ' . ucfirst(str_replace('_', ' ', $log->status_before)) . ' ke ' . ucfirst(str_replace('_', ' ', $log->status_after)),
                        default => ucfirst(str_replace('_', ' ', $log->action)),
                    };

                    $historyItems->push([
                        'type' => 'Mutasi — ' . ucfirst(str_replace('_', ' ', $log->action)),
                        'description' => $description,
                        'user' => $log->handledBy->name ?? '-',
                        'date' => $log->created_at,
                        'notes' => $log->notes,
                    ]);
                }

                foreach ($asset->maintenanceLogs as $log) {
                    $historyItems->push([
                        'type' => 'Maintenance',
                        'description' => $log->issue . ' (Status: ' . ucfirst(str_replace('_', ' ', $log->status)) . ')',
                        'user' => $log->createdBy->name ?? '-',
                        'date' => $log->created_at,
                        'notes' => $log->action_taken,
                    ]);
                }

                $historyItems = $historyItems->sortByDesc('date')->values();
            @endphp

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-4">Riwayat Lengkap</h3>

                @if ($historyItems->isEmpty())
                    <p class="text-gray-400 text-sm">Belum ada riwayat.</p>
                @else
                    <div class="relative border-l-2 border-gray-200 dark:border-gray-700 ml-2 space-y-6">
                        @foreach ($historyItems as $item)
                            <div class="relative pl-6">
                                <span class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-brand-500 border-2 border-white dark:border-gray-800"></span>
                                <p class="text-xs text-gray-400">{{ $item['type'] }}</p>
                                <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $item['description'] }}</p>
                                @if ($item['notes'])
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $item['notes'] }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">
                                    Oleh {{ $item['user'] }} · {{ $item['date']->translatedFormat('d F Y H:i') }}
                                    ({{ $item['date']->diffForHumans() }})
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>