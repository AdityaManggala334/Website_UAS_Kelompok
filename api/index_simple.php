<?php
// index_simple.php
// ======================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>=== INDEX SIMPLE ===</h2>";

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Cookie Data:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    echo "<h3 style='color:green'>✅ User LOGIN: " . ($_SESSION['nama_depan'] ?? 'Unknown') . "</h3>";
    echo "<p>Role: " . ($_SESSION['role'] ?? 'unknown') . "</p>";
    echo "<p><a href='api/logout.php'>Logout</a></p>";
} else {
    echo "<h3 style='color:red'>❌ User TIDAK login</h3>";
    echo "<p><a href='api/login.php'>Login</a></p>";
}
?>