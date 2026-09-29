<div x-data="{ open: false, title: 'Konfirmasi', message: '', _cb: null }"
     x-show="open"
     style="display: none;"
     @open-confirm-modal.window="
        open = true;
        title = $event.detail.title || 'Konfirmasi';
        message = $event.detail.message || '';
        _cb = $event.detail.onConfirm;
     "
     @keydown.escape.window="open = false; _cb = null"
     class="fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" @click="open = false; _cb = null"></div>

    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6" @click.stop>
        <div class="flex items-start gap-3">
            <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <div class="flex-1 min-w-0 pt-1">
                <p class="font-semibold text-gray-900 dark:text-white" x-text="title"></p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="message"></p>
            </div>
        </div>

        <div class="flex justify-end gap-2 mt-5">
            <button type="button" @click="open = false; _cb = null"
                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                Batal
            </button>
            <button type="button"
                    @click="open = false; const cb = _cb; _cb = null; if (cb) cb();"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition">
                Ya, Lanjutkan
            </button>
        </div>
    </div>
</div>

<script>
    /**
     * Pengganti window.confirm() bawaan browser supaya konsisten dengan tampilan aplikasi.
     * Pakai: confirmAction(pesan, callbackKalauYa, judulOpsional)
     */
    window.confirmAction = function (message, onConfirm, title) {
        window.dispatchEvent(new CustomEvent('open-confirm-modal', { detail: { message, onConfirm, title } }));
    };
</script>
