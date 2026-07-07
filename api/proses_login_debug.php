<?php
// api/test_login.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>TEST LOGIN STEP BY STEP</h2>";

// STEP 1: Test koneksi
echo "<h3>1. Test Koneksi Database</h3>";
require_once 'koneksi.php';
if ($conn) {
    echo "✅ Koneksi berhasil<br>";
} else {
    die("❌ Koneksi gagal");
}

// STEP 2: Test query
echo "<h3>2. Test Query Users</h3>";
$result = mysqli_query($conn, "SELECT * FROM users LIMIT 1");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "✅ Query berhasil. Data ditemukan:<br>";
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "❌ Query gagal: " . mysqli_error($conn);
}

// STEP 3: Test login dengan email hardcoded
echo "<h3>3. Test Login dengan Email</h3>";
$test_email = 'budi@email.com'; // Ganti dengan email yang sudah terdaftar

$stmt = mysqli_prepare($conn, 
    "SELECT id_users, nama_depan, nama_belakang, username, email, password, role 
     FROM users 
     WHERE email = ? 
     LIMIT 1"
);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $test_email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($user) {
        echo "✅ User ditemukan untuk email: $test_email<br>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
    } else {
        echo "❌ User tidak ditemukan untuk email: $test_email<br>";
    }
} else {
    echo "❌ Error prepare: " . mysqli_error($conn);
}

echo "<h3 style='color:green'>✅ Test selesai</h3>";
?>