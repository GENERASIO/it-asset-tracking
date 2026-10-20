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

Cara utama (untuk end user, tidak perlu buka PowerShell sama sekali):

1. Download paket dari halaman **Kelola Agent Token** (sudah berisi `ITAssetAgentSetup.exe` + `config.json` dengan token yang benar).
2. Extract zip-nya, lalu double-click `ITAssetAgentSetup.exe`.
3. Klik **Yes** di prompt izin admin (UAC) yang muncul, dan **"Run anyway"** kalau Windows menampilkan peringatan SmartScreen (installer belum di-sign dengan sertifikat berbayar).

Installer ini otomatis: menyalin file ke `%ProgramData%\ITAssetAgent`, mendaftarkan Scheduled Task yang jalan sebagai **SYSTEM** (jadi tidak butuh hak admin lagi setelah instalasi awal ini, dan tidak akan kena blokir kebijakan "running scripts is disabled" karena bukan menjalankan `.ps1` mentah), lalu langsung check-in sekali.

Bisa di-uninstall lewat **Control Panel → Programs → Uninstall a program → IT Asset Agent** seperti aplikasi Windows pada umumnya.

Source installer ada di `agents/windows/installer/AgentSetup.iss` (build pakai [Inno Setup 6](https://jrsoftware.org/isinfo.php), hasil compile-nya di-commit sebagai `agents/windows/ITAssetAgentSetup.exe` karena server produksi Linux tidak bisa compile installer Windows).

Cara lama (manual, untuk debug/laptop yang tidak ikut kebijakan restriktif):

```powershell
cd agents\windows
copy config.example.json config.json
notepad config.json   # isi ApiUrl dan ApiToken
./install.ps1
```

Untuk mencabut cara lama: jalankan `agents\windows\uninstall.ps1` di laptop yang bersangkutan.

## macOS

```bash
cd agents/macos
cp config.example.sh config.sh
nano config.sh   # isi API_URL dan API_TOKEN
chmod +x install.sh uninstall.sh
./install.sh
```

Untuk mencabut: jalankan `agents/macos/uninstall.sh` di Mac yang bersangkutan.

## Lokasi Laptop (GPS)

Setiap check-in, agent mencoba mengirim lokasi laptop. Hasilnya tampil sebagai peta di halaman **detail aset** (perlu login), bukan di halaman publik scan QR. Kalau lokasi gagal diambil, check-in tetap jalan dan lokasi terakhir yang diketahui tidak dihapus. Alasannya dicatat di `checkin.log`.

- **Windows**: memakai Windows Location API. Location Services di laptop harus aktif (Settings → Privacy → Location).
- **macOS**: script bash tidak boleh mengakses lokasi, jadi dipakai app helper kecil `ITAssetLocation.app` (source di `macos/location-helper/`). Saat pertama jalan muncul pop-up izin lokasi sekali; user harus klik **Allow**. Kalau ditolak, bisa diizinkan lagi di System Settings → Privacy & Security → Location Services → IT Asset Location.
  - Build di Mac (butuh `xcode-select --install`): `bash agents/macos/location-helper/build.sh`, lalu commit `agents/macos/ITAssetLocation.zip` yang dihasilkan. Selama zip ini belum ada, agent macOS jalan tanpa lokasi.

Akurasi biasanya puluhan sampai ratusan meter (dihitung dari Wi-Fi sekitar, bukan satelit). Karena agent juga jalan saat laptop dibawa pulang, pastikan karyawan sudah diberi tahu sesuai kebijakan perusahaan (UU PDP).

## Update Otomatis

Respons check-in menyertakan hash SHA-256 script check-in versi server (`checkin.ps1` / `checkin.sh`). Kalau berbeda dengan milik agent, agent mengunduh versi baru dari `GET /api/agent/script?os=windows|macos`, mencocokkan hash, lalu mengganti dirinya; versi baru berlaku mulai check-in berikutnya. Jadi perubahan script cukup di-deploy ke server.

Yang **tidak** ikut update otomatis: installer Windows, `config.json`/`config.sh`, Scheduled Task/LaunchAgent, dan `ITAssetLocation.app` — perubahan pada bagian ini tetap butuh install ulang.

## Catatan Keamanan

- Token bersifat rahasia dan hanya ditampilkan sekali saat dibuat — kalau hilang, cabut token lama dan buat yang baru.
- Endpoint check-in dibatasi (throttle 30 request/menit) dan hanya menerima field spesifik hardware, bukan field sensitif (kategori/lokasi/harga tidak bisa diisi lewat agent).
- Cabut token dari halaman admin kalau agent sudah tidak dipakai (misal batch laptop lama).
