<?php
// api/instruksi_gopay.php
// ======================================================
// INSTRUKSI PEMBAYARAN GOPAY / QRIS
// ======================================================

require_once 'koneksi.php';
require_once 'auth_check.php';

$alat = $_GET['alat'] ?? 'Alat';
$durasi = (int)($_GET['durasi'] ?? 0);
$total = (float)($_GET['total'] ?? 0);
$metode = $_GET['metode'] ?? 'GoPay';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bayar via GoPay - Ladusync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f0fdf4;
            padding-top: 80px;
        }
        .card {
            max-width: 450px;
            margin: auto;
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(6,78,59,0.06), 0 8px 24px rgba(6,78,59,0.07);
            border: 1px solid rgba(6,78,59,0.08);
        }
        .btn-gopay {
            background: linear-gradient(135deg, #00AED6, #0084A7);
            border: none;
            color: white;
            transition: all 0.2s;
        }
        .btn-gopay:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,174,214,0.35);
            color: white;
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: none;
            transition: all 0.2s;
        }
        .btn-secondary:hover { background: #e2e8f0; }
        .qr-wrapper {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            max-width: 220px;
            margin: 0 auto;
        }
        .qr-wrapper img { width: 100%; height: auto; }
        .navbar-custom { background-color: #064E3B !important; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold fs-3" href="index.php">🌾 Ladusync</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><span class="nav-link text-white-50">👋 <?= htmlspecialchars($username) ?></span></li>
                <li class="nav-item">
                    <a class="btn btn-outline-warning btn-sm fw-bold" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="card text-center">
        <h4 class="fw-bold text-emerald-900 mb-2">📱 Scan QR Code</h4>
        <p class="text-muted mb-4">Bayar dengan GoPay atau QRIS</p>

        <div class="qr-wrapper mb-4">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=LADUSYNC-<?= urlencode($total) ?>" 
                 alt="QR Code Pembayaran" 
                 onerror="this.src='https://placehold.co/200x200/064E3B/white?text=QR+Code'">
        </div>

        <p class="mb-1 text-muted">Total yang harus dibayar:</p>
        <h4 class="fw-bold mb-4" style="color:#00AED6;">Rp <?= number_format($total, 0, ',', '.') ?></h4>

        <div class="alert alert-light border small text-muted mb-4">
            Buka aplikasi <strong>GoPay</strong>, pilih <strong>Scan/Bayar</strong>, lalu arahkan kamera ke QR di atas.
        </div>

        <!-- ✅ PERBAIKAN: Link ke sukses.php dengan parameter lengkap -->
        <div class="d-grid gap-2">
            <a href="sukses.php?alat=<?= urlencode($alat) ?>&durasi=<?= $durasi ?>&total=<?= $total ?>&metode=GoPay" 
               class="btn btn-gopay py-3 fw-bold">
                ✅ Saya Sudah Bayar
            </a>
            <a href="daftar_alat.php" class="btn btn-secondary py-2">↩️ Batal</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>
</body>
</html>