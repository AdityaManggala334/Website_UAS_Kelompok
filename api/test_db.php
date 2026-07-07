<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>=== TEST KONEKSI DATABASE TIDB ===</h2>";

// Panggil koneksi
require_once 'koneksi.php';

// Cek apakah koneksi berhasil
if (!$conn) {
    echo "<p style='color:red'>❌ Koneksi GAGAL: " . mysqli_connect_error() . "</p>";
    exit();
}

echo "<p style='color:green'>✅ Koneksi BERHASIL!</p>";

// Cek SSL
$result = mysqli_query($conn, "SHOW STATUS LIKE 'Ssl_cipher'");
if ($result && $row = mysqli_fetch_assoc($result)) {
    echo "<p>🔒 SSL Aktif - Cipher: " . $row['Value'] . "</p>";
}

// Cek jumlah users
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "<p>📊 Total users dalam database: " . $row['total'] . "</p>";
} else {
    echo "<p style='color:red'>❌ Error query users: " . mysqli_error($conn) . "</p>";
}

// Tampilkan daftar users
$result = mysqli_query($conn, "SELECT id_users, username, email, role FROM users LIMIT 10");
if ($result && mysqli_num_rows($result) > 0) {
    echo "<h3>📋 Daftar Users:</h3>";
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse'>";
    echo "<tr style='background:#ddd'><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id_users'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>⚠️ Tabel users kosong atau tidak ditemukan.</p>";
}

echo "<hr>";
echo "<p>✅ Test selesai. Jika muncul data users, koneksi database berhasil.</p>";
?>
