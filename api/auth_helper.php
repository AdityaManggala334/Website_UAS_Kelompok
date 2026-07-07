<?php
// api/auth_helper.php
// ======================================================
// AUTHENTICATION HELPER - LADUSYNC (DENGAN JWT)
// ======================================================

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/koneksi.php';

// ============================================================
// FUNGSI VERIFIKASI JWT
// ============================================================

function verifyJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    
    list($header, $payload, $signature) = $parts;
    $expectedSignature = base64_encode(hash_hmac('sha256', $header . '.' . $payload, 'LADUSYNC_SECRET_KEY', true));
    
    if ($signature !== $expectedSignature) return null;
    
    $data = json_decode(base64_decode($payload), true);
    if (!$data || isset($data['exp']) && $data['exp'] < time()) return null;
    
    return $data;
}

// ============================================================
// MULAI SESSION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// CEK LOGIN: JWT > SESSION > COOKIE (sm_uid)
// ============================================================

$is_logged_in = false;
$user_id = 0;
$username = 'Guest';
$namaDepan = 'Guest';
$namaBelakang = '';
$namaLengkap = 'Pengunjung Umum';
$email = '';
$role = 'guest';

// === 1. CEK JWT DARI COOKIE (Prioritas Utama) ===
if (isset($_COOKIE['auth_token'])) {
    $tokenData = verifyJWT($_COOKIE['auth_token']);
    if ($tokenData) {
        $user_id = $tokenData['user_id'];
        $username = $tokenData['username'] ?? 'User';
        $namaDepan = $tokenData['nama_depan'] ?? $username;
        $role = $tokenData['role'] ?? 'guest';
        $is_logged_in = true;
        $namaLengkap = $namaDepan;
        
        // Sinkronkan ke session (fallback)
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['nama_depan'] = $namaDepan;
    }
}

// === 2. FALLBACK: CEK SESSION (jika JWT tidak ada) ===
if (!$is_logged_in && isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    $user_id = (int)$_SESSION['user_id'];
    $username = $_SESSION['username'] ?? 'User';
    $namaDepan = $_SESSION['nama_depan'] ?? $username;
    $role = $_SESSION['role'] ?? 'guest';
    $is_logged_in = true;
    $namaLengkap = $namaDepan;
}

// === 3. FALLBACK: CEK COOKIE LAMA (sm_uid) ===
if (!$is_logged_in && isset($_COOKIE['sm_uid'])) {
    $uid = (int)$_COOKIE['sm_uid'];
    $stmt = mysqli_prepare($conn,
        "SELECT id_users, username, nama_depan, role FROM users WHERE id_users = ? LIMIT 1"
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $uid);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        
        if ($user) {
            $user_id = $user['id_users'];
            $username = $user['username'];
            $namaDepan = $user['nama_depan'] ?? $username;
            $role = $user['role'] ?? 'guest';
            $is_logged_in = true;
            $namaLengkap = $namaDepan;
            
            // Sinkronkan ke session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['nama_depan'] = $namaDepan;
        }
    }
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
function hasRole($roleCheck) {
    global $role;
    if (!isLoggedIn()) return false;
    return $role === $roleCheck;
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

?>
