<?php
// ============================================================
// FILE: upload_bukti.php
// FUNGSI: Upload bukti pembayaran peminjaman alat (Cloudinary)
// ============================================================

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth_helper.php';

// ============================================================
// CEK LOGIN
// ============================================================
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// ============================================================
// AMBIL DATA USER
// ============================================================
$userData = getCurrentUser();
$user_id = $userData['id'];
$username = $userData['username'];

// ============================================================
// INISIALISASI VARIABEL
// ============================================================
$id_peminjaman = (int)($_GET['id'] ?? 0);
$error = null;
$success = null;
$peminjaman = null;

// ============================================================
// AMBIL DATA PEMINJAMAN
// ============================================================
if ($id_peminjaman > 0) {
    $query = "SELECT p.* 
              FROM peminjaman p 
              WHERE p.id = $id_peminjaman AND p.id_users = $user_id";
    $result = mysqli_query($conn, $query);
    $peminjaman = mysqli_fetch_assoc($result);
    
    if (!$peminjaman) {
        $error = "Data peminjaman tidak ditemukan.";
    }
} else {
    $error = "ID peminjaman tidak valid.";
}

// ============================================================
// PROSES SIMPAN URL DARI CLOUDINARY
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bukti_url'])) {
    $bukti_url = mysqli_real_escape_string($conn, $_POST['bukti_url']);
    $id_peminjaman_val = (int)$_POST['id_peminjaman'];
    
    if (!empty($bukti_url)) {
        // Update database dengan URL dari Cloudinary
        $sql = "UPDATE peminjaman SET 
                    bukti_transfer = '$bukti_url',
                    status = 'menunggu_verifikasi'
                WHERE id = $id_peminjaman_val AND id_users = $user_id";
        
        if (mysqli_query($conn, $sql)) {
            // Ambil data peminjaman terbaru untuk redirect
            $query_data = "SELECT nama_alat, durasi, total_bayar, metode_bayar, no_invoice 
                           FROM peminjaman 
                           WHERE id = $id_peminjaman_val";
            $result_data = mysqli_query($conn, $query_data);
            $data = mysqli_fetch_assoc($result_data);
            
            if ($data) {
                $params = http_build_query([
                    'alat' => $data['nama_alat'],
                    'durasi' => $data['durasi'],
                    'total' => $data['total_bayar'],
                    'metode' => $data['metode_bayar'],
                    'invoice' => $data['no_invoice'],
                    'id' => $id_peminjaman_val
                ]);
                
                header("Location: sukses.php?$params");
                exit();
            } else {
                header("Location: riwayat.php?tab=transaksi&status=success");
                exit();
            }
        } else {
            $error = "Gagal menyimpan data ke database: " . mysqli_error($conn);
        }
    } else {
        $error = "URL bukti tidak valid. Silakan upload ulang.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Upload Bukti Pembayaran - Ladusync</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- ============================================ -->
    <!-- CLOUDINARY UPLOAD WIDGET                     -->
    <!-- ============================================ -->
    <script src="https://upload-widget.cloudinary.com/global/all.js"></script>
    
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
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary {
            background: linear-gradient(135deg, var(--sawah), var(--sawah-light));
            color: white;
            box-shadow: 0 4px 16px rgba(47,82,51,0.20);
        }
        .btn-primary:hover { box-shadow: 0 6px 24px rgba(47,82,51,0.30); }
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
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
        
        .upload-box {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 2rem 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #fafafa;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .upload-box:hover {
            border-color: var(--sawah);
            background: #f0fdf4;
        }
        .upload-box .icon { font-size: 3rem; margin-bottom: 0.5rem; }
        .upload-box .text { font-size: 0.9rem; color: #6b7280; }
        .upload-box .sub { font-size: 0.75rem; color: #9ca3af; }
        
        /* ============================================ */
        /* PREVIEW GAMBAR FULL SESUAI KOTAK            */
        /* ============================================ */
        #file-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--sawah);
            margin-top: 0.5rem;
        }
        
        #preview-container {
            width: 100%;
            margin-top: 0.75rem;
        }
        
        #preview-container img {
            width: 100%;
            max-height: 320px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            display: block;
        }
        
        /* Status upload berhasil - ubah warna border */
        .upload-box.uploaded {
            border-color: #22c55e;
            background: #f0fdf4;
        }
        
        .success-box {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            color: #065f46;
        }
        .success-box .icon { font-size: 3rem; display: block; margin-bottom: 0.5rem; }
        .error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            color: #991b1b;
        }
        .info-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: #92400e;
        }
        @media (max-width: 480px) {
            .card { padding: 1.25rem; }
            #preview-container img { max-height: 200px; }
        }
    </style>
</head>
<body>

<div class="card">

    <a href="riwayat.php?tab=transaksi" class="back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
        Kembali ke Riwayat
    </a>

    <div class="text-center">
        <div class="text-5xl mb-3"></div>
        <h1 class="font-display text-xl font-bold text-ink">Upload Bukti Pembayaran</h1>
        <p class="text-sm text-lempung mt-1">Upload bukti transfer untuk verifikasi</p>
    </div>

    <?php if ($success): ?>
        <div class="success-box mt-4">
            <span class="icon"></span>
            <p class="font-semibold"><?= htmlspecialchars($success) ?></p>
            <p class="text-sm mt-1">Mengalihkan ke halaman sukses...</p>
        </div>
    <?php else: ?>

        <?php if ($error): ?>
            <div class="error-box mt-4">
                <p class="font-semibold">⚠️ Error</p>
                <p class="text-sm mt-1"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($peminjaman): ?>
            <div class="mt-4 p-3 bg-gray-50 rounded-xl border border-gray-200 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Alat</span>
                    <span class="font-semibold"><?= htmlspecialchars($peminjaman['nama_alat']) ?></span>
                </div>
                <div class="flex justify-between mt-1">
                    <span class="text-gray-500">Total</span>
                    <span class="font-semibold text-sawah">Rp <?= number_format($peminjaman['total_bayar'], 0, ',', '.') ?></span>
                </div>
                <div class="flex justify-between mt-1">
                    <span class="text-gray-500">Status</span>
                    <span class="font-semibold <?= $peminjaman['status'] == 'lunas' ? 'text-green-600' : 'text-yellow-600' ?>">
                        <?= ucfirst(str_replace('_', ' ', $peminjaman['status'] ?? 'Menunggu')) ?>
                    </span>
                </div>
                <?php if (!empty($peminjaman['no_invoice'])): ?>
                    <div class="flex justify-between mt-1">
                        <span class="text-gray-500">Invoice</span>
                        <span class="font-mono text-xs font-semibold"><?= htmlspecialchars($peminjaman['no_invoice']) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="info-box">
                <strong>Tips:</strong> Pastikan file bukti terlihat jelas dan nominal sesuai dengan total tagihan.
            </div>

            <!-- ============================================ -->
            <!-- FORM UPLOAD DENGAN CLOUDINARY                -->
            <!-- ============================================ -->
            <form method="POST" id="uploadForm" class="mt-4">
                <input type="hidden" name="id_peminjaman" value="<?= $id_peminjaman ?>">
                <input type="hidden" name="bukti_url" id="bukti_url">

                <!-- Upload Box (Tombol untuk membuka Cloudinary Widget) -->
                <div class="upload-box" id="uploadBox">
                    <div class="icon" id="uploadIcon">📷</div>
                    <div class="text" id="uploadText">Klik untuk pilih file bukti</div>
                    <div class="sub" id="uploadSub">JPG, PNG, PDF (Max 5MB)</div>
                    <div id="file-name"></div>
                    <div id="preview-container"></div>
                </div>

                <!-- Tombol Simpan (aktif setelah upload sukses) -->
                <div class="flex flex-col gap-3 mt-4">
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                        💾 Simpan Bukti
                    </button>
                    <a href="riwayat.php?tab=transaksi" class="btn btn-secondary">Lewati</a>
                </div>
            </form>

            <p class="text-center text-xs text-gray-400 mt-4">
                * Bukti akan diverifikasi admin dalam 1x24 jam
            </p>

        <?php else: ?>
            <div class="error-box mt-4">
                <p class="font-semibold">⚠️ Data tidak ditemukan</p>
                <p class="text-sm mt-1">Silakan kembali ke halaman riwayat</p>
                <a href="riwayat.php?tab=transaksi" class="btn btn-secondary mt-3">Kembali ke Riwayat</a>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<!-- ============================================ -->
<!-- JAVASCRIPT CLOUDINARY WIDGET                 -->
<!-- ============================================ -->
<script>
// === KONFIGURASI CLOUDINARY ===
// Ganti dengan Cloud Name Anda!
const CLOUD_NAME = 'ak6uebhl';
const UPLOAD_PRESET = 'ladusync_upload';
const FOLDER_NAME = 'bukti_pembayaran';

// === ELEMEN ===
const uploadBox = document.getElementById('uploadBox');
const uploadIcon = document.getElementById('uploadIcon');
const uploadText = document.getElementById('uploadText');
const uploadSub = document.getElementById('uploadSub');
const fileName = document.getElementById('file-name');
const previewContainer = document.getElementById('preview-container');
const submitBtn = document.getElementById('submitBtn');
const buktiUrlInput = document.getElementById('bukti_url');

// === INISIALISASI WIDGET CLOUDINARY ===
const myWidget = cloudinary.createUploadWidget(
    {
        cloudName: CLOUD_NAME,
        uploadPreset: UPLOAD_PRESET,
        sources: ['local', 'camera'],
        multiple: false,
        maxFileSize: 5000000, // 5MB
        folder: FOLDER_NAME,
        showAdvancedOptions: false,
        cropping: false,
        defaultSource: 'local'
    },
    (error, result) => {
        // === PROSES SETELAH UPLOAD ===
        if (!error && result && result.event === "success") {
            const imageUrl = result.info.secure_url;
            const fileNameRaw = result.info.original_filename || 'file';
            const fileSize = (result.info.bytes / 1024 / 1024).toFixed(2);
            
            // Isi input hidden dengan URL
            buktiUrlInput.value = imageUrl;
            
            // Sembunyikan icon, text, sub
            uploadIcon.style.display = 'none';
            uploadText.style.display = 'none';
            uploadSub.style.display = 'none';
            
            // Tampilkan nama file
            fileName.textContent = fileNameRaw + ' (' + fileSize + ' MB)';
            
            // Tampilkan preview FULL (object-fit: contain)
            previewContainer.innerHTML = 
                '<img src="' + imageUrl + '" alt="Preview Bukti" loading="lazy">';
            
            // Tambahkan class uploaded untuk ubah border
            uploadBox.classList.add('uploaded');
            
            // Aktifkan tombol submit
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Simpan Bukti';
            submitBtn.style.opacity = '1';
        }
    }
);

// === BUKA WIDGET SAAT UPLOAD BOX DIKLIK ===
uploadBox.addEventListener('click', function() {
    myWidget.open();
});

// === SUBMIT FORM ===
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    if (!buktiUrlInput.value) {
        e.preventDefault();
        alert('⚠️ Silakan upload bukti terlebih dahulu!');
        return;
    }
    
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '⏳ Menyimpan...';
    btn.style.opacity = '0.7';
});
</script>

</body>
</html>
