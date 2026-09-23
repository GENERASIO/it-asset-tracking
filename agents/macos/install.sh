#!/bin/bash
# Memasang IT Asset Agent: menyalin script ke ~/Library/Application Support dan mendaftarkan
# LaunchAgent yang menjalankan checkin.sh saat login dan berulang tiap hari.
#
# Cara pakai:
#   1. Isi config.sh (salin dari config.example.sh) di folder ini dengan API_URL + API_TOKEN.
#   2. chmod +x install.sh && ./install.sh

set -euo pipefail

SOURCE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TARGET_DIR="$HOME/Library/Application Support/ITAssetAgent"
PLIST_PATH="$HOME/Library/LaunchAgents/com.yaygroup.itassetagent.plist"

if [ ! -f "$SOURCE_DIR/config.sh" ]; then
    echo "config.sh belum ada. Salin config.example.sh jadi config.sh lalu isi API_URL dan API_TOKEN sebelum install." >&2
    exit 1
fi

mkdir -p "$TARGET_DIR"
cp "$SOURCE_DIR/checkin.sh" "$TARGET_DIR/checkin.sh"
cp "$SOURCE_DIR/config.sh" "$TARGET_DIR/config.sh"
chmod +x "$TARGET_DIR/checkin.sh"

cat > "$PLIST_PATH" <<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>com.yaygroup.itassetagent</string>
    <key>ProgramArguments</key>
    <array>
        <string>/bin/bash</string>
        <string>$TARGET_DIR/checkin.sh</string>
    </array>
    <key>RunAtLoad</key>
    <true/>
    <key>StartInterval</key>
    <integer>86400</integer>
</dict>
</plist>
EOF

launchctl unload "$PLIST_PATH" 2>/dev/null || true
launchctl load "$PLIST_PATH"

echo "Berhasil dipasang. Agent akan check-in saat login dan setiap 24 jam."

# Jalankan sekali sekarang supaya asetnya langsung muncul.
bash "$TARGET_DIR/checkin.sh"
echo "Check-in pertama sudah dijalankan. Cek log di $TARGET_DIR/checkin.log"
