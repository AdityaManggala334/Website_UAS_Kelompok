<?php
ob_start();
require_once dirname(__FILE__) . '/config.php';
// Guard Ketat: Hanya Admin dan Petugas yang boleh mengelola modul keluhan masuk
$userData = requireLogin();
if (strtolower($userData['role']) !== 'admin' && strtolower($userData['role']) !== 'petugas') {
    header("Location: dashboard.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Laporan Masuk Pengguna | Ladusync</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0f172a] text-slate-200 p-8">
    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <a href="dashboard.php" class="text-xs text-emerald-400 hover:underline">← Dashboard</a>
            <h1 class="text-2xl font-black text-white mt-1">Manajemen Pengaduan & Keluhan Pengguna</h1>
        </div>
        <div class="bg-[#1e293b] rounded-2xl border border-slate-800 p-6 text-xs text-slate-400 italic text-center">
             Menunggu sinkronisasi pengiriman tiket keluhan dari database lapangan...
        </div>
    </div>
</body>
</html>