#!/bin/bash
# Build ITAssetLocation.app (universal: Apple Silicon + Intel) lalu bungkus jadi
# agents/macos/ITAssetLocation.zip yang ikut di paket download agent macOS.
#
# Harus dijalankan di Mac yang sudah terpasang Xcode Command Line Tools
# (xcode-select --install). Hasil zip-nya di-commit ke repo, sama seperti
# ITAssetAgentSetup.exe untuk Windows, karena server produksi tidak bisa build app macOS.

set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BUILD_DIR="$DIR/build"
APP="$BUILD_DIR/ITAssetLocation.app"
OUTPUT_ZIP="$DIR/../ITAssetLocation.zip"

rm -rf "$BUILD_DIR"
mkdir -p "$APP/Contents/MacOS"
cp "$DIR/Info.plist" "$APP/Contents/Info.plist"

for arch in arm64 x86_64; do
    swiftc -O -target "$arch-apple-macos11" -o "$BUILD_DIR/ITAssetLocation-$arch" "$DIR/main.swift"
done

lipo -create -output "$APP/Contents/MacOS/ITAssetLocation" \
    "$BUILD_DIR/ITAssetLocation-arm64" "$BUILD_DIR/ITAssetLocation-x86_64"

# Tanda tangan ad-hoc: wajib supaya bisa jalan di Apple Silicon. Tidak perlu akun
# Apple Developer berbayar karena app ini dipasang lewat install.sh, bukan dibuka dari Finder.
codesign --force --sign - "$APP"

rm -f "$OUTPUT_ZIP"
ditto -c -k --keepParent "$APP" "$OUTPUT_ZIP"

echo "Selesai: $OUTPUT_ZIP"
echo "Tes cepat: open -W -n -a \"$APP\" --args /tmp/lokasi.txt && cat /tmp/lokasi.txt"
