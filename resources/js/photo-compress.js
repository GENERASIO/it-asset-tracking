function compressImage(file) {
    return new Promise((resolve) => {
        // Kalau ada langkah yang gagal (mis. HEIC dari iPhone yang tidak bisa didecode
        // browser buat kompresi), jangan macet - lanjut pakai file aslinya saja supaya
        // upload tetap jalan (validasi server yang akan kasih tahu kalau formatnya
        // memang tidak didukung).
        const fallbackToOriginal = () => resolve(file);

        const reader = new FileReader();
        reader.onerror = fallbackToOriginal;
        reader.onload = function (event) {
            const img = new Image();
            img.onerror = fallbackToOriginal;
            img.onload = function () {
                try {
                    const canvas = document.createElement('canvas');
                    const maxDimension = 1600;
                    let width = img.width;
                    let height = img.height;

                    if (width > height && width > maxDimension) {
                        height = Math.round(height * (maxDimension / width));
                        width = maxDimension;
                    } else if (height > maxDimension) {
                        width = Math.round(width * (maxDimension / height));
                        height = maxDimension;
                    }

                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob(function (blob) {
                        if (!blob) {
                            fallbackToOriginal();
                            return;
                        }
                        const compressedFile = new File([blob], file.name, {
                            type: 'image/jpeg',
                            lastModified: Date.now(),
                        });
                        console.log('Ukuran asli:', (file.size / 1024).toFixed(0) + 'KB', '→ setelah kompresi:', (compressedFile.size / 1024).toFixed(0) + 'KB');
                        resolve(compressedFile);
                    }, 'image/jpeg', 0.75);
                } catch (err) {
                    fallbackToOriginal();
                }
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });
}

/**
 * Pasang kompresi otomatis + preview thumbnail pada input file foto aset.
 * Dipakai bersama oleh form Tambah dan Edit Aset supaya logikanya tidak dobel.
 *
 * Batas jumlah foto diambil dari atribut data-max-photos di elemen input
 * (di-set per halaman lewat Blade, misal 5 untuk Tambah, atau sisa slot untuk Edit).
 *
 * File yang dipilih ditampung di array terpisah (bukan langsung pakai e.target.files)
 * karena input dengan atribut capture="environment" di banyak browser Android/iOS
 * memaksa kamera cuma bisa ambil 1 foto per klik - foto sebelumnya akan hilang
 * (ketiban) kalau tidak ditampung manual begini.
 */
window.setupPhotoCompression = function (inputSelector, previewListSelector) {
    const photoInput = document.querySelector(inputSelector);
    if (!photoInput) return;

    const maxPhotos = parseInt(photoInput.dataset.maxPhotos, 10) || 5;
    let selectedFiles = [];

    function syncInputFiles() {
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach((f) => dataTransfer.items.add(f));
        photoInput.files = dataTransfer.files;
    }

    function renderPreview() {
        const list = document.querySelector(previewListSelector);
        if (!list) return;

        list.innerHTML = '';
        selectedFiles.forEach((f, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'relative group';

            const img = document.createElement('img');
            img.src = URL.createObjectURL(f);
            img.className = 'h-20 w-20 object-cover rounded-lg border';
            wrapper.appendChild(img);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-600 text-white text-xs flex items-center justify-center leading-none';
            removeBtn.textContent = '×';
            removeBtn.addEventListener('click', function () {
                selectedFiles.splice(index, 1);
                syncInputFiles();
                renderPreview();
            });
            wrapper.appendChild(removeBtn);

            list.appendChild(wrapper);
        });
    }

    photoInput.addEventListener('change', async function (e) {
        const newFiles = Array.from(e.target.files);
        if (!newFiles.length) return;

        const compressed = await Promise.all(newFiles.map(compressImage));

        compressed.forEach((f) => {
            if (selectedFiles.length < maxPhotos) {
                selectedFiles.push(f);
            }
        });

        syncInputFiles();
        renderPreview();
    });
};

window.setupPhotoCompression('input[name="photos[]"]', '#photo-preview-list');
