# ⚡ Dejede Explorer OpenWrt 25.12.x

![OpenWrt](https://img.shields.io/badge/OpenWrt-24.x%20--%2025.x-1b4b34?logo=openwrt&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.x-777bb4?logo=php&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-blue)
![Status](https://img.shields.io/badge/status-stable-brightgreen)
![Auth](https://img.shields.io/badge/auth-session%20%2B%20CSRF-important)

**Dejede Explorer** adalah pengelola berkas (*file manager*) berbasis web yang ringan, satu file inti, dan dirancang untuk berjalan langsung di server web OpenWrt (`uhttpd` + PHP-CGI).

> ⚠️ **Perhatian:** aplikasi ini memberi akses baca/tulis/hapus langsung ke filesystem router lewat browser. Gunakan hanya di jaringan yang kamu percaya, ganti kredensial default segera, dan jangan expose ke WAN tanpa VPN.

---
<img width="1919" height="1079" alt="image" src="https://github.com/user-attachments/assets/26865b3c-428c-4fe2-86da-212cba548d80" />

## 📋 Daftar Isi
1. [Fitur](#-fitur)
2. [Prasyarat Sistem](#-prasyarat-sistem)
3. [Instalasi Dependensi PHP](#️-instalasi-dependensi-php)
4. [Instalasi Dejede Explorer](#-instalasi-dejede-explorer)
5. [Konfigurasi Web Server (uHTTPd)](#️-konfigurasi-web-server-uhttpd)
6. [Cara Penggunaan](#-cara-penggunaan)
7. [Konfigurasi Lanjutan](#-konfigurasi-lanjutan)
8. [Catatan Keamanan](#-catatan-keamanan)

---

## ✨ Fitur

| Fitur | Keterangan |
|---|---|
| 🔐 Login | Autentikasi session, password disimpan sebagai hash (bukan plain text) |
| 🛡️ CSRF Protection | Token CSRF pada seluruh aksi upload, hapus, rename, buat folder, dan simpan file |
| 📁 Navigasi Folder | Breadcrumb dinamis, dikunci ke root path yang dikonfigurasi |
| 📤 Upload File | Validasi ukuran maksimal dan blokir ekstensi berbahaya (`.php`, `.sh`, `.phtml`, dll) |
| 📝 Edit File Teks | Editor teks langsung di browser untuk file konfigurasi/script |
| ⬇️ Download File | Unduh file langsung dari browser |
| ➕ Buat Folder | Buat direktori baru di lokasi saat ini |
| ✏️ Rename & Hapus | Ganti nama atau hapus file/folder dengan konfirmasi |
| 🌗 Tema Dark/Light | Toggle tema, default **light**, tersimpan di `localStorage` |
| 🪟 Efek Glass Hover | Frosted-glass blur muncul saat cursor melintas di menu, tombol, dan baris tabel |
| ⚙️ Konfigurasi Terpisah | Kredensial & pengaturan disimpan di `config.php`, terpisah dari logika aplikasi |

---

## 📦 Prasyarat Sistem
* Router dengan firmware OpenWrt (21.x – 25.x).
* Akses SSH ke router.
* `uhttpd` sudah terpasang (bawaan OpenWrt).
* Ruang penyimpanan internal cukup (free space disarankan > 5 MB untuk PHP + modul).

---

## 🛠️ Instalasi Dependensi PHP

Masuk ke router via SSH, lalu pasang PHP dan modul session (**wajib**, karena aplikasi ini memakai session untuk login).

### OpenWrt 24.x ke atas (manajer paket `apk`)
```bash
apk update
apk add php8 php8-cgi php8-mod-session
```

### OpenWrt versi lama (manajer paket `opkg`)
```bash
opkg update
opkg install php8 php8-cgi php8-mod-session
```

Setelah instalasi, modul session biasanya otomatis aktif lewat file konfigurasi terpisah (`/etc/php8/20_session.ini`). Verifikasi dengan:
```bash
apk info -L php8-mod-session   # atau: opkg files php8-mod-session
```

---

## 📂 Instalasi Dejede Explorer

### Opsi A — Instalasi Otomatis (disarankan)
Jalankan installer langsung dari router via SSH:
```bash
wget -O /tmp/install.sh https://raw.githubusercontent.com/dejede/dejedexp/main/install.sh
sh /tmp/install.sh
```
Script ini otomatis akan: memasang PHP + modul session, membuat folder `/www/dejedexp`, mengunduh `index.php` & `config.php` dari repo, mengonfigurasi `uhttpd`, lalu merestart layanan.

### Opsi B — Instalasi Manual

**1. Buat direktori kerja**
Taruh `index.php` dan `config.php` dalam satu folder khusus, bukan langsung di `/www/` (supaya tidak bentrok dengan `index.html` bawaan LuCI):
```bash
mkdir -p /www/dejedexp
scp index.php config.php root@192.168.1.1:/www/dejedexp/
```

**2. Sesuaikan `config.php`**
```php
// Folder yang boleh dikelola lewat file manager
$managed_folder = '/';   // '/' = akses penuh ke seluruh filesystem, atau ganti ke folder tertentu
```

> Kredensial login **tidak** disimpan di `config.php` — file `auth.php` dibuat otomatis oleh `index.php` saat pertama kali diakses (default: `admin` / `admin`), lalu bisa diganti langsung dari dalam aplikasi.

**3. Set permission**
```bash
chmod 644 /www/dejedexp/index.php /www/dejedexp/config.php
```

---

## ⚙️ Konfigurasi Web Server (uHTTPd)

Pastikan `uhttpd` mengeksekusi file `.php`, bukan menyajikannya sebagai teks mentah:

```bash
uci set uhttpd.main.index_page='index.php'
uci add_list uhttpd.main.interpreter='.php=/usr/bin/php-cgi'
uci commit uhttpd

/etc/init.d/uhttpd restart
```

---

## 💡 Cara Penggunaan

1. Buka browser, akses:
   ```
   http://IP-ROUTER/dejedexp/
   ```
2. **Login pertama kali** dengan kredensial default:
   - Username: `admin`
   - Password: `admin`
3. **Segera ganti password default** — buka menu 🔑 Akun & Sandi di dalam aplikasi setelah login pertama, atau edit `auth.php` langsung:
   ```bash
   php -r "echo password_hash('password_baru_yang_kuat', PASSWORD_DEFAULT);"
   ```
4. Gunakan action bar untuk upload file / buat folder, klik nama file untuk edit, atau tombol Download/Hapus di kolom aksi.
5. Toggle ikon 🌙/☀️ di sidebar untuk beralih tema dark/light.

---

## 🔧 Konfigurasi Lanjutan

Pengaturan umum ada di `config.php`:

```php
$managed_folder      = '/';   // root direktori yang dikelola ('/' = akses penuh)
$blocked_extensions  = ['php','phtml','sh','cgi', ...]; // ekstensi upload yang diblokir
$max_upload_size     = 10 * 1024 * 1024; // batas ukuran upload (bytes)
```

Kredensial login ada di `auth.php` (dibuat otomatis, jangan diedit manual kecuali darurat):
```php
$auth_user      = 'admin';
$auth_pass_hash = '...'; // hasil password_hash()
```

---

## 🔒 Catatan Keamanan

- **Ganti kredensial default** (`admin`/`admin`) lewat menu 🔑 Akun & Sandi segera setelah instalasi.
- Jika `$managed_folder` diset ke `/`, aplikasi ini punya akses setara root ke seluruh filesystem — perlakukan seperti akses shell root, jangan expose ke WAN tanpa VPN.
- Validasi ekstensi upload (`$blocked_extensions`) mencegah file executable diunggah lewat form, tapi bukan pengganti firewall — batasi akses jaringan ke halaman ini sebisa mungkin hanya dari LAN.
- Disarankan mengaktifkan HTTPS di `uhttpd` agar kredensial login tidak dikirim sebagai teks polos.
