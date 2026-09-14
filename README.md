# 🚀 Dejede Explorer for OpenWrt

**Dejede Explorer** adalah pengelola berkas (*file manager*) berbasis web yang ringan, aman, dan dirancang khusus untuk berjalan langsung di dalam sistem router OpenWrt serta terintegrasi penuh ke dalam antarmuka LuCI.

---

## 📋 Daftar Isi
1. [Prasyarat Sistem](#-prasyarat-sistem)
2. [Instalasi Dependensi](#-instalasi-dependensi)
3. [Instalasi Dejede Explorer](#-instalasi-dejede-explorer)
    - [Membuat Direktori Utama](#1-buat-direktori-utama)
    - [Membuat File `config.php`](#2-membuat-file-configphp)
    - [Membuat File `index.php`](#3-membuat-file-indexphp)
4. [Integrasi ke LuCI Dashboard](#-integrasi-ke-luci-dashboard)
5. [Konfigurasi Web Server (uHTTPd)](#-konfigurasi-web-server-uhttpd)
6. [Cara Penggunaan](#-cara-penggunaan)

---

## 📦 Prasyarat Sistem
* Router OpenWrt (direkomendasikan versi 21.x hingga 25.x).
* Akses SSH ke router.
* Ruang penyimpanan internal yang cukup (Free space > 1MB).

---

## 🛠️ Instalasi Dependensi

Masuk ke router Anda via SSH, lalu pasang paket PHP yang dibutuhkan oleh sistem:

### 1. Untuk OpenWrt 25.x (Menggunakan manajer paket `apk`)
```bash
apk update
apk add php8 php8-mod-session php8-cgi
```[cite: 4]

### 2. Untuk OpenWrt versi lama (Menggunakan manajer paket `opkg`)
```bash
opkg update
opkg install php8 php8-mod-session php8-cgi
```[cite: 4]

---

## 📂 Instalasi Dejede Explorer

### 1. Buat Direktori Utama
Buat folder khusus untuk aplikasi di dalam direktori web server router:
```bash
mkdir -p /www/dejedexp

🧩 Integrasi ke LuCI Dashboard
Agar Dejede Explorer muncul langsung di dalam menu Services pada dashboard LuCI, jalankan perintah ini via SSH:

Bash
# 1. Buat file Menu JSON
mkdir -p /usr/share/luci/menu.d/
cat << 'EOF' > /usr/share/luci/menu.d/luci-app-dejedexp.json
{
    "admin/services/dejedexp": {
        "title": "Dejede Explorer",
        "order": 90,
        "action": {
            "type": "view",
            "path": "dejedexp"
        }
    }
}
EOF

# 2. Buat file View Javascript untuk Iframe
mkdir -p /www/luci-static/resources/view/
cat << 'EOF' > /www/luci-static/resources/view/dejedexp.js
'use strict';
'require view';

return view.extend({
    render: function() {
        return E('div', { class: 'cbi-map' }, [
            E('h2', { name: 'content' }, 'Dejede Explorer'),
            E('div', { class: 'cbi-map-descr' }, 'Manajemen File Sistem OpenWrt'),
            E('div', { class: 'cbi-section', style: 'padding: 0; background: transparent; box-shadow: none;' }, [
                E('iframe', {
                    src: '/dejedexp/',
                    style: 'width: 100%; height: 85vh; min-height: 700px; border: none; border-radius: 8px;'
                })
            ])
        ]);
    },
    handleSaveApply: null,
    handleSave: null,
    handleReset: null
});
EOF
⚙️ Konfigurasi Web Server (uHTTPd)
Agar web server uHTTPd di OpenWrt mengenali dan mengeksekusi file .php dengan benar:

Bash
uci set uhttpd.main.index_page='index.php'
uci add_list uhttpd.main.interpreter='.php=/usr/bin/php-cgi'
uci commit uhttpd

# Bersihkan cache LuCI dan restart layanan
rm -rf /tmp/luci-*
/etc/init.d/rpcd restart
/etc/init.d/uhttpd restart
💡 Cara Penggunaan
Buka browser Anda dan akses IP router OpenWrt (misal: http://192.168.1.1).

Masuk ke menu Services -> Dejede Explorer.
Masukkan Username: admin dan Password: admin pada halaman login pertama kali.
Anda bebas mengubah kredensial akun kapan saja melalui tombol 🔑 Akun & Sandi di dalam panel aplikasi.

⚙️ Konfigurasi Web Server (uHTTPd)
Pastikan web server uHTTPd di OpenWrt mengenali dan mengeksekusi ekstensi .php:

Bash
uci set uhttpd.main.index_page='index.php'
uci add_list uhttpd.main.interpreter='.php=/usr/bin/php-cgi'
uci commit uhttpd
Restart layanan web server dan cache LuCI:

Bash
rm -rf /tmp/luci-*
/etc/init.d/rpcd restart
/etc/init.d/uhttpd restart

💡 Penggunaan
Buka peramban (browser) Anda dan akses halaman LuCI router (misal: http://192.168.1.1).
Masuk ke menu Services -> Dejede Explorer.
Login Pertama: Masukkan Username admin dan Password admin (Anda dapat mengubah kredensial ini kapan saja melalui menu 🔑 Akun & Sandi di dalam aplikasi).
Nikmati kemudahan menjelajah direktori sistem, menyunting teks, mengubah permission (Chmod) secara interaktif, hingga mengunggah berkas langsung dari antarmuka LuCI.
