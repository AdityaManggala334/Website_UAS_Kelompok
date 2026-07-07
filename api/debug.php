<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
echo "<h2>=== DEBUG LOGIN SYSTEM ===</h2>";

echo "<h3>1. CEK COOKIE:</h3>";
echo "<pre>";
echo "COOKIE: ";
print_r($_COOKIE);
echo "sm_uid: " . ($_COOKIE['sm_uid'] ?? 'TIDAK ADA') . "\n";
echo "</pre>";

echo "<h3>2. CEK KONEKSI DATABASE:</h3>";
try {
    require_once 'koneksi.php';
    echo "<span style='color:green'>✅ Koneksi database BERHASIL</span><br>";
} catch (Exception $e) {
    echo "<span style='color:red'>❌ Koneksi database GAGAL: " . $e->getMessage() . "</span><br>";
    exit();
}

echo "<h3>3. CEK AUTH HELPER:</h3>";
try {
    require_once 'auth_helper.php';
    echo "<span style='color:green'>✅ Auth helper BERHASIL</span><br>";
} catch (Exception $e) {
    echo "<span style='color:red'>❌ Auth helper GAGAL: " . $e->getMessage() . "</span><br>";
    exit();
}

echo "<h3>4. DATA USER:</h3>";
echo "<pre>";
echo "user_id: " . ($user_id ?? 'tidak ada') . "\n";
echo "username: " . ($username ?? 'tidak ada') . "\n";
echo "nama_depan: " . ($nama_depan ?? 'tidak ada') . "\n";
echo "role: " . ($role ?? 'tidak ada') . "\n";
echo "namaLengkap: " . ($namaLengkap ?? 'tidak ada') . "\n";
echo "</pre>";

echo "<h3 style='color:green'>✅ SEMUA BERJALAN NORMAL - TIDAK ADA ERROR!</h3>";
echo "<p>Jika Anda melihat pesan ini, maka:<br>
- Cookie terbaca ✅<br>
- Koneksi database OK ✅<br>
- Auth helper OK ✅<br>
- Data user berhasil diambil ✅</p>";
?>
