<?php
// api/pembayaran.php
// ======================================================
// KONFIRMASI PEMBAYARAN - LADUSYNC
// ======================================================

ob_start();
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth_helper.php';

// ============================================================
// DEBUG: Cek semua data
// ============================================================
error_log("=== PEMBAYARAN.PHP DIAKSES ===");
error_log("GET: " . print_r($_GET, true));
error_log("POST: " . print_r($_POST, true));

// Cek login - gunakan fungsi dari auth_helper
if (!isLoggedIn()) {
    error_log("=== USER TIDAK LOGIN, REDIRECT KE LOGIN ===");
    header("Location: login.php");
    exit();
}

// Ambil user data
$userData = getCurrentUser();
$user_id = $userData['id'];
$username = $userData['username'];
$role = $userData['role'];

error_log("=== USER DATA: user_id=$user_id, username=$username, role=$role ===");

// ============================================================
// AMBIL DATA DARI DATABASE (berdasarkan token)
// ============================================================
$token = $_GET['token'] ?? '';

if (empty($token)) {
    error_log("=== TOKEN KOSONG! REDIRECT KE DAFTAR_ALAT ===");
    header("Location: daftar_alat.php");
    exit();
}

// Ambil data dari tabel temp_sewa
$token_esc = mysqli_real_escape_string($conn, $token);
$sql = "SELECT * FROM temp_sewa WHERE token = '$token_esc' AND expires_at > NOW()";
$result = mysqli_query($conn, $sql);
$temp = mysqli_fetch_assoc($result);

if (!$temp) {
    error_log("=== DATA DENGAN TOKEN $token TIDAK DITEMUKAN ATAU EXPIRED! REDIRECT KE DAFTAR_ALAT ===");
    header("Location: daftar_alat.php");
    exit();
}

error_log("=== DATA DARI DATABASE: " . print_r($temp, true));

// Ambil data dari database
$id_alat = (int)$temp['id_alat'];
$nama_alat = $temp['nama_alat'];
$harga = (float)$temp['harga'];
$durasi = (int)$temp['durasi'];
$total_bayar = (float)$temp['total'];
$metode = $temp['metode'] ?? 'bca';
$stok = (int)$temp['stok'];

// ============================================================
// MAPPING METODE PEMBAYARAN
// ============================================================
$metode_list = [
    'gopay' => ['name' => 'GoPay', 'icon' => '📱', 'category' => 'ewallet', 'desc' => 'Bayar via GoPay'],
    'dana' => ['name' => 'DANA', 'icon' => '🟣', 'category' => 'ewallet', 'desc' => 'Bayar via DANA'],
    'ovo' => ['name' => 'OVO', 'icon' => '🟢', 'category' => 'ewallet', 'desc' => 'Bayar via OVO'],
    'shopee_pay' => ['name' => 'ShopeePay', 'icon' => '🟠', 'category' => 'ewallet', 'desc' => 'Bayar via ShopeePay'],
    'qris' => ['name' => 'QRIS', 'icon' => '📱', 'category' => 'ewallet', 'desc' => 'Scan QR Code'],
    'bca' => ['name' => 'BCA', 'icon' => '🏦', 'category' => 'bank', 'desc' => 'Transfer Bank BCA'],
    'mandiri' => ['name' => 'Mandiri', 'icon' => '🏦', 'category' => 'bank', 'desc' => 'Transfer Bank Mandiri'],
    'bri' => ['name' => 'BRI', 'icon' => '🏦', 'category' => 'bank', 'desc' => 'Transfer Bank BRI'],
    'bni' => ['name' => 'BNI', 'icon' => '🏦', 'category' => 'bank', 'desc' => 'Transfer Bank BNI']
];

// ============================================================
// FUNGSI GENERATE INVOICE (PASTI UNIK DENGAN CEK DATABASE)
// ============================================================
function generateInvoice($conn) {
    $max_attempts = 10;
    $attempt = 0;
    
    while ($attempt < $max_attempts) {
        // Format: INV-20260705-xxxxxxxx (8 karakter unik)
        $no_invoice = 'INV-' . date('Ymd') . '-' . substr(md5(uniqid() . rand(1000, 9999) . microtime(true)), 0, 8);
        
        // Cek di database apakah invoice sudah ada
        $check = mysqli_query($conn, "SELECT id FROM peminjaman WHERE no_invoice = '$no_invoice'");
        if (mysqli_num_rows($check) == 0) {
            return $no_invoice;
        }
        $attempt++;
    }
    
    // Fallback: gunakan timestamp presisi tinggi
    return 'INV-' . date('Ymd') . '-' . uniqid() . rand(100, 999);
}

// ============================================================
// PROSES KONFIRMASI
// ============================================================
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi'])) {
    $status = 'menunggu_verifikasi';
    
    // ✅ Generate invoice yang UNIK dengan cek database
    $no_invoice = generateInvoice($conn);
    
    // Format tanggal
    $tanggal_pinjam = date('Y-m-d');
    $tanggal_kembali_estimasi = date('Y-m-d', strtotime("+$durasi days"));
    
    // ✅ Konversi semua nilai
    $id_alat_val = (int)$id_alat;
    $user_id_val = (int)$user_id;
    $username_val = mysqli_real_escape_string($conn, $username);
    $nama_alat_val = mysqli_real_escape_string($conn, $nama_alat);
    $no_invoice_val = mysqli_real_escape_string($conn, $no_invoice);
    $durasi_val = (int)$durasi;
    $tanggal_pinjam_val = $tanggal_pinjam;
    $tanggal_kembali_estimasi_val = $tanggal_kembali_estimasi;
    $total_bayar_val = (float)$total_bayar;
    $metode_val = mysqli_real_escape_string($conn, $metode);
    $status_val = $status;
    
    // Debug log
    error_log("=== INSERT PEMINJAMAN ===");
    error_log("no_invoice: $no_invoice_val");
    error_log("id_alat: $id_alat_val");
    error_log("user_id: $user_id_val");
    
    // Cek user_id
    if ($user_id_val <= 0) {
        $error = "User ID tidak valid. Silakan login ulang.";
        error_log("ERROR: User ID tidak valid: $user_id_val");
    } else {
        mysqli_begin_transaction($conn);
        
        try {
            // 1. Cek stok alat
            $cek_stok = mysqli_query($conn, "SELECT stok FROM alat WHERE id = $id_alat_val");
            $data_stok = mysqli_fetch_assoc($cek_stok);
            
            if (!$data_stok || $data_stok['stok'] <= 0) {
                throw new Exception("Stok alat habis, tidak dapat melanjutkan peminjaman");
            }
            
            // 2. Kurangi stok
            $update_stok = mysqli_prepare($conn, "UPDATE alat SET stok = stok - 1 WHERE id = ? AND stok > 0");
            mysqli_stmt_bind_param($update_stok, 'i', $id_alat_val);
            mysqli_stmt_execute($update_stok);
            $stok_updated = mysqli_stmt_affected_rows($update_stok) > 0;
            mysqli_stmt_close($update_stok);
            
            if (!$stok_updated) {
                throw new Exception("Gagal mengurangi stok. Stok mungkin sudah habis.");
            }
            
            // 3. INSERT PEMINJAMAN
            $sql = "INSERT INTO peminjaman 
                    (id_alat, id_users, username, nama_alat, no_invoice, durasi, 
                     tanggal_pinjam, tanggal_kembali_estimasi, total_bayar, metode_bayar, status) 
                    VALUES (
                        $id_alat_val, 
                        $user_id_val, 
                        '$username_val', 
                        '$nama_alat_val', 
                        '$no_invoice_val', 
                        $durasi_val, 
                        '$tanggal_pinjam_val', 
                        '$tanggal_kembali_estimasi_val', 
                        $total_bayar_val, 
                        '$metode_val', 
                        '$status_val'
                    )";
            
            error_log("SQL: $sql");
            
            $insert = mysqli_query($conn, $sql);
            
            if (!$insert) {
                throw new Exception("Gagal insert: " . mysqli_error($conn));
            }
            
            $insert_id = mysqli_insert_id($conn);
            error_log("Insert berhasil! ID: $insert_id");
            
            // 4. Hapus data dari temp_sewa setelah berhasil
            mysqli_query($conn, "DELETE FROM temp_sewa WHERE token = '$token_esc'");
            
            mysqli_commit($conn);
            
            // Redirect ke instruksi pembayaran
            $metode_lower = strtolower($metode);
            
            $instruksi_map = [
                'gopay' => 'instruksi_ewallet.php',
                'dana' => 'instruksi_ewallet.php',
                'ovo' => 'instruksi_ewallet.php',
                'shopee_pay' => 'instruksi_ewallet.php',
                'qris' => 'instruksi_ewallet.php',
                'bca' => 'instruksi_bank.php',
                'mandiri' => 'instruksi_bank.php',
                'bri' => 'instruksi_bank.php',
                'bni' => 'instruksi_bank.php'
            ];
            
            $file_instruksi = $instruksi_map[$metode_lower] ?? 'instruksi_bank.php';
            
            $params = http_build_query([
                'alat' => $nama_alat_val,
                'durasi' => $durasi_val,
                'total' => $total_bayar_val,
                'metode' => $metode_val,
                'id' => $insert_id
            ]);
            
            header("Location: $file_instruksi?$params");
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Gagal memproses: " . $e->getMessage();
            error_log("ERROR: " . $e->getMessage());
        }
    }
}

// ============================================================
// FUNGSI HELPER
// ============================================================
function getMetodeIcon($metode) {
    global $metode_list;
    return $metode_list[$metode]['icon'] ?? '💳';
}

function getMetodeName($metode) {
    global $metode_list;
    return $metode_list[$metode]['name'] ?? ucfirst($metode);
}

function getMetodeDesc($metode) {
    global $metode_list;
    return $metode_list[$metode]['desc'] ?? 'Metode Pembayaran';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Konfirmasi Pembayaran - Ladusync</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --tanah: #0F1D16;
            --sawah: #2F5233;
            --sawah-light: #4A7050;
            --gabah: #B9843A;
            --gabah-light: #D3A868;
            --kertas: #F5F1E5;
            --kertas-2: #ECE5D3;
            --lempung: #8A7357;
            --ink: #23301F;
            --kritis: #9C4130;
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
            max-width: 580px;
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
        }

        .summary {
            background: #F8FAFC;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(138,115,87,0.06);
        }
        .summary .row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 0.85rem;
        }
        .summary .row .label { color: var(--lempung); }
        .summary .row .value { font-weight: 600; color: var(--ink); }
        .summary .divider {
            border: none;
            border-top: 1px solid rgba(138,115,87,0.06);
            margin: 6px 0;
        }
        .summary .total {
            display: flex;
            justify-content: space-between;
            font-family: 'Fraunces', serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--sawah);
            padding-top: 6px;
        }

        .invoice-box {
            background: rgba(47,82,51,0.04);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border: 1px dashed rgba(47,82,51,0.15);
        }
        .invoice-box .label {
            font-size: 0.6rem;
            font-weight: 600;
            color: var(--lempung);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .invoice-box .value {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--sawah);
        }

        .method-display {
            background: rgba(47,82,51,0.04);
            border: 1px solid rgba(47,82,51,0.10);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 1rem 0 1.25rem;
        }
        .method-display .icon {
            font-size: 1.8rem;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(47,82,51,0.06);
            border-radius: 10px;
            flex-shrink: 0;
        }
        .method-display .info {
            flex: 1;
        }
        .method-display .name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--ink);
        }
        .method-display .desc {
            font-size: 0.7rem;
            color: #94A3B8;
        }
        .method-display .badge {
            font-size: 0.6rem;
            font-weight: 700;
            color: var(--sawah);
            background: rgba(47,82,51,0.08);
            padding: 2px 10px;
            border-radius: 20px;
        }

        .stock-warning {
            background: #FFFBEB;
            border: 1px solid #FDE68A;
            border-radius: 10px;
            padding: 0.6rem 1rem;
            font-size: 0.75rem;
            color: #92400E;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            margin-top: 0.5rem;
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

        @media (max-width: 640px) {
            .card { padding: 1.25rem; }
            .card-title { font-size: 1.1rem; }
            .summary .row { font-size: 0.8rem; }
            .summary .total { font-size: 1rem; }
        }
        @media (max-width: 480px) {
            .card { padding: 1rem; border-radius: 16px; }
            .method-display { padding: 0.6rem 0.75rem; }
            .method-display .icon { font-size: 1.4rem; width: 36px; height: 36px; }
            .method-display .name { font-size: 0.8rem; }
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

    <div class="icon-wrapper">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="1" y="4" width="22" height="16" rx="2"/>
            <line x1="1" y1="10" x2="23" y2="10"/>
            <circle cx="6" cy="14" r="1.5" fill="currentColor"/>
            <circle cx="12" cy="14" r="1.5" fill="currentColor"/>
            <circle cx="18" cy="14" r="1.5" fill="currentColor"/>
        </svg>
    </div>

    <h1 class="card-title">Konfirmasi Pesanan</h1>
    <p class="card-sub">Periksa kembali data peminjaman Anda</p>

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

    <div class="invoice-box">
        <span class="label">No. Invoice</span>
        <span class="value" id="invoicePreview">INV-<?= date('Ymd') ?>-XXXX</span>
    </div>

    <div class="summary">
        <div class="row">
            <span class="label">Nama Alat</span>
            <span class="value"><?= htmlspecialchars($nama_alat) ?></span>
        </div>
        <div class="row">
            <span class="label">Durasi Sewa</span>
            <span class="value"><?= $durasi ?> hari</span>
        </div>
        <div class="row">
            <span class="label">Estimasi Kembali</span>
            <span class="value"><?= date('d M Y', strtotime("+$durasi days")) ?></span>
        </div>
        <div class="divider"></div>
        <div class="total">
            <span class="label">Total Tagihan</span>
            <span>Rp <?= number_format($total_bayar, 0, ',', '.') ?></span>
        </div>
    </div>

    <div class="stock-warning">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span>Stok akan dikurangi setelah konfirmasi. Pastikan data sudah benar!</span>
    </div>

    <div class="method-display">
        <span class="icon"><?= getMetodeIcon($metode) ?></span>
        <div class="info">
            <div class="name"><?= getMetodeName($metode) ?></div>
            <div class="desc"><?= getMetodeDesc($metode) ?></div>
        </div>
        <span class="badge">✓ Terpilih</span>
    </div>

    <form method="POST" id="confirmForm">
        <input type="hidden" name="konfirmasi" value="1">
        <input type="hidden" name="id" value="<?= $id_alat ?>">
        <input type="hidden" name="nama_alat" value="<?= htmlspecialchars($nama_alat) ?>">
        <input type="hidden" name="durasi" value="<?= $durasi ?>">
        <input type="hidden" name="total" value="<?= $total_bayar ?>">

        <div class="btn-group">
            <button type="submit" class="btn btn-primary" id="confirmBtn">
                ✅ Konfirmasi & Pesan
            </button>
            <a href="daftar_alat.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>

</div>

<script>
    // Generate invoice preview (hanya untuk tampilan)
    function generateInvoicePreview() {
        const now = new Date();
        const date = now.getFullYear() + 
                     String(now.getMonth() + 1).padStart(2, '0') + 
                     String(now.getDate()).padStart(2, '0');
        const random = String(Math.floor(1000 + Math.random() * 9000));
        return 'INV-' + date + '-' + random;
    }
    document.getElementById('invoicePreview').textContent = generateInvoicePreview();

    // Konfirmasi
    document.getElementById('confirmForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('confirmBtn');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '⏳ Memproses...';
        btn.style.opacity = '0.7';
        
        if (confirm('🛒 Konfirmasi peminjaman ini?\n\n' +
                   '📋 Alat: <?= addslashes($nama_alat) ?>\n' +
                   '⏱️ Durasi: <?= $durasi ?> hari\n' +
                   '💰 Total: Rp <?= number_format($total_bayar, 0, ',', '.') ?>\n' +
                   '💳 Metode: <?= addslashes(getMetodeName($metode)) ?>\n\n' +
                   '⚠️ Pastikan data sudah benar!')) {
            this.submit();
        } else {
            btn.disabled = false;
            btn.innerHTML = originalText;
            btn.style.opacity = '1';
        }
    });

    // Keyboard shortcut
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            const form = document.getElementById('confirmForm');
            const activeElement = document.activeElement;
            if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'SELECT')) {
                form.dispatchEvent(new Event('submit'));
            }
        }
        if (e.key === 'Escape') {
            window.location.href = 'daftar_alat.php';
        }
    });
</script>

</body>
</html>
