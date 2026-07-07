<?php
// api/test_redirect.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>TEST REDIRECT</h2>";

// 1. Test session
echo "<h3>1. Test Session</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['test'] = 'test_value';
echo "✅ Session test disimpan: " . $_SESSION['test'] . "<br>";

// 2. Test cookie
echo "<h3>2. Test Cookie</h3>";
setcookie('test_cookie', 'test_value', time()+3600, '/');
echo "✅ Cookie test disimpan<br>";

// 3. Test redirect ke index.php
echo "<h3>3. Test Redirect ke index.php</h3>";
echo "Redirecting...<br>";
header("Location: ../index.php");
exit();
?>