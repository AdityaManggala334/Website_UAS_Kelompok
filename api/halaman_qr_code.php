<?php
// api/halaman_qr_code.php
// ======================================================
// HALAMAN QR CODE UNTUK PEMINJAMAN
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

$id_peminjaman = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_peminjaman <= 0) {
    header("Location: daftar_alat.php");
    exit();
}

// Ambil data peminjaman
$query = mysqli_query($conn, 
    "SELECT p.*, a.nama_alat, a.id as id_alat 
     FROM peminjaman p 
     LEFT JOIN alat a ON p.id_alat = a.id 
     WHERE p.id = $id_peminjaman AND p.id_users = $user_id"
);
$data = mysqli_fetch_assoc($query);

if (!$data) {
    header("Location: daftar_alat.php");
    exit();
}

// Cek status (hanya boleh lihat QR jika lunas atau dipinjam)
if (!in_array($data['status'], ['lunas', 'dipinjam', 'dikembalikan'])) {
    echo "<script>alert('Status peminjaman belum siap untuk scan QR!'); window.location.href='detail_peminjaman.php?id=" . $id_peminjaman . "';</script>";
    exit();
}

// ============================================================
// GENERATE QR CODE - PAKAI API GRATIS (tanpa library)
// ============================================================
$qr_data = json_encode([
    'id_peminjaman' => $data['id'],
    'no_invoice' => $data['no_invoice'],
    'user_id' => $user_id,
    'nama_alat' => $data['nama_alat'],
    'username' => $username,
    'timestamp' => time()
]);

// URL QR Code API (gratis, tanpa install)
$qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr_data);

// Buat folder uploads/qr/ jika belum ada
$qrDir = __DIR__ . '/uploads/qr/';
if (!is_dir($qrDir)) {
    mkdir($qrDir, 0777, true);
}

// Simpan QR Code lokal (cache)
$qr_file = $qrDir . 'qr_' . $data['id'] . '_' . $data['no_invoice'] . '.png';
if (!file_exists($qr_file)) {
    // Download dari API
    $qr_content = file_get_contents($qr_url);
    if ($qr_content !== false) {
        file_put_contents($qr_file, $qr_content);
    }
}

// Path QR untuk ditampilkan
$qr_path = file_exists($qr_file) ? 'uploads/qr/qr_' . $data['id'] . '_' . $data['no_invoice'] . '.png' : $qr_url;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>QR Code Peminjaman - Ladusync</title>
    
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
            --kertas: #F5F1E5;
            --lempung: #8A7357;
            --ink: #23301F;
        }
        body {
            font-family: 'Sora', sans-serif;
            background: var(--kertas);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 20px;
            max-width: 480px;
            width: 100%;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(15,29,22,0.10);
            border: 1px solid rgba(138,115,87,0.10);
            text-align: center;
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
            background: linear-gradient(90deg, var(--sawah), var(--gabah), var(--sawah));
        }
        .qr-container {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            border: 2px solid rgba(47,82,51,0.10);
            margin: 1.5rem 0;
            display: inline-block;
        }
        .qr-container img {
            width: 220px;
            height: 220px;
            display: block;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Sora', sans-serif;
            display: inline-block;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--sawah), var(--sawah-light));
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(47,82,51,0.25);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: var(--lempung);
        }
        .btn-secondary:hover { background: #e2e8f0; }
        
        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .status-lunas { background: #d1fae5; color: #065f46; }
        .status-dipinjam { background: #e0e7ff; color: #3730a3; }
        .status-dikembalikan { background: #f3e8ff; color: #6d28d9; }
    </style>
</head>
<body>

<div class="card">
    <!-- Icon & Title -->
    <div class="flex justify-center mb-2">
        <span class="text-4xl">📱</span>
    </div>
    <h1 class="font-display text-xl font-bold text-ink">QR Code Peminjaman</h1>
    <p class="text-sm text-lempung mt-1">Tunjukkan QR ini ke pegawai Ladusync</p>

    <!-- Status -->
    <div class="mt-3">
        <span class="status-badge status-<?= $data['status'] ?>">
            <?php
            $status_label = [
                'lunas' => '✅ Siap Diambil',
                'dipinjam' => '🔧 Sedang Dipinjam',
                'dikembalikan' => '📦 Dikembalikan'
            ];
            echo $status_label[$data['status']] ?? $data['status'];
            ?>
        </span>
    </div>

    <!-- QR Code -->
    <div class="flex justify-center">
        <div class="qr-container">
            <img src="<?= htmlspecialchars($qr_path) ?>" alt="QR Code Peminjaman" 
                 onerror="this.src='<?= $qr_url ?>'">
        </div>
    </div>

    <!-- Info -->
    <div class="text-sm text-left bg-gray-50 rounded-lg p-4 mb-4">
        <div class="flex justify-between py-1">
            <span class="text-lempung">Invoice</span>
            <span class="font-mono font-bold"><?= htmlspecialchars($data['no_invoice'] ?? '-') ?></span>
        </div>
        <div class="flex justify-between py-1">
            <span class="text-lempung">Alat</span>
            <span class="font-semibold"><?= htmlspecialchars($data['nama_alat'] ?? '-') ?></span>
        </div>
        <div class="flex justify-between py-1">
            <span class="text-lempung">User</span>
            <span class="font-semibold"><?= htmlspecialchars($username) ?></span>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-col gap-2">
        <a href="detail_peminjaman.php?id=<?= $id_peminjaman ?>" class="btn btn-primary">
            📋 Detail Peminjaman
        </a>
        <a href="daftar_alat.php" class="btn btn-secondary">← Kembali ke Katalog</a>
    </div>

    <!-- Tips -->
    <div class="mt-4 text-xs text-lempung bg-blue-50 rounded-lg p-3">
        💡 Tips: Simpan QR ini atau screenshot untuk memudahkan proses pengambilan alat.
    </div>
</div>

</body>
</html>