<?php
// api/instruksi_ewallet.php
// ======================================================
// INSTRUKSI E-WALLET - LADUSYNC
// ======================================================

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth_helper.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$alat = $_GET['alat'] ?? 'Alat';
$durasi = (int)($_GET['durasi'] ?? 0);
$total = (float)($_GET['total'] ?? 0);
$metode = $_GET['metode'] ?? 'gopay';
$id_peminjaman = (int)($_GET['id'] ?? 0);

$metode_labels = [
    'gopay' => ['name' => 'GoPay', 'icon' => '📱', 'color' => '#00AED6', 'bg' => 'bg-cyan-50', 'border' => 'border-cyan-200', 'text' => 'text-cyan-700'],
    'dana' => ['name' => 'DANA', 'icon' => '🟣', 'color' => '#0054A6', 'bg' => 'bg-indigo-50', 'border' => 'border-indigo-200', 'text' => 'text-indigo-700'],
    'ovo' => ['name' => 'OVO', 'icon' => '🟢', 'color' => '#00BFA5', 'bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-700'],
    'shopee_pay' => ['name' => 'ShopeePay', 'icon' => '🟠', 'color' => '#EE4D2D', 'bg' => 'bg-orange-50', 'border' => 'border-orange-200', 'text' => 'text-orange-700'],
    'qris' => ['name' => 'QRIS', 'icon' => '📱', 'color' => '#1A73E8', 'bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-700']
];

$metode_info = $metode_labels[strtolower($metode)] ?? $metode_labels['gopay'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Instruksi Pembayaran - Ladusync</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --tanah: #0F1D16;
            --sawah: #2F5233;
            --sawah-light: #4A7050;
            --gabah: #B9843A;
            --gabah-light: #D3A868;
            --kertas: #F5F1E5;
            --lempung: #8A7357;
            --ink: #23301F;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sora', sans-serif;
            background: var(--kertas);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .card {
            background: white;
            border-radius: 20px;
            max-width: 560px;
            width: 100%;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(15,29,22,0.10);
            border: 1px solid rgba(138,115,87,0.10);
            animation: slideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--sawah), var(--gabah-light), var(--sawah));
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            text-align: center;
            font-family: 'Sora', sans-serif;
            display: inline-block;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s ease;
        }
        .btn:hover::before { left: 100%; }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary {
            background: linear-gradient(135deg, var(--sawah), var(--sawah-light));
            color: white;
            box-shadow: 0 4px 16px rgba(47,82,51,0.20);
        }
        .btn-primary:hover { box-shadow: 0 6px 24px rgba(47,82,51,0.30); }
        .btn-secondary {
            background: #f1f5f9;
            color: var(--lempung);
        }
        .btn-secondary:hover { background: #e2e8f0; }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--lempung);
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            transition: color 0.2s;
            margin-bottom: 1.25rem;
        }
        .back-link:hover { color: var(--sawah); }
        @media (max-width: 480px) {
            .card { padding: 1.25rem; }
        }
    </style>
</head>
<body>

<div class="card">

    <a href="daftar_alat.php" class="back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
        Kembali ke Katalog
    </a>

    <!-- Header -->
    <div class="text-center">
        <div class="text-5xl mb-3"><?= $metode_info['icon'] ?></div>
        <h1 class="font-display text-xl font-bold text-ink">Bayar via <?= $metode_info['name'] ?></h1>
        <p class="text-sm text-lempung mt-1">Scan QR Code atau transfer sesuai nominal</p>
    </div>

    <!-- Info Metode -->
    <div class="mt-4 p-3 rounded-xl text-center <?= $metode_info['bg'] ?> border <?= $metode_info['border'] ?>">
        <span class="font-bold <?= $metode_info['text'] ?>"><?= $metode_info['name'] ?></span>
        <span class="text-sm text-gray-500 block mt-1">ID Transaksi: #<?= str_pad($id_peminjaman, 4, '0', STR_PAD_LEFT) ?></span>
    </div>

    <!-- QR Code -->
    <div class="flex justify-center mt-6">
        <div class="bg-white p-4 rounded-xl border-2 border-gray-200 shadow-lg">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=LADUSYNC-<?= urlencode($metode) ?>-<?= $id_peminjaman ?>" 
                 alt="QR Code" 
                 class="w-48 h-48"
                 onerror="this.src='https://placehold.co/200x200/064E3B/white?text=QR+Code'">
        </div>
    </div>

    <!-- Total -->
    <div class="text-center mt-4">
        <p class="text-sm text-lempung">Total yang harus dibayar</p>
        <p class="font-display text-2xl font-bold text-sawah">Rp <?= number_format($total, 0, ',', '.') ?></p>
    </div>

    <!-- Instruksi -->
    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-800">
        <p class="font-semibold">💡 Cara Pembayaran:</p>
        <ol class="list-decimal list-inside mt-1 space-y-1 text-blue-700">
            <li>Buka aplikasi <strong><?= $metode_info['name'] ?></strong></li>
            <li>Pilih menu <strong>"Bayar"</strong> atau <strong>"Scan QR"</strong></li>
            <li>Arahkan kamera ke QR Code di atas</li>
            <li>Konfirmasi pembayaran sebesar <strong>Rp <?= number_format($total, 0, ',', '.') ?></strong></li>
            <li>Setelah berhasil, klik tombol <strong>"Saya Sudah Bayar"</strong></li>
        </ol>
    </div>

    <!-- Tombol -->
    <div class="flex flex-col gap-3 mt-6">
        <a href="upload_bukti.php?id=<?= $id_peminjaman ?>" class="btn btn-primary">
            ✅ Saya Sudah Bayar
        </a>
        <a href="daftar_alat.php" class="btn btn-secondary">↩️ Batal</a>
    </div>

    <!-- Footer Info -->
    <p class="text-center text-xs text-gray-400 mt-4">
        * Bukti pembayaran akan diverifikasi oleh admin dalam 1x24 jam
    </p>

</div>

</body>
</html>