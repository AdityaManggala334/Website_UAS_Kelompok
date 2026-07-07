<?php
// api/detail_peminjaman.php
// ======================================================
// HALAMAN DETAIL PEMINJAMAN - LADUSYNC
// ======================================================

require_once 'koneksi.php';
require_once 'auth_helper.php';

// Cek login
if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}

// Ambil ID peminjaman dari URL
$id_peminjaman = (int)($_GET['id'] ?? 0);

if ($id_peminjaman <= 0) {
    header("Location: riwayat.php?tab=transaksi");
    exit();
}

// Ambil data peminjaman
$query = mysqli_query($conn, "
    SELECT p.*, a.nama_alat, a.harga, a.gambar, u.username 
    FROM peminjaman p
    LEFT JOIN alat a ON p.id_alat = a.id
    LEFT JOIN users u ON p.id_users = u.id_users
    WHERE p.id = $id_peminjaman AND p.id_users = $user_id
");

$pinjam = mysqli_fetch_assoc($query);

if (!$pinjam) {
    header("Location: riwayat.php?tab=transaksi");
    exit();
}

// Format tanggal
$tanggal_pinjam = date('d M Y', strtotime($pinjam['tanggal']));
$tanggal_kembali = $pinjam['tanggal_kembali_estimasi'] ? date('d M Y', strtotime($pinjam['tanggal_kembali_estimasi'])) : '-';

// Status badge
$status_class = '';
$status_label = '';
switch ($pinjam['status']) {
    case 'lunas':
        $status_class = 'bg-green-100 text-green-800 border-green-300';
        $status_label = '✅ Lunas';
        break;
    case 'dipinjam':
        $status_class = 'bg-blue-100 text-blue-800 border-blue-300';
        $status_label = '📦 Dipinjam';
        break;
    case 'terlambat':
        $status_class = 'bg-red-100 text-red-800 border-red-300';
        $status_label = '⚠️ Terlambat';
        break;
    case 'menunggu':
        $status_class = 'bg-yellow-100 text-yellow-800 border-yellow-300';
        $status_label = '⏳ Menunggu Verifikasi';
        break;
    default:
        $status_class = 'bg-gray-100 text-gray-800 border-gray-300';
        $status_label = ucfirst($pinjam['status']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Detail Peminjaman - Ladusync</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Sora', sans-serif; 
            background: #F5F1E5; 
            color: #23301F;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .font-display { font-family: 'Fraunces', serif; }
        
        .card {
            background: white;
            border-radius: 16px;
            max-width: 600px;
            width: 100%;
            padding: 2rem;
            box-shadow: 0 8px 40px rgba(28,43,30,0.10);
            border: 1px solid rgba(138,115,87,0.12);
            animation: fadeIn 0.4s ease both;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #8A7A66;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            transition: color 0.2s;
        }
        .back-btn:hover { color: #2F5233; }
        
        .divider {
            border: none;
            border-top: 1px solid rgba(138,115,87,0.12);
            margin: 1.25rem 0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.9rem;
        }
        .info-row .label { color: #8A7A66; }
        .info-row .value { font-weight: 600; }
        
        .status-badge {
            display: inline-block;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            border: 1px solid;
        }
        
        .btn-primary {
            display: inline-block;
            padding: 0.7rem 1.5rem;
            background: linear-gradient(135deg, #2F5233, #4A7050);
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            text-align: center;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(47,82,51,0.25);
        }
        
        .btn-secondary {
            display: inline-block;
            padding: 0.7rem 1.5rem;
            background: #f1f5f9;
            color: #4B7563;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            text-align: center;
        }
        .btn-secondary:hover { background: #e2e8f0; }
        
        .btn-group {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        .btn-group .btn-primary,
        .btn-group .btn-secondary {
            flex: 1;
            min-width: 140px;
        }
        
        .gambar-alat {
            width: 100%;
            max-height: 250px;
            object-fit: cover;
            border-radius: 10px;
            background: #f1f5f9;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 480px) {
            .card { padding: 1.25rem; }
            .info-row { font-size: 0.8rem; flex-wrap: wrap; gap: 4px; }
            .btn-group { flex-direction: column; }
            .btn-group .btn-primary,
            .btn-group .btn-secondary { min-width: unset; }
        }
    </style>
</head>
<body>

<div class="card">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <a href="riwayat.php?tab=transaksi" class="back-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            Kembali
        </a>
        <span class="font-display font-bold text-sm" style="color:var(--sawah);">Detail Peminjaman</span>
    </div>
    
    <!-- Gambar Alat -->
    <?php if (!empty($pinjam['gambar'])): ?>
        <img src="<?= htmlspecialchars($pinjam['gambar']) ?>" alt="<?= htmlspecialchars($pinjam['nama_alat']) ?>" class="gambar-alat" onerror="this.style.display='none'">
    <?php endif; ?>
    
    <!-- Nama Alat & Status -->
    <div class="flex items-start justify-between mb-3">
        <h2 class="font-display text-xl font-bold" style="color:var(--ink);">
            <?= htmlspecialchars($pinjam['nama_alat'] ?? 'Alat Tidak Diketahui') ?>
        </h2>
        <span class="status-badge <?= $status_class ?>"><?= $status_label ?></span>
    </div>
    
    <!-- Detail Peminjaman -->
    <div class="bg-[#F8F6F0] rounded-lg p-4 my-4">
        <div class="info-row">
            <span class="label">Invoice</span>
            <span class="value font-mono text-sm"><?= htmlspecialchars($pinjam['invoice'] ?? '-') ?></span>
        </div>
        <div class="info-row">
            <span class="label">Tanggal Pinjam</span>
            <span class="value"><?= $tanggal_pinjam ?></span>
        </div>
        <div class="info-row">
            <span class="label">Estimasi Kembali</span>
            <span class="value"><?= $tanggal_kembali ?></span>
        </div>
        <div class="info-row">
            <span class="label">Durasi</span>
            <span class="value"><?= $pinjam['durasi'] ?> Hari</span>
        </div>
        <div class="info-row">
            <span class="label">Metode Pembayaran</span>
            <span class="value"><?= htmlspecialchars($pinjam['metode_pembayaran'] ?? '-') ?></span>
        </div>
        <hr class="divider">
        <div class="info-row" style="font-size:1.1rem;">
            <span class="label font-bold">Total Bayar</span>
            <span class="value" style="color:var(--sawah);">Rp <?= number_format($pinjam['total_bayar'], 0, ',', '.') ?></span>
        </div>
    </div>
    
    <!-- Informasi Tambahan -->
    <?php if ($pinjam['status'] === 'menunggu'): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-800 mb-4">
            ⏳ Peminjaman ini sedang menunggu verifikasi admin. Proses verifikasi memakan waktu 1x24 jam.
        </div>
    <?php elseif ($pinjam['status'] === 'terlambat'): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800 mb-4">
            ⚠️ Peminjaman ini sudah melewati batas waktu pengembalian. Segera kembalikan alat untuk menghindari denda.
        </div>
    <?php elseif ($pinjam['status'] === 'lunas'): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-800 mb-4">
            ✅ Peminjaman ini sudah selesai dan dilunasi. Terima kasih telah menggunakan layanan Ladusync.
        </div>
    <?php endif; ?>
    
    <!-- Tombol Aksi -->
    <div class="btn-group">
        <a href="riwayat.php?tab=transaksi" class="btn-secondary">
            📋 Lihat Semua Riwayat
        </a>
        <?php if ($pinjam['status'] === 'dipinjam' || $pinjam['status'] === 'terlambat'): ?>
            <a href="konfirmasi_kembali.php?id=<?= $id_peminjaman ?>" class="btn-primary">
                📦 Konfirmasi Pengembalian
            </a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>