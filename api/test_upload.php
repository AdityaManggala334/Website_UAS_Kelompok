<?php
// api/check_folder.php
echo "<h2>Check Folder Upload</h2>";

$folders = [
    'upload/' => __DIR__ . '/upload/',
    'upload/bukti/' => __DIR__ . '/upload/bukti/'
];

foreach ($folders as $name => $path) {
    echo "<h3>Folder: " . $name . "</h3>";
    echo "<p>Path: " . $path . "</p>";
    
    if (file_exists($path)) {
        echo "<p style='color:green;'>✅ Folder ditemukan!</p>";
        echo "<p>Permission: " . substr(sprintf('%o', fileperms($path)), -4) . "</p>";
        if (is_writable($path)) {
            echo "<p style='color:green;'>✅ Bisa ditulis!</p>";
        } else {
            echo "<p style='color:red;'>❌ Tidak bisa ditulis!</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ Folder tidak ditemukan!</p>";
        echo "<p>Mencoba membuat folder...</p>";
        if (@mkdir($path, 0777, true)) {
            echo "<p style='color:green;'>✅ Folder berhasil dibuat!</p>";
        } else {
            echo "<p style='color:red;'>❌ Gagal membuat folder! Buat manual.</p>";
            echo "<p>Jalankan: <code>mkdir -p " . $path . "</code></p>";
        }
    }
    echo "<hr>";
}

// Cek file yang sudah ada
echo "<h3>File yang sudah diupload:</h3>";
$bukti_path = __DIR__ . '/upload/bukti/';
if (file_exists($bukti_path)) {
    $files = scandir($bukti_path);
    $files = array_diff($files, ['.', '..']);
    if (count($files) > 0) {
        echo "<ul>";
        foreach ($files as $file) {
            echo "<li>" . $file . " (" . round(filesize($bukti_path . $file) / 1024, 2) . " KB)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Tidak ada file.</p>";
    }
}
?>