<?php
// api/proses_laporan.php
// ======================================================
// PROSES LAPORAN KENDALA - AJAX VERSION (RETURN JSON)
// ======================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'koneksi.php';

// ✅ Set header untuk JSON
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah ada data POST
if (!isset($_POST['kirim_laporan'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses tidak sah.']);
    exit();
}

// Ambil data
$nama_pelapor  = trim($_POST['nama_pelapor'] ?? '');
$lokasi        = trim($_POST['lokasi_kendala'] ?? '');
$jenis_kendala = trim($_POST['jenis_kendala'] ?? '');
$deskripsi     = trim($_POST['deskripsi'] ?? '');

// Validasi
if (empty($nama_pelapor) || empty($lokasi) || empty($jenis_kendala)) {
    echo json_encode(['status' => 'error', 'message' => 'Mohon isi semua kolom.']);
    exit();
}

// ID user (jika login)
$id_users = null;
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    $id_users = (int)$_SESSION['user_id'];
}

// Simpan ke database
$stmt = mysqli_prepare($conn, 
    "INSERT INTO laporan_kendala (id_users, nama_pelapor, lokasi, jenis_kendala, deskripsi, status) 
     VALUES (?, ?, ?, ?, ?, 'baru')"
);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . mysqli_error($conn)]);
    exit();
}

mysqli_stmt_bind_param($stmt, 'issss', $id_users, $nama_pelapor, $lokasi, $jenis_kendala, $deskripsi);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success', 'message' => 'Laporan berhasil dikirim! Petugas akan segera menangani.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan laporan. Coba lagi.']);
}

mysqli_stmt_close($stmt);
exit();
?>