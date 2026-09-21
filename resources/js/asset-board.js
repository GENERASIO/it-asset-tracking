import Sortable from 'sortablejs';

window.addEventListener('DOMContentLoaded', () => {
    const columns = document.querySelectorAll('.board-column-body');
    if (! columns.length) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    columns.forEach((column) => {
        Sortable.create(column, {
            group: 'assets',
            animation: 150,
            ghostClass: 'opacity-50',
            delay: 150,
            delayOnTouchOnly: true,
            touchStartThreshold: 5,
            onEnd: (event) => {
                const card = event.item;
                const newStatus = event.to.closest('.board-column')?.dataset.status;
                const oldStatus = event.from.closest('.board-column')?.dataset.status;

                if (! newStatus || newStatus === oldStatus) return;

                const assetId = card.dataset.assetId;

                console.log(card.dataset);

                fetch(`/assets/${assetId}/update-status`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ status: newStatus }),
                }).then((response) => {
                    if (! response.ok) {
                        throw new Error('Gagal memperbarui status aset.');
                    }
                }).catch(() => {
                    alert('Gagal memperbarui status aset. Halaman akan dimuat ulang.');
                    window.location.reload();
                });
            },
        });
    });
});
