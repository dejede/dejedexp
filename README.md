# ⚡ Dejede Explorer

![OpenWrt](https://img.shields.io/badge/OpenWrt-21.x%20--%2025.x-1b4b34?logo=openwrt&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.x-777bb4?logo=php&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-blue)
![Status](https://img.shields.io/badge/status-stable-brightgreen)
![Auth](https://img.shields.io/badge/auth-session%20%2B%20CSRF-important)

**Dejede Explorer** adalah pengelola berkas (*file manager*) berbasis web yang ringan, satu file inti, dan dirancang untuk berjalan langsung di server web OpenWrt (`uhttpd` + PHP-CGI).

> ⚠️ **Perhatian:** aplikasi ini memberi akses baca/tulis/hapus langsung ke filesystem router lewat browser. Gunakan hanya di jaringan yang kamu percaya, ganti kredensial default segera, dan jangan expose ke WAN tanpa VPN.

---

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

### 1. Buat direktori kerja
Taruh `manager.php` dan `config.php` dalam satu folder di web root, misalnya langsung di `/www/`:
```bash
scp manager.php config.php root@192.168.1.1:/www/
```

### 2. Sesuaikan `config.php`
Buka `config.php` dan atur:
```php
// Folder yang boleh dikelola lewat file manager
$managed_folder = '/www/files';   // atau '/' untuk akses penuh ke seluruh filesystem

// Kredensial login
$auth_user = 'admin';
$auth_pass_hash = '...'; // hasil dari: php -r "echo password_hash('password_kamu', PASSWORD_DEFAULT);"
```

> `config.php` sengaja dipisah dari `manager.php` supaya kredensial tidak ikut hilang/tertimpa saat kamu update logika aplikasinya.

### 3. Set permission
```bash
chmod 644 /www/manager.php /www/config.php
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
   http://IP-ROUTER/manager.php
   ```
2. **Login pertama kali** dengan kredensial default:
   - Username: `admin`
   - Password: `admin`
3. **Segera ganti password default** — generate hash baru dan perbarui `$auth_pass_hash` di `config.php`:
   ```bash
   php -r "echo password_hash('password_baru_yang_kuat', PASSWORD_DEFAULT);"
   ```
4. Gunakan action bar untuk upload file / buat folder, klik nama file untuk edit, atau tombol Download/Hapus di kolom aksi.
5. Toggle ikon 🌙/☀️ di sidebar untuk beralih tema dark/light.

---

## 🔧 Konfigurasi Lanjutan

Semua pengaturan ada di `config.php`:

```php
$managed_folder      = '/www/files';   // root direktori yang dikelola
$auth_user            = 'admin';
$auth_pass_hash       = '...';
$blocked_extensions   = ['php','phtml','sh','cgi', ...]; // ekstensi upload yang diblokir
$max_upload_size      = 10 * 1024 * 1024; // batas ukuran upload (bytes)
```

---

## 🔒 Catatan Keamanan

- **Ganti kredensial default** (`admin`/`admin`) sebelum digunakan di luar pengujian lokal.
- Jika `$managed_folder` diset ke `/`, aplikasi ini punya akses setara root ke seluruh filesystem — perlakukan seperti akses shell root, jangan expose ke WAN tanpa VPN.
- Validasi ekstensi upload (`$blocked_extensions`) mencegah file executable diunggah lewat form, tapi bukan pengganti firewall — batasi akses jaringan ke halaman ini sebisa mungkin hanya dari LAN.
- Disarankan mengaktifkan HTTPS di `uhttpd` agar kredensial login tidak dikirim sebagai teks polos.
