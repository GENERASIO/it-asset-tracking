# IT Asset Agent

Agent kecil yang, sekali dipasang di laptop Windows/macOS, otomatis mendaftarkan/mengupdate data aset (serial number, model, RAM, storage, dsb) ke IT Asset Tracking secara berkala — tanpa perlu input manual dari Role IT.

## Cara Kerja

1. Admin (`super_admin`) membuat token di halaman **Kelola User → Kelola Agent Token**.
2. Token tersebut dimasukkan ke file konfigurasi agent (`config.json` untuk Windows, `config.sh` untuk macOS).
3. Script instalasi mendaftarkan tugas terjadwal (Scheduled Task di Windows, LaunchAgent di macOS) yang menjalankan check-in saat login dan sekali sehari.
4. Setiap check-in mengirim data hardware ke `POST /api/agent/checkin`. Server mencocokkan aset berdasarkan **serial number**:
   - Kalau sudah ada aset dengan serial number itu → data hardware & `last_seen_at` diupdate (field yang sudah diisi manual seperti kategori, lokasi, brand/model tidak ditimpa).
   - Kalau belum ada → aset baru otomatis dibuat dengan kategori "Lainnya" dan lokasi "Tidak Diketahui" (silakan dilengkapi manual oleh IT setelahnya).

## Windows

```powershell
cd agents\windows
copy config.example.json config.json
notepad config.json   # isi ApiUrl dan ApiToken
./install.ps1
```

Untuk mencabut: jalankan `agents\windows\uninstall.ps1` di laptop yang bersangkutan.

## macOS

```bash
cd agents/macos
cp config.example.sh config.sh
nano config.sh   # isi API_URL dan API_TOKEN
chmod +x install.sh uninstall.sh
./install.sh
```

Untuk mencabut: jalankan `agents/macos/uninstall.sh` di Mac yang bersangkutan.

## Catatan Keamanan

- Token bersifat rahasia dan hanya ditampilkan sekali saat dibuat — kalau hilang, cabut token lama dan buat yang baru.
- Endpoint check-in dibatasi (throttle 30 request/menit) dan hanya menerima field spesifik hardware, bukan field sensitif (kategori/lokasi/harga tidak bisa diisi lewat agent).
- Cabut token dari halaman admin kalau agent sudah tidak dipakai (misal batch laptop lama).
