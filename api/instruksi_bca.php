<?php
// api/instruksi_bca.php
// ======================================================
// INSTRUKSI TRANSFER BCA
// ======================================================

require_once 'koneksi.php';
require_once 'auth_check.php';

$alat = $_GET['alat'] ?? 'Alat';
$durasi = (int)($_GET['durasi'] ?? 0);
$total = (float)($_GET['total'] ?? 0);
$metode = $_GET['metode'] ?? 'BCA';

// 🔍 DEBUG: Cek data yang diterima
// echo "alat: $alat, durasi: $durasi, total: $total, metode: $metode";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instruksi BCA - Ladusync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f0fdf4;
            padding-top: 80px;
        }
        .card {
            max-width: 500px;
            margin: auto;
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(6,78,59,0.06), 0 8px 24px rgba(6,78,59,0.07);
            border: 1px solid rgba(6,78,59,0.08);
        }
        .btn-primary {
            background: linear-gradient(135deg, #065F46, #064E3B);
            border: none;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(6,78,59,0.35);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: none;
            transition: all 0.2s;
        }
        .btn-secondary:hover { background: #e2e8f0; }
        .rekening-box {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
        }
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
    <div class="card">
        <h4 class="fw-bold text-center text-emerald-900 mb-2">🏦 Instruksi Transfer BCA</h4>
        <p class="text-muted text-center mb-4">Silakan transfer sesuai nominal berikut:</p>

        <div class="text-center mb-4">
            <h2 class="fw-bold text-success">Rp <?= number_format($total, 0, ',', '.') ?></h2>
            <small class="text-muted">Nominal yang harus ditransfer</small>
        </div>

        <div class="rekening-box mb-4">
            <small class="text-muted">Nomor Rekening BCA</small>
            <div class="d-flex justify-content-center align-items-center gap-3 mt-2">
                <strong class="fs-3 text-primary">8830 1234 567</strong>
                <button class="btn btn-sm btn-outline-primary" onclick="copyRekening('88301234567')">📋 Salin</button>
            </div>
            <small class="d-block text-secondary mt-2">Atas Nama: <strong>LADUSYNC OFFICIAL</strong></small>
        </div>

        <div class="alert alert-warning small">
            <strong>⚠️ Penting!</strong> Pastikan nominal transfer <strong>sesuai</strong> dengan total tagihan.
        </div>

        <!-- ✅ PERBAIKAN: Link ke sukses.php dengan parameter lengkap -->
        <div class="d-grid gap-2">
            <a href="sukses.php?alat=<?= urlencode($alat) ?>&durasi=<?= $durasi ?>&total=<?= $total ?>&metode=<?= urlencode($metode) ?>" 
               class="btn btn-primary py-3 fw-bold">
                ✅ Saya Sudah Bayar
            </a>
            <a href="daftar_alat.php" class="btn btn-secondary py-2">↩️ Batal</a>
        </div>
    </div>
</div>

<script>
function copyRekening(nomer) {
    navigator.clipboard.writeText(nomer).then(function() {
        alert('Nomor rekening ' + nomer + ' berhasil disalin!');
    }).catch(function() {
        var temp = document.createElement('input');
        temp.value = nomer;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        alert('Nomor rekening ' + nomer + ' berhasil disalin!');
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>
</body>
</html>