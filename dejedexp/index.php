<?php
session_start();

/* ============================================================
   KONFIGURASI & AUTENTIKASI
   ============================================================ */
$config_file = __DIR__ . '/config.php';
$auth_file   = __DIR__ . '/auth.php';

if (!file_exists($config_file)) {
    die('config.php tidak ditemukan.');
}
require $config_file;

// Buat auth.php otomatis jika belum ada (Default: admin / admin)[cite: 4]
if (!file_exists($auth_file)) {
    $default_user = 'admin';
    $default_hash = password_hash('admin', PASSWORD_DEFAULT);
    $auth_content = "<?php\n\$auth_user = '$default_user';\n\$auth_pass_hash = '$default_hash';\n?>";
    file_put_contents($auth_file, $auth_content);
}
require $auth_file;

/* ============================================================
   HELPER
   ============================================================ */
function safe_path($root, $path) {
    $resolved = realpath($path);
    if ($resolved === false) $resolved = $path; 
    if ($root === '/') return $resolved;
    $root_norm = rtrim($root, '/');
    if ($resolved === $root_norm) return $resolved;
    if (strpos($resolved, $root_norm . '/') === 0) return $resolved;
    return false;
}

function safe_basename($name) {
    $name = basename($name);
    if ($name === '' || $name === '.' || $name === '..') return false;
    if (strpos($name, "\0") !== false) return false;
    return $name;
}

function check_csrf() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('CSRF token tidak valid.');
    }
}

/* ============================================================
   LOGIN & LOGOUT
   ============================================================ */
if (isset($_POST['login_user']) && isset($_POST['login_pass'])) {
    if (hash_equals($auth_user, $_POST['login_user']) && password_verify($_POST['login_pass'], $auth_pass_hash)) {
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: ?dir=" . urlencode($root_path));
        exit;
    } else {
        $login_error = 'Username atau password salah.';
    }
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header("Location: ?");
    exit;
}

/* ============================================================
   GANTI USERNAME & PASSWORD
   ============================================================ */
if (isset($_POST['change_auth'])) {
    check_csrf();
    $old_pass = $_POST['old_pass'];
    $new_user = trim($_POST['new_user']);
    $new_pass = $_POST['new_pass'];

    if (password_verify($old_pass, $auth_pass_hash)) {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $safe_user = str_replace("'", "\'", $new_user);
        
        $auth_content = "<?php\n\$auth_user = '$safe_user';\n\$auth_pass_hash = '$new_hash';\n?>";
        file_put_contents($auth_file, $auth_content);
        
        $_SESSION = [];
        session_destroy();
        header("Location: ?msg=" . urlencode("Kredensial berhasil diubah. Silakan login kembali."));
        exit;
    } else {
        $auth_error = "Password lama salah!";
    }
}

/* ============================================================
   TAMPILAN LOGIN
   ============================================================ */
if (empty($_SESSION['authenticated'])) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - Dejede Explorer</title>
        <script>
            (function() {
                const savedTheme = localStorage.getItem('dejede_theme') || 'light';
                if (savedTheme === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            })();
        </script>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            :root { --bg: #f5f5f7; --bg-alt: #ffffff; --bg-alt2: #f0f0f2; --border: #e0e0e3; --text: #1c1c1e; --accent: #0066cc; --accent-hover: #0077ed; --danger: #cc3333; --success: #28a745; }
            [data-theme="dark"] { --bg: #121212; --bg-alt: #181818; --bg-alt2: #1e1e1e; --border: #2a2a2a; --text: #e0e0e0; --accent: #0066cc; --accent-hover: #0077ed; --danger: #ff6b6b; --success: #2ecc71; }
            body { background: var(--bg); color: var(--text); font-family: -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; transition: background 0.2s, color 0.2s; }
            .login-box { background: var(--bg-alt); border: 1px solid var(--border); border-radius: 10px; padding: 32px; width: 320px; max-width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
            [data-theme="dark"] .login-box { box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
            .login-box h1 { font-size: 18px; margin-bottom: 20px; text-align: center; color: var(--text); white-space: nowrap; }
            .login-box input { width: 100%; background: var(--bg-alt2); color: var(--text); border: 1px solid var(--border); padding: 12px; border-radius: 6px; margin-bottom: 12px; font-size: 14px; outline:none; transition: border-color 0.2s; }
            .login-box input:focus { border-color: var(--accent); }
            .login-box button { width: 100%; background: var(--accent); border: 1px solid var(--accent-hover); color: #fff; padding: 12px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: bold; transition: background 0.2s; }
            .login-box button:hover { background: var(--accent-hover); }
            .msg { font-size: 13px; margin-bottom: 12px; text-align: center; padding: 8px; border-radius: 6px; }
            .msg.error { background: rgba(204,51,51,0.1); color: var(--danger); border: 1px solid var(--danger); }
            .msg.success { background: rgba(40,167,69,0.1); color: var(--success); border: 1px solid var(--success); }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>⚡ Dejede Explorer</h1>
            <?php if (!empty($login_error)): ?>
                <div class="msg error"><?php echo htmlspecialchars($login_error); ?></div>
            <?php elseif (isset($_GET['msg'])): ?>
                <div class="msg success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="text" name="login_user" placeholder="Username" autofocus required>
                <input type="password" name="login_pass" placeholder="Password" required>
                <button type="submit">Masuk</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$csrf_token = $_SESSION['csrf_token'];

/* ============================================================
   STATE & ROUTING
   ============================================================ */
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : $root_path;
$resolved_dir = safe_path($root_path, $current_dir);
if ($resolved_dir === false || !is_dir($resolved_dir)) {
    $current_dir = $root_path;
} else {
    $current_dir = rtrim($resolved_dir, '/'); 
    if ($current_dir === '') $current_dir = '/'; 
}
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
$sort_param = "&sort=" . urlencode($sort);

/* ============================================================
   AKSI FILE MANAGER
   ============================================================ */
if (isset($_GET['delete']) && isset($_GET['csrf'])) {
    if (!hash_equals($csrf_token, $_GET['csrf'])) die('CSRF token tidak valid.');
    $name = safe_basename($_GET['delete']);
    if ($name !== false) {
        $target = $current_dir === '/' ? '/' . $name : $current_dir . '/' . $name;
        $safe_target = safe_path($root_path, $target);
        if ($safe_target !== false && $safe_target !== $root_path) {
            is_dir($safe_target) ? @rmdir($safe_target) : @unlink($safe_target);
        }
    }
    header("Location: ?dir=" . urlencode($current_dir) . $sort_param);
    exit;
}

if (isset($_POST['new_folder'])) {
    check_csrf();
    $folder_name = safe_basename($_POST['new_folder']);
    if ($folder_name !== false) {
        $target = $current_dir === '/' ? '/' . $folder_name : $current_dir . '/' . $folder_name;
        @mkdir($target, 0755);
    }
    header("Location: ?dir=" . urlencode($current_dir) . $sort_param);
    exit;
}

if (isset($_POST['new_file'])) {
    check_csrf();
    $file_name = safe_basename($_POST['new_file']);
    if ($file_name !== false) {
        $target = $current_dir === '/' ? '/' . $file_name : $current_dir . '/' . $file_name;
        if (!file_exists($target)) {
            @file_put_contents($target, "");
            @chmod($target, 0644); 
        }
    }
    header("Location: ?dir=" . urlencode($current_dir) . $sort_param);
    exit;
}

// Upload file menolak ekstensi terlarang[cite: 3]
if (isset($_FILES['fileToUpload'])) {
    check_csrf();
    $file = $_FILES['fileToUpload'];
    $orig_name = safe_basename($file['name']);
    if ($orig_name !== false && $file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
        if (in_array($ext, $blocked_extensions, true)) {
            $upload_error = 'Tipe file ini dilarang.';
        } elseif ($file['size'] > $max_upload_size) {
            $upload_error = 'Ukuran file terlalu besar.';
        } else {
            $target_file = $current_dir === '/' ? '/' . $orig_name : $current_dir . '/' . $orig_name;
            @move_uploaded_file($file['tmp_name'], $target_file);
        }
    }
    header("Location: ?dir=" . urlencode($current_dir) . $sort_param);
    exit;
}

if (isset($_POST['save_file'])) {
    check_csrf();
    $file_path = $_POST['file_path'];
    $safe_file = safe_path($root_path, $file_path);
    if ($safe_file !== false && file_exists($safe_file) && !is_dir($safe_file)) {
        file_put_contents($safe_file, $_POST['file_content']);
        $save_dir = dirname($safe_file);
        if ($save_dir === '\\') $save_dir = '/';
    } else {
        $save_dir = $current_dir;
    }
    header("Location: ?dir=" . urlencode($save_dir) . $sort_param);
    exit;
}

if (isset($_POST['chmod_name']) && isset($_POST['chmod_val'])) {
    check_csrf();
    $target_name = safe_basename($_POST['chmod_name']);
    $new_perm = octdec($_POST['chmod_val']); 
    if ($target_name !== false && $new_perm !== 0) {
        $target = $current_dir === '/' ? '/' . $target_name : $current_dir . '/' . $target_name;
        $safe_target = safe_path($root_path, $target);
        if ($safe_target !== false) @chmod($safe_target, $new_perm);
    }
    header("Location: ?dir=" . urlencode($current_dir) . $sort_param);
    exit;
}

if (isset($_GET['download'])) {
    $name = safe_basename($_GET['download']);
    if ($name !== false) {
        $file = $current_dir === '/' ? '/' . $name : $current_dir . '/' . $name;
        $safe_file = safe_path($root_path, $file);
        if ($safe_file !== false && file_exists($safe_file) && !is_dir($safe_file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($safe_file) . '"');
            header('Content-Length: ' . filesize($safe_file));
            readfile($safe_file);
            exit;
        }
    }
}

if (isset($_GET['preview_img'])) {
    $name = safe_basename($_GET['preview_img']);
    if ($name !== false) {
        $file = $current_dir === '/' ? '/' . $name : $current_dir . '/' . $name;
        $safe_file = safe_path($root_path, $file);
        if ($safe_file !== false && file_exists($safe_file) && !is_dir($safe_file)) {
            $ext = strtolower(pathinfo($safe_file, PATHINFO_EXTENSION));
            $mime_types = ['png'=>'image/png', 'jpg'=>'image/jpeg', 'jpeg'=>'image/jpeg', 'gif'=>'image/gif', 'bmp'=>'image/bmp', 'ico'=>'image/x-icon', 'webp'=>'image/webp', 'svg'=>'image/svg+xml'];
            $mime = isset($mime_types[$ext]) ? $mime_types[$ext] : 'application/octet-stream';
            header("Content-Type: $mime");
            header('Content-Length: ' . filesize($safe_file));
            readfile($safe_file);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dejede Explorer</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #f5f5f7; --bg-alt: #ffffff; --bg-alt2: #f0f0f2;
            --border: #e0e0e3; --text: #1c1c1e; --text-muted: #6e6e73;
            --text-dim: #8a8a8e; --accent: #0066cc; --accent-hover: #0077ed;
            --danger: #cc3333; --danger-hover: #dd4444; --link: #0066cc;
            --code-text: #007a5e; --row-hover: #f0f0f2; --modal-bg: rgba(0,0,0,0.8);
        }
        [data-theme="dark"] {
            --bg: #121212; --bg-alt: #181818; --bg-alt2: #1e1e1e;
            --border: #2a2a2a; --text: #e0e0e0; --text-muted: #888;
            --text-dim: #777; --accent: #0066cc; --accent-hover: #0077ed;
            --danger: #cc3333; --danger-hover: #dd4444; --link: #4dabf7;
            --code-text: #00ffcc; --row-hover: #181818; --modal-bg: rgba(0,0,0,0.9);
        }

        body { background: var(--bg); color: var(--text); font-family: -apple-system, sans-serif; display: flex; height: 100vh; overflow: hidden; font-size: 14px; transition: background 0.2s, color 0.2s; }

        .sidebar { width: 260px; background: var(--bg-alt); border-right: 1px solid var(--border); display: flex; flex-direction: column; transition: transform 0.3s ease; z-index: 100; flex-shrink: 0; }
        .sidebar-header { padding: 15px; font-weight: bold; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .sidebar-header .brand { font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
        .sidebar-controls { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

        .sidebar-menu { padding: 15px; overflow-y: auto; flex: 1; }
        .menu-section { font-size: 11px; text-transform: uppercase; color: var(--text-dim); margin-bottom: 8px; font-weight: 600; margin-top: 20px; }
        .menu-section:first-child { margin-top: 0; }
        
        .menu-item { display: flex; align-items: center; gap: 8px; padding: 10px 12px; color: var(--text-muted); text-decoration: none; border-radius: 6px; margin-bottom: 4px; font-size: 13px; cursor:pointer; border:none; background:transparent; width:100%; text-align:left; font-family: inherit; }
        .menu-item:hover, .menu-item.active { background: var(--row-hover); color: var(--text); }

        .theme-toggle { background: var(--bg-alt2); border: 1px solid var(--border); width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; color:var(--text); }
        .menu-toggle { background: transparent; border: none; color: var(--text); font-size: 20px; width: 32px; height: 32px; display: none; align-items: center; justify-content: center; cursor: pointer; }
        .close-btn { display: none; background: transparent; border: none; font-size: 18px; color: var(--text); cursor: pointer; width: 32px; height: 32px; align-items: center; justify-content: center; }

        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { padding: 15px 20px; background: var(--bg-alt); border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }

        .breadcrumb { display: flex; align-items: center; background: var(--bg-alt2); padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); flex: 1; overflow-x: auto; white-space: nowrap; }
        .breadcrumb::-webkit-scrollbar { height: 4px; }
        .breadcrumb a { color: var(--link); text-decoration: none; padding: 0 4px; font-weight: 500; }
        .breadcrumb span { color: var(--text-dim); margin: 0 2px; }

        .action-bar { padding: 12px 20px; background: var(--bg-alt); border-bottom: 1px solid var(--border); display: flex; gap: 15px; flex-wrap: wrap; }
        .action-group { flex: 1; min-width: 250px; display: flex; gap: 8px; }
        .file-input-wrapper { flex: 1; background: var(--bg-alt2); border: 1px solid var(--border); border-radius: 6px; padding: 6px 10px; overflow: hidden; display:flex; align-items:center; }
        .file-input-wrapper input { width: 100%; color: var(--text-muted); font-size: 13px; outline: none; }
        input[type="text"], input[type="password"] { background: var(--bg-alt2); color: var(--text); border: 1px solid var(--border); padding: 8px 12px; border-radius: 6px; font-size: 13px; flex: 1; min-width: 0; outline: none; }

        .btn { background: var(--bg-alt2); color: var(--text); border: 1px solid var(--border); padding: 8px 14px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; white-space: nowrap;}
        .btn:hover { background: var(--row-hover); border-color: var(--accent); }
        .btn-primary { background: var(--accent); border-color: var(--accent-hover); color: #fff; }
        .btn-primary:hover { background: var(--accent-hover); color: #fff; }
        .btn-danger { background: var(--danger); border-color: var(--danger-hover); color: #fff; }
        .btn-danger:hover { background: var(--danger-hover); color: #fff; }
        
        .btn-icon-text { display: inline; margin-left: 5px; }

        .table-container { flex: 1; overflow-y: auto; background: var(--bg-alt); }
        table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
        th { background: var(--bg-alt2); color: var(--text-muted); font-size: 11px; text-transform: uppercase; padding: 12px 15px; position: sticky; top: 0; z-index: 10; border-bottom: 1px solid var(--border); white-space: nowrap; }
        td { padding: 10px 15px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:hover { background: var(--row-hover); }
        
        td a.file-link { color: var(--text); text-decoration: none; display: flex; align-items: center; gap: 8px; font-weight: 500; word-break: break-all; cursor: pointer; }
        td a.file-link:hover { color: var(--link); }
        
        .perm-badge { background: var(--bg-alt2); padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 11px; color: var(--text-dim); border: 1px solid var(--border); cursor: pointer; }
        .perm-badge:hover { border-color: var(--accent); color: var(--accent); }
        
        .action-buttons { display: flex; gap: 6px; justify-content: flex-end; align-items: center; flex-wrap: nowrap; white-space: nowrap; }
        .action-buttons .btn { padding: 6px 10px; font-size: 12px; }
        .sort-arrow { color: var(--text-dim); text-decoration: none; margin-left: 4px; font-size: 10px; }
        .sort-arrow.active { color: var(--accent); font-weight: bold; }

        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: var(--modal-bg); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(3px); padding: 15px; }
        .modal-content { background: var(--bg-alt); padding: 20px; border-radius: 10px; width: 400px; max-width: 100%; max-height: 90%; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 10px; font-size:16px; font-weight:bold; }
        .modal-body { overflow: auto; display: flex; flex-direction: column; gap:12px; }
        .modal-body img { max-width: 100%; max-height: 65vh; display: block; object-fit: contain; }

        /* Chmod Grid UI */
        .chmod-grid { display: grid; grid-template-columns: 70px repeat(4, 1fr); gap: 10px 8px; align-items: center; font-size: 13px; }
        .chmod-grid label { display: flex; align-items: center; gap: 4px; cursor: pointer; font-weight: normal; }

        @media (max-width: 850px) {
            .sidebar { position: fixed; height: 100%; left: -260px; box-shadow: 2px 0 10px rgba(0,0,0,0.2); }
            .sidebar.active { left: 0; }
            .close-btn, .menu-toggle { display: flex; }
            .action-bar { flex-direction: column; gap: 10px; padding: 15px; }
            .action-group { min-width: 100%; }
            th:nth-child(2), td:nth-child(2), th:nth-child(3), td:nth-child(3), th:nth-child(4), td:nth-child(4) { display: none; }
            th:nth-child(1), td:nth-child(1) { width: 100%; }
            th:nth-child(5), td:nth-child(5) { width: 1%; white-space: nowrap; padding-left: 0; }
            .btn-icon-text { display: none; }
            .action-buttons .btn { padding: 8px; width: 32px; height: 32px; }
        }
    </style>
</head>
<body>
    
    <!-- Formulir Tersembunyi untuk CHMOD -->
    <form id="chmodForm" method="post" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" name="chmod_name" id="chmodName">
        <input type="hidden" name="chmod_val" id="chmodVal">
    </form>

    <!-- Modal Preview Gambar -->
    <div id="imageModal" class="modal-overlay" onclick="if(event.target === this) closeModal('imageModal')">
        <div class="modal-content" style="width: auto;">
            <div class="modal-header">
                <span id="previewTitle" style="color:var(--text); word-break:break-all; padding-right:15px;"></span>
                <button onclick="closeModal('imageModal')" class="btn btn-danger" style="padding: 4px 10px;">✕</button>
            </div>
            <div class="modal-body" style="background: var(--bg-alt2); padding: 10px; border-radius: 6px; align-items:center;">
                <img id="previewImg" src="">
            </div>
        </div>
    </div>

    <!-- Modal Pengaturan Hak Akses (CHMOD Baru) -->
    <div id="chmodModal" class="modal-overlay" onclick="if(event.target === this) closeModal('chmodModal')">
        <div class="modal-content" style="width: 380px;">
            <div class="modal-header">
                <span id="chmodModalTitle">Permissions</span>
                <button onclick="closeModal('chmodModal')" class="btn" style="padding: 4px 10px;">✕</button>
            </div>
            <div class="modal-body">
                <div class="chmod-grid">
                    <!-- Header -->
                    <div></div><div>R</div><div>W</div><div>X</div><div></div>
                    
                    <!-- Owner -->
                    <div style="font-weight:500;">Owner</div>
                    <div><input type="checkbox" id="owner_r" onchange="calculateOctal()"></div>
                    <div><input type="checkbox" id="owner_w" onchange="calculateOctal()"></div>
                    <div><input type="checkbox" id="owner_x" onchange="calculateOctal()"></div>
                    <div></div>

                    <!-- Group -->
                    <div style="font-weight:500;">Group</div>
                    <div><input type="checkbox" id="group_r" onchange="calculateOctal()"></div>
                    <div><input type="checkbox" id="group_w" onchange="calculateOctal()"></div>
                    <div><input type="checkbox" id="group_x" onchange="calculateOctal()"></div>
                    <div></div>

                    <!-- Others -->
                    <div style="font-weight:500;">Others</div>
                    <div><input type="checkbox" id="others_r" onchange="calculateOctal()"></div>
                    <div><input type="checkbox" id="others_w" onchange="calculateOctal()"></div>
                    <div><input type="checkbox" id="others_x" onchange="calculateOctal()"></div>
                    <div></div>

                    <!-- Special Flags -->
                    <div></div>
                    <div style="grid-column: span 3;"><label><input type="checkbox" id="special_suid" onchange="calculateOctal()"> Set UID</label></div>
                    <div></div>

                    <div></div>
                    <div style="grid-column: span 3;"><label><input type="checkbox" id="special_sgid" onchange="calculateOctal()"> Set GID</label></div>
                    <div></div>

                    <div></div>
                    <div style="grid-column: span 3;"><label><input type="checkbox" id="special_sticky" onchange="calculateOctal()"> Sticky bit</label></div>
                    <div></div>
                </div>

                <div style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                    <span style="font-weight: 500; width: 60px;">Octal:</span>
                    <input type="text" id="octalInput" value="0644" oninput="parseOctalInput(this.value)" style="font-family: monospace; font-weight: bold;">
                </div>

                <button type="button" onclick="submitChmod()" class="btn btn-primary" style="margin-top: 10px;">💾 Simpan Perubahan</button>
            </div>
        </div>
    </div>

    <!-- Modal Pengaturan Akun -->
    <div id="authModal" class="modal-overlay" onclick="if(event.target === this) closeModal('authModal')" <?php if(!empty($auth_error)) echo 'style="display:flex;"'; ?>>
        <div class="modal-content">
            <div class="modal-header">
                <span>⚙️ Pengaturan Akun</span>
                <button onclick="closeModal('authModal')" class="btn" style="padding: 4px 10px;">✕</button>
            </div>
            <form class="modal-body" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <?php if (!empty($auth_error)): ?>
                    <div style="color: var(--danger); font-size:13px; text-align:center; padding: 8px; background: rgba(204,51,51,0.1); border-radius: 6px; margin-bottom:5px; border: 1px solid var(--danger);"><?php echo htmlspecialchars($auth_error); ?></div>
                <?php endif; ?>

                <label style="font-size:12px; color:var(--text-muted);">Username Baru</label>
                <input type="text" name="new_user" value="<?php echo htmlspecialchars($auth_user); ?>" required>
                
                <label style="font-size:12px; color:var(--text-muted);">Password Baru</label>
                <input type="password" name="new_pass" placeholder="Ketik password baru..." required>

                <hr style="border:0; border-top:1px solid var(--border); margin:5px 0;">

                <label style="font-size:12px; color:var(--danger);">Verifikasi Password Saat Ini</label>
                <input type="password" name="old_pass" placeholder="Password lama untuk konfirmasi..." required>
                
                <button type="submit" name="change_auth" class="btn btn-primary" style="margin-top:10px;">💾 Simpan & Logout</button>
            </form>
        </div>
    </div>

    <script>
        let currentTargetFile = '';

        function openChmodModal(filename, currentPerm) {
            currentTargetFile = filename;
            document.getElementById('chmodModalTitle').textContent = 'Permissions: ' + filename;
            
            let permVal = parseInt(currentPerm, 8);
            if (isNaN(permVal)) permVal = 0644;
            let permStr = permVal.toString(8).padStart(4, '0');

            let special = parseInt(permStr[0]);
            let owner = parseInt(permStr[1]);
            let group = parseInt(permStr[2]);
            let others = parseInt(permStr[3]);

            document.getElementById('special_suid').checked = (special & 4) !== 0;
            document.getElementById('special_sgid').checked = (special & 2) !== 0;
            document.getElementById('special_sticky').checked = (special & 1) !== 0;

            document.getElementById('owner_r').checked = (owner & 4) !== 0;
            document.getElementById('owner_w').checked = (owner & 2) !== 0;
            document.getElementById('owner_x').checked = (owner & 1) !== 0;

            document.getElementById('group_r').checked = (group & 4) !== 0;
            document.getElementById('group_w').checked = (group & 2) !== 0;
            document.getElementById('group_x').checked = (group & 1) !== 0;

            document.getElementById('others_r').checked = (others & 4) !== 0;
            document.getElementById('others_w').checked = (others & 2) !== 0;
            document.getElementById('others_x').checked = (others & 1) !== 0;

            document.getElementById('octalInput').value = permStr;
            document.getElementById('chmodModal').style.display = 'flex';
        }

        function calculateOctal() {
            let o = (document.getElementById('owner_r').checked ? 4 : 0) + 
                    (document.getElementById('owner_w').checked ? 2 : 0) + 
                    (document.getElementById('owner_x').checked ? 1 : 0);

            let g = (document.getElementById('group_r').checked ? 4 : 0) + 
                    (document.getElementById('group_w').checked ? 2 : 0) + 
                    (document.getElementById('group_x').checked ? 1 : 0);

            let e = (document.getElementById('others_r').checked ? 4 : 0) + 
                    (document.getElementById('others_w').checked ? 2 : 0) + 
                    (document.getElementById('others_x').checked ? 1 : 0);

            let s = (document.getElementById('special_suid').checked ? 4 : 0) + 
                    (document.getElementById('special_sgid').checked ? 2 : 0) + 
                    (document.getElementById('special_sticky').checked ? 1 : 0);

            let result = s.toString() + o.toString() + g.toString() + e.toString();
            document.getElementById('octalInput').value = result;
        }

        function parseOctalInput(val) {
            val = val.trim().padStart(4, '0');
            if (val.length > 4) val = val.slice(-4);
            let s = parseInt(val[0], 8) || 0;
            let o = parseInt(val[1], 8) || 0;
            let g = parseInt(val[2], 8) || 0;
            let e = parseInt(val[3], 8) || 0;

            document.getElementById('special_suid').checked = (s & 4) !== 0;
            document.getElementById('special_sgid').checked = (s & 2) !== 0;
            document.getElementById('special_sticky').checked = (s & 1) !== 0;

            document.getElementById('owner_r').checked = (o & 4) !== 0;
            document.getElementById('owner_w').checked = (o & 2) !== 0;
            document.getElementById('owner_x').checked = (o & 1) !== 0;

            document.getElementById('group_r').checked = (g & 4) !== 0;
            document.getElementById('group_w').checked = (g & 2) !== 0;
            document.getElementById('group_x').checked = (g & 1) !== 0;

            document.getElementById('others_r').checked = (e & 4) !== 0;
            document.getElementById('others_w').checked = (e & 2) !== 0;
            document.getElementById('others_x').checked = (e & 1) !== 0;
        }

        function submitChmod() {
            let val = document.getElementById('octalInput').value.trim();
            document.getElementById('chmodName').value = currentTargetFile;
            document.getElementById('chmodVal').value = val;
            document.getElementById('chmodForm').submit();
        }

        function showPreview(url, name) {
            document.getElementById('previewTitle').textContent = name;
            document.getElementById('previewImg').src = url;
            document.getElementById('imageModal').style.display = 'flex';
        }
        function openAuthModal() {
            document.getElementById('authModal').style.display = 'flex';
        }
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
            if (id === 'imageModal') document.getElementById('previewImg').src = '';
        }
    </script>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <span class="brand">⚡ Dejede Explorer</span>
            <div class="sidebar-controls">
                <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()">🌙</button>
                <button class="close-btn" onclick="toggleSidebar()">✕</button>
            </div>
        </div>
        <div class="sidebar-menu">
            <div class="menu-section">Utama</div>
            <a href="?dir=<?php echo urlencode($root_path); ?>" class="menu-item <?php echo $current_dir === $root_path ? 'active' : ''; ?>">🌐 Root (Terkunci)</a>
            
            <div class="menu-section">OpenWrt Links</div>
            <a href="?dir=%2Fetc%2Fconfig" class="menu-item <?php echo strpos($current_dir, '/etc/config') === 0 ? 'active' : ''; ?>">⚙️ /etc/config</a>
            <a href="?dir=%2Fwww" class="menu-item <?php echo strpos($current_dir, '/www') === 0 ? 'active' : ''; ?>">🌐 /www</a>
            <a href="?dir=%2Ftmp" class="menu-item <?php echo strpos($current_dir, '/tmp') === 0 ? 'active' : ''; ?>">🗑️ /tmp</a>
            <a href="?dir=%2Froot" class="menu-item <?php echo strpos($current_dir, '/root') === 0 ? 'active' : ''; ?>">🏠 /root</a>
            <a href="?dir=%2Fetc" class="menu-item <?php echo strpos($current_dir, '/etc') === 0 && strpos($current_dir, '/etc/config') !== 0 ? 'active' : ''; ?>">📁 /etc (System)</a>
            <a href="?dir=%2Fusr%2Fbin" class="menu-item <?php echo strpos($current_dir, '/usr/bin') === 0 ? 'active' : ''; ?>">⚡ /usr/bin</a>
            <a href="?dir=%2Fvar" class="menu-item <?php echo strpos($current_dir, '/var') === 0 ? 'active' : ''; ?>">📊 /var</a>
            <a href="?dir=%2Fmnt" class="menu-item <?php echo strpos($current_dir, '/mnt') === 0 ? 'active' : ''; ?>">💾 /mnt (Storage)</a>
            
            <div class="menu-section">Pengaturan</div>
            <button onclick="openAuthModal()" class="menu-item">🔑 Akun & Sandi</button>
            <a href="?logout=1" class="menu-item">🚪 Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="topbar">
            <button class="menu-toggle" onclick="toggleSidebar()" style="background:var(--bg-alt2); border:1px solid var(--border); padding:4px;">☰</button>
            <div class="breadcrumb">
                <?php
                if ($root_path === '/') {
                    echo "<a href='?dir=%2F'>root</a>";
                } else {
                    $rel = trim(substr($current_dir, strlen($root_path)), '/');
                    echo "<a href='?dir=" . urlencode($root_path) . "'>root</a>";
                }
                $rel = trim(substr($current_dir, strlen($root_path === '/' ? '' : $root_path)), '/');
                if ($rel !== '') {
                    $parts = explode('/', $rel);
                    $accumulate = $root_path === '/' ? '' : $root_path;
                    foreach ($parts as $part) {
                        if ($part === '') continue;
                        $accumulate .= '/' . $part;
                        echo "<span>/</span><a href='?dir=" . urlencode($accumulate) . "'>" . htmlspecialchars($part) . "</a>";
                    }
                }
                ?>
            </div>
            <span style="color:var(--text-muted);font-size:13px;font-weight:500;">👤 <?php echo htmlspecialchars($auth_user); ?></span>
        </div>

        <div class="action-bar">
            <form class="action-group" action="?dir=<?php echo urlencode($current_dir) . $sort_param; ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="file-input-wrapper"><input type="file" name="fileToUpload" required></div>
                <button type="submit" class="btn btn-primary">📤<span class="btn-icon-text">Upload</span></button>
            </form>
            <form class="action-group" action="?dir=<?php echo urlencode($current_dir) . $sort_param; ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="text" name="new_folder" placeholder="Nama folder..." required>
                <button type="submit" class="btn">📁<span class="btn-icon-text">Buat Folder</span></button>
            </form>
            <form class="action-group" action="?dir=<?php echo urlencode($current_dir) . $sort_param; ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="text" name="new_file" placeholder="Nama berkas..." required>
                <button type="submit" class="btn">📄<span class="btn-icon-text">Buat Berkas</span></button>
            </form>
        </div>

        <div class="table-container">
            <?php
            function getSortLink($col, $current_sort, $dir) {
                $asc = $col . '_asc';
                $desc = $col . '_desc';
                $next = $asc;
                $arrow = '↕';
                $class = 'sort-arrow';
                if ($current_sort === $asc) { $next = $desc; $arrow = '▲'; $class .= ' active'; }
                elseif ($current_sort === $desc) { $next = $asc; $arrow = '▼'; $class .= ' active'; }
                return "<a href='?dir=" . urlencode($dir) . "&sort=$next' class='$class'>$arrow</a>";
            }

            $edit_file = null;
            if (isset($_GET['edit'])) {
                $edit_name = safe_basename($_GET['edit']);
                if ($edit_name !== false) {
                    $candidate = $current_dir === '/' ? '/' . $edit_name : $current_dir . '/' . $edit_name;
                    $safe_edit = safe_path($root_path, $candidate);
                    if ($safe_edit !== false && file_exists($safe_edit) && !is_dir($safe_edit)) {
                        $edit_file = $safe_edit;
                    }
                }
            }
            ?>
            
            <?php if ($edit_file !== null): ?>
                <div style="padding: 20px;">
                    <h3 style="margin-bottom: 15px;">📝 Edit: <?php echo htmlspecialchars(basename($edit_file)); ?></h3>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($edit_file); ?>">
                        <textarea name="file_content" rows="22" style="width:100%; background:var(--bg-alt2); color:var(--code-text); border:1px solid var(--border); padding:15px; border-radius:8px; font-family:monospace; font-size:13px; outline:none; resize:vertical;"><?php echo htmlspecialchars(@file_get_contents($edit_file)); ?></textarea><br>
                        <div style="margin-top: 15px; display: flex; gap: 10px;">
                            <button type="submit" name="save_file" class="btn btn-primary">💾 Simpan</button>
                            <a href="?dir=<?php echo urlencode($current_dir) . $sort_param; ?>" class="btn">❌ Batal</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nama Berkas <?php echo getSortLink('name', $sort, $current_dir); ?></th>
                            <th>Ukuran <?php echo getSortLink('size', $sort, $current_dir); ?></th>
                            <th>Modifikasi <?php echo getSortLink('date', $sort, $current_dir); ?></th>
                            <th>Akses</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $scan = @scandir($current_dir);
                    if ($scan !== false) {
                        $is_root = ($current_dir === $root_path || $current_dir === '/');
                        
                        $items = [];
                        foreach ($scan as $file) {
                            if ($file == '.' || $file == '..') continue; 
                            $path = $current_dir === '/' ? '/' . $file : $current_dir . '/' . $file;
                            $is_dir = is_dir($path);
                            $items[] = [
                                'name'   => $file,
                                'path'   => $path,
                                'is_dir' => $is_dir,
                                'size'   => $is_dir ? 0 : (int)@filesize($path),
                                'mtime'  => (int)@filemtime($path),
                                'perms'  => substr(sprintf('%o', @fileperms($path)), -4)
                            ];
                        }

                        usort($items, function($a, $b) use ($sort) {
                            if ($a['is_dir'] !== $b['is_dir']) return $a['is_dir'] ? -1 : 1; 
                            switch ($sort) {
                                case 'size_asc':  return $a['size'] <=> $b['size'];
                                case 'size_desc': return $b['size'] <=> $a['size'];
                                case 'date_asc':  return $a['mtime'] <=> $b['mtime'];
                                case 'date_desc': return $b['mtime'] <=> $a['mtime'];
                                case 'name_desc': return strcasecmp($b['name'], $a['name']);
                                case 'name_asc':
                                default:          return strcasecmp($a['name'], $b['name']);
                            }
                        });

                        if (!$is_root) {
                            $parent_dir = dirname($current_dir);
                            if ($parent_dir === '\\') $parent_dir = '/';
                            echo "<tr style='background: var(--bg-alt2);'>";
                            echo "<td><a href='?dir=" . urlencode($parent_dir) . "' class='file-link' style='color:var(--accent);'>🔙 <strong>.. (Kembali)</strong></a></td>";
                            echo "<td>-</td><td>-</td><td>-</td><td></td>";
                            echo "</tr>";
                        }

                        $img_exts = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'ico', 'webp', 'svg'];

                        foreach ($items as $item) {
                            $file = $item['name'];
                            $path = $item['path'];
                            $display_name = htmlspecialchars($file);
                            $size_str = $item['is_dir'] ? '-' : $item['size'] . ' B';
                            $mtime_str = date("Y-m-d H:i", $item['mtime']);
                            $perm_str = $item['perms'];
                            
                            $enc_file = urlencode($file);
                            $enc_dir = urlencode($current_dir);
                            $del_link = "?dir=$enc_dir&delete=$enc_file&csrf=" . urlencode($csrf_token) . $sort_param;

                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            $is_img = !$item['is_dir'] && in_array($ext, $img_exts, true);
                            $preview_link = "?dir=$enc_dir&preview_img=$enc_file";

                            echo "<tr>";
                            if ($item['is_dir']) {
                                echo "<td><a href='?dir=" . urlencode($path) . "' class='file-link'>📁 <strong>$display_name</strong></a></td>";
                                echo "<td style='color:var(--text-dim);'>$size_str</td>";
                                echo "<td style='color:var(--text-dim);'>$mtime_str</td>";
                                echo "<td><span class='perm-badge' onclick=\"openChmodModal('$display_name', '$perm_str')\" title='Ubah Hak Akses'>$perm_str</span></td>";
                                echo "<td>
                                        <div class='action-buttons'>
                                            <button onclick=\"openChmodModal('$display_name', '$perm_str')\" class='btn' title='Ubah Hak Akses'>🔑<span class='btn-icon-text'>Perms</span></button>
                                            <a href='$del_link' onclick=\"return confirm('Hapus folder $display_name dan seluruh isinya?')\" class='btn btn-danger' title='Hapus Folder'>🗑️<span class='btn-icon-text'>Hapus</span></a>
                                        </div>
                                      </td>";
                            } else {
                                if ($is_img) {
                                    echo "<td><a onclick=\"showPreview('$preview_link', '$display_name')\" class='file-link'>🖼️ $display_name</a></td>";
                                } else {
                                    echo "<td><a href='?dir=$enc_dir&edit=$enc_file' class='file-link'>📄 $display_name</a></td>";
                                }
                                
                                echo "<td style='color:var(--text-dim);'>$size_str</td>";
                                echo "<td style='color:var(--text-dim);'>$mtime_str</td>";
                                echo "<td><span class='perm-badge' onclick=\"openChmodModal('$display_name', '$perm_str')\" title='Ubah Hak Akses'>$perm_str</span></td>";
                                
                                echo "<td>
                                        <div class='action-buttons'>
                                            <a href='?dir=$enc_dir&download=$enc_file' class='btn' title='Download'>⬇️<span class='btn-icon-text'>Unduh</span></a>";
                                
                                if ($is_img) {
                                    echo "<button onclick=\"showPreview('$preview_link', '$display_name')\" class='btn' title='Lihat Gambar'>👁️<span class='btn-icon-text'>Lihat</span></button>";
                                } else {
                                    echo "<a href='?dir=$enc_dir&edit=$enc_file' class='btn' title='Edit File'>✏️<span class='btn-icon-text'>Edit</span></a>";
                                }
                                            
                                echo "      <button onclick=\"openChmodModal('$display_name', '$perm_str')\" class='btn' title='Ubah Hak Akses'>🔑<span class='btn-icon-text'>Perms</span></button>
                                            <a href='$del_link' onclick=\"return confirm('Yakin menghapus file $display_name?')\" class='btn btn-danger' title='Hapus File'>🗑️<span class='btn-icon-text'>Hapus</span></a>
                                        </div>
                                      </td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='color:var(--danger);text-align:center;padding:20px;'>Direktori kosong / tidak ada hak akses baca.</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); }
        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            document.getElementById('themeToggle').textContent = theme === 'dark' ? '☀️' : '🌙';
        }
        function toggleTheme() {
            const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            localStorage.setItem('dejede_theme', next);
            applyTheme(next);
        }
        (function() { 
            const savedTheme = localStorage.getItem('dejede_theme') || 'light';
            applyTheme(savedTheme); 
        })();
    </script>
</body>
</html>