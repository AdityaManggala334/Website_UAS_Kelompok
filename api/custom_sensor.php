<?php
ob_start();
require_once dirname(__FILE__) . '/config.php';
// Guard: Hanya Admin dan Petugas Lapangan yang berhak melakukan kalibrasi hardware
$userData = requireLogin();
if (strtolower($userData['role']) !== 'admin' && strtolower($userData['role']) !== 'petugas_lapangan') {
    header("Location: dashboard.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Kustomisasi Alat Sensor | Ladusync</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0f172a] text-slate-200 p-8">
    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <a href="dashboard.php" class="text-xs text-emerald-400 hover:underline">← Dashboard</a>
            <h1 class="text-2xl font-black text-white mt-1">Kalibrasi & Ambang Batas Parameter Sensor IoT</h1>
        </div>
        <div class="bg-[#1e293b] p-6 rounded-2xl border border-slate-800">
             <p class="text-xs text-slate-400">Gunakan modul ini untuk menyesuaikan toleransi pembacaan tinggi muka air dan debit air.</p>
        </div>
    </div>
</body>
</html>