<x-mobile-layout title="Scan Aset">
    <div class="p-4 max-w-md mx-auto">
        <div class="flex items-center justify-between mb-3">
            <h1 class="text-lg font-bold text-gray-800">📷 Scan Aset</h1>
            <a href="{{ route('assets.index') }}" class="text-sm text-brand-500">Daftar Aset</a>
        </div>

        <div id="camera-error" class="hidden mb-3 bg-red-50 text-red-700 p-3 rounded-lg text-sm"></div>

        <div id="reader" class="w-full rounded-lg overflow-hidden border border-gray-300"></div>

        <div id="result" class="mt-4 hidden">
            <div class="bg-white shadow rounded-lg p-4">
                <p class="text-xs text-gray-500">Kode Aset</p>
                <p id="r-code" class="font-mono font-bold text-lg text-gray-800"></p>
                <p id="r-name" class="text-gray-800"></p>
                <p id="r-status" class="text-sm mt-1 text-gray-600"></p>
                <p id="r-location" class="text-sm text-gray-600"></p>

                <div class="flex gap-2 mt-4">
                    <a id="btn-detail" href="#"
                       class="flex-1 text-center bg-brand-500 hover:bg-brand-600 text-white py-2 rounded-lg text-sm">
                        Lihat Detail
                    </a>
                    <button id="btn-scan-again"
                            class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg text-sm">
                        Scan Lagi
                    </button>
                </div>
            </div>
        </div>

        <div id="not-found" class="mt-4 hidden bg-red-50 text-red-700 p-3 rounded-lg text-sm"></div>
    </div>
</x-mobile-layout>