<?php
/* ============================================================
   KONFIGURASI SISTEM
   ============================================================ */

// Akses penuh ke root OpenWrt
$managed_folder = '/';
$root_path = realpath($managed_folder);
if ($root_path === false) {
    $root_path = '/';
}

// Ekstensi file yang DILARANG untuk diupload
$blocked_extensions = ['php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar',
                        'pht', 'cgi', 'pl', 'py', 'sh', 'asp', 'aspx', 'jsp',
                        'exe', 'htaccess', 'ini'];

// Ukuran upload maksimal (bytes) - 10 MB
$max_upload_size = 10 * 1024 * 1024;
?>