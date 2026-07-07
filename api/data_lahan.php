<?php
// ============================================================
// HASIL PANEN - LADUSYNC
// ============================================================

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth_helper.php';

// Cek login
if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}

// ============================================================
// PROSES TAMBAH LAHAN
// ============================================================
if (isset($_POST['tambah_lahan'])) {
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $luas = floatval($_POST['luas']);
    $status = mysqli_real_escape_string($conn, $_POST['status_lahan']);
    
    $query = "INSERT INTO lahan (user_id, lokasi, luas, status_lahan) VALUES ('$user_id', '$lokasi', '$luas', '$status')";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Lahan berhasil ditambahkan!";
    } else {
        $_SESSION['error'] = "Gagal menambahkan lahan: " . mysqli_error($conn);
    }
    header("Location: data_lahan.php#lahan");
    exit();
}

// ============================================================
// PROSES TAMBAH PANEN (Sesuai struktur tabel hasil_panen)
// ============================================================
if (isset($_POST['tambah_panen'])) {
    $id_users = $user_id;
    $komoditas = mysqli_real_escape_string($conn, $_POST['komoditas']);
    $luas_lahan = floatval($_POST['luas_lahan']);
    $hasil_ton = floatval($_POST['hasil_ton']);
    $tanggal_panen = mysqli_real_escape_string($conn, $_POST['tanggal_panen']);
    $musim = mysqli_real_escape_string($conn, $_POST['musim']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan'] ?? '');
    
    $query = "INSERT INTO hasil_panen (id_users, komoditas, luas_lahan, hasil_ton, tanggal_panen, musim, keterangan) 
              VALUES ('$id_users', '$komoditas', '$luas_lahan', '$hasil_ton', '$tanggal_panen', '$musim', '$keterangan')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Data panen berhasil ditambahkan!";
    } else {
        $_SESSION['error'] = "Gagal menambahkan panen: " . mysqli_error($conn);
    }
    header("Location: data_lahan.php#panen");
    exit();
}

// ============================================================
// PROSES EDIT LAHAN
// ============================================================
if (isset($_POST['edit_lahan'])) {
    $id = intval($_POST['id_lahan']);
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $luas = floatval($_POST['luas']);
    $status = mysqli_real_escape_string($conn, $_POST['status_lahan']);
    
    $query = "UPDATE lahan SET lokasi='$lokasi', luas='$luas', status_lahan='$status' WHERE id='$id' AND user_id='$user_id'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Lahan berhasil diupdate!";
    } else {
        $_SESSION['error'] = "Gagal update lahan: " . mysqli_error($conn);
    }
    header("Location: data_lahan.php#lahan");
    exit();
}

// ============================================================
// PROSES HAPUS LAHAN
// ============================================================
if (isset($_GET['hapus_lahan'])) {
    $id = intval($_GET['hapus_lahan']);
    $query = "DELETE FROM lahan WHERE id = '$id' AND user_id = '$user_id'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Lahan berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus lahan";
    }
    header("Location: data_lahan.php#lahan");
    exit();
}

// ============================================================
// PROSES HAPUS PANEN
// ============================================================
if (isset($_GET['hapus_panen'])) {
    $id = intval($_GET['hapus_panen']);
    $query = "DELETE FROM hasil_panen WHERE id_panen = '$id' AND id_users = '$user_id'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Data panen berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus panen";
    }
    header("Location: data_lahan.php#panen");
    exit();
}

// ============================================================
// AMBIL DATA LAHAN
// ============================================================
$query_lahan = "SELECT * FROM lahan WHERE user_id = '$user_id' ORDER BY created_at DESC";
$result_lahan = mysqli_query($conn, $query_lahan);

// ============================================================
// AMBIL DATA PANEN (Sesuai struktur hasil_panen)
// ============================================================
$query_panen = "SELECT * FROM hasil_panen WHERE id_users = '$user_id' ORDER BY tanggal_panen DESC LIMIT 50";
$result_panen = mysqli_query($conn, $query_panen);

// ============================================================
// HITUNG STATISTIK
// ============================================================
$total_panen = 0;
$total_hasil = 0;
$total_luas = 0;
$total_pendapatan = 0;

if ($result_panen) {
    $total_panen = mysqli_num_rows($result_panen);
    mysqli_data_seek($result_panen, 0);
    while($row = mysqli_fetch_assoc($result_panen)) {
        $total_hasil += $row['hasil_ton'];
        $total_luas += $row['luas_lahan'];
        $total_pendapatan += $row['hasil_ton'] * 7000000;
    }
    mysqli_data_seek($result_panen, 0);
}

$rata_per_hektar = ($total_luas > 0) ? ($total_hasil / $total_luas) : 0;

// Hitung total lahan dari tabel lahan
$total_lahan = 0;
if ($result_lahan) {
    $total_lahan = mysqli_num_rows($result_lahan);
    mysqli_data_seek($result_lahan, 0);
}

// Ambil data untuk edit
$edit_lahan = null;
if (isset($_GET['edit_lahan_id'])) {
    $id = intval($_GET['edit_lahan_id']);
    $query = "SELECT * FROM lahan WHERE id='$id' AND user_id='$user_id'";
    $result = mysqli_query($conn, $query);
    $edit_lahan = mysqli_fetch_assoc($result);
}

// Flash messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Hasil Panen — Ladusync</title>
    
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
        --air: #35648C;
        --air-light: #5C87AD;
        --gabah: #B9843A;
        --gabah-light: #D3A868;
        --pop: #B6FF5E;
        --pop-dim: rgba(182,255,94,0.14);
        --kertas: #F5F1E5;
        --kertas-2: #ECE5D3;
        --lempung: #8A7357;
        --ink: #23301F;
        --kritis: #9C4130;
        --sidebar-w: 248px;
        --topbar-h: 64px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body { font-family: 'Sora', sans-serif; background: var(--kertas); color: var(--ink); min-height: 100vh; }
    .font-display { font-family: 'Fraunces', serif; }
    .font-mono { font-family: 'JetBrains Mono', monospace; }

    @keyframes livePulse { 0%,100%{opacity:1} 50%{opacity:0.25} }
    .live-dot { animation: livePulse 2.2s ease-in-out infinite; }

    .profil-wrap:hover .profil-dropdown { display: block; }
    .profil-dropdown {
        display: none; position: absolute; right: 0; top: 100%; margin-top: 8px;
        background: white; border-radius: 4px; min-width: 210px;
        box-shadow: 0 12px 34px rgba(20,32,25,0.20); z-index: 50; overflow: hidden;
        border: 1px solid rgba(138,115,87,0.18);
    }

    /* ===== SIDEBAR ===== */
    .app-shell { display: flex; min-height: 100vh; }
    .sidebar {
        position: fixed; top: 0; left: 0;
        width: var(--sidebar-w); height: 100vh;
        background: linear-gradient(180deg, var(--tanah) 0%, var(--tanah-2) 100%);
        display: flex; flex-direction: column; z-index: 70;
        border-right: 1px solid rgba(182,255,94,0.08);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }
    .sidebar.collapsed { transform: translateX(-100%); width: 0; }
    .sidebar.open { transform: translateX(0); width: var(--sidebar-w); box-shadow: 20px 0 60px rgba(10,20,16,0.35); }

    .sidebar-logo {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        padding: 20px 20px 16px; flex-shrink: 0;
        border-bottom: 1px solid rgba(245,241,229,0.08);
    }
    .sidebar-logo .logo-wrap { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }

    .sidebar-close-btn {
        display: flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 6px;
        border: 1px solid rgba(245,241,229,0.12);
        background: rgba(245,241,229,0.06); color: rgba(245,241,229,0.5);
        cursor: pointer; transition: all 0.2s ease; flex-shrink: 0; padding: 0;
    }
    .sidebar-close-btn:hover { background: rgba(245,241,229,0.12); color: #fff; border-color: rgba(245,241,229,0.25); }

    .sidebar-toggle-hamburger {
        display: none; align-items: center; justify-content: center;
        width: 38px; height: 38px; border-radius: 8px;
        border: 1px solid rgba(138,115,87,0.20);
        background: transparent; cursor: pointer; color: var(--ink);
        transition: all 0.2s ease; flex-shrink: 0;
    }
    .sidebar-toggle-hamburger:hover { background: rgba(47,82,51,0.06); border-color: var(--sawah); }

    .sidebar-nav {
        flex: 1; overflow-y: auto; padding: 16px 12px;
        display: flex; flex-direction: column; gap: 2px;
    }
    .sidebar-nav::-webkit-scrollbar { width: 4px; }
    .sidebar-nav::-webkit-scrollbar-track { background: rgba(245,241,229,0.05); }
    .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(245,241,229,0.15); border-radius: 2px; }

    .sidebar-section-label {
        font-size: 0.62rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.1em; color: rgba(245,241,229,0.30);
        padding: 14px 12px 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    .nav-link {
        color: rgba(245,241,229,0.62); position: relative;
        font-size: 0.84rem; font-weight: 500;
        padding: 10px 12px; border-radius: 8px;
        transition: all 0.18s ease;
        display: flex; align-items: center; gap: 12px;
        text-decoration: none; white-space: nowrap;
        border-left: 2px solid transparent; min-height: 44px;
        cursor: pointer; background: none; border-right: none; width: 100%; text-align: left;
        font-family: inherit;
    }
    .nav-link:hover { color: #fff; background: rgba(245,241,229,0.06); }
    .nav-link.active { color: var(--pop); background: var(--pop-dim); border-left: 2px solid var(--pop); }
    .nav-link .nav-icon { width: 17px; height: 17px; flex-shrink: 0; }
    .nav-link .nav-text { font-size: 0.84rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nav-link-admin { background: rgba(211,168,104,0.12); color: #D3A868; border-left: 2px solid #D3A868; }
    .nav-link-admin:hover { background: rgba(211,168,104,0.22); color: #D3A868; }

    .sidebar-bottom { flex-shrink: 0; padding: 12px; border-top: 1px solid rgba(245,241,229,0.08); }
    .nav-link-logout { color: rgba(255,138,120,0.85); }
    .nav-link-logout:hover { color: #fff; background: rgba(156,65,48,0.35); }

    .sidebar-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(10,20,16,0.55); z-index: 65;
        opacity: 0; transition: opacity 0.3s ease;
    }
    .sidebar-overlay.open { display: block; opacity: 1; }

    .sidebar.collapsed .nav-text,
    .sidebar.collapsed .sidebar-section-label,
    .sidebar.collapsed .logo-text { opacity: 0; transition: opacity 0.15s ease; }
    .sidebar:not(.collapsed) .nav-text,
    .sidebar:not(.collapsed) .sidebar-section-label,
    .sidebar:not(.collapsed) .logo-text { opacity: 1; transition: opacity 0.15s ease 0.1s; }

    /* ===== MAIN AREA ===== */
    .main-area {
        margin-left: var(--sidebar-w); width: calc(100% - var(--sidebar-w));
        display: flex; flex-direction: column; min-height: 100vh;
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .main-area.sidebar-collapsed { margin-left: 0; width: 100%; }

    .topbar {
        height: var(--topbar-h); flex-shrink: 0;
        background: rgba(245,241,229,0.92); backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(138,115,87,0.16);
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 0 20px; position: sticky; top: 0; z-index: 55;
    }
    .topbar-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .topbar-brand { font-size: 0.78rem; font-weight: 600; color: var(--lempung); display: flex; align-items: center; gap: 6px; white-space: nowrap; }
    .nav-right { display: flex; align-items: center; flex-shrink: 0; margin-left: auto; }

    /* ===== CONTENT ===== */
    .content { 
        padding: 1.25rem; 
        flex: 1; 
        overflow-y: auto; 
        max-width: 1200px; 
        margin: 0 auto; 
        width: 100%; 
    }

    /* ===== RESPONSIVE SIDEBAR ===== */
    @media (min-width: 993px) {
        .sidebar { transform: translateX(0) !important; }
        .sidebar.collapsed { transform: translateX(0) !important; width: 0 !important; border-right: none; }
        .sidebar:not(.collapsed) { width: var(--sidebar-w) !important; }
        .sidebar-overlay { display: none !important; }
        .sidebar-close-btn { display: flex !important; }
        .sidebar-toggle-hamburger { display: flex !important; }
        .main-area.sidebar-collapsed { margin-left: 0; width: 100%; }
        .main-area:not(.sidebar-collapsed) { margin-left: var(--sidebar-w); width: calc(100% - var(--sidebar-w)); }
    }

    @media (max-width: 992px) {
        .sidebar { transform: translateX(-100%); width: var(--sidebar-w) !important; }
        .sidebar.open { transform: translateX(0); box-shadow: 20px 0 60px rgba(10,20,16,0.35); }
        .sidebar.collapsed { transform: translateX(-100%) !important; }
        .sidebar-overlay.open { display: block; opacity: 1; }
        .main-area { margin-left: 0; width: 100%; }
        .sidebar-close-btn { display: flex !important; }
        .sidebar-toggle-hamburger { display: flex !important; }
        .sidebar.collapsed .sidebar-close-btn { display: none !important; }
        .sidebar:not(.open) .sidebar-close-btn { display: none !important; }
    }

    @media (max-width: 768px) {
        .topbar { padding: 0 14px; height: 56px; }
        .topbar-brand.hidden-sm { display: none; }
        .content { padding: 0.75rem; }
        .profil-name { display: none !important; }
        .sidebar-toggle-hamburger { width: 34px; height: 34px; }
        .stat-grid { grid-template-columns: repeat(2,1fr) !important; gap: 0.75rem !important; }
        .stat-card { padding: 0.75rem !important; }
        .stat-card .value { font-size: 1.2rem !important; }
        .table-wrap { overflow-x: auto; }
        .table-wrap table { min-width: 600px; }
        .modal-content { padding: 1.5rem; margin: 1rem; }
    }

    @media (max-width: 480px) {
        .topbar { padding: 0 10px; height: 50px; }
        .sidebar { width: 280px !important; }
        .content { padding: 0.5rem; }
        .stat-grid { grid-template-columns: 1fr 1fr !important; gap: 0.5rem !important; }
        .stat-card .value { font-size: 1rem !important; }
    }

    /* ===== PANEL STYLES ===== */
    .stat-card {
        background: white;
        border: 1px solid rgba(138,115,87,0.12);
        border-radius: 12px;
        padding: 1rem 1.2rem;
        box-shadow: 0 1px 3px rgba(28,43,30,0.05), 0 8px 24px rgba(28,43,30,0.06);
        transition: transform 0.2s ease;
    }
    .stat-card:hover { transform: translateY(-2px); }

    .btn-primary {
        background: var(--sawah);
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.8rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-decoration: none;
    }
    .btn-primary:hover { background: var(--sawah-light); transform: translateY(-1px); }
    
    .btn-gabah {
        background: var(--gabah);
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.8rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-decoration: none;
    }
    .btn-gabah:hover { background: var(--gabah-light); transform: translateY(-1px); }

    .btn-danger {
        background: var(--kritis);
        color: white;
        padding: 4px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.7rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-decoration: none;
    }
    .btn-danger:hover { background: #7a3226; }

    .btn-edit {
        background: #3B82F6;
        color: white;
        padding: 4px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.7rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-decoration: none;
    }
    .btn-edit:hover { background: #2563EB; }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 100;
        display: none;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(4px);
    }
    .modal-overlay.active { display: flex; }
    .modal-content {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        animation: modalIn 0.3s ease;
    }
    @keyframes modalIn {
        from { transform: scale(0.9); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .form-input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid rgba(138,115,87,0.25);
        border-radius: 8px;
        font-size: 0.85rem;
        outline: none;
        transition: all 0.2s ease;
        font-family: 'Sora', sans-serif;
    }
    .form-input:focus { border-color: var(--gabah); box-shadow: 0 0 0 3px rgba(185,132,58,0.15); }
    .form-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .flash {
        padding: 10px 14px;
        border-radius: 10px;
        font-size: 0.8rem;
        font-weight: 500;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .flash-success { background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534; }
    .flash-error { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; }

    .section-card {
        background: white;
        border: 1px solid rgba(138,115,87,0.12);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(28,43,30,0.05), 0 8px 24px rgba(28,43,30,0.06);
    }
    .section-head {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid rgba(138,115,87,0.08);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
        background: linear-gradient(135deg, #F5F1E5, #ECE5D3);
    }
    .section-title {
        font-family: 'Fraunces', serif;
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--ink);
    }
    .section-sub {
        font-size: 0.7rem;
        color: #94A3B8;
        margin-top: 2px;
    }

    .tab-btn {
        padding: 10px 20px;
        font-weight: 700;
        font-size: 0.8rem;
        border: none;
        background: transparent;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: 'Sora', sans-serif;
        border-bottom: 2px solid transparent;
        color: #94A3B8;
    }
    .tab-btn:hover { color: var(--ink); }
    .tab-btn.active {
        color: var(--sawah);
        border-bottom-color: var(--sawah);
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    @media (max-width: 768px) { .stat-grid { grid-template-columns: repeat(2, 1fr); gap: 0.75rem; } }
    @media (max-width: 480px) { .stat-grid { grid-template-columns: 1fr 1fr; gap: 0.5rem; } }

    .badge-status {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 0.65rem;
        font-weight: 700;
    }
    .badge-active { background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0; }
    .badge-istirahat { background: #FFF7ED; color: #C2410C; border: 1px solid #FED7AA; }
    .badge-persiapan { background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; }

    .badge-rendeng { background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A; }
    .badge-gadu { background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0; }

    footer {
        background: var(--tanah);
        color: rgba(245,241,229,0.4);
        padding: 1.5rem 1rem;
        text-align: center;
        font-size: 0.7rem;
        margin-top: auto;
        width: 100%;
        border-top: 1px solid rgba(211,168,104,0.12);
    }
    footer span {
        color: rgba(245,241,229,0.6);
    }
    </style>
</head>
<body>

<div class="app-shell">

<!-- ===== SIDEBAR ===== -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="sidebar">

    <div class="sidebar-logo">
        <div class="logo-wrap">
            <a href="index.php" class="flex items-center gap-2.5 no-underline flex-shrink-0">
                <div class="logo-icon w-9 h-9 rounded-md flex items-center justify-center" style="background:var(--pop-dim);border:1px solid rgba(182,255,94,0.35);">
                    <svg width="18" height="18" viewBox="0 0 44 44" fill="none">
                        <path d="M22 7C22 7 13 18 13 24C13 29.52 17.03 34 22 34C26.97 34 31 29.52 31 24C31 18 22 7 22 7Z" fill="#B6FF5E"/>
                        <line x1="18" y1="24" x2="26" y2="24" stroke="#0F1D16" stroke-width="1.8" stroke-linecap="round"/>
                        <circle cx="18" cy="24" r="1.4" fill="#0F1D16"/>
                        <circle cx="26" cy="24" r="1.4" fill="#0F1D16"/>
                        <line x1="22" y1="20" x2="22" y2="28" stroke="#0F1D16" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </div>
                <span class="logo-text text-base font-bold text-white tracking-tight font-display">Ladusync</span>
            </a>
        </div>
        <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Tutup menu">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Menu Utama</div>

        <a href="index.php" class="nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
                <rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            <span class="nav-text">Beranda</span>
        </a>

        <a href="index.php#sistem-terintegrasi" class="nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <span class="nav-text">Layanan</span>
        </a>

        <a href="index.php#monitoring" class="nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            <span class="nav-text">Monitor Sensor</span>
        </a>

        <a href="peta.php" class="nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
            </svg>
            <span class="nav-text">Peta</span>
        </a>

        <a href="bps.php" class="nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M3 9h18M9 21V9"/>
            </svg>
            <span class="nav-text">Data BPS</span>
        </a>

        <a href="riwayat.php" class="nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
            <span class="nav-text">Riwayat</span>
        </a>

        <div class="sidebar-section-label">Layanan Petani</div>

        <a href="daftar_alat.php" class="nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <span class="nav-text">Sewa Alat</span>
        </a>

        <a href="data_lahan.php" class="nav-link active">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
            <span class="nav-text">Hasil Panen</span>
        </a>

        <?php if ($is_logged_in && $role === 'administrator'): ?>
        <div class="sidebar-section-label">Administrasi</div>
        <a href="dashboard.php" class="nav-link nav-link-admin">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="14" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
            </svg>
            <span class="nav-text">Dashboard Admin</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-bottom">
        <?php if ($is_logged_in): ?>
            <a href="logout.php" class="nav-link nav-link-logout">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span class="nav-text">Keluar</span>
            </a>
        <?php else: ?>
            <a href="login.php" class="nav-link" style="color:var(--pop);">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                    <polyline points="10 17 15 12 10 7"/>
                </svg>
                <span class="nav-text">Masuk / Register</span>
            </a>
        <?php endif; ?>
    </div>
</aside>

<!-- ===== MAIN AREA ===== -->
<div class="main-area" id="mainArea">

    <header class="topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle-hamburger" id="sidebarToggleHamburger" aria-label="Buka menu">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <button class="sidebar-toggle-hamburger" id="sidebarToggleCollapse" aria-label="Tutup navigasi" title="Tutup navigasi">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="15" y1="18" x2="9" y2="12"/>
                    <line x1="15" y1="6" x2="9" y2="12"/>
                </svg>
            </button>
            <span class="topbar-brand hidden sm:flex">
                <span class="live-dot inline-block w-1.5 h-1.5 rounded-full" style="background:var(--pop);"></span>
                Hasil Panen
            </span>
        </div>

        <div class="nav-right">
            <div class="profil-wrap relative">
                <button class="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium bg-transparent border-none cursor-pointer" style="color:var(--ink);">
                    <div class="w-7 h-7 rounded-md flex items-center justify-center font-bold text-xs" style="background:rgba(47,82,51,0.12);color:var(--sawah);">
                        <?= strtoupper(substr($namaDepan, 0, 1)) ?>
                    </div>
                    <span class="profil-name hidden sm:inline"><?= htmlspecialchars($namaDepan) ?></span>
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </button>

                <div class="profil-dropdown">
                    <div class="px-4 py-3 border-b" style="background:linear-gradient(135deg,#F5F1E5,#ECE5D3);border-color:rgba(138,115,87,0.18);">
                        <div class="font-bold text-sm font-display" style="color:var(--sawah);"><?= htmlspecialchars($namaLengkap) ?></div>
                        <div class="text-xs text-slate-500 mt-0.5 capitalize"><?= str_replace('_', ' ', $role) ?></div>
                    </div>
                    <?php if ($is_logged_in): ?>
                        <a href="dashboard.php" class="flex items-center gap-2 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition-colors no-underline">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg> Dashboard
                        </a>
                        <a href="logout.php" class="flex items-center gap-2 px-4 py-3 text-sm hover:bg-red-50 transition-colors no-underline" style="color:var(--kritis);">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Keluar
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="flex items-center gap-2 px-4 py-3 text-sm font-bold hover:bg-slate-50 transition-colors no-underline" style="color:var(--sawah);">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/></svg> Masuk / Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- ===== CONTENT ===== -->
    <div class="content">

        <!-- HEADER -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="font-display text-2xl font-bold" style="color:var(--sawah);">Dashboard Hasil Panen</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola data lahan dan catat hasil panen Anda</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button onclick="openModal('modal-lahan')" class="btn-primary">
                    <svg class="inline w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Lahan
                </button>
                <button onclick="openModal('modal-panen')" class="btn-gabah">
                    <svg class="inline w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Catat Panen
                </button>
            </div>
        </div>

        <!-- FLASH MESSAGES -->
        <?php if ($success): ?>
            <div class="flash flash-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="flash flash-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- STATISTIK -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Total Panen</div>
                <div class="value text-2xl font-bold font-mono" style="color:var(--sawah);"><?= $total_panen ?></div>
                <div class="text-xs text-slate-400 mt-1">Kali panen</div>
            </div>
            <div class="stat-card">
                <div class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Total Hasil</div>
                <div class="value text-2xl font-bold font-mono" style="color:var(--sawah);"><?= number_format($total_hasil, 1) ?></div>
                <div class="text-xs text-slate-400 mt-1">Ton</div>
            </div>
            <div class="stat-card">
                <div class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Total Luas Panen</div>
                <div class="value text-2xl font-bold font-mono" style="color:var(--gabah);"><?= number_format($total_luas, 2) ?></div>
                <div class="text-xs text-slate-400 mt-1">Hektar</div>
            </div>
            <div class="stat-card">
                <div class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Rata-rata per Hektar</div>
                <div class="value text-2xl font-bold font-mono" style="color:var(--sawah);"><?= number_format($rata_per_hektar, 2) ?></div>
                <div class="text-xs text-slate-400 mt-1">Ton/Ha</div>
            </div>
        </div>

        <!-- TAB: LAHAN & PANEN -->
        <div class="section-card">
            <div class="section-head">
                <div>
                    <div class="section-title">Manajemen Lahan & Panen</div>
                    <div class="section-sub">Kelola data lahan dan riwayat panen Anda</div>
                </div>
                <div class="flex gap-1">
                    <button onclick="switchTab('lahan')" id="tab-lahan-btn" class="tab-btn active">Lahan</button>
                    <button onclick="switchTab('panen')" id="tab-panen-btn" class="tab-btn">Panen</button>
                </div>
            </div>

            <!-- TAB LAHAN -->
            <div id="tab-lahan" class="p-4 sm:p-6">
                <div class="table-wrap overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b" style="border-color:rgba(138,115,87,0.12);">
                                <th class="text-left py-3 px-3 text-xs font-bold uppercase tracking-wider" style="color:rgba(138,115,87,0.55);">#</th>
                                <th class="text-left py-3 px-3 text-xs font-bold uppercase tracking-wider" style="color:rgba(138,115,87,0.55);">Lokasi</th>
                                <th class="text-left py-3 px-3 text-xs font-bold uppercase tracking-wider" style="color:rgba(138,115,87,0.55);">Luas (Ha)</th>
                                <th class="text-left py-3 px-3 text-xs font-bold uppercase tracking-wider" style="color:rgba(138,115,87,0.55);">Status</th>
                                <th class="text-left py-3 px-3 text-xs font-bold uppercase tracking-wider" style="color:rgba(138,115,87,0.55);">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_lahan && mysqli_num_rows($result_lahan) > 0): ?>
                                <?php $no = 1; while($row = mysqli_fetch_assoc($result_lahan)): ?>
                                <tr class="border-b" style="border-color:rgba(138,115,87,0.06);">
                                    <td class="py-3 px-3 text-sm font-mono text-slate-400"><?= $no++ ?></td>
                                    <td class="py-3 px-3 text-sm font-medium"><?= htmlspecialchars($row['lokasi']) ?></td>
                                    <td class="py-3 px-3 text-sm font-mono"><?= number_format($row['luas'], 2) ?></td>
                                    <td class="py-3 px-3">
                                        <span class="badge-status badge-<?= strtolower($row['status_lahan']) ?>">
                                            <?= htmlspecialchars($row['status_lahan']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="flex gap-2 flex-wrap">
                                            <a href="?edit_lahan_id=<?= $row['id'] ?>#lahan" class="btn-edit">✏️ Edit</a>
                                            <a href="?hapus_lahan=<?= $row['id'] ?>" class="btn-danger" onclick="return confirm('Yakin hapus lahan ini?')">🗑️ Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="py-8 text-center text-slate-400 text-sm">Belum ada data lahan. Klik "Tambah Lahan" untuk mulai.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB PANEN -->
            <div id="tab-panen" class="p-4 sm:p-6 hidden">
                <div class="table-wrap overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b" style="border-color:rgba(138,115,87,0.12);">
                                <th class="text-left py-3 px-3 text-xs font-bold uppercase tracking-wider" style="color:rgba(138,115,87,0.55);">#</th>
                                <th class="text-left py-3 px-3 text-xs font-bold uppercase tracking-wider" style="color:rgba(138,115,87,0.55);">Tanggal</th>
                                <th class="text-left py-3 px-3 text-xs font-bold uppercase tracking-wider" style="color:rgba(138,115,87,0.55);">Komoditas</th>
                                <th class="text-left py-3 px-3 text-xs font-bold uppercase tracking-wider" style="color:rgba(138,115,87,0.55);">Luas (Ha)</th>
                                <th class="text-left py-3 px-3 text-xs font-bold uppercase tracking-wider" style="color:rgba(138,115,87,0.55);">Hasil (Ton)</th>
                                <th class="text-left py-3 px-3 text-xs font-bold uppercase tracking-wider" style="color:rgba(138,115,87,0.55);">Musim</th>
                                <th class="text-left py-3 px-3 text-xs font-bold uppercase tracking-wider" style="color:rgba(138,115,87,0.55);">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_panen && mysqli_num_rows($result_panen) > 0): ?>
                                <?php $no = 1; while($row = mysqli_fetch_assoc($result_panen)): ?>
                                <tr class="border-b" style="border-color:rgba(138,115,87,0.06);">
                                    <td class="py-3 px-3 text-sm font-mono text-slate-400"><?= $no++ ?></td>
                                    <td class="py-3 px-3 text-sm font-mono"><?= date('d M Y', strtotime($row['tanggal_panen'])) ?></td>
                                    <td class="py-3 px-3 text-sm font-medium"><?= htmlspecialchars($row['komoditas']) ?></td>
                                    <td class="py-3 px-3 text-sm font-mono"><?= number_format($row['luas_lahan'], 2) ?></td>
                                    <td class="py-3 px-3 text-sm font-bold font-mono" style="color:var(--gabah);"><?= number_format($row['hasil_ton'], 2) ?></td>
                                    <td class="py-3 px-3">
                                        <span class="badge-status badge-<?= $row['musim'] ?>">
                                            <?= ucfirst($row['musim']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <a href="?hapus_panen=<?= $row['id_panen'] ?>" class="btn-danger" onclick="return confirm('Yakin hapus data panen ini?')">🗑️ Hapus</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="py-8 text-center text-slate-400 text-sm">Belum ada data panen. Klik "Catat Panen" untuk mulai.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <footer>
        &copy; 2026 <span>Ladusync</span> — Sistem Manajemen Panen Terintegrasi · Universitas Sebelas Maret
    </footer>

</div>
</div>

<!-- ============================================================ -->
<!-- MODAL TAMBAH/EDIT LAHAN -->
<!-- ============================================================ -->
<div id="modal-lahan" class="modal-overlay" onclick="if(event.target===this) closeModal('modal-lahan')">
    <div class="modal-content">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-display text-xl font-bold" style="color:var(--sawah);">
                <?= $edit_lahan ? 'Edit Lahan' : 'Tambah Lahan' ?>
            </h2>
            <button onclick="closeModal('modal-lahan')" class="text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
        </div>
        <form method="POST">
            <?php if ($edit_lahan): ?>
                <input type="hidden" name="id_lahan" value="<?= $edit_lahan['id'] ?>">
                <input type="hidden" name="edit_lahan" value="1">
            <?php else: ?>
                <input type="hidden" name="tambah_lahan" value="1">
            <?php endif; ?>
            <div class="space-y-4">
                <div>
                    <label class="form-label">Lokasi Lahan</label>
                    <input type="text" name="lokasi" required placeholder="Contoh: Blok A, Sawah Utara" class="form-input" value="<?= $edit_lahan ? htmlspecialchars($edit_lahan['lokasi']) : '' ?>">
                </div>
                <div>
                    <label class="form-label">Luas Lahan (Hektar)</label>
                    <input type="number" name="luas" required step="0.01" min="0.01" placeholder="0.00" class="form-input" value="<?= $edit_lahan ? $edit_lahan['luas'] : '' ?>">
                </div>
                <div>
                    <label class="form-label">Status Lahan</label>
                    <select name="status_lahan" required class="form-input">
                        <option value="Aktif" <?= $edit_lahan && $edit_lahan['status_lahan'] == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="Istirahat" <?= $edit_lahan && $edit_lahan['status_lahan'] == 'Istirahat' ? 'selected' : '' ?>>Istirahat</option>
                        <option value="Persiapan" <?= $edit_lahan && $edit_lahan['status_lahan'] == 'Persiapan' ? 'selected' : '' ?>>Persiapan</option>
                    </select>
                </div>
                <button type="submit" class="w-full <?= $edit_lahan ? 'btn-gabah' : 'btn-primary' ?> py-3 text-base">
                    <?= $edit_lahan ? 'Update Lahan' : 'Simpan Lahan' ?>
                </button>
                <?php if ($edit_lahan): ?>
                    <a href="data_lahan.php#lahan" class="block text-center text-sm text-slate-400 hover:text-slate-600">Batal Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL TAMBAH PANEN -->
<!-- ============================================================ -->
<div id="modal-panen" class="modal-overlay" onclick="if(event.target===this) closeModal('modal-panen')">
    <div class="modal-content">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-display text-xl font-bold" style="color:var(--gabah);">🌾 Catat Hasil Panen</h2>
            <button onclick="closeModal('modal-panen')" class="text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="tambah_panen" value="1">
            <div class="space-y-4">
                <div>
                    <label class="form-label">Komoditas</label>
                    <input type="text" name="komoditas" required placeholder="Contoh: Padi, Jagung, Cabai" class="form-input">
                </div>
                <div>
                    <label class="form-label">Luas Lahan (Hektar)</label>
                    <input type="number" name="luas_lahan" required step="0.01" min="0.01" placeholder="0.00" class="form-input">
                </div>
                <div>
                    <label class="form-label">Hasil Panen (Ton)</label>
                    <input type="number" name="hasil_ton" required step="0.01" min="0.01" placeholder="0.00" class="form-input">
                </div>
                <div>
                    <label class="form-label">Tanggal Panen</label>
                    <input type="date" name="tanggal_panen" required class="form-input" value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label class="form-label">Musim</label>
                    <select name="musim" required class="form-input">
                        <option value="rendeng">Rendeng (Musim Hujan)</option>
                        <option value="gadu">Gadu (Musim Kemarau)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Keterangan (Opsional)</label>
                    <textarea name="keterangan" class="form-input" rows="2" placeholder="Catatan tambahan..."></textarea>
                </div>
                <button type="submit" class="w-full btn-gabah py-3 text-base">Simpan Panen</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================================ -->
<script>
// Sidebar toggle
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainArea = document.getElementById('mainArea');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleHamburger = document.getElementById('sidebarToggleHamburger');
    const toggleCollapse = document.getElementById('sidebarToggleCollapse');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const STORAGE_KEY = 'sidebar_collapsed';

    function isSidebarOpen() { return sidebar.classList.contains('open'); }

    function openSidebar() {
        sidebar.classList.add('open');
        sidebar.classList.remove('collapsed');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
        if (window.innerWidth > 992) localStorage.setItem(STORAGE_KEY, 'false');
        updateMainArea();
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        sidebar.classList.add('collapsed');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
        if (window.innerWidth > 992) localStorage.setItem(STORAGE_KEY, 'true');
        updateMainArea();
    }

    function updateMainArea() {
        if (sidebar.classList.contains('collapsed') || !sidebar.classList.contains('open')) {
            mainArea.classList.add('sidebar-collapsed');
        } else {
            mainArea.classList.remove('sidebar-collapsed');
        }
    }

    function loadSidebarState() {
        const isDesktop = window.innerWidth > 992;
        if (isDesktop) {
            const collapsed = localStorage.getItem(STORAGE_KEY);
            if (collapsed === 'true') {
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('open');
                mainArea.classList.add('sidebar-collapsed');
            } else {
                sidebar.classList.remove('collapsed');
                sidebar.classList.add('open');
                mainArea.classList.remove('sidebar-collapsed');
            }
            overlay.classList.remove('open');
        } else {
            sidebar.classList.add('collapsed');
            sidebar.classList.remove('open');
            mainArea.classList.add('sidebar-collapsed');
            overlay.classList.remove('open');
        }
    }

    if (toggleHamburger) {
        toggleHamburger.addEventListener('click', function(e) {
            e.stopPropagation();
            if (window.innerWidth <= 992) { openSidebar(); }
            else {
                if (sidebar.classList.contains('collapsed')) {
                    sidebar.classList.remove('collapsed');
                    sidebar.classList.add('open');
                    localStorage.setItem(STORAGE_KEY, 'false');
                    updateMainArea();
                } else {
                    sidebar.classList.add('collapsed');
                    sidebar.classList.remove('open');
                    localStorage.setItem(STORAGE_KEY, 'true');
                    updateMainArea();
                }
            }
        });
    }

    if (toggleCollapse) {
        toggleCollapse.addEventListener('click', function(e) {
            e.stopPropagation();
            if (window.innerWidth <= 992) { closeSidebar(); }
            else {
                if (sidebar.classList.contains('collapsed')) {
                    sidebar.classList.remove('collapsed');
                    sidebar.classList.add('open');
                    localStorage.setItem(STORAGE_KEY, 'false');
                } else {
                    sidebar.classList.add('collapsed');
                    sidebar.classList.remove('open');
                    localStorage.setItem(STORAGE_KEY, 'true');
                }
                updateMainArea();
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (window.innerWidth <= 992) { closeSidebar(); }
            else {
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('open');
                localStorage.setItem(STORAGE_KEY, 'true');
                updateMainArea();
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() { closeSidebar(); });
    }

    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            const isDesktop = window.innerWidth > 992;
            if (isDesktop) {
                const collapsed = localStorage.getItem(STORAGE_KEY);
                if (collapsed === 'true') {
                    sidebar.classList.add('collapsed');
                    sidebar.classList.remove('open');
                    mainArea.classList.add('sidebar-collapsed');
                } else {
                    sidebar.classList.remove('collapsed');
                    sidebar.classList.add('open');
                    mainArea.classList.remove('sidebar-collapsed');
                }
                overlay.classList.remove('open');
                document.body.style.overflow = '';
            } else {
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('open');
                mainArea.classList.add('sidebar-collapsed');
                overlay.classList.remove('open');
                document.body.style.overflow = '';
            }
        }, 200);
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isSidebarOpen()) closeSidebar();
    });

    loadSidebarState();

    // Deteksi hash untuk tab
    const hash = window.location.hash;
    if (hash === '#lahan') {
        switchTab('lahan');
    } else if (hash === '#panen') {
        switchTab('panen');
    }
});

// ===== TAB SWITCH =====
function switchTab(tab) {
    const lahanTab = document.getElementById('tab-lahan');
    const panenTab = document.getElementById('tab-panen');
    const lahanBtn = document.getElementById('tab-lahan-btn');
    const panenBtn = document.getElementById('tab-panen-btn');
    
    if (tab === 'lahan') {
        lahanTab.classList.remove('hidden');
        panenTab.classList.add('hidden');
        lahanBtn.classList.add('active');
        panenBtn.classList.remove('active');
        history.pushState(null, null, '#lahan');
    } else {
        lahanTab.classList.add('hidden');
        panenTab.classList.remove('hidden');
        panenBtn.classList.add('active');
        lahanBtn.classList.remove('active');
        history.pushState(null, null, '#panen');
    }
}

// ===== MODAL =====
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Tutup modal dengan ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(function(el) {
            el.classList.remove('active');
            document.body.style.overflow = 'auto';
        });
    }
});

// Auto open edit modal
<?php if ($edit_lahan): ?>
document.addEventListener('DOMContentLoaded', function() {
    openModal('modal-lahan');
});
<?php endif; ?>
</script>

</body>
</html>