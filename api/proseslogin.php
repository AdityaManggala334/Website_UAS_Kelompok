<?php
// api/proseslogin.php
// ======================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'koneksi.php';

if (!isset($_POST['login'])) {
    header("Location: login.php");
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header("Location: login.php?error=kosong");
    exit();
}

$stmt = mysqli_prepare($conn,
    "SELECT id_users, nama_depan, nama_belakang, username, email, password, role
     FROM users
     WHERE email = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user || !password_verify($password, $user['password'])) {
    header("Location: login.php?error=salah");
    exit();
}

// ============================================================
// START SESSION
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// SIMPAN DATA USER KE SESSION (fallback)
// ============================================================
$_SESSION['user_id'] = $user['id_users'];
$_SESSION['nama_depan'] = $user['nama_depan'];
$_SESSION['nama_belakang'] = $user['nama_belakang'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

$header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
$payload = base64_encode(json_encode([
    'user_id' => $user['id_users'],
    'username' => $user['username'],
    'nama_depan' => $user['nama_depan'],
    'role' => $user['role'],
    'exp' => time() + (24 * 60 * 60) // 24 jam
]));
$signature = base64_encode(hash_hmac('sha256', $header . '.' . $payload, 'LADUSYNC_SECRET_KEY', true));
$jwt = $header . '.' . $payload . '.' . $signature;

// Simpan JWT di cookie (httpOnly untuk keamanan)
setcookie('auth_token', $jwt, time() + (24 * 60 * 60), '/', '', false, true);

$expire = time() + (8 * 60 * 60);
setcookie('sm_uid', (string)$user['id_users'], $expire, '/', '', false, true);

header("Location: index.php");
exit();
?>
