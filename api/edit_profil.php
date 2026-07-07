<?php
// TAMPILKAN SEMUA ERROR
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

// 1. MUAT KONEKSI
require_once __DIR__ . '/koneksi.php';

// 2. MULAI SESSION JIKA BELUM
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. PASTIKAN VARIABEL $conn ADA
if (!isset($conn)) {
    die("Error: Variabel koneksi database \$conn tidak ditemukan.");
}

// 4. PROTEKSI HALAMAN - CEK SESSION ATAU COOKIE
$is_logged_in = false;
$user_id = 0;

// Cek session
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    $user_id = (int)$_SESSION['user_id'];
    $is_logged_in = true;
} 
// Cek cookie (sm_uid dari auth_helper)
elseif (isset($_COOKIE['sm_uid'])) {
    $user_id = (int)$_COOKIE['sm_uid'];
    $is_logged_in = true;
    $_SESSION['user_id'] = $user_id;
}
// Cek cookie panenusa_auth (fallback)
elseif (isset($_COOKIE['panenusa_auth'])) {
    $auth_data = json_decode($_COOKIE['panenusa_auth'], true);
    if ($auth_data && isset($auth_data['user_id'])) {
        $user_id = (int)$auth_data['user_id'];
        $is_logged_in = true;
        $_SESSION['user_id'] = $user_id;
    }
}

if (!$is_logged_in || $user_id <= 0) {
    header("Location: ../login.php");
    exit();
}

// 5. AMBIL DATA USER SAAT INI
$query_user = "SELECT id_users, nama_depan, nama_belakang, username, email, role, bio 
               FROM users WHERE id_users = '$user_id' LIMIT 1";
$result_user = mysqli_query($conn, $query_user);

if (!$result_user) {
    die("Error query: " . mysqli_error($conn));
}

$user_data = mysqli_fetch_assoc($result_user);

if (!$user_data) {
    header("Location: ../logout.php");
    exit();
}

// 6. PROSES UPDATE PROFIL
$error = null;
$success = null;

if (isset($_POST['update'])) {
    $nama_depan_baru = mysqli_real_escape_string($conn, trim($_POST['nama_depan'] ?? ''));
    $nama_belakang_baru = mysqli_real_escape_string($conn, trim($_POST['nama_belakang'] ?? ''));
    $bio_baru = mysqli_real_escape_string($conn, trim($_POST['bio'] ?? ''));
    
    // Validasi
    if (empty($nama_depan_baru)) {
        $error = "Nama depan tidak boleh kosong!";
    } else {
        // Update database
        $sql = "UPDATE users SET 
                    nama_depan = '$nama_depan_baru',
                    nama_belakang = '$nama_belakang_baru',
                    bio = '$bio_baru'
                WHERE id_users = '$user_id'";
        
        if (mysqli_query($conn, $sql)) {
            // Sinkronisasi Session
            $_SESSION['nama_depan'] = $nama_depan_baru;
            $_SESSION['nama_belakang'] = $nama_belakang_baru;
            $_SESSION['bio'] = $bio_baru;
            $_SESSION['nama'] = trim($nama_depan_baru . ' ' . $nama_belakang_baru);
            
            // Sinkronisasi Cookie panenusa_auth
            $authData = [
                'user_id' => $user_id,
                'nama' => trim($nama_depan_baru . ' ' . $nama_belakang_baru),
                'role' => $_SESSION['role'] ?? 'User'
            ];
            setcookie('panenusa_auth', json_encode($authData), time() + (86400 * 30), "/", "", false, true);
            
            // Set cookie sm_uid
            setcookie('sm_uid', $user_id, time() + (86400 * 30), "/");
            
            $success = "Profil berhasil diperbarui!";
            
            // Refresh data
            $query_user = "SELECT id_users, nama_depan, nama_belakang, username, email, role, bio 
                           FROM users WHERE id_users = '$user_id' LIMIT 1";
            $result_user = mysqli_query($conn, $query_user);
            $user_data = mysqli_fetch_assoc($result_user);
        } else {
            $error = "Gagal menyimpan data: " . mysqli_error($conn);
        }
    }
}

// Ambil data untuk ditampilkan di form
$nama_depan = $user_data['nama_depan'] ?? '';
$nama_belakang = $user_data['nama_belakang'] ?? '';
$bio = $user_data['bio'] ?? '';
$nama_lengkap = trim($nama_depan . ' ' . $nama_belakang);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Ladusync</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --tanah: #0F1D16;
            --sawah: #2F5233;
            --sawah-light: #4A7050;
            --gabah: #B9843A;
            --gabah-light: #D3A868;
            --kertas: #F5F1E5;
            --ink: #23301F;
        }
        body { font-family: 'Sora', sans-serif; background: var(--kertas); color: var(--ink); }
        h1, h2, h3, .font-display { font-family: 'Fraunces', serif; }
        .btn-primary {
            background: linear-gradient(135deg, var(--sawah-light), var(--sawah));
            color: white;
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(47,82,51,0.25);
        }
        .avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--sawah-light), var(--sawah));
            color: white;
            flex-shrink: 0;
        }
        .form-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6B5F4F;
        }
        .form-input, .form-textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid rgba(138,115,87,0.25);
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            background: white;
            color: var(--ink);
        }
        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--gabah);
            box-shadow: 0 0 0 3px rgba(185,132,58,0.15);
        }
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        .btn-outline {
            border: 1.5px solid rgba(138,115,87,0.3);
            background: transparent;
            color: var(--ink);
            transition: all 0.2s ease;
        }
        .btn-outline:hover {
            background: rgba(138,115,87,0.08);
            border-color: var(--gabah);
        }
        .divider {
            border: none;
            border-top: 1px solid rgba(138,115,87,0.12);
            margin: 20px 0;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 12px 16px;
            border-radius: 8px;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 12px 16px;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden border" style="border-color:rgba(138,115,87,0.15);">
        
        <!-- Header -->
        <div class="px-6 py-5 border-b" style="background:linear-gradient(135deg, var(--tanah), #1a2d22);border-color:rgba(211,168,104,0.15);">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background:rgba(182,255,94,0.12);border:1px solid rgba(182,255,94,0.25);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#B6FF5E" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white font-display">Edit Profil</h1>
                        <p class="text-xs" style="color:rgba(245,241,229,0.5);">Ubah nama dan bio Anda</p>
                    </div>
                </div>
                <a href="index.php" class="text-xs font-medium transition-colors no-underline flex items-center gap-1" style="color:rgba(245,241,229,0.5);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    Kembali
                </a>
            </div>
        </div>

        <!-- Form -->
        <form action="" method="POST" class="p-6 space-y-5">
            
            <!-- Success/Error Messages -->
            <?php if (isset($success)): ?>
                <div class="alert-success">
                    ✅ <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert-error">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Profile Info -->
            <div class="flex items-center gap-4 p-4 rounded-xl" style="background:rgba(245,241,229,0.4);border:1px solid rgba(138,115,87,0.12);">
                <div class="avatar-placeholder">
                    <?= strtoupper(substr($nama_depan, 0, 1) ?: 'U') ?>
                </div>
                <div>
                    <div class="font-semibold text-sm" style="color:var(--ink);">
                        <?= htmlspecialchars($nama_lengkap ?: 'Pengguna') ?>
                    </div>
                    <div class="text-xs" style="color:#8A7A66;">
                        @<?= htmlspecialchars($user_data['username'] ?? 'username') ?>
                    </div>
                    <div class="text-xs" style="color:#8A7A66;">
                        <?= htmlspecialchars($user_data['email'] ?? '') ?>
                    </div>
                </div>
            </div>

            <hr class="divider">

            <!-- Nama Depan -->
            <div>
                <label class="form-label block mb-1">Nama Depan</label>
                <input type="text" name="nama_depan" value="<?= htmlspecialchars($nama_depan) ?>" required class="form-input" placeholder="Masukkan nama depan">
            </div>

            <!-- Nama Belakang -->
            <div>
                <label class="form-label block mb-1">Nama Belakang</label>
                <input type="text" name="nama_belakang" value="<?= htmlspecialchars($nama_belakang) ?>" class="form-input" placeholder="Masukkan nama belakang (opsional)">
            </div>

            <!-- Bio / Keterangan -->
            <div>
                <label class="form-label block mb-1">Bio / Keterangan</label>
                <textarea name="bio" class="form-textarea" placeholder="Ceritakan tentang diri Anda, profesi, atau deskripsi singkat..."><?= htmlspecialchars($bio) ?></textarea>
                <p class="text-xs mt-1" style="color:#8A7A66;">Tampil di profil Anda. Maksimal 500 karakter.</p>
            </div>

            <hr class="divider">

            <!-- Tombol Aksi -->
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" name="update" class="btn-primary px-6 py-3 rounded-lg text-sm font-bold flex-1 flex items-center justify-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Simpan Perubahan
                </button>
                <a href="index.php" class="btn-outline px-6 py-3 rounded-lg text-sm font-medium text-center flex items-center justify-center gap-2 no-underline" style="color:var(--ink);">
                    Batal
                </a>
            </div>

        </form>

        <!-- Footer -->
        <div class="px-6 py-4 border-t text-center text-xs" style="border-color:rgba(138,115,87,0.12);color:#8A7A66;">
            <a href="dashboard.php" class="hover:underline no-underline" style="color:#8A7A66;">Dashboard</a>
            &middot;
            <span>Ladusync</span>
        </div>
    </div>
</div>

</body>
</html>