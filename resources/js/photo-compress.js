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
 */
window.setupPhotoCompression = function (inputSelector, previewListSelector) {
    const photoInput = document.querySelector(inputSelector);
    if (!photoInput) return;

    photoInput.addEventListener('change', async function (e) {
        const files = Array.from(e.target.files);
        if (!files.length) return;

        const compressedFiles = await Promise.all(files.map(compressImage));

        const dataTransfer = new DataTransfer();
        compressedFiles.forEach((f) => dataTransfer.items.add(f));
        photoInput.files = dataTransfer.files;

        const list = document.querySelector(previewListSelector);
        if (list) {
            list.innerHTML = '';
            compressedFiles.forEach((f) => {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(f);
                img.className = 'h-20 w-20 object-cover rounded-lg border';
                list.appendChild(img);
            });
        }
    });
};

window.setupPhotoCompression('input[name="photos[]"]', '#photo-preview-list');
