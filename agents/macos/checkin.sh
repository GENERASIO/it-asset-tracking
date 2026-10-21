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
# Intel Mac: "Intel(R) Core(TM) i5-8257U ...", Apple Silicon: "Apple M2" (dari baris Chip).
CPU=$(sysctl -n machdep.cpu.brand_string 2>/dev/null || true)
if [ -z "$CPU" ]; then
    CPU=$(system_profiler SPHardwareDataType | awk -F': ' '/Chip/ {print $2; exit}')
fi
HOSTNAME=$(scutil --get ComputerName 2>/dev/null || hostname -s)
RAM_GB=$(( $(sysctl -n hw.memsize) / 1024 / 1024 / 1024 ))
STORAGE_GB=$(df -g / | tail -1 | awk '{print $2}')
MAC_ADDRESS=$(ifconfig en0 2>/dev/null | awk '/ether/{print $2; exit}')
USERNAME=$(whoami)

# Lokasi diambil oleh ITAssetLocation.app (lihat location-helper/main.swift), karena macOS
# tidak mengizinkan script bash mengakses lokasi. Dibuka lewat `open` supaya izin lokasi
# tercatat atas nama app itu. Kalau app tidak ada, izin ditolak, atau timeout, check-in
# tetap jalan tanpa koordinat.
LOCATION_APP="$INSTALL_DIR/ITAssetLocation.app"
GEO_JSON=""
GEO_INFO="lokasi=-"

# Ambil lokasi dari ITAssetLocation.app. Set GEO_JSON + GEO_INFO kalau berhasil. Return 0/1.
capture_location() {
    GEO_JSON=""
    [ -d "$LOCATION_APP" ] || { log "Lokasi tidak diambil - ITAssetLocation.app tidak ada."; return 1; }

    local f="$INSTALL_DIR/location.txt"
    rm -f "$f"
    if open -W -n -g -a "$LOCATION_APP" --args "$f" 2>/dev/null && [ -f "$f" ]; then
        read -r GEO_STATUS GEO_LAT GEO_LNG GEO_ACC < "$f" || true
        rm -f "$f"
        local num='^-?[0-9]+(\.[0-9]+)?$'
        if [ "${GEO_STATUS:-}" = "OK" ] && [[ "${GEO_LAT:-}" =~ $num ]] && [[ "${GEO_LNG:-}" =~ $num ]] && [[ "${GEO_ACC:-}" =~ $num ]]; then
            GEO_JSON=",
  \"latitude\": $GEO_LAT,
  \"longitude\": $GEO_LNG,
  \"location_accuracy_m\": $GEO_ACC"
            GEO_INFO="lokasi=$GEO_LAT,$GEO_LNG (+/-${GEO_ACC}m)"
            return 0
        fi
        log "Lokasi tidak diambil - ${GEO_STATUS:-?}"
        return 1
    fi
    rm -f "$f"
    log "Lokasi tidak diambil - ITAssetLocation.app gagal dijalankan."
    return 1
}

# Normalnya lokasi hanya dicatat saat tersambung Wi-Fi kantor. Kalau aset ditandai HILANG
# (diketahui dari respons check-in), lokasi dikirim di fase kedua di bawah walau bukan Wi-Fi kantor.
DEFAULT_SSIDS="SUSHI|FreeWifi-SUSHI|yaygroup_lt1|Yaygroup LT2|yaygroup|TrainingCenter|TrainingCenter_Guest"
ALLOWED_SSIDS="${LOCATION_SSIDS:-$DEFAULT_SSIDS}"
CURRENT_SSID=$(networksetup -getairportnetwork en0 2>/dev/null | sed -n 's/^Current Wi-Fi Network: //p')

on_office_wifi=false
if [ -n "$CURRENT_SSID" ]; then
    IFS='|' read -ra _ssid_list <<< "$ALLOWED_SSIDS"
    for s in "${_ssid_list[@]}"; do
        [ "$s" = "$CURRENT_SSID" ] && on_office_wifi=true && break
    done
fi

if [ "$on_office_wifi" = true ]; then
    capture_location || true
fi

JSON=$(cat <<EOF
{
  "serial_number": "$(json_escape "$SERIAL")",
  "hostname": "$(json_escape "$HOSTNAME")",
  "os_name": "macOS",
  "os_version": "$(json_escape "$OS_VERSION")",
  "cpu": "$(json_escape "$CPU")",
  "brand": "Apple",
  "model": "$(json_escape "$MODEL")",
  "ram_gb": $RAM_GB,
  "storage_gb": $STORAGE_GB,
  "mac_address": "$(json_escape "$MAC_ADDRESS")",
  "username": "$(json_escape "$USERNAME")"$GEO_JSON
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
    log "OK - $BODY $GEO_INFO"

    # Fase kedua: aset ditandai HILANG tapi bukan Wi-Fi kantor -> ambil lokasi di mana pun, kirim lagi.
    if [ "$on_office_wifi" != true ] && printf '%s' "$BODY" | grep -q '"track_location_always":true'; then
        if capture_location; then
            LOST_JSON="{\"serial_number\":\"$(json_escape "$SERIAL")\",\"hostname\":\"$(json_escape "$HOSTNAME")\",\"os_name\":\"macOS\"$GEO_JSON}"
            curl -s -o /dev/null -X POST "$API_URL" -H "Authorization: Bearer $API_TOKEN" -H "Content-Type: application/json" -d "$LOST_JSON"
            log "MODE HILANG - lokasi dikirim dari luar kantor: $GEO_INFO"
        else
            log "MODE HILANG - lokasi tidak bisa diambil."
        fi
    elif [ "$on_office_wifi" != true ]; then
        log "Lokasi dilewati - bukan Wi-Fi kantor (SSID: ${CURRENT_SSID:-tidak tersambung})."
    fi

    EXPECTED_HASH=$(printf '%s' "$BODY" | sed -n 's/.*"script_sha256":"\([0-9a-f]\{64\}\)".*/\1/p')
    if [ -n "$EXPECTED_HASH" ]; then
        update_agent_script "$EXPECTED_HASH"
    fi
else
    log "GAGAL - HTTP $HTTP_CODE - $BODY"
fi
