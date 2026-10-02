<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $asset->asset_code }} · IT Asset Tracking</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
</head>
<body class="font-sans antialiased bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-sm">
        <div class="flex items-center justify-center gap-2 mb-4">
            <div class="w-9 h-9 rounded-lg bg-brand-500 text-white flex items-center justify-center font-bold">Y</div>
            <div>
                <p class="font-semibold text-gray-800 text-sm leading-tight">PT. YAY Enak Semua</p>
                <p class="text-xs text-gray-500 leading-tight">IT Asset Tracking</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            @if ($asset->photo)
                <img src="{{ asset('uploads/assets/' . $asset->photo) }}" class="w-full h-48 object-cover">
            @else
                <div class="w-full h-32 bg-gray-50 flex items-center justify-center text-gray-300">
                    <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
            @endif

            <div class="p-5">
                @php
                    $statusColors = [
                        'available' => 'bg-green-100 text-green-700',
                        'in_use' => 'bg-blue-100 text-blue-700',
                        'maintenance' => 'bg-yellow-100 text-yellow-700',
                        'broken' => 'bg-red-100 text-red-700',
                        'retired' => 'bg-gray-100 text-gray-500',
                    ];
                @endphp
                <span class="inline-block px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$asset->status] ?? 'bg-gray-100 text-gray-500' }}">
                    {{ ucfirst(str_replace('_', ' ', $asset->status)) }}
                </span>

                <p class="font-mono font-bold text-xl text-gray-800 mt-3">{{ $asset->asset_code }}</p>
                <p class="text-gray-600">{{ $asset->name ?: '-' }}</p>

                <div class="border-t border-gray-100 mt-4 pt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Kategori</span>
                        <span class="text-gray-700">{{ $asset->category->name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Lokasi</span>
                        <span class="text-gray-700">{{ $asset->location->name ?? '-' }}</span>
                    </div>
                    @if ($asset->brand || $asset->model)
                        <div class="flex justify-between">
                            <span class="text-gray-400">Merk/Model</span>
                            <span class="text-gray-700">{{ trim(($asset->brand ?? '').' '.($asset->model ?? '')) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-gray-400 mt-4">
            Info terbatas untuk publik. Hubungi tim IT untuk detail lebih lanjut.
        </p>
    </div>

</body>
</html>
