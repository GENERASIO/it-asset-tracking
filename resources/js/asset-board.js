import Sortable from 'sortablejs';

window.addEventListener('DOMContentLoaded', () => {
    const columns = document.querySelectorAll('.board-column-body');
    if (! columns.length) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    columns.forEach((column) => {
        Sortable.create(column, {
            group: 'assets',
            animation: 200,
            easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            delay: 100,
            delayOnTouchOnly: true,
            touchStartThreshold: 5,
            onStart: (evt) => {
                document.querySelectorAll('.board-column').forEach((col) => {
                    col.classList.add('board-column-droppable');
                });
                evt.from.closest('.board-column')?.classList.add('board-column-source');
            },
            onMove: (evt) => {
                document.querySelectorAll('.board-column-hover').forEach((col) => {
                    col.classList.remove('board-column-hover');
                });
                evt.to.closest('.board-column')?.classList.add('board-column-hover');
                return true;
            },
            onEnd: (event) => {
                document.querySelectorAll('.board-column').forEach((col) => {
                    col.classList.remove('board-column-droppable', 'board-column-source', 'board-column-hover');
                });

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
