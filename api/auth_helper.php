<?php
// api/auth_helper.php
// ======================================================
// AUTHENTICATION HELPER - LADUSYNC
// ======================================================

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/koneksi.php';

// ============================================================
// CEK SESSION ATAU COOKIE (Support kedua metode)
// ============================================================

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === CEK SESSION TERLEBIH DAHULU ===
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    $uid = (int)$_SESSION['user_id'];
    $is_logged_in = true;
} 
// === JIKA SESSION TIDAK ADA, CEK COOKIE ===
elseif (isset($_COOKIE['sm_uid'])) {
    $uid = (int)$_COOKIE['sm_uid'];
    $is_logged_in = true;
    
    // Sinkronkan cookie ke session
    $_SESSION['user_id'] = $uid;
} 
// === TIDAK ADA SESSION ATAU COOKIE ===
else {
    $uid = 0;
    $is_logged_in = false;
}

// ============================================================
// AMBIL DATA USER DARI DATABASE
// ============================================================

if ($uid > 0) {
    $stmt = mysqli_prepare($conn,
        "SELECT id_users, nama_depan, nama_belakang, username, email, role
         FROM users WHERE id_users = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    
    if ($user) {
        // Set variabel user
        $user_id      = (int)$user['id_users'];
        $username     = $user['username'];
        $nama_depan   = $user['nama_depan'];
        $nama_belakang = $user['nama_belakang'];
        $email        = $user['email'] ?? '';
        $role         = $user['role'];
        
        $namaDepan   = htmlspecialchars($nama_depan ?: $username);
        $namaBelakang = htmlspecialchars($nama_belakang ?? '');
        $namaLengkap = htmlspecialchars(trim($nama_depan . ' ' . $nama_belakang) ?: $username);
        
        // Sinkronkan session jika login via cookie
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['nama_depan'] = $nama_depan;
            $_SESSION['nama_belakang'] = $nama_belakang;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;
        }
        
        $is_logged_in = true;
    } else {
        // User tidak ditemukan, hapus cookie/session
        setcookie('sm_uid', '', time() - 3600, '/');
        unset($_SESSION['user_id']);
        $is_logged_in = false;
        
        // Set default guest values
        $user_id = 0;
        $username = 'Guest';
        $namaDepan = 'Guest';
        $namaBelakang = '';
        $namaLengkap = 'Pengunjung Umum';
        $email = '';
        $role = 'guest';
    }
} else {
    // Guest / Tidak login
    $user_id = 0;
    $username = 'Guest';
    $namaDepan = 'Guest';
    $namaBelakang = '';
    $namaLengkap = 'Pengunjung Umum';
    $email = '';
    $role = 'guest';
}

// ============================================================
// FUNGSI HELPER
// ============================================================

/**
 * Cek apakah user sudah login
 */
function isLoggedIn() {
    global $is_logged_in;
    return isset($is_logged_in) && $is_logged_in === true;
}

/**
 * Cek apakah user memiliki role tertentu
 */
function hasRole($role) {
    global $role;
    if (!isLoggedIn()) return false;
    return $role === $role;
}

/**
 * Redirect ke halaman login jika belum login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Redirect jika role tidak sesuai
 */
function requireRole($requiredRole) {
    global $role;
    requireLogin();
    if ($role !== $requiredRole) {
        header("Location: index.php");
        exit();
    }
}

/**
 * Ambil data user saat ini
 */
function getCurrentUser() {
    global $user_id, $username, $namaDepan, $namaBelakang, $namaLengkap, $email, $role;
    return [
        'id' => $user_id,
        'username' => $username,
        'nama_depan' => $namaDepan,
        'nama_belakang' => $namaBelakang,
        'nama_lengkap' => $namaLengkap,
        'email' => $email,
        'role' => $role,
        'is_logged_in' => isLoggedIn()
    ];
}

// ============================================================
// DEBUG (Hapus di production)
// ============================================================
// error_log("AUTH: user_id=" . ($user_id ?? 0) . ", is_logged_in=" . ($is_logged_in ? 'true' : 'false'));
?>