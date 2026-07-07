<?php
// ============================================================
// FILE: upload_bukti.php
// FUNGSI: Upload bukti pembayaran peminjaman alat
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
// INISIALISASI VARIABEL
// ============================================================
$id_peminjaman = (int)($_GET['id'] ?? 0);
$success = null;
$error = null;
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
// PROSES UPLOAD BUKTI
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $id_peminjaman_val = (int)$_POST['id_peminjaman'];
    
    // Cek apakah ada file yang diupload
    if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== 0) {
        $error = "Silakan pilih file bukti pembayaran terlebih dahulu.";
    } else {
        // Validasi file
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
        $file_type = $_FILES['bukti']['type'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if ($_FILES['bukti']['size'] > $max_size) {
            $error = "Ukuran file terlalu besar. Maksimal 5MB.";
        } elseif (!in_array($file_type, $allowed_types)) {
            $error = "Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.";
        } else {
            // ============================================================
            // FOLDER UPLOAD: api/uploads/bukti/
            // ============================================================
            $upload_dir = __DIR__ . '/uploads/bukti/';
            
            // Buat folder jika belum ada
            if (!file_exists($upload_dir)) {
                $old_umask = umask(0);
                @mkdir($upload_dir, 0777, true);
                umask($old_umask);
            }
            
            // Cek apakah folder bisa ditulis
            if (!is_writable($upload_dir)) {
                @chmod($upload_dir, 0777);
            }
            
            // ============================================================
            // PROSES UPLOAD FILE
            // ============================================================
            if (is_writable($upload_dir)) {
                $file_extension = pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION);
                $new_filename = 'bukti_' . $id_peminjaman_val . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['bukti']['tmp_name'], $upload_path)) {
                    // Simpan path relatif ke database
                    $file_path_db = 'api/uploads/bukti/' . $new_filename;
                    
                    // Update database
                    $sql = "UPDATE peminjaman SET 
                                bukti_transfer = '$file_path_db',
                                status = 'menunggu_verifikasi'
                            WHERE id = $id_peminjaman_val AND id_users = $user_id";
                    
                    if (mysqli_query($conn, $sql)) {
                        // ============================================================
                        // ✅ REDIRECT KE SUKSES.PHP (BUKAN RIWAYAT)
                        // ============================================================
                        // Ambil data peminjaman terbaru untuk dikirim ke sukses.php
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
                            // Fallback: redirect ke riwayat jika gagal ambil data
                            header("Location: riwayat.php?tab=transaksi&status=success");
                            exit();
                        }
                    } else {
                        $error = "Gagal menyimpan data ke database: " . mysqli_error($conn);
                        if (file_exists($upload_path)) {
                            @unlink($upload_path);
                        }
                    }
                } else {
                    $error = "Gagal mengupload file. Coba lagi.";
                }
            } else {
                $error = "Folder upload tidak bisa ditulis. Path: " . $upload_dir;
            }
        }
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
        
        .drop-zone {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #fafafa;
        }
        .drop-zone:hover {
            border-color: var(--sawah);
            background: #f0fdf4;
        }
        .drop-zone.dragover {
            border-color: var(--sawah);
            background: #dcfce7;
        }
        .drop-zone .icon { font-size: 3rem; margin-bottom: 0.5rem; }
        .drop-zone .text { font-size: 0.9rem; color: #6b7280; }
        .drop-zone .sub { font-size: 0.75rem; color: #9ca3af; }
        #file-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--sawah);
            margin-top: 0.5rem;
        }
        .preview-img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 0.5rem;
            border: 1px solid #e5e7eb;
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
        <div class="text-5xl mb-3">📤</div>
        <h1 class="font-display text-xl font-bold text-ink">Upload Bukti Pembayaran</h1>
        <p class="text-sm text-lempung mt-1">Upload bukti transfer untuk verifikasi</p>
    </div>

    <?php if ($success): ?>
        <div class="success-box mt-4">
            <span class="icon">✅</span>
            <p class="font-semibold"><?= htmlspecialchars($success) ?></p>
            <p class="text-sm mt-1">Mengalihkan ke halaman sukses...</p>
        </div>
    <?php else: ?>

        <?php if ($error): ?>
            <div class="error-box mt-4">
                <p class="font-semibold">⚠️ Error</p>
                <p class="text-sm mt-1"><?= $error ?></p>
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
                <strong>💡 Tips:</strong> Pastikan file bukti terlihat jelas dan nominal sesuai dengan total tagihan.
            </div>

            <form action="" method="POST" enctype="multipart/form-data" id="uploadForm" class="mt-4">
                <input type="hidden" name="id_peminjaman" value="<?= $id_peminjaman ?>">
                <input type="hidden" name="upload" value="1">
                
                <div class="drop-zone" id="dropZone">
                    <div class="icon">📷</div>
                    <div class="text">Klik atau drag & drop untuk upload</div>
                    <div class="sub">JPG, PNG, GIF, WEBP (Max 5MB)</div>
                    <input type="file" name="bukti" id="fileInput" accept="image/*" class="hidden" required>
                    <div id="file-name"></div>
                    <div id="preview-container"></div>
                </div>

                <div class="flex flex-col gap-3 mt-4">
                    <button type="submit" class="btn btn-primary" id="uploadBtn">
                        📤 Upload Bukti
                    </button>
                    <a href="riwayat.php?tab=transaksi" class="btn btn-secondary">↩️ Lewati</a>
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

<script>
// ============================================================
// DRAG & DROP UPLOAD
// ============================================================
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileName = document.getElementById('file-name');
const previewContainer = document.getElementById('preview-container');

if (dropZone) {
    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            updateFileInfo(e.dataTransfer.files[0]);
        }
    });
}

if (fileInput) {
    fileInput.addEventListener('change', function() {
        if (this.files.length) {
            updateFileInfo(this.files[0]);
        }
    });
}

function updateFileInfo(file) {
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
    if (!validTypes.includes(file.type)) {
        alert('❌ Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.');
        fileInput.value = '';
        fileName.textContent = '';
        previewContainer.innerHTML = '';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        alert('❌ Ukuran file terlalu besar. Maksimal 5MB.');
        fileInput.value = '';
        fileName.textContent = '';
        previewContainer.innerHTML = '';
        return;
    }
    
    fileName.textContent = '📎 ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
    
    const reader = new FileReader();
    reader.onload = function(e) {
        previewContainer.innerHTML = '<img src="' + e.target.result + '" class="preview-img" alt="Preview">';
    };
    reader.readAsDataURL(file);
}

// ============================================================
// SUBMIT FORM
// ============================================================
document.getElementById('uploadForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('uploadBtn');
    btn.disabled = true;
    btn.innerHTML = '⏳ Mengupload...';
    btn.style.opacity = '0.7';
});
</script>

</body>
</html>