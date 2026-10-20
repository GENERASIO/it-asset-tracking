<div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <h3 class="font-semibold text-gray-700 dark:text-gray-300">Berita Acara Serah Terima (BAST)</h3>
        <a href="{{ route('bast.create', ['ids' => [$asset->id]]) }}"
           class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 px-3 py-1.5 rounded-lg text-sm hover:bg-gray-200 dark:hover:bg-gray-600">
            + Buat BAST
        </a>
    </div>

    @forelse ($asset->bastDocuments as $bast)
        <a href="{{ route('bast.show', $bast) }}"
           class="flex flex-wrap items-center justify-between gap-2 py-2 border-b last:border-b-0 border-gray-100 dark:border-gray-700 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50 -mx-2 px-2 rounded">
            <span class="text-gray-900 dark:text-white">
                {{ $bast->handover_date_label }} — ke {{ $bast->second_party_name }}
            </span>
            @if ($bast->isSigned())
                <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">Sudah ditandatangani</span>
            @else
                <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800">Belum upload tanda tangan</span>
            @endif
        </a>
    @empty
        <p class="text-sm text-gray-500">Belum ada BAST untuk aset ini.</p>
    @endforelse
</div>
