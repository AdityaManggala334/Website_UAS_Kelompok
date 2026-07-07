<?php
// api/sukses.php
// ======================================================
// HALAMAN SUKSES - TAMPIL SETELAH UPLOAD BUKTI
// ======================================================

require_once 'koneksi.php';
require_once 'auth_helper.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Ambil data dari URL
$alat = $_GET['alat'] ?? 'Alat';
$durasi = (int)($_GET['durasi'] ?? 0);
$total = (float)($_GET['total'] ?? 0);
$metode = $_GET['metode'] ?? '-';
$invoice = $_GET['invoice'] ?? '-';
$id_peminjaman = (int)($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Sukses - Ladusync</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --tanah:        #0F1D16;
            --tanah-2:      #0A1410;
            --sawah:        #2F5233;
            --sawah-light:  #4A7050;
            --sawah-bg:     #EEF4EA;
            --gabah:        #B9843A;
            --gabah-light:  #D3A868;
            --kertas:       #F5F1E5;
            --kertas-2:     #ECE5D3;
            --ink:          #23301F;
            --kritis:       #9C4130;
            --text-muted:   #8A7A66;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; -webkit-tap-highlight-color: transparent; }
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
        h1, h2, h3, .font-display { font-family: 'Fraunces', serif; }
        .font-mono-data { font-family: 'JetBrains Mono', monospace; }

        /* ===== SUCCESS CARD ===== */
        .card {
            background: white;
            border: 1px solid rgba(138,115,87,0.18);
            border-radius: 20px;
            padding: 2.5rem 2rem;
            box-shadow: 0 1px 3px rgba(28,43,30,0.05), 0 8px 40px rgba(28,43,30,0.08);
            max-width: 480px;
            width: 100%;
            text-align: center;
            animation: fadeInUp 0.6s ease both;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== ICON ===== */
        .icon-wrapper {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: rgba(16,185,129,0.10);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            border: 2px solid rgba(16,185,129,0.20);
        }
        .icon-wrapper svg { color: #10B981; }

        /* ===== TITLE ===== */
        .card-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--ink);
            margin-bottom: 0.25rem;
        }
        .card-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        /* ===== BADGE ===== */
        .badge-status {
            display: inline-block;
            padding: 4px 20px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            background: rgba(245,158,11,0.10);
            color: #D97706;
            margin-bottom: 1.25rem;
            border: 1px solid rgba(245,158,11,0.20);
        }

        /* ===== TIMELINE ===== */
        .timeline {
            display: flex;
            justify-content: space-between;
            margin: 1rem 0 1.5rem;
            position: relative;
            padding: 0 0.25rem;
        }
        .timeline::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 8%;
            right: 8%;
            height: 2px;
            background: #e5e7eb;
            transform: translateY(-50%);
        }
        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            position: relative;
            z-index: 1;
            flex: 1;
        }
        .timeline-step .dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            font-weight: 700;
        }
        .timeline-step .dot-active { background: #10B981; color: white; }
        .timeline-step .dot-done { background: #10B981; color: white; }
        .timeline-step .dot-pending { background: #e5e7eb; color: #94A3B8; }
        .timeline-step .label {
            font-size: 0.55rem;
            font-weight: 600;
            color: #94A3B8;
            text-align: center;
            margin-top: 2px;
        }
        .timeline-step .label-active { color: #10B981; }

        /* ===== SUMMARY ===== */
        .summary {
            background: var(--sawah-bg);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
            text-align: left;
            border: 1px solid rgba(47,82,51,0.10);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 0.85rem;
        }
        .summary-row .label { color: var(--text-muted); }
        .summary-row .value { font-weight: 600; }
        .summary-divider {
            border: none;
            border-top: 1px solid rgba(47,82,51,0.12);
            margin: 0.5rem 0;
        }
        .summary-total {
            display: flex;
            justify-content: space-between;
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--sawah);
        }
        .invoice-code {
            font-family: 'JetBrains Mono', monospace;
            background: rgba(0,0,0,0.04);
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        /* ===== ALERT ===== */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.8rem;
            text-align: left;
            margin-bottom: 1.5rem;
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            line-height: 1.5;
        }
        .alert strong { color: #78350f; }

        /* ===== BUTTONS ===== */
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--sawah), var(--sawah-light));
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(47,82,51,0.25);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: var(--text-muted);
        }
        .btn-secondary:hover { background: #e2e8f0; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 480px) {
            .card { padding: 1.5rem 1.25rem; }
            .card-title { font-size: 1.2rem; }
            .card-subtitle { font-size: 0.75rem; }
            .icon-wrapper { width: 68px; height: 68px; }
            .icon-wrapper svg { width: 32px; height: 32px; }
            .summary-row { font-size: 0.75rem; }
            .summary-total { font-size: 0.95rem; }
            .btn { font-size: 0.8rem; padding: 0.6rem 1rem; }
            .timeline-step .dot { width: 24px; height: 24px; font-size: 0.5rem; }
            .timeline-step .label { font-size: 0.5rem; }
            .badge-status { font-size: 0.6rem; padding: 3px 14px; }
            .alert { font-size: 0.7rem; padding: 0.6rem 0.8rem; }
            body { padding: 12px; }
        }

        @media (max-width: 380px) {
            .card { padding: 1rem 0.75rem; }
            .card-title { font-size: 1rem; }
            .summary-row { font-size: 0.65rem; }
            .summary-total { font-size: 0.8rem; }
            .btn { font-size: 0.7rem; padding: 0.5rem 0.8rem; }
            .timeline-step .dot { width: 20px; height: 20px; font-size: 0.4rem; }
            .timeline-step .label { font-size: 0.4rem; }
            .icon-wrapper { width: 56px; height: 56px; }
            .icon-wrapper svg { width: 26px; height: 26px; }
        }
    </style>
</head>
<body>

    <div class="card">
        <!-- Icon Sukses -->
        <div class="icon-wrapper">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>

        <h2 class="card-title">🎉 Peminjaman Berhasil!</h2>
        <p class="card-subtitle">Bukti pembayaran Anda telah berhasil diupload.</p>

        <div class="badge-status">⏳ Menunggu Verifikasi Admin</div>

        <!-- Timeline Status -->
        <div class="timeline">
            <div class="timeline-step">
                <div class="dot dot-done">✓</div>
                <span class="label label-active">Pesan</span>
            </div>
            <div class="timeline-step">
                <div class="dot dot-done">✓</div>
                <span class="label label-active">Upload</span>
            </div>
            <div class="timeline-step">
                <div class="dot dot-active">⏳</div>
                <span class="label label-active">Verifikasi</span>
            </div>
            <div class="timeline-step">
                <div class="dot dot-pending">-</div>
                <span class="label">Ambil</span>
            </div>
            <div class="timeline-step">
                <div class="dot dot-pending">-</div>
                <span class="label">Selesai</span>
            </div>
        </div>

        <!-- Detail Peminjaman -->
        <div class="summary">
            <div class="summary-row">
                <span class="label">Invoice</span>
                <span class="value invoice-code"><?= htmlspecialchars($invoice) ?></span>
            </div>
            <div class="summary-row">
                <span class="label">Alat</span>
                <span class="value"><?= htmlspecialchars(ucfirst($alat)) ?></span>
            </div>
            <div class="summary-row">
                <span class="label">Durasi</span>
                <span class="value"><?= $durasi ?> Hari</span>
            </div>
            <div class="summary-row">
                <span class="label">Metode</span>
                <span class="value"><?= htmlspecialchars($metode) ?></span>
            </div>
            <hr class="summary-divider">
            <div class="summary-total">
                <span>Total Bayar</span>
                <span>Rp <?= number_format($total, 0, ',', '.') ?></span>
            </div>
        </div>

        <!-- Informasi -->
        <div class="alert">
            <strong>Informasi</strong><br>
            Pembayaran Anda akan diverifikasi oleh admin dalam <strong>1x24 jam</strong>.
            Status peminjaman dapat Anda pantau di halaman <strong>Riwayat</strong>.
        </div>

        <!-- Action Buttons -->
        <div class="btn-group">
            <a href="riwayat.php?tab=transaksi" class="btn btn-primary">
                📋 Lihat Status Transaksi
            </a>
            <a href="daftar_alat.php" class="btn btn-secondary">
                🔧 Kembali ke Katalog
            </a>
        </div>
    </div>

</body>
</html>