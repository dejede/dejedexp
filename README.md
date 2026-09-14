# 🚀 Dejede Explorer for OpenWrt

**Dejede Explorer** adalah pengelola berkas (*file manager*) berbasis web yang ringan, aman, dan dirancang khusus untuk berjalan langsung di dalam sistem router OpenWrt (terintegrasi dengan antarmuka LuCI).

---

## 📋 Daftar Isi
1. [Prasyarat Sistem](#-prasyarat-sistem)
2. [Instalasi Dependensi](#-instalasi-dependensi)
3. [Instalasi Dejede Explorer](#-instalasi-dejede-explorer)
4. [Integrasi ke LuCI (OpenWrt Dashboard)](#-integrasi-ke-luci)
5. [Konfigurasi & Penggunaan](#-konfigurasi--penggunaan)

---

## 📦 Prasyarat Sistem
* Router OpenWrt (direkomendasikan versi 21.x hingga 25.x).
* Akses SSH ke router.
* Ruang penyimpanan internal yang cukup (Free space > 1MB).

---

## 🛠️ Instalasi Dependensi

Masuk ke router Anda via SSH, lalu pasang dependensi PHP yang dibutuhkan oleh sistem:

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

1. Buat direktori khusus untuk aplikasi di dalam folder web server router:
   ```bash
   mkdir -p /www/dejedexp
