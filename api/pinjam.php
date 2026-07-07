<?php
// api/pinjam.php
// ======================================================
// KONFIRMASI SEWA ALAT - LADUSYNC
// ======================================================

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth_helper.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$id_alat = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_alat <= 0) {
    header("Location: daftar_alat.php");
    exit();
}

// Ambil data alat
$stmt = mysqli_prepare($conn, "SELECT * FROM alat WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_alat);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    header("Location: daftar_alat.php");
    exit();
}

if ($data['stok'] <= 0) {
    echo "<script>alert('Maaf, stok alat ini sedang habis!'); window.location.href='daftar_alat.php';</script>";
    exit();
}

// Proses form
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $durasi = (int)($_POST['durasi'] ?? 1);
    $metode = $_POST['metode'] ?? 'bca';
    
    // Validasi
    if ($durasi < 1) {
        $error = "Durasi minimal 1 hari";
    } elseif ($durasi > 30) {
        $error = "Durasi maksimal 30 hari";
    } elseif (empty($metode)) {
        $error = "Pilih metode pembayaran";
    } else {
        // Simpan ke session untuk pembayaran
        $_SESSION['sewa_temp'] = [
            'id_alat' => $id_alat,
            'nama_alat' => $data['nama_alat'],
            'harga' => $data['harga'],
            'durasi' => $durasi,
            'total' => $durasi * $data['harga'],
            'metode' => $metode,
            'stok' => $data['stok']
        ];
        
        header("Location: pembayaran.php");
        exit();
    }
}

// Hitung total
$harga_per_hari = $data['harga'];
$total_min = $harga_per_hari;
$total_max = $harga_per_hari * 30;

// Mapping metode pembayaran
$metode_list = [
    // E-Wallet
    'gopay' => [
        'name' => 'GoPay',
        'icon' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5d/GoPay_logo.svg/120px-GoPay_logo.svg.png',
        'fallback' => '📱',
        'category' => 'ewallet',
        'desc' => 'Bayar pakai GoPay'
    ],
    'dana' => [
        'name' => 'DANA',
        'icon' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/72/DANA_Logo.svg/120px-DANA_Logo.svg.png',
        'fallback' => '🟣',
        'category' => 'ewallet',
        'desc' => 'Bayar pakai DANA'
    ],
    'qris' => [
        'name' => 'QRIS',
        'icon' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1a/QRIS_logo.svg/120px-QRIS_logo.svg.png',
        'fallback' => '📱',
        'category' => 'ewallet',
        'desc' => 'Scan QR Code'
    ],
    // Bank Transfer
    'bca' => [
        'name' => 'BCA',
        'icon' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/BCA_logo.svg/120px-BCA_logo.svg.png',
        'fallback' => '🏦',
        'category' => 'bank',
        'desc' => 'Transfer Bank BCA'
    ],
    'bri' => [
        'name' => 'BRI',
        'icon' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2c/BRI_logo.svg/120px-BRI_logo.svg.png',
        'fallback' => '🏦',
        'category' => 'bank',
        'desc' => 'Transfer Bank BRI'
    ],
    'mandiri' => [
        'name' => 'Mandiri',
        'icon' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/64/Bank_Mandiri_logo_2016.svg/120px-Bank_Mandiri_logo_2016.svg.png',
        'fallback' => '🏦',
        'category' => 'bank',
        'desc' => 'Transfer Bank Mandiri'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Konfirmasi Sewa - Ladusync</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --tanah: #0F1D16;
            --tanah-2: #0A1410;
            --sawah: #2F5233;
            --sawah-light: #4A7050;
            --gabah: #B9843A;
            --gabah-light: #D3A868;
            --pop: #B6FF5E;
            --kertas: #F5F1E5;
            --kertas-2: #ECE5D3;
            --lempung: #8A7357;
            --ink: #23301F;
            --sidebar-w: 248px;
            --topbar-h: 64px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
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
        .font-display { font-family: 'Fraunces', serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .card {
            background: white;
            border-radius: 20px;
            max-width: 620px;
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

        .icon-wrapper {
            width: 64px;
            height: 64px;
            background: rgba(47,82,51,0.08);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
        }
        .icon-wrapper svg { color: var(--sawah); }

        .card-title {
            font-family: 'Fraunces', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 0.25rem;
        }
        .card-sub {
            font-size: 0.85rem;
            color: var(--lempung);
            margin-bottom: 1.5rem;
        }

        .error-msg {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            color: #991B1B;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
            animation: fadeIn 0.3s ease;
        }

        .tool-summary {
            background: #F8FAFC;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(138,115,87,0.06);
        }
        .tool-summary .row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 0.85rem;
        }
        .tool-summary .row .label { color: var(--lempung); }
        .tool-summary .row .value { font-weight: 600; color: var(--ink); }
        .tool-summary .row .value.price { color: var(--sawah); font-weight: 700; }
        .tool-summary .divider {
            border: none;
            border-top: 1px solid rgba(138,115,87,0.06);
            margin: 6px 0;
        }

        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--lempung);
            margin-bottom: 4px;
        }
        .form-group .required {
            color: #EF4444;
            margin-left: 2px;
        }

        .form-control {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid rgba(138,115,87,0.15);
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: 'Sora', sans-serif;
            background: white;
            color: var(--ink);
            transition: all 0.2s ease;
            outline: none;
        }
        .form-control:focus {
            border-color: var(--sawah);
            box-shadow: 0 0 0 3px rgba(47,82,51,0.08);
        }

        .form-hint {
            font-size: 0.7rem;
            color: #94A3B8;
            margin-top: 4px;
            display: block;
        }

        /* ===== TAB METODE ===== */
        .tab-wrapper {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 4px;
            display: flex;
            gap: 4px;
            margin-bottom: 1rem;
        }
        .tab-btn {
            flex: 1;
            padding: 10px 8px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: transparent;
            color: #94A3B8;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .tab-btn:hover {
            background: rgba(255,255,255,0.5);
            color: var(--ink);
        }
        .tab-btn.active {
            background: white;
            color: var(--sawah);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .tab-btn .tab-icon { font-size: 1.1rem; }

        .method-tab {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .method-tab.active { display: block; }

        /* ===== METHOD GRID ===== */
        .method-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 0.6rem;
        }
        @media (max-width: 480px) {
            .method-grid { grid-template-columns: repeat(2, 1fr); }
        }

        .method-option {
            position: relative;
            cursor: pointer;
        }
        .method-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .method-option .method-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 0.5rem;
            border: 2px solid rgba(138,115,87,0.12);
            border-radius: 12px;
            transition: all 0.2s ease;
            background: white;
            gap: 6px;
            text-align: center;
            min-height: 80px;
        }
        .method-option .method-box:hover {
            border-color: var(--sawah-light);
            background: rgba(47,82,51,0.02);
            transform: translateY(-2px);
        }
        .method-option input[type="radio"]:checked + .method-box {
            border-color: var(--sawah);
            background: rgba(47,82,51,0.05);
            box-shadow: 0 0 0 3px rgba(47,82,51,0.08);
        }
        .method-option .method-icon {
            width: 48px;
            height: 48px;
            object-fit: contain;
            display: block;
        }
        .method-option .method-icon.fallback {
            font-size: 1.8rem;
            line-height: 48px;
        }
        .method-option .method-name {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--ink);
        }
        .method-option .method-desc {
            font-size: 0.55rem;
            color: #94A3B8;
            line-height: 1.2;
        }

        .total-box {
            background: linear-gradient(135deg, #F5F1E5, #ECE5D3);
            border-radius: 12px;
            padding: 0.75rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1rem 0 1.25rem;
        }
        .total-box .label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--lempung);
        }
        .total-box .value {
            font-family: 'Fraunces', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--sawah);
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            text-align: center;
            font-family: 'Sora', sans-serif;
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
        .btn-primary:hover {
            box-shadow: 0 6px 24px rgba(47,82,51,0.30);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: var(--lempung);
        }
        .btn-secondary:hover { background: #e2e8f0; }

        .btn-qty {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            border: 2px solid rgba(138,115,87,0.12);
            background: white;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ink);
        }
        .btn-qty:hover {
            border-color: var(--sawah);
            background: rgba(47,82,51,0.04);
        }

        @media (max-width: 640px) {
            .card { padding: 1.25rem; }
            .card-title { font-size: 1.1rem; }
            .method-grid { grid-template-columns: repeat(2, 1fr); }
            .total-box .value { font-size: 1.1rem; }
            .tab-btn { font-size: 0.65rem; padding: 8px 4px; }
        }
        @media (max-width: 480px) {
            .card { padding: 1rem; border-radius: 16px; }
            .method-grid { grid-template-columns: 1fr 1fr; }
            .tool-summary { padding: 0.75rem 1rem; }
            .tool-summary .row { font-size: 0.8rem; }
            .method-option .method-icon { width: 36px; height: 36px; }
            .method-option .method-box { min-height: 64px; padding: 0.5rem; }
        }
    </style>
</head>
<body>

<div class="card">

    <!-- Back Link -->
    <a href="daftar_alat.php" class="back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
        Kembali ke Katalog
    </a>

    <!-- Icon & Title -->
    <div class="icon-wrapper">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
    </div>

    <h1 class="card-title">Konfirmasi Sewa</h1>
    <p class="card-sub">Isi durasi dan pilih metode pembayaran</p>

    <!-- Error Message -->
    <?php if (!empty($error)): ?>
    <div class="error-msg">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Detail Alat -->
    <div class="tool-summary">
        <div class="row">
            <span class="label">Nama Alat</span>
            <span class="value"><?= htmlspecialchars($data['nama_alat']) ?></span>
        </div>
        <div class="row">
            <span class="label">Harga Sewa</span>
            <span class="value price">Rp <?= number_format($data['harga'], 0, ',', '.') ?> <span style="font-weight:400;font-size:0.75rem;color:#94A3B8;">/ hari</span></span>
        </div>
        <div class="divider"></div>
        <div class="row">
            <span class="label">Stok Tersedia</span>
            <span class="value" style="color:<?= $data['stok'] > 3 ? 'var(--sawah)' : 'var(--gabah)' ?>;">
                <?= $data['stok'] ?> unit
                <?php if ($data['stok'] <= 3): ?>
                <span style="font-size:0.65rem;font-weight:400;color:var(--gabah);">(Stok terbatas!)</span>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" id="sewaForm">
        <!-- Durasi -->
        <div class="form-group">
            <label>
                Durasi Sewa <span class="required">*</span>
            </label>
            <div style="display:flex;gap:8px;align-items:center;">
                <button type="button" class="btn-qty" onclick="adjustDurasi(-1)">−</button>
                <input type="number" id="durasiInput" name="durasi" class="form-control" value="1" min="1" max="30" required style="text-align:center;font-size:1.1rem;font-weight:700;">
                <button type="button" class="btn-qty" onclick="adjustDurasi(1)">+</button>
            </div>
            <span class="form-hint">Minimal 1 hari · Maksimal 30 hari</span>
        </div>

        <!-- Metode Pembayaran -->
        <div class="form-group">
            <label>
                Metode Pembayaran <span class="required">*</span>
            </label>

            <!-- Tab Navigation -->
            <div class="tab-wrapper">
                <button type="button" class="tab-btn active" data-tab="ewallet" onclick="switchTab('ewallet')">
                    <span class="tab-icon">📱</span> E-Wallet
                </button>
                <button type="button" class="tab-btn" data-tab="bank" onclick="switchTab('bank')">
                    <span class="tab-icon">🏦</span> Transfer Bank
                </button>
            </div>

            <!-- E-Wallet Tab -->
            <div id="tab-ewallet" class="method-tab active">
                <div class="method-grid">
                    <?php 
                    $ewallet_methods = ['dana', 'gopay', 'qris'];
                    foreach ($ewallet_methods as $key): 
                        $m = $metode_list[$key];
                    ?>
                    <label class="method-option">
                        <input type="radio" name="metode" value="<?= $key ?>" <?= $key === 'gopay' ? 'checked' : '' ?>>
                        <div class="method-box">
                            <img src="<?= $m['icon'] ?>" alt="<?= $m['name'] ?>" class="method-icon" onerror="this.style.display='none';this.parentElement.querySelector('.fallback').style.display='block'">
                            <span class="method-icon fallback" style="display:none;"><?= $m['fallback'] ?></span>
                            <span class="method-name"><?= $m['name'] ?></span>
                            <span class="method-desc"><?= $m['desc'] ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Bank Transfer Tab -->
            <div id="tab-bank" class="method-tab" style="display:none;">
                <div class="method-grid">
                    <?php 
                    $bank_methods = ['bca', 'bri', 'mandiri'];
                    foreach ($bank_methods as $key): 
                        $m = $metode_list[$key];
                    ?>
                    <label class="method-option">
                        <input type="radio" name="metode" value="<?= $key ?>">
                        <div class="method-box">
                            <img src="<?= $m['icon'] ?>" alt="<?= $m['name'] ?>" class="method-icon" onerror="this.style.display='none';this.parentElement.querySelector('.fallback').style.display='block'">
                            <span class="method-icon fallback" style="display:none;"><?= $m['fallback'] ?></span>
                            <span class="method-name"><?= $m['name'] ?></span>
                            <span class="method-desc"><?= $m['desc'] ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Total -->
        <div class="total-box">
            <span class="label">Total Bayar</span>
            <span class="value" id="totalDisplay">Rp <?= number_format($data['harga'], 0, ',', '.') ?></span>
        </div>

        <!-- Buttons -->
        <div class="btn-group">
            <a href="pembayaran.php" class="btn btn-primary">🛒 Lanjut ke Pembayaran</a>
            <a href="daftar_alat.php" class="btn btn-secondary">Batal</a>
        </form>

</div>

<!-- ============================================ -->
<!-- JAVASCRIPT                                   -->
<!-- ============================================ -->
<script>
    const hargaPerHari = <?= (int)$data['harga'] ?>;
    const minDurasi = 1;
    const maxDurasi = 30;

    // ===== TAB SWITCH =====
    function switchTab(tab) {
        // Hide all tabs
        document.querySelectorAll('.method-tab').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        
        // Show selected tab
        document.getElementById('tab-' + tab).style.display = 'block';
        document.querySelector(`.tab-btn[data-tab="${tab}"]`).classList.add('active');
    }

    // ===== DURASI =====
    function updateTotal() {
        const durasi = parseInt(document.getElementById('durasiInput').value) || 1;
        const total = durasi * hargaPerHari;
        document.getElementById('totalDisplay').textContent = 'Rp ' + total.toLocaleString('id-ID');
    }

    function adjustDurasi(change) {
        const input = document.getElementById('durasiInput');
        let val = parseInt(input.value) || 0;
        val = Math.max(minDurasi, Math.min(maxDurasi, val + change));
        input.value = val;
        updateTotal();
    }

    document.getElementById('durasiInput').addEventListener('input', function() {
        let val = parseInt(this.value) || 1;
        val = Math.max(minDurasi, Math.min(maxDurasi, val));
        this.value = val;
        updateTotal();
    });

    // ===== VALIDASI FORM =====
    document.getElementById('sewaForm').addEventListener('submit', function(e) {
        const durasi = parseInt(document.getElementById('durasiInput').value) || 0;
        if (durasi < 1) {
            e.preventDefault();
            alert('⚠️ Durasi minimal 1 hari!');
            return false;
        }
        if (durasi > 30) {
            e.preventDefault();
            alert('⚠️ Durasi maksimal 30 hari!');
            return false;
        }
        const metode = document.querySelector('input[name="metode"]:checked');
        if (!metode) {
            e.preventDefault();
            alert('⚠️ Silakan pilih metode pembayaran!');
            return false;
        }
        return true;
    });

    // ===== INISIALISASI =====
    updateTotal();

    // Fallback untuk gambar logo yang gagal load
    document.querySelectorAll('.method-icon:not(.fallback)').forEach(img => {
        img.addEventListener('error', function() {
            this.style.display = 'none';
            const fallback = this.parentElement.querySelector('.fallback');
            if (fallback) fallback.style.display = 'block';
        });
    });
</script>

</body>
</html>
