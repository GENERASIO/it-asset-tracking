<x-app-layout title="Edit Lisensi">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Edit Lisensi</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('software-licenses.update', $entitlement) }}" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                @csrf @method('PUT')
                @include('software-licenses.partials.form')
                <div class="mt-4 flex gap-2">
                    <button type="submit" class="bg-brand-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-brand-600 transition-all duration-150 hover:scale-105 active:scale-95">
                        Simpan
                    </button>
                    <a href="{{ route('software-licenses.index') }}" class="px-4 py-2 rounded-lg text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
