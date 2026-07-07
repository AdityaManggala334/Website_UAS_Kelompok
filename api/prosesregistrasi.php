<?php
// KONFIGURASI AWAL DAN INISIALISASI
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ PERBAIKAN 1: Gunakan koneksi.php (bukan config.php)
require_once __DIR__ . '/koneksi.php';
global $conn;

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal!");
}

// Cek apakah ada data POST
if (!isset($_POST['register'])) {
    header("Location: login.php");
    exit();
}

// ✅ PERBAIKAN 2: Ambil data sesuai field di form
$nama_depan    = trim($_POST['nama_depan'] ?? '');
$nama_belakang = trim($_POST['nama_belakang'] ?? '');
$username      = trim($_POST['username'] ?? '');
$email         = trim($_POST['email'] ?? '');
$password      = $_POST['password'] ?? '';
$konfirm       = $_POST['konfirm'] ?? '';

// ====== VALIDASI INPUT ======
if ($nama_depan === '' || $username === '' || $email === '' || $password === '') {
    header("Location: login.php?tab=register&reg_error=kosong");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: login.php?tab=register&reg_error=email_invalid");
    exit();
}

if (strlen($password) < 6) {
    header("Location: login.php?tab=register&reg_error=pendek");
    exit();
}

if ($password !== $konfirm) {
    header("Location: login.php?tab=register&reg_error=beda");
    exit();
}

// ====== CEK APAKAH USERNAME ATAU EMAIL SUDAH TERDAFTAR ======
$stmt = mysqli_prepare($conn, "SELECT id_users FROM users WHERE username = ? OR email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'ss', $username, $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    header("Location: login.php?tab=register&reg_error=duplikat");
    exit();
}
mysqli_stmt_close($stmt);

// ====== HASH PASSWORD & INSERT USER BARU ======
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$defaultRole = 'petani'; // Role default untuk akun baru

// ✅ PERBAIKAN 3: Sesuaikan dengan struktur tabel users
$insert = mysqli_prepare($conn, 
    "INSERT INTO users (nama_depan, nama_belakang, username, email, password, role) 
     VALUES (?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($insert, 'ssssss', 
    $nama_depan, 
    $nama_belakang, 
    $username, 
    $email, 
    $hashedPassword, 
    $defaultRole
);

if (mysqli_stmt_execute($insert)) {
    mysqli_stmt_close($insert);
    // ====== SUKSES, ARAHKAN KE LOGIN ======
    header("Location: login.php?sukses=register");
    exit();
} else {
    $error = mysqli_stmt_error($insert);
    mysqli_stmt_close($insert);
    die("Error menyimpan data: " . $error);
}
?>