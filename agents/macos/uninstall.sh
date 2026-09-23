#!/bin/bash
# Mencabut IT Asset Agent dari Mac ini: hapus LaunchAgent dan file terpasang.

PLIST_PATH="$HOME/Library/LaunchAgents/com.yaygroup.itassetagent.plist"
TARGET_DIR="$HOME/Library/Application Support/ITAssetAgent"

launchctl unload "$PLIST_PATH" 2>/dev/null || true
rm -f "$PLIST_PATH"
rm -rf "$TARGET_DIR"

echo "IT Asset Agent sudah dicabut dari Mac ini."
