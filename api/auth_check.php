<?php
// api/auth_check.php
// ======================================================
// STANDARISASI CEK LOGIN UNTUK SEMUA HALAMAN USER
// ======================================================
// Digunakan di: daftar_alat.php, pinjam.php, pembayaran.php, 
// instruksi_bca.php, instruksi_gopay.php, sukses.php, dll
// ======================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ======================================================
// 1. START SESSION
// ======================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ======================================================
// 2. CEK LOGIN DARI SESSION
// ======================================================
$is_logged_in = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;

// ======================================================
// 3. FALLBACK: CEK DARI COOKIE (sm_uid)
// ======================================================
if (!$is_logged_in && isset($_COOKIE['sm_uid'])) {
    require_once 'koneksi.php';
    
    $uid = (int)$_COOKIE['sm_uid'];
    $stmt = mysqli_prepare($conn, "SELECT id_users, username, role FROM users WHERE id_users = ?");
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $uid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($user) {
            // Restore session dari cookie
            $_SESSION['user_id'] = $user['id_users'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $is_logged_in = true;
        }
    }
}

// ======================================================
// 4. JIKA TIDAK LOGIN, REDIRECT KE LOGIN
// ======================================================
if (!$is_logged_in) {
    // Simpan halaman tujuan untuk redirect setelah login
    $current_url = $_SERVER['REQUEST_URI'];
    setcookie("redirect_after_login", $current_url, time() + 3600, "/");
    
    header("Location: login.php");
    exit();
}

// ======================================================
// 5. SET VARIABEL GLOBAL UNTUK DIGUNAKAN DI HALAMAN
// ======================================================
$user_id = (int)$_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8');
$role = $_SESSION['role'] ?? 'user';

// ======================================================
// 6. (OPSIONAL) AMBIL DATA LENGKAP USER DARI DATABASE
// ======================================================
// Jika butuh data tambahan seperti nama_depan, nama_belakang, email
// Bisa diaktifkan jika diperlukan

/*
require_once 'koneksi.php';
$stmt = mysqli_prepare($conn, "SELECT nama_depan, nama_belakang, email FROM users WHERE id_users = ?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$nama_depan = $user_data['nama_depan'] ?? $username;
$nama_belakang = $user_data['nama_belakang'] ?? '';
$email = $user_data['email'] ?? '';
$namaLengkap = trim($nama_depan . ' ' . $nama_belakang) ?: $username;
*/

// ======================================================
// 7. FUNGSI HELPER (Opsional)
// ======================================================

/**
 * Cek apakah user memiliki role tertentu
 */
function hasRole($role_check) {
    global $role;
    return $role === $role_check;
}

/**
 * Cek apakah user adalah administrator
 */
function isAdmin() {
    return hasRole('administrator');
}

/**
 * Cek apakah user adalah petani
 */
function isPetani() {
    return hasRole('petani');
}

/**
 * Cek apakah user adalah petugas lapangan
 */
function isPetugasLapangan() {
    return hasRole('petugas_lapangan');
}

/**
 * Cek apakah user adalah koordinator irigasi
 */
function isKoordinator() {
    return hasRole('koordinator_irigasi');
}
?>