#!/bin/sh
# ============================================================
# Dejede Explorer - Installer untuk OpenWrt
# Repo: https://github.com/dejede/dejedexp
# ============================================================

set -e

REPO_RAW_BASE="https://raw.githubusercontent.com/dejede/dejedexp/main/www/dejedexp"
INSTALL_DIR="/www/dejedexp"

echo "=============================================="
echo " Dejede Explorer - Installer"
echo "=============================================="

# ------------------------------------------------------------
# 1. Deteksi package manager (apk vs opkg)
# ------------------------------------------------------------
if command -v apk >/dev/null 2>&1; then
    PKG_MGR="apk"
elif command -v opkg >/dev/null 2>&1; then
    PKG_MGR="opkg"
else
    echo "[ERROR] Tidak ditemukan apk maupun opkg. Instalasi dibatalkan."
    exit 1
fi
echo "[INFO] Package manager terdeteksi: $PKG_MGR"

# ------------------------------------------------------------
# 2. Install PHP + modul session
# ------------------------------------------------------------
echo "[INFO] Memperbarui daftar paket..."
if [ "$PKG_MGR" = "apk" ]; then
    apk update
else
    opkg update
fi

echo "[INFO] Memasang php8, php8-cgi, php8-mod-session..."
if [ "$PKG_MGR" = "apk" ]; then
    apk add php8 php8-cgi php8-mod-session
else
    opkg install php8 php8-cgi php8-mod-session
fi

# ------------------------------------------------------------
# 3. Pastikan folder tujuan ada
# ------------------------------------------------------------
echo "[INFO] Menyiapkan direktori $INSTALL_DIR..."
mkdir -p "$INSTALL_DIR"

# ------------------------------------------------------------
# 4. Download file aplikasi dari GitHub (raw)
# ------------------------------------------------------------
download_file() {
    local url="$1"
    local dest="$2"
    echo "[INFO] Mengunduh $(basename "$dest")..."
    if command -v wget >/dev/null 2>&1; then
        wget -q -O "$dest" "$url"
    elif command -v curl >/dev/null 2>&1; then
        curl -fsSL -o "$dest" "$url"
    else
        echo "[ERROR] Tidak ada wget maupun curl di sistem ini."
        exit 1
    fi

    if [ ! -s "$dest" ]; then
        echo "[ERROR] Gagal mengunduh $url (file kosong/tidak ditemukan)."
        echo "        Cek manual: $url"
        exit 1
    fi
}

download_file "$REPO_RAW_BASE/index.php"  "$INSTALL_DIR/index.php"
download_file "$REPO_RAW_BASE/config.php" "$INSTALL_DIR/config.php"

chmod 644 "$INSTALL_DIR/index.php" "$INSTALL_DIR/config.php"

# ------------------------------------------------------------
# 5. Konfigurasi uHTTPd agar mengeksekusi .php
# ------------------------------------------------------------
echo "[INFO] Mengonfigurasi uhttpd..."
uci set uhttpd.main.index_page='index.php'

# Cek apakah interpreter .php sudah terdaftar, hindari duplikasi
if ! uci get uhttpd.main.interpreter 2>/dev/null | grep -q '\.php='; then
    uci add_list uhttpd.main.interpreter='.php=/usr/bin/php-cgi'
fi

uci commit uhttpd

# ------------------------------------------------------------
# 6. Restart layanan
# ------------------------------------------------------------
echo "[INFO] Merestart uhttpd..."
/etc/init.d/uhttpd restart

# ------------------------------------------------------------
# 7. Selesai
# ------------------------------------------------------------
ROUTER_IP=$(uci get network.lan.ipaddr 2>/dev/null || echo "IP-ROUTER-KAMU")

echo ""
echo "=============================================="
echo " Instalasi selesai!"
echo "=============================================="
echo " Akses via browser : http://$ROUTER_IP/dejedexp/"
echo " Username default  : admin"
echo " Password default  : admin"
echo ""
echo " PENTING: auth.php akan dibuat otomatis saat"
echo " pertama kali dibuka. Segera ganti kredensial"
echo " default lewat menu di dalam aplikasi setelah"
echo " login pertama kali."
echo "=============================================="
