<x-app-layout title="BAST {{ $bast->handover_date_label }}">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Berita Acara Serah Terima</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-3 bg-green-100 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="p-3 bg-red-100 text-red-700 rounded-lg text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $bast->city }}, {{ $bast->handover_date_label }}</p>
                        <p class="text-xs text-gray-400">Dibuat oleh {{ $bast->creator->name ?? '-' }} · {{ $bast->created_at->diffForHumans() }}</p>
                    </div>
                    @if ($bast->isSigned())
                        <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700">Sudah ditandatangani</span>
                    @else
                        <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">Belum upload tanda tangan</span>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Pihak Pertama</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $bast->first_party_name }}</p>
                        <p class="text-gray-600 dark:text-gray-300">{{ $bast->first_party_position }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Pihak Kedua</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $bast->second_party_name }}</p>
                        <p class="text-gray-600 dark:text-gray-300">{{ $bast->second_party_position }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-3 py-2">No.</th>
                                <th class="px-3 py-2">Jenis</th>
                                <th class="px-3 py-2">Detail</th>
                                <th class="px-3 py-2">Serial Number</th>
                                <th class="px-3 py-2">Jml</th>
                                <th class="px-3 py-2">Kondisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bast->items as $item)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ $item->item_type }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">
                                        {{ $item->detail }}
                                        @if ($item->asset)
                                            <a href="{{ route('assets.show', $item->asset) }}" class="block text-xs font-mono text-blue-600 dark:text-blue-400 hover:underline">{{ $item->asset->asset_code }}</a>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ $item->serial_number ?: '-' }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ $item->quantity }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white">{{ $item->condition }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('bast.pdf', $bast) }}" target="_blank"
                       class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600 transition-all duration-150 hover:scale-105 active:scale-95">
                        Cetak / Unduh PDF
                    </a>
                    <form action="{{ route('bast.destroy', $bast) }}" method="POST"
                          onsubmit="event.preventDefault(); confirmAction('BAST ini beserta file scan tanda tangannya akan dihapus.', () => this.submit(), 'Hapus BAST?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30">Hapus</button>
                    </form>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-1">Scan BAST yang Sudah Ditandatangani</h3>

                @if ($bast->isSigned())
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                        <a href="{{ route('bast.signed.show', $bast) }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $bast->signed_file_original_name ?: 'Lihat file' }}</a>
                        <span class="text-gray-400">· di-upload {{ $bast->signed_uploaded_at?->diffForHumans() }}</span>
                    </p>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Cetak PDF di atas, tandatangani kedua pihak, lalu upload hasil scan / foto di sini sebagai arsip.
                    </p>
                @endif

                <form action="{{ route('bast.signed.upload', $bast) }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                    @csrf
                    <input type="file" name="signed_file" required accept=".pdf,.jpg,.jpeg,.png"
                           class="text-sm text-gray-700 dark:text-gray-300">
                    <button type="submit" class="bg-gray-800 dark:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 dark:hover:bg-gray-500">
                        {{ $bast->isSigned() ? 'Ganti File' : 'Upload' }}
                    </button>
                    <span class="text-xs text-gray-400">PDF, JPG, atau PNG · maks 10 MB</span>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
