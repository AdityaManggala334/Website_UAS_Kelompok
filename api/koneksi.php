<?php
// api/koneksi.php
// ======================================================
// KONEKSI DATABASE - LADUSYNC
// OPTIMIZED FOR VERCEL + TiDB CLOUD
// ======================================================

// ============================================================
// KONFIGURASI DARI ENVIRONMENT VARIABLES (VERCEL)
// ============================================================

$host = getenv('DB_HOST') ?: 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$port = getenv('DB_PORT') ?: '4000';
$user = getenv('DB_USER') ?: '3VymqArhi1i7iYr.root';
$pass = getenv('DB_PASS') ?: 'P1TaHA9Kfhr6vJ1V';
$db   = getenv('DB_NAME') ?: 'db_ladusync';

// ============================================================
// KONEKSI KE TiDB CLOUD
// ============================================================

// Inisialisasi mysqli
$conn = mysqli_init();

if (!$conn) {
    die("Error: mysqli_init gagal");
}

// Set timeout untuk koneksi (penting untuk Vercel serverless)
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 30);

// Coba koneksi dengan SSL (wajib untuk TiDB Serverless)
$ssl_connected = @mysqli_real_connect(
    $conn, 
    $host, 
    $user, 
    $pass, 
    $db, 
    $port, 
    NULL, 
    MYSQLI_CLIENT_SSL
);

// Jika SSL gagal, coba tanpa SSL (untuk local development)
if (!$ssl_connected) {
    // Reset koneksi
    $conn = mysqli_init();
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 30);
    
    // Coba tanpa SSL
    $conn = @mysqli_real_connect(
        $conn, 
        $host, 
        $user, 
        $pass, 
        $db, 
        $port
    );
}

// Jika masih gagal, tampilkan error
if (!$conn) {
    // Log error untuk debugging (di Vercel akan muncul di logs)
    error_log("Koneksi database gagal: " . mysqli_connect_error());
    
    // Tampilkan pesan error yang aman (jangan tampilkan detail di production)
    die("Koneksi database gagal. Silakan coba lagi nanti.");
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Cek apakah koneksi masih aktif
if (!mysqli_ping($conn)) {
    die("Koneksi database terputus.");
}

// ============================================================
// FUNGSI HELPER UNTUK QUERY YANG AMAN
// ============================================================

/**
 * Eksekusi query dengan error handling
 */
function query($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("Query error: " . mysqli_error($conn) . " | SQL: " . $sql);
        return false;
    }
    return $result;
}

/**
 * Escape string untuk keamanan
 */
function escape($string) {
    global $conn;
    return mysqli_real_escape_string($conn, $string);
}

/**
 * Cek apakah koneksi aktif
 */
function is_connected() {
    global $conn;
    return $conn !== null && mysqli_ping($conn);
}

// ============================================================
// SESSION START (Untuk Vercel serverless)
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// DEBUG (Hanya untuk development, matikan di production)
// ============================================================
// if (getenv('APP_ENV') === 'development') {
//     error_log("Database connected: " . mysqli_get_host_info($conn));
// }
?>