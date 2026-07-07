<?php
// forum.php
// ======================================================
// HALAMAN FORUM DISKUSI - LADUSYNC
// ======================================================

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth_check.php';

// Cek apakah user login
if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}

// Ambil role user dari database jika belum tersedia
if (!isset($user_role) || empty($user_role)) {
    $query_role = mysqli_query($conn, "SELECT role FROM users WHERE id_users = $user_id");
    if ($query_role && mysqli_num_rows($query_role) > 0) {
        $data_role = mysqli_fetch_assoc($query_role);
        $user_role = $data_role['role'] ?? 'guest';
    } else {
        $user_role = 'guest';
    }
}

$msg = '';
$error = '';

// ==========================================
// PROSES HAPUS DISKUSI
// ==========================================
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id_diskusi = (int)$_GET['hapus'];
    
    $cek = mysqli_query($conn, "SELECT id_users FROM diskusi WHERE id_diskusi = $id_diskusi");
    $data = mysqli_fetch_assoc($cek);
    
    if ($data) {
        $is_owner = ($data['id_users'] == $user_id);
        $is_admin = ($user_role === 'administrator');
        
        if ($is_owner || $is_admin) {
            mysqli_query($conn, "DELETE FROM komentar_diskusi WHERE id_diskusi = $id_diskusi");
            mysqli_query($conn, "DELETE FROM diskusi WHERE id_diskusi = $id_diskusi");
            $msg = "✅ Diskusi berhasil dihapus!";
        } else {
            $error = "❌ Anda tidak memiliki izin untuk menghapus diskusi ini.";
        }
    }
}

// ==========================================
// PROSES INPUT POSTINGAN BARU (FORUM)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'create_post') {
    $topik  = trim(mysqli_real_escape_string($conn, $_POST['topik'] ?? ''));
    $konten = trim(mysqli_real_escape_string($conn, $_POST['konten'] ?? ''));
    
    if (!empty($topik) && !empty($konten)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO diskusi (id_users, judul, konten, kategori, created_at) VALUES (?, ?, ?, 'forum', NOW())");
        mysqli_stmt_bind_param($stmt, 'iss', $user_id, $topik, $konten);
        
        if (mysqli_stmt_execute($stmt)) {
            $msg = "✅ Pertanyaan forum berhasil diterbitkan!";
        } else {
            $error = "❌ Gagal menyimpan: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "⚠️ Kolom topik dan isi diskusi wajib diisi.";
    }
}

// ==========================================
// PROSES KOMENTAR / BALAS
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'add_comment') {
    $id_diskusi = (int)$_POST['id_diskusi'];
    $komentar = trim(mysqli_real_escape_string($conn, $_POST['komentar'] ?? ''));
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    
    if (!empty($komentar) && $id_diskusi > 0) {
        // CEK APAKAH KOLOM parent_id ADA
        $check_column = mysqli_query($conn, "SHOW COLUMNS FROM komentar_diskusi LIKE 'parent_id'");
        $has_parent_id = mysqli_num_rows($check_column) > 0;
        
        if ($has_parent_id) {
            $stmt = mysqli_prepare($conn, "INSERT INTO komentar_diskusi (id_diskusi, id_users, komentar, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt, 'iisi', $id_diskusi, $user_id, $komentar, $parent_id);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO komentar_diskusi (id_diskusi, id_users, komentar, created_at) VALUES (?, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt, 'iis', $id_diskusi, $user_id, $komentar);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $msg = "✅ Balasan berhasil ditambahkan!";
        } else {
            $error = "❌ Gagal menyimpan balasan: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "⚠️ Kolom balasan tidak boleh kosong.";
    }
}

// ==========================================
// PROSES HAPUS KOMENTAR
// ==========================================
if (isset($_GET['hapus_komentar']) && is_numeric($_GET['hapus_komentar'])) {
    $id_komentar = (int)$_GET['hapus_komentar'];
    
    $cek = mysqli_query($conn, "SELECT k.id_users, k.id_diskusi, d.id_users as pemilik_diskusi FROM komentar_diskusi k JOIN diskusi d ON k.id_diskusi = d.id_diskusi WHERE k.id_komentar = $id_komentar");
    $data = mysqli_fetch_assoc($cek);
    
    if ($data) {
        $is_owner = ($data['id_users'] == $user_id);
        $is_admin = ($user_role === 'administrator');
        $is_diskusi_owner = ($data['pemilik_diskusi'] == $user_id);
        
        if ($is_owner || $is_admin || $is_diskusi_owner) {
            mysqli_query($conn, "DELETE FROM komentar_diskusi WHERE id_komentar = $id_komentar");
            $msg = "✅ Komentar berhasil dihapus!";
        } else {
            $error = "❌ Anda tidak memiliki izin untuk menghapus komentar ini.";
        }
    }
}

// ==========================================
// AMBIL DATA DISKUSI DARI DATABASE
// ==========================================
$query = mysqli_query($conn, "
    SELECT d.*, u.username, u.nama_depan, u.nama_belakang, u.role 
    FROM diskusi d 
    LEFT JOIN users u ON d.id_users = u.id_users 
    WHERE d.kategori = 'forum' OR d.kategori IS NULL
    ORDER BY d.created_at DESC 
    LIMIT 50
");

$diskusi_data = [];
while ($row = mysqli_fetch_assoc($query)) {
    // CEK APAKAH KOLOM parent_id ADA
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM komentar_diskusi LIKE 'parent_id'");
    $has_parent_id = mysqli_num_rows($check_column) > 0;
    
    // Ambil komentar untuk setiap diskusi
    if ($has_parent_id) {
        $komentar_query = mysqli_query($conn, "
            SELECT 
                k.*, 
                u.username, u.nama_depan, u.nama_belakang, u.role,
                p.username as parent_username, 
                p.nama_depan as parent_nama_depan, 
                p.nama_belakang as parent_nama_belakang
            FROM komentar_diskusi k 
            LEFT JOIN users u ON k.id_users = u.id_users 
            LEFT JOIN komentar_diskusi kp ON k.parent_id = kp.id_komentar
            LEFT JOIN users p ON kp.id_users = p.id_users
            WHERE k.id_diskusi = " . $row['id_diskusi'] . "
            ORDER BY k.created_at ASC
        ");
    } else {
        $komentar_query = mysqli_query($conn, "
            SELECT k.*, u.username, u.nama_depan, u.nama_belakang, u.role
            FROM komentar_diskusi k 
            LEFT JOIN users u ON k.id_users = u.id_users 
            WHERE k.id_diskusi = " . $row['id_diskusi'] . "
            ORDER BY k.created_at ASC
        ");
    }
    
    $komentar_data = [];
    while ($komentar = mysqli_fetch_assoc($komentar_query)) {
        $komentar_data[] = $komentar;
    }
    
    $row['komentar'] = $komentar_data;
    $row['has_parent_id'] = $has_parent_id;
    $diskusi_data[] = $row;
}

// Jika tidak ada data, gunakan data contoh
if (empty($diskusi_data)) {
    $diskusi_data = [
        [
            'id_diskusi' => 1,
            'judul' => 'Penanganan Hama Wereng Cokelat di Padi',
            'konten' => 'Lahan Blok B saya mulai terserang wereng cokelat. Adakah rekomendasi pestisida nabati yang ampuh?',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'id_users' => 1,
            'username' => 'budi',
            'nama_depan' => 'Budi',
            'nama_belakang' => 'Santoso',
            'role' => 'petani',
            'has_parent_id' => false,
            'komentar' => []
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Forum Diskusi - Ladusync</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --tanah: #1C2B1E;
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

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--kertas);
            color: var(--ink);
            padding-top: 80px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        h1, h2, h3 {
            font-family: 'Fraunces', serif;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
            flex: 1;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid rgba(138,115,87,0.18);
            box-shadow: 0 4px 12px rgba(28,43,30,0.06);
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(28,43,30,0.10);
        }
        
        .btn-primary {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, var(--sawah), var(--sawah-light));
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(47,82,51,0.25);
        }
        .btn-danger {
            background: #fee2e2;
            color: #991b1b;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.7rem;
        }
        .btn-danger:hover {
            background: #fecaca;
        }
        .btn-sm {
            padding: 4px 12px;
            font-size: 0.7rem;
        }
        
        .nav-forum {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            background: var(--tanah);
            border-bottom: 1px solid rgba(211,168,104,0.18);
            padding: 12px 20px;
        }
        .nav-forum-inner {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .nav-brand span {
            font-family: 'Fraunces', serif;
            font-weight: 700;
            font-size: 1.2rem;
            color: white;
        }
        .back-link {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: white;
        }
        
        .forum-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(47,82,51,0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        
        .badge-role {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .badge-admin {
            background: #FDF4FF;
            color: #7E22CE;
            border: 1px solid #E9D5FF;
        }
        .badge-petani {
            background: #F0FDF4;
            color: #15803D;
            border: 1px solid #BBF7D0;
        }
        .badge-petugas {
            background: #EFF6FF;
            color: #1D4ED8;
            border: 1px solid #BFDBFE;
        }
        .badge-koordinator {
            background: #FFF7ED;
            color: #C2410C;
            border: 1px solid #FED7AA;
        }
        .badge-guest {
            background: #F1F5F9;
            color: #64748B;
            border: 1px solid #E2E8F0;
        }

        /* ============================================ */
        /* KOMENTAR DENGAN REPLY - SEPERTI FACEBOOK     */
        /* ============================================ */
        
        /* Komentar utama */
        .komentar-item {
            display: flex;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(138,115,87,0.06);
            position: relative;
        }
        .komentar-item:last-child {
            border-bottom: none;
        }
        
        /* === KOMENTAR BALASAN (REPLY) === */
        .komentar-item.is-reply {
            margin-left: 48px;
            padding-left: 16px;
            border-left: 3px solid var(--gabah-light);
            border-radius: 0 8px 8px 0;
            background: rgba(211,168,104,0.03);
        }
        
        /* Garis penghubung seperti Facebook */
        .komentar-item.is-reply::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 10px;
            width: 16px;
            height: 28px;
            border-bottom: 2px solid var(--gabah-light);
            border-left: 2px solid var(--gabah-light);
            border-radius: 0 0 0 8px;
        }
        
        /* Indikator "Membalas [Nama]" */
        .reply-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.6rem;
            color: var(--gabah);
            background: rgba(185,132,58,0.08);
            padding: 1px 10px;
            border-radius: 12px;
            margin-bottom: 3px;
            border: 1px solid rgba(185,132,58,0.10);
        }
        
        .reply-indicator svg {
            width: 10px;
            height: 10px;
        }
        
        .reply-indicator .to-name {
            font-weight: 700;
            color: var(--sawah);
        }
        
        /* Tombol Balas */
        .btn-reply {
            background: none;
            border: none;
            color: #6B7280;
            font-size: 0.7rem;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 4px;
            transition: all 0.2s;
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .btn-reply:hover {
            color: var(--sawah);
            background: rgba(47,82,51,0.06);
        }
        
        /* Form Reply */
        .reply-form {
            display: none;
            margin-top: 8px;
            padding: 10px 12px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid rgba(138,115,87,0.08);
        }
        .reply-form.active {
            display: block;
        }
        .reply-form .replying-to {
            font-size: 0.7rem;
            color: var(--gabah);
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
        }
        .reply-form .reply-input-group {
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }
        .reply-form textarea {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid rgba(138,115,87,0.15);
            border-radius: 8px;
            font-size: 0.75rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            outline: none;
            transition: border-color 0.2s;
            color: var(--ink);
            background: white;
            resize: vertical;
            min-height: 38px;
            max-height: 100px;
        }
        .reply-form textarea:focus {
            border-color: var(--sawah);
            box-shadow: 0 0 0 3px rgba(47,82,51,0.06);
        }
        .reply-form .btn-submit-reply {
            padding: 6px 14px;
            background: linear-gradient(135deg, var(--sawah), var(--sawah-light));
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Plus Jakarta Sans', sans-serif;
            white-space: nowrap;
            height: fit-content;
            flex-shrink: 0;
        }
        .reply-form .btn-submit-reply:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(47,82,51,0.2);
        }
        .reply-form .btn-cancel-reply {
            padding: 6px 12px;
            background: #f1f5f9;
            color: #475569;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Plus Jakarta Sans', sans-serif;
            white-space: nowrap;
            height: fit-content;
            flex-shrink: 0;
        }
        .reply-form .btn-cancel-reply:hover {
            background: #e2e8f0;
        }
        
        .komentar-avatar {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: rgba(47,82,51,0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.7rem;
            color: var(--sawah);
            flex-shrink: 0;
            margin-top: 2px;
        }
        .komentar-body {
            flex: 1;
            min-width: 0;
        }
        .komentar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
        }
        .komentar-nama {
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--ink);
        }
        .komentar-waktu {
            font-size: 0.6rem;
            color: #94A3B8;
        }
        .komentar-teks {
            font-size: 0.8rem;
            color: #475569;
            margin-top: 2px;
            line-height: 1.6;
            word-wrap: break-word;
        }
        .komentar-actions {
            display: flex;
            gap: 4px;
            margin-top: 4px;
            align-items: center;
            flex-wrap: wrap;
        }
        .komentar-actions .btn-delete {
            background: none;
            border: none;
            color: #94A3B8;
            font-size: 0.6rem;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 4px;
            transition: all 0.2s;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .komentar-actions .btn-delete:hover {
            background: #f1f5f9;
            color: #EF4444;
        }

        /* Thread Card */
        .thread-card {
            background: white;
            border-radius: 12px;
            padding: 20px 24px;
            border: 1px solid rgba(138,115,87,0.12);
            box-shadow: 0 2px 8px rgba(28,43,30,0.04);
            margin-bottom: 20px;
            transition: all 0.2s;
        }
        .thread-card:hover {
            border-color: rgba(47,82,51,0.2);
            box-shadow: 0 4px 16px rgba(28,43,30,0.08);
        }
        .thread-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            flex-wrap: wrap;
            gap: 8px;
        }
        .thread-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .thread-avatar {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: rgba(47,82,51,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--sawah);
            flex-shrink: 0;
        }
        .thread-user-info .name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--ink);
        }
        .thread-user-info .time {
            font-size: 0.7rem;
            color: #94A3B8;
        }
        .thread-title {
            font-family: 'Fraunces', serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 4px;
        }
        .thread-content {
            font-size: 0.85rem;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 12px;
            word-wrap: break-word;
        }

        /* Komentar Section */
        .komentar-section {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 2px solid rgba(138,115,87,0.08);
        }

        /* Form komentar utama */
        .komentar-form {
            margin-top: 12px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .komentar-form textarea {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid rgba(138,115,87,0.2);
            border-radius: 8px;
            font-size: 0.8rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            outline: none;
            transition: border-color 0.2s;
            color: var(--ink);
            background: #FAF8F4;
            resize: vertical;
            min-height: 44px;
            max-height: 120px;
        }
        .komentar-form textarea:focus {
            border-color: var(--sawah);
            box-shadow: 0 0 0 3px rgba(47,82,51,0.06);
        }
        .komentar-form .btn-submit-komentar {
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--sawah), var(--sawah-light));
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Plus Jakarta Sans', sans-serif;
            white-space: nowrap;
            height: fit-content;
            flex-shrink: 0;
        }
        .komentar-form .btn-submit-komentar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(47,82,51,0.2);
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 999;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-box {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 520px;
            width: 100%;
            border: 1px solid rgba(138,115,87,0.18);
            box-shadow: 0 24px 60px rgba(0,0,0,0.2);
            animation: modalIn 0.3s ease;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-title {
            font-family: 'Fraunces', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 4px;
        }
        .modal-sub {
            font-size: 0.8rem;
            color: #94A3B8;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94A3B8;
            margin-bottom: 4px;
        }
        .form-input, .form-textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid rgba(138,115,87,0.25);
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            outline: none;
            transition: border-color 0.2s;
            color: var(--ink);
            background: white;
        }
        .form-input:focus, .form-textarea:focus {
            border-color: var(--sawah);
            box-shadow: 0 0 0 3px rgba(47,82,51,0.08);
        }
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }
        .modal-actions .btn {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
        }
        .btn-cancel:hover {
            background: #e2e8f0;
        }
        .btn-submit {
            background: linear-gradient(135deg, var(--sawah), var(--sawah-light));
            color: white;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(47,82,51,0.25);
        }

        /* Flash message */
        .flash {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .flash-success {
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            color: #15803D;
        }
        .flash-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #991B1B;
        }

        @media (max-width: 640px) {
            .thread-header {
                flex-direction: column;
            }
            .modal-box {
                padding: 20px;
            }
            .container {
                padding: 0 12px;
            }
            .card {
                padding: 16px;
            }
            .thread-card {
                padding: 16px;
            }
            .komentar-form {
                flex-direction: column;
            }
            .komentar-form .btn-submit-komentar {
                width: 100%;
            }
            .komentar-item.is-reply {
                margin-left: 20px;
                padding-left: 10px;
            }
            .komentar-item.is-reply::before {
                left: -14px;
                width: 10px;
            }
            .reply-form .reply-input-group {
                flex-direction: column;
            }
            .reply-form .btn-submit-reply {
                width: 100%;
            }
            .reply-form .btn-cancel-reply {
                width: 100%;
            }
            .reply-form .reply-actions {
                display: flex;
                gap: 6px;
                width: 100%;
            }
            .reply-form .reply-actions button {
                flex: 1;
            }
        }

        /* Footer */
        .main-footer {
            background: var(--tanah);
            color: rgba(245,241,229,0.4);
            text-align: center;
            padding: 20px 20px;
            margin-top: 40px;
            border-top: 1px solid rgba(211,168,104,0.12);
            font-size: 0.75rem;
            width: 100%;
        }
        .main-footer span {
            color: rgba(245,241,229,0.6);
        }
    </style>
</head>
<body>

<!-- ===== NAVBAR FORUM ===== -->
<nav class="nav-forum">
    <div class="nav-forum-inner">
        <a href="index.php" class="nav-brand">
            <svg width="28" height="28" viewBox="0 0 44 44" fill="none">
                <path d="M22 7C22 7 13 18 13 24C13 29.52 17.03 34 22 34C26.97 34 31 29.52 31 24C31 18 22 7 22 7Z" fill="#D3A868"/>
                <line x1="18" y1="24" x2="26" y2="24" stroke="#1C2B1E" stroke-width="1.8" stroke-linecap="round"/>
                <circle cx="18" cy="24" r="1.4" fill="#1C2B1E"/>
                <circle cx="26" cy="24" r="1.4" fill="#1C2B1E"/>
                <line x1="22" y1="20" x2="22" y2="28" stroke="#1C2B1E" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            <span>Ladusync</span>
        </a>
        <a href="index.php" class="back-link">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            Dashboard
        </a>
    </div>
</nav>

<!-- ===== CONTENT ===== -->
<div class="container">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-emerald-900">Forum Diskusi</h1>
            <p class="text-sm text-slate-400 mt-1">Ruang interaksi antar petani dan petugas irigasi</p>
        </div>
        <button onclick="openModal()" class="btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="inline mr-2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Diskusi Baru
        </button>
    </div>

    <!-- Flash Messages -->
    <?php if ($msg): ?>
        <div class="flash flash-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Daftar Diskusi -->
    <?php foreach ($diskusi_data as $post): 
        $nama = htmlspecialchars($post['nama_depan'] . ' ' . ($post['nama_belakang'] ?? ''));
        $username = htmlspecialchars($post['username'] ?? 'User');
        $role = $post['role'] ?? 'guest';
        $roleLabel = match($role) {
            'administrator' => 'Admin Sistem',
            'petani' => 'Petani',
            'petugas_lapangan' => 'Petugas Lapangan',
            'koordinator_irigasi' => 'Koordinator Irigasi',
            default => 'Pengguna'
        };
        $badgeClass = match($role) {
            'administrator' => 'badge-admin',
            'petani' => 'badge-petani',
            'petugas_lapangan' => 'badge-petugas',
            'koordinator_irigasi' => 'badge-koordinator',
            default => 'badge-guest'
        };
        $initial = strtoupper(substr($nama ?: $username, 0, 1));
        $time = date('d M Y, H:i', strtotime($post['created_at']));
        
        $can_delete = ($user_role === 'administrator' || $user_id == ($post['id_users'] ?? 0));
        $has_parent_id = $post['has_parent_id'] ?? false;
    ?>
    <div class="thread-card">
        <div class="thread-header">
            <div class="thread-user">
                <div class="thread-avatar"><?= $initial ?></div>
                <div class="thread-user-info">
                    <div class="name"><?= $nama ?: $username ?></div>
                    <div class="time"><?= $time ?></div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span class="badge-role <?= $badgeClass ?>"><?= $roleLabel ?></span>
                <?php if ($can_delete): ?>
                <button onclick="if(confirm('Hapus diskusi ini beserta semua komentarnya?')){window.location.href='forum.php?hapus=<?= $post['id_diskusi'] ?>'}" 
                        class="btn-danger btn-sm" style="padding:4px 12px;font-size:0.65rem;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;margin-right:2px;">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                    Hapus
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <h3 class="thread-title"><?= htmlspecialchars($post['judul']) ?></h3>
        <p class="thread-content"><?= nl2br(htmlspecialchars($post['konten'])) ?></p>
        
        <!-- Komentar Section -->
        <div class="komentar-section">
            <!-- Daftar Komentar -->
            <?php if (!empty($post['komentar'])): ?>
                <?php foreach ($post['komentar'] as $komentar): 
                    $k_nama = htmlspecialchars($komentar['nama_depan'] . ' ' . ($komentar['nama_belakang'] ?? ''));
                    $k_username = htmlspecialchars($komentar['username'] ?? 'User');
                    $k_initial = strtoupper(substr($k_nama ?: $k_username, 0, 1));
                    $k_time = date('d M Y, H:i', strtotime($komentar['created_at']));
                    
                    // Cek apakah ini reply (parent_id != 0)
                    $is_reply = ($has_parent_id && isset($komentar['parent_id']) && $komentar['parent_id'] > 0);
                    
                    // Ambil nama parent (orang yang dibalas)
                    $parent_name = '';
                    if ($is_reply && !empty($komentar['parent_nama_depan'])) {
                        $parent_name = htmlspecialchars($komentar['parent_nama_depan'] . ' ' . ($komentar['parent_nama_belakang'] ?? ''));
                    } elseif ($is_reply && !empty($komentar['parent_username'])) {
                        $parent_name = htmlspecialchars($komentar['parent_username']);
                    }
                    
                    $can_delete_komentar = ($user_role === 'administrator' || 
                                            $user_id == ($komentar['id_users'] ?? 0) || 
                                            $user_id == ($post['id_users'] ?? 0));
                ?>
                <div class="komentar-item <?= $is_reply ? 'is-reply' : '' ?>">
                    <div class="komentar-avatar"><?= $k_initial ?></div>
                    <div class="komentar-body">
                        <div class="komentar-header">
                            <span class="komentar-nama"><?= $k_nama ?: $k_username ?></span>
                            <span class="komentar-waktu"><?= $k_time ?></span>
                        </div>
                        
                        <!-- Reply Indicator (Membalas X) -->
                        <?php if ($is_reply && !empty($parent_name)): ?>
                            <div class="reply-indicator">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 15 10 8 17 15"/>
                                    <line x1="10" y1="15" x2="10" y2="8"/>
                                </svg>
                                Membalas <span class="to-name"><?= $parent_name ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Teks komentar (tanpa \r\n) -->
                        <div class="komentar-teks"><?= nl2br(htmlspecialchars($komentar['komentar'])) ?></div>
                        
                        <div class="komentar-actions">
                            <!-- Tombol BALAS -->
                            <?php if ($has_parent_id): ?>
                            <button class="btn-reply" onclick="toggleReplyForm('<?= $post['id_diskusi'] ?>', '<?= $komentar['id_komentar'] ?>', '<?= $k_nama ?: $k_username ?>')">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 15 10 8 17 15"/>
                                    <line x1="10" y1="15" x2="10" y2="8"/>
                                </svg>
                                Balas
                            </button>
                            <?php endif; ?>
                            
                            <?php if ($can_delete_komentar): ?>
                            <button class="btn-delete" onclick="if(confirm('Hapus komentar ini?')){window.location.href='forum.php?hapus_komentar=<?= $komentar['id_komentar'] ?>'}">
                                Hapus
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Form Reply (muncul saat tombol Balas diklik) -->
                        <?php if ($has_parent_id): ?>
                        <div class="reply-form" id="reply-form-<?= $post['id_diskusi'] ?>-<?= $komentar['id_komentar'] ?>">
                            <form action="" method="POST" onsubmit="return validateReplyForm(this)">
                                <input type="hidden" name="action_type" value="add_comment">
                                <input type="hidden" name="id_diskusi" value="<?= $post['id_diskusi'] ?>">
                                <input type="hidden" name="parent_id" value="<?= $komentar['id_komentar'] ?>">
                                
                                <span class="replying-to">↳ Membalas <strong><?= $k_nama ?: $k_username ?></strong></span>
                                <div class="reply-input-group">
                                    <textarea name="komentar" placeholder="Tulis balasan untuk <?= $k_nama ?: $k_username ?>..." required></textarea>
                                    <div class="reply-actions" style="display:flex;gap:4px;flex-wrap:wrap;">
                                        <button type="submit" class="btn-submit-reply">Kirim</button>
                                        <button type="button" class="btn-cancel-reply" onclick="cancelReply('<?= $post['id_diskusi'] ?>', '<?= $komentar['id_komentar'] ?>')">Batal</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#94A3B8;font-size:0.75rem;padding:8px 0;">Belum ada balasan. Jadilah yang pertama!</p>
            <?php endif; ?>
            
            <!-- Form Komentar Utama -->
            <form action="" method="POST" class="komentar-form">
                <input type="hidden" name="action_type" value="add_comment">
                <input type="hidden" name="id_diskusi" value="<?= $post['id_diskusi'] ?>">
                <input type="hidden" name="parent_id" value="0">
                <textarea name="komentar" placeholder="Tulis komentar Anda..." required></textarea>
                <button type="submit" class="btn-submit-komentar">Kirim</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Jika Tidak Ada Diskusi -->
    <?php if (empty($diskusi_data)): ?>
    <div class="card text-center py-12">
        <div class="forum-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                <path d="M8 10h.01"/>
                <path d="M12 10h.01"/>
                <path d="M16 10h.01"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-slate-700">Belum Ada Diskusi</h3>
        <p class="text-sm text-slate-400 mt-2">Jadilah yang pertama memulai diskusi!</p>
        <button onclick="openModal()" class="btn-primary mt-4">Mulai Diskusi</button>
    </div>
    <?php endif; ?>

</div>

<!-- ===== FOOTER ===== -->
<footer class="main-footer">
    &copy; 2026 <span>Ladusync</span> — Forum Diskusi Ekosistem Pertanian
</footer>

<!-- ===== MODAL ===== -->
<div class="modal-overlay" id="modalForum">
    <div class="modal-box">
        <h3 class="modal-title">Mulai Diskusi Baru</h3>
        <p class="modal-sub">Bagikan pertanyaan atau pengalaman Anda dengan komunitas</p>

        <form action="" method="POST">
            <input type="hidden" name="action_type" value="create_post">

            <div class="form-group">
                <label class="form-label">Judul / Topik</label>
                <input type="text" name="topik" class="form-input" placeholder="Contoh: Cara mengatasi debit air menurun..." required>
            </div>

            <div class="form-group">
                <label class="form-label">Isi Diskusi</label>
                <textarea name="konten" class="form-textarea" placeholder="Ceritakan masalah atau pengalaman Anda secara detail..." required></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-submit">Terbitkan</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== JAVASCRIPT ===== -->
<script>
    // ============================================================
    // MODAL FUNCTIONS
    // ============================================================
    function openModal() {
        document.getElementById('modalForum').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        document.getElementById('modalForum').classList.remove('active');
        document.body.style.overflow = '';
    }
    document.getElementById('modalForum').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // ============================================================
    // REPLY FUNCTIONS - SEPERTI FACEBOOK
    // ============================================================
    function toggleReplyForm(id_diskusi, id_komentar, nama_user) {
        var formId = 'reply-form-' + id_diskusi + '-' + id_komentar;
        var form = document.getElementById(formId);
        
        if (!form) return;
        
        // Cek apakah form sedang aktif
        if (form.classList.contains('active')) {
            // Sembunyikan form
            form.classList.remove('active');
        } else {
            // Sembunyikan semua form reply lain
            var allForms = document.querySelectorAll('.reply-form');
            allForms.forEach(function(f) {
                f.classList.remove('active');
            });
            
            // Tampilkan form yang dipilih
            form.classList.add('active');
            
            // Focus ke textarea
            var textarea = form.querySelector('textarea');
            if (textarea) {
                setTimeout(function() {
                    textarea.focus();
                }, 100);
            }
        }
    }

    function cancelReply(id_diskusi, id_komentar) {
        var formId = 'reply-form-' + id_diskusi + '-' + id_komentar;
        var form = document.getElementById(formId);
        if (form) {
            form.classList.remove('active');
            var textarea = form.querySelector('textarea');
            if (textarea) {
                textarea.value = '';
            }
        }
    }

    // ============================================================
    // VALIDASI FORM REPLY - CEK KOMENTAR TIDAK KOSONG
    // ============================================================
    function validateReplyForm(form) {
        var textarea = form.querySelector('textarea[name="komentar"]');
        if (textarea && textarea.value.trim() === '') {
            alert('Silakan tulis balasan terlebih dahulu.');
            textarea.focus();
            return false;
        }
        return true;
    }

    // ============================================================
    // CLOSE REPLY FORM WITH ESC KEY
    // ============================================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var activeForms = document.querySelectorAll('.reply-form.active');
            activeForms.forEach(function(form) {
                form.classList.remove('active');
            });
        }
    });

    // ============================================================
    // CLICK OUTSIDE REPLY FORM TO CLOSE
    // ============================================================
    document.addEventListener('click', function(e) {
        var forms = document.querySelectorAll('.reply-form.active');
        forms.forEach(function(form) {
            if (!form.contains(e.target) && !e.target.closest('.btn-reply')) {
                form.classList.remove('active');
            }
        });
    });

    // ============================================================
    // ENTER KEY UNTUK SUBMIT KOMENTAR (Shift+Enter untuk new line)
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        var textareas = document.querySelectorAll('.komentar-form textarea, .reply-form textarea');
        textareas.forEach(function(textarea) {
            textarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    var form = this.closest('form');
                    if (form) {
                        form.submit();
                    }
                }
            });
        });
    });
</script>

</body>
</html>