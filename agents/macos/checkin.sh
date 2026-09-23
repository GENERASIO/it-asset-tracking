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

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "201" ]; then
    log "OK - $BODY"
else
    log "GAGAL - HTTP $HTTP_CODE - $BODY"
fi
