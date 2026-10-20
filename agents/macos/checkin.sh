#!/bin/bash
# Mengumpulkan info hardware Mac ini dan mengirim (check-in) ke IT Asset Tracking.
# Dijalankan otomatis oleh LaunchAgent com.yaygroup.itassetagent (lihat install.sh).

set -euo pipefail

INSTALL_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$INSTALL_DIR/config.sh"
LOG_FILE="$INSTALL_DIR/checkin.log"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

json_escape() {
    printf '%s' "$1" | sed 's/\\/\\\\/g; s/"/\\"/g'
}

if [ ! -f "$CONFIG_FILE" ]; then
    log "GAGAL - config.sh tidak ditemukan. Salin dari config.example.sh dan isi API_URL/API_TOKEN."
    exit 1
fi

# shellcheck source=/dev/null
source "$CONFIG_FILE"

SERIAL=$(system_profiler SPHardwareDataType | awk -F': ' '/Serial Number/ {print $2; exit}')
MODEL=$(system_profiler SPHardwareDataType | awk -F': ' '/Model Name/ {print $2; exit}')
OS_VERSION=$(sw_vers -productVersion)
HOSTNAME=$(scutil --get ComputerName 2>/dev/null || hostname -s)
RAM_GB=$(( $(sysctl -n hw.memsize) / 1024 / 1024 / 1024 ))
STORAGE_GB=$(df -g / | tail -1 | awk '{print $2}')
MAC_ADDRESS=$(ifconfig en0 2>/dev/null | awk '/ether/{print $2; exit}')
USERNAME=$(whoami)

JSON=$(cat <<EOF
{
  "serial_number": "$(json_escape "$SERIAL")",
  "hostname": "$(json_escape "$HOSTNAME")",
  "os_name": "macOS",
  "os_version": "$(json_escape "$OS_VERSION")",
  "brand": "Apple",
  "model": "$(json_escape "$MODEL")",
  "ram_gb": $RAM_GB,
  "storage_gb": $STORAGE_GB,
  "mac_address": "$(json_escape "$MAC_ADDRESS")",
  "username": "$(json_escape "$USERNAME")"
}
EOF
)

RESPONSE=$(curl -s -w '\n%{http_code}' -X POST "$API_URL" \
    -H "Authorization: Bearer $API_TOKEN" \
    -H "Content-Type: application/json" \
    -d "$JSON")

HTTP_CODE=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | sed '$d')

# Update otomatis: kalau hash checkin.sh di server beda dengan file ini, unduh versi server
# lalu timpa file ini. Versi baru dipakai mulai check-in berikutnya. File hanya ditimpa
# kalau hash hasil unduhan cocok dengan yang dilaporkan server.
update_agent_script() {
    local expected="$1"
    local self_path="$INSTALL_DIR/$(basename "${BASH_SOURCE[0]}")"
    local tmp_path="$self_path.download"
    local current downloaded script_url

    current=$(shasum -a 256 "$self_path" | awk '{print $1}')
    [ "$current" = "$expected" ] && return 0

    script_url="${API_URL%/checkin}/script?os=macos"
    if ! curl -fsS -o "$tmp_path" -H "Authorization: Bearer $API_TOKEN" "$script_url"; then
        log "UPDATE GAGAL - tidak bisa mengunduh $script_url"
        rm -f "$tmp_path"
        return 0
    fi

    downloaded=$(shasum -a 256 "$tmp_path" | awk '{print $1}')
    if [ "$downloaded" != "$expected" ]; then
        log "UPDATE GAGAL - hash unduhan ($downloaded) tidak cocok dengan server ($expected)"
        rm -f "$tmp_path"
        return 0
    fi

    chmod +x "$tmp_path"
    mv -f "$tmp_path" "$self_path"
    log "UPDATE - checkin.sh diperbarui ke versi ${expected:0:12}, berlaku mulai check-in berikutnya."
}

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "201" ]; then
    log "OK - $BODY"

    EXPECTED_HASH=$(printf '%s' "$BODY" | sed -n 's/.*"script_sha256":"\([0-9a-f]\{64\}\)".*/\1/p')
    if [ -n "$EXPECTED_HASH" ]; then
        update_agent_script "$EXPECTED_HASH"
    fi
else
    log "GAGAL - HTTP $HTTP_CODE - $BODY"
fi
