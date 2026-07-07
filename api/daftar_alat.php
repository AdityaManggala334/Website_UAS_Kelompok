<?php
// api/daftar_alat.php
// ======================================================
// KATALOG ALAT PERTANIAN - LADUSYNC
// ======================================================

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth_helper.php';

// Cek apakah user login
if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}

$adminNama = $namaLengkap;
$adminId   = (int)$user_id;

// Ambil data alat
$result = mysqli_query($conn, "SELECT * FROM alat ORDER BY id ASC");
if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}
$total_alat = mysqli_num_rows($result);

// Hitung total stok
$total_stok = 0;
mysqli_data_seek($result, 0);
while ($row = mysqli_fetch_assoc($result)) {
    $total_stok += $row['stok'];
}
mysqli_data_seek($result, 0);

// ============================================================
// AMBIL DATA PEMINJAMAN USER (untuk badge status)
// ============================================================
$user_peminjaman = [];
if ($is_logged_in) {
    $query_pinjam = mysqli_query($conn, 
        "SELECT id_alat, status, tanggal_kembali_estimasi 
         FROM peminjaman 
         WHERE id_users = $user_id 
         AND status IN ('lunas', 'dipinjam', 'terlambat')"
    );
    while ($row = mysqli_fetch_assoc($query_pinjam)) {
        $user_peminjaman[$row['id_alat']] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Katalog Alat - Ladusync</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
    /* ===== SEMUA CSS SAMA SEPERTI SEBELUMNYA ===== */
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
    .content { padding: 1.25rem; flex: 1; overflow-y: auto; max-width: 1200px; margin: 0 auto; width: 100%; }

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
    }

    @media (max-width: 480px) {
        .topbar { padding: 0 10px; height: 50px; }
        .sidebar { width: 280px !important; }
        .content { padding: 0.5rem; }
    }

    /* ===== CATALOG STYLES ===== */
    .hero-section {
        background: linear-gradient(135deg, var(--tanah), var(--sawah));
        padding: 2rem 1.5rem;
        margin-bottom: 2rem;
        border-radius: 12px;
        position: relative;
        overflow: hidden;
    }
    .hero-section::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.5;
    }
    .hero-content {
        position: relative;
        z-index: 1;
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.5rem;
    }
    .hero-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: white;
        letter-spacing: -0.03em;
    }
    .hero-title span { color: var(--gabah-light); }
    .hero-subtitle {
        font-size: 0.85rem;
        color: rgba(255,255,255,0.5);
        margin-top: 4px;
    }
    .hero-stats {
        display: flex;
        gap: 2rem;
    }
    .hero-stat {
        text-align: center;
    }
    .hero-stat-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: white;
        display: block;
    }
    .hero-stat-label {
        font-size: 0.65rem;
        color: rgba(255,255,255,0.4);
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    /* ===== FILTER ===== */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        align-items: center;
    }
    .filter-search {
        flex: 1;
        min-width: 200px;
        position: relative;
    }
    .filter-search input {
        width: 100%;
        padding: 0.6rem 2.5rem 0.6rem 2.5rem;
        border: 1px solid rgba(138,115,87,0.18);
        border-radius: 10px;
        font-size: 0.85rem;
        font-family: 'Sora', sans-serif;
        background: white;
        color: var(--ink);
        transition: all 0.2s;
        outline: none;
    }
    .filter-search input:focus {
        border-color: var(--sawah);
        box-shadow: 0 0 0 3px rgba(47,82,51,0.08);
    }
    .filter-search .search-icon {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94A3B8;
        pointer-events: none;
    }
    .filter-search .clear-btn {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94A3B8;
        cursor: pointer;
        display: none;
        background: none;
        border: none;
        padding: 4px;
        border-radius: 50%;
        transition: all 0.2s;
    }
    .filter-search .clear-btn:hover {
        background: rgba(0,0,0,0.05);
        color: var(--ink);
    }
    .filter-search .clear-btn.show { display: block; }

    .filter-select {
        padding: 0.6rem 2rem 0.6rem 1rem;
        border: 1px solid rgba(138,115,87,0.18);
        border-radius: 10px;
        font-size: 0.85rem;
        font-family: 'Sora', sans-serif;
        background: white;
        color: var(--ink);
        appearance: none;
        cursor: pointer;
        transition: all 0.2s;
        outline: none;
        min-width: 140px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394A3B8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
    }
    .filter-select:focus {
        border-color: var(--sawah);
        box-shadow: 0 0 0 3px rgba(47,82,51,0.08);
    }

    /* ===== TOOLBAR ===== */
    .toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .toolbar-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .toolbar-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--ink);
        font-family: 'Fraunces', serif;
    }
    .toolbar-badge {
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        background: rgba(47,82,51,0.08);
        color: var(--sawah);
    }
    .toolbar-view {
        display: flex;
        gap: 4px;
    }
    .toolbar-view button {
        padding: 6px 8px;
        border: 1px solid rgba(138,115,87,0.15);
        border-radius: 6px;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94A3B8;
    }
    .toolbar-view button:hover {
        background: var(--kertas-2);
    }
    .toolbar-view button.active {
        border-color: var(--sawah);
        background: rgba(47,82,51,0.06);
        color: var(--sawah);
    }

    /* ===== PRODUCT GRID ===== */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(1, 1fr);
        gap: 1.25rem;
    }
    @media (min-width: 640px) {
        .product-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 1024px) {
        .product-grid { grid-template-columns: repeat(3, 1fr); }
    }

    .product-card {
        background: white;
        border: 1px solid rgba(138,115,87,0.12);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(28,43,30,0.04);
        display: flex;
        flex-direction: column;
        cursor: pointer;
        position: relative;
    }
    .product-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 40px rgba(28,43,30,0.12);
        border-color: rgba(47,82,51,0.25);
    }
    .product-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--sawah), var(--gabah-light), var(--sawah));
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .product-card:hover::after {
        opacity: 1;
    }

    .product-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        background: #f1f5f9;
        display: block;
        transition: transform 0.5s ease;
    }
    .product-card:hover .product-image {
        transform: scale(1.03);
    }

    .product-body {
        padding: 1rem 1.25rem 1.25rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .product-tag {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 0.6rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        background: rgba(47,82,51,0.08);
        color: var(--sawah);
        margin-bottom: 6px;
        align-self: flex-start;
    }
    .product-name {
        font-family: 'Fraunces', serif;
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: 4px;
        line-height: 1.3;
    }
    .product-desc {
        font-size: 0.78rem;
        color: var(--lempung);
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: 0.75rem;
        flex: 1;
    }
    .product-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 0.75rem;
        border-top: 1px solid rgba(138,115,87,0.08);
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .product-price {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--sawah);
    }
    .product-price span {
        font-size: 0.7rem;
        font-weight: 400;
        color: #94A3B8;
    }
    .product-stock {
        font-size: 0.7rem;
        font-weight: 600;
        padding: 2px 10px;
        border-radius: 20px;
    }
    .product-stock.available {
        background: rgba(47,82,51,0.08);
        color: var(--sawah);
    }
    .product-stock.limited {
        background: rgba(211,168,104,0.12);
        color: var(--gabah);
    }
    .product-stock.empty {
        background: rgba(156,65,48,0.08);
        color: var(--kritis);
    }

    /* ===== BADGE PEMINJAMAN USER ===== */
    .pinjam-badge {
        margin-top: 6px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.65rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        background: rgba(47,82,51,0.06);
        color: var(--sawah);
        border: 1px solid rgba(47,82,51,0.10);
    }
    .pinjam-badge.overdue {
        background: rgba(156,65,48,0.06);
        color: var(--kritis);
        border-color: rgba(156,65,48,0.15);
    }
    .pinjam-badge .badge-link {
        color: var(--sawah);
        text-decoration: none;
        font-weight: 700;
        font-size: 0.6rem;
        padding: 2px 6px;
        border-radius: 4px;
        transition: all 0.2s;
    }
    .pinjam-badge .badge-link:hover {
        background: rgba(47,82,51,0.06);
    }
    .pinjam-badge.overdue .badge-link {
        color: var(--kritis);
    }
    .pinjam-badge.overdue .badge-link:hover {
        background: rgba(156,65,48,0.06);
    }

    .btn-sewa {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        text-align: center;
        width: 100%;
        margin-top: 0.5rem;
        font-family: 'Sora', sans-serif;
        background: linear-gradient(135deg, var(--sawah), var(--sawah-light));
        color: white;
        position: relative;
        overflow: hidden;
    }
    .btn-sewa::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
        transition: left 0.5s ease;
    }
    .btn-sewa:hover::before {
        left: 100%;
    }
    .btn-sewa:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(47,82,51,0.25);
    }
    .btn-sewa:active {
        transform: translateY(0);
    }
    .btn-sewa.disabled {
        background: #e2e8f0;
        color: #94A3B8;
        cursor: not-allowed;
    }
    .btn-sewa.disabled:hover {
        transform: none;
        box-shadow: none;
    }
    .btn-sewa.disabled::before {
        display: none;
    }

    /* ===== PRODUCT DETAIL MODAL (sama) ===== */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(10,20,16,0.6);
        backdrop-filter: blur(8px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 20px;
        animation: fadeIn 0.3s ease;
    }
    .modal-overlay.active { display: flex; }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideUp {
        from { transform: translateY(30px) scale(0.95); opacity: 0; }
        to { transform: translateY(0) scale(1); opacity: 1; }
    }

    .modal-box {
        background: white;
        border-radius: 16px;
        max-width: 650px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 24px 80px rgba(0,0,0,0.3);
    }
    .modal-box::-webkit-scrollbar { width: 4px; }
    .modal-box::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 0 0 16px 0; }
    .modal-box::-webkit-scrollbar-thumb { background: rgba(138,115,87,0.3); border-radius: 2px; }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(138,115,87,0.08);
        background: linear-gradient(135deg, #F5F1E5, #ECE5D3);
        border-radius: 16px 16px 0 0;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .modal-header h3 {
        font-family: 'Fraunces', serif;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--ink);
    }
    .modal-close {
        background: none;
        border: none;
        font-size: 1.3rem;
        cursor: pointer;
        color: #94A3B8;
        transition: all 0.2s ease;
        padding: 4px 8px;
        border-radius: 6px;
    }
    .modal-close:hover {
        background: rgba(0,0,0,0.05);
        color: var(--kritis);
        transform: rotate(90deg);
    }

    .modal-body { padding: 1.5rem; }
    .modal-image {
        width: 100%;
        max-height: 300px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 1rem;
        background: #f1f5f9;
    }
    .modal-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        margin: 1rem 0;
    }
    .modal-info-item {
        background: #f8fafc;
        padding: 0.75rem 1rem;
        border-radius: 10px;
        border: 1px solid rgba(138,115,87,0.06);
    }
    .modal-info-item .label {
        font-size: 0.6rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #94A3B8;
        display: block;
    }
    .modal-info-item .value {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--ink);
        margin-top: 2px;
    }
    .modal-info-item .value.price {
        color: var(--sawah);
    }
    .modal-info-item .value.stock-ok { color: var(--sawah); }
    .modal-info-item .value.stock-limited { color: var(--gabah); }
    .modal-info-item .value.stock-empty { color: var(--kritis); }

    .modal-desc {
        font-size: 0.82rem;
        color: var(--lempung);
        line-height: 1.6;
        margin: 0.75rem 0 1rem;
        padding: 0.75rem 1rem;
        background: #f8fafc;
        border-radius: 10px;
        border-left: 3px solid var(--gabah);
    }
    .modal-footer {
        display: flex;
        gap: 0.75rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(138,115,87,0.08);
        flex-wrap: wrap;
    }
    .modal-footer .btn-sewa {
        width: auto;
        padding: 0.6rem 2rem;
        margin-top: 0;
        flex: 1;
    }
    .modal-footer .btn-close {
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        border: 1px solid rgba(138,115,87,0.18);
        background: white;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        color: var(--lempung);
        font-family: 'Sora', sans-serif;
    }
    .modal-footer .btn-close:hover {
        background: #f1f5f9;
        border-color: rgba(138,115,87,0.3);
    }

    /* ===== EMPTY STATE ===== */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border: 1px solid rgba(138,115,87,0.12);
        border-radius: 12px;
    }
    .empty-state-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 1.5rem;
        background: rgba(47,82,51,0.04);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    .empty-state-icon svg { color: #94A3B8; opacity: 0.4; }
    .empty-state-title {
        font-family: 'Fraunces', serif;
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: 4px;
    }
    .empty-state-desc {
        font-size: 0.85rem;
        color: var(--lempung);
    }

    /* ===== LIST VIEW ===== */
    .product-grid.list-view {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .product-grid.list-view .product-card {
        flex-direction: row;
        align-items: center;
        padding: 0.75rem;
    }
    .product-grid.list-view .product-image {
        width: 120px;
        height: 120px;
        border-radius: 8px;
        flex-shrink: 0;
    }
    .product-grid.list-view .product-body {
        padding: 0 1rem;
    }
    .product-grid.list-view .btn-sewa {
        width: auto;
        padding: 0.4rem 1.5rem;
    }
    .product-grid.list-view .product-footer {
        border-top: none;
        padding-top: 0;
    }
    .product-grid.list-view .product-desc {
        -webkit-line-clamp: 1;
    }

    @media (max-width: 640px) {
        .product-grid.list-view .product-card {
            flex-direction: column;
        }
        .product-grid.list-view .product-image {
            width: 100%;
            height: 150px;
        }
        .product-grid.list-view .btn-sewa {
            width: 100%;
        }
        .hero-stats {
            gap: 1rem;
            flex-wrap: wrap;
        }
        .modal-info-grid {
            grid-template-columns: 1fr;
        }
        .modal-footer {
            flex-direction: column;
        }
        .modal-footer .btn-sewa {
            width: 100%;
        }
    }

    /* ===== FOOTER ===== */
    .main-footer {
        background: var(--tanah);
        color: rgba(245,241,229,0.4);
        margin-top: 40px;
        border-top: 1px solid rgba(211,168,104,0.12);
        width: 100%;
    }
    .main-footer .footer-inner {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 20px 1.5rem;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
    }
    .main-footer .footer-brand {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .main-footer .footer-brand .brand {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }
    .main-footer .footer-brand .brand span {
        font-family: 'Fraunces', serif;
        font-weight: 700;
        font-size: 1.1rem;
        color: white;
    }
    .main-footer .footer-brand p {
        font-size: 0.75rem;
        color: rgba(245,241,229,0.4);
        max-width: 300px;
        line-height: 1.5;
    }
    .main-footer .footer-links h4 {
        color: rgba(245,241,229,0.6);
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 0.75rem;
        font-weight: 700;
    }
    .main-footer .footer-links ul {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }
    .main-footer .footer-links ul li a {
        color: rgba(245,241,229,0.35);
        text-decoration: none;
        font-size: 0.75rem;
        transition: color 0.2s;
    }
    .main-footer .footer-links ul li a:hover {
        color: rgba(245,241,229,0.7);
    }
    .main-footer .footer-bottom {
        border-top: 1px solid rgba(211,168,104,0.08);
        padding: 1rem 20px;
        text-align: center;
        font-size: 0.7rem;
        color: rgba(245,241,229,0.25);
    }
    .main-footer .footer-bottom span {
        color: rgba(245,241,229,0.45);
    }

    @media (max-width: 640px) {
        .main-footer .footer-inner {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        .main-footer .footer-brand p {
            max-width: 100%;
        }
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

        <a href="daftar_alat.php" class="nav-link active">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <span class="nav-text">Sewa Alat</span>
        </a>

        <a href="data_lahan.php" class="nav-link">
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
                Katalog Alat Pertanian
            </span>
        </div>

        <div class="nav-right">
            <div class="profil-wrap relative">
                <button class="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium bg-transparent border-none cursor-pointer" style="color:var(--ink);">
                    <div class="w-7 h-7 rounded-md flex items-center justify-center font-bold text-xs" style="background:rgba(47,82,51,0.12);color:var(--sawah);">
                        <?= strtoupper(substr($username, 0, 1)) ?>
                    </div>
                    <span class="profil-name hidden sm:inline"><?= htmlspecialchars($username) ?></span>
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </button>

                <div class="profil-dropdown">
                    <div class="px-4 py-3 border-b" style="background:linear-gradient(135deg,#F5F1E5,#ECE5D3);border-color:rgba(138,115,87,0.18);">
                        <div class="font-bold text-sm font-display" style="color:var(--sawah);"><?= htmlspecialchars($username) ?></div>
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

        <!-- HERO -->
        <div class="hero-section">
            <div class="hero-content">
                <div>
                    <h1 class="hero-title">Katalog <span>Alat Pertanian</span></h1>
                    <p class="hero-subtitle">Temukan alat modern untuk mendukung produktivitas pertanian Anda</p>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-value"><?= $total_alat ?></span>
                        <span class="hero-stat-label">Total Alat</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-value"><?= $total_stok ?></span>
                        <span class="hero-stat-label">Total Stok</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-search">
                <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="searchInput" placeholder="Cari alat..." oninput="filterProducts()">
                <button class="clear-btn" id="clearBtn" onclick="clearSearch()" title="Hapus pencarian">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <select class="filter-select" id="sortSelect" onchange="filterProducts()">
                <option value="default">Urutkan</option>
                <option value="name">Nama A-Z</option>
                <option value="price-low">Harga Terendah</option>
                <option value="price-high">Harga Tertinggi</option>
                <option value="stock">Stok Tersedia</option>
            </select>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="toolbar-left">
                <span class="toolbar-title">Semua Alat</span>
                <span class="toolbar-badge" id="itemCount"><?= $total_alat ?> item</span>
            </div>
            <div class="toolbar-view">
                <button class="active" onclick="setView('grid')" title="Grid View">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                </button>
                <button onclick="setView('list')" title="List View">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"/>
                        <line x1="8" y1="12" x2="21" y2="12"/>
                        <line x1="8" y1="18" x2="21" y2="18"/>
                        <line x1="3" y1="6" x2="3.01" y2="6"/>
                        <line x1="3" y1="12" x2="3.01" y2="12"/>
                        <line x1="3" y1="18" x2="3.01" y2="18"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="product-grid" id="productGrid">
            <?php if ($total_alat > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)):
                    $nama = htmlspecialchars($row['nama_alat']);
                    $deskripsi = htmlspecialchars($row['deskripsi'] ?? 'Deskripsi tidak tersedia');
                    $harga = number_format($row['harga'], 0, ',', '.');
                    $harga_raw = $row['harga'];
                    $stok = (int)$row['stok'];
                    $id = (int)$row['id'];
                    $gambar = !empty($row['gambar']) ? htmlspecialchars($row['gambar']) : 'https://placehold.co/600x400/e2e8f0/64748b?text=' . urlencode($nama);

                    if ($stok > 5) {
                        $stock_class = 'available';
                        $stock_label = 'Stok Tersedia';
                    } elseif ($stok > 0) {
                        $stock_class = 'limited';
                        $stock_label = 'Stok Terbatas';
                    } else {
                        $stock_class = 'empty';
                        $stock_label = 'Habis';
                    }

                    // Cek apakah user sedang meminjam alat ini
                    $user_pinjam = isset($user_peminjaman[$id]) ? $user_peminjaman[$id] : null;
                    $is_pinjam = $user_pinjam && in_array($user_pinjam['status'], ['lunas', 'dipinjam', 'terlambat']);
                    
                    // Hitung sisa hari
                    $sisa_hari = 0;
                    $is_overdue = false;
                    if ($is_pinjam && $user_pinjam['tanggal_kembali_estimasi']) {
                        $tgl_kembali = new DateTime($user_pinjam['tanggal_kembali_estimasi']);
                        $sekarang = new DateTime();
                        $diff = $sekarang->diff($tgl_kembali);
                        $sisa_hari = $diff->days;
                        if ($sekarang > $tgl_kembali && $user_pinjam['status'] === 'dipinjam') {
                            $is_overdue = true;
                            $sisa_hari = -$sisa_hari;
                        }
                    }
                ?>
                <div class="product-card" 
                     data-id="<?= $id ?>"
                     data-name="<?= strtolower($nama) ?>" 
                     data-price="<?= $harga_raw ?>" 
                     data-stock="<?= $stok ?>"
                     onclick="openDetail(<?= $id ?>, '<?= addslashes($nama) ?>', '<?= addslashes($deskripsi) ?>', '<?= $harga ?>', <?= $stok ?>, '<?= $gambar ?>', <?= $harga_raw ?>)">
                    <img src="<?= $gambar ?>" alt="<?= $nama ?>" class="product-image" loading="lazy" onerror="this.src='https://placehold.co/600x400/e2e8f0/64748b?text=No+Image'">
                    <div class="product-body">
                        <span class="product-tag">Alat Pertanian</span>
                        <h3 class="product-name"><?= $nama ?></h3>
                        <p class="product-desc"><?= $deskripsi ?></p>
                        <div class="product-footer">
                            <div>
                                <div class="product-price">Rp <?= $harga ?> <span>/ hari</span></div>
                            </div>
                            <span class="product-stock <?= $stock_class ?>"><?= $stock_label ?></span>
                        </div>
                        
                        <!-- ✅ BADGE STATUS PEMINJAMAN USER -->
                        <?php if ($is_pinjam): ?>
                        <div class="pinjam-badge <?= $is_overdue ? 'overdue' : '' ?>">
                            <span>
                                <?php if ($is_overdue): ?>
                                    ⚠️ Terlambat <?= abs($sisa_hari) ?> hari
                                <?php elseif ($sisa_hari <= 3 && $sisa_hari > 0): ?>
                                    ⏳ Sisa <?= $sisa_hari ?> hari
                                <?php elseif ($sisa_hari > 3): ?>
                                    ✅ Dipinjam (<?= $sisa_hari ?> hari)
                                <?php else: ?>
                                    ✅ Siap diambil
                                <?php endif; ?>
                            </span>
                            <a href="detail_peminjaman.php?id=<?= $user_pinjam['id'] ?? 0 ?>" class="badge-link" onclick="event.stopPropagation();">
                                Detail →
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($stok > 0 && !$is_pinjam): ?>
                            <a href="pinjam.php?id=<?= $id ?>" class="btn-sewa" onclick="event.stopPropagation();">Sewa Sekarang</a>
                        <?php elseif ($stok > 0 && $is_pinjam): ?>
                            <button class="btn-sewa disabled" disabled onclick="event.stopPropagation();">Sedang Dipinjam</button>
                        <?php else: ?>
                            <button class="btn-sewa disabled" disabled onclick="event.stopPropagation();">Stok Habis</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <div class="empty-state-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </div>
                    <h3 class="empty-state-title">Belum Ada Alat</h3>
                    <p class="empty-state-desc">Silakan tambahkan alat melalui dashboard admin.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="main-footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <a href="index.php" class="brand">
                    <svg width="28" height="28" viewBox="0 0 44 44" fill="none">
                        <path d="M22 7C22 7 13 18 13 24C13 29.52 17.03 34 22 34C26.97 34 31 29.52 31 24C31 18 22 7 22 7Z" fill="#D3A868"/>
                        <line x1="18" y1="24" x2="26" y2="24" stroke="#1C2B1E" stroke-width="1.8" stroke-linecap="round"/>
                        <circle cx="18" cy="24" r="1.4" fill="#1C2B1E"/>
                        <circle cx="26" cy="24" r="1.4" fill="#1C2B1E"/>
                        <line x1="22" y1="20" x2="22" y2="28" stroke="#1C2B1E" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <span>Ladusync</span>
                </a>
                <p>Platform ekosistem digital agrikultur — menyatukan irigasi pintar, pencatatan hasil panen, dan peminjaman alat tani dalam satu sistem terpadu.</p>
            </div>
            <div class="footer-links">
                <h4>Navigasi</h4>
                <ul>
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="daftar_alat.php">Katalog Alat</a></li>
                    <li><a href="forum.php">Forum Diskusi</a></li>
                    <li><a href="peta.php">Peta Sensor</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4>Layanan</h4>
                <ul>
                    <li><a href="daftar_alat.php">Sewa Alat</a></li>
                    <li><a href="data_lahan.php">Hasil Panen</a></li>
                    <li><a href="bps.php">Data BPS</a></li>
                    <li><a href="riwayat.php">Riwayat Data</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4>Kontak</h4>
                <ul>
                    <li style="color:rgba(245,241,229,0.35);font-size:0.75rem;">Fakultas Pertanian, UNS</li>
                    <li style="color:rgba(245,241,229,0.35);font-size:0.75rem;">Jl. Ir. Sutami No.36A, Surakarta</li>
                    <li style="color:rgba(245,241,229,0.35);font-size:0.75rem;">📧 kontak@ladusync.id</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2026 <span>Ladusync</span> — Sistem Integrasi Agrikultur · Universitas Sebelas Maret
        </div>
    </footer>

</div>
</div>

<!-- ===== PRODUCT DETAIL MODAL ===== -->
<div class="modal-overlay" id="detailModal" onclick="if(event.target===this) closeDetail()">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalTitle">Detail Alat</h3>
            <button class="modal-close" onclick="closeDetail()">✕</button>
        </div>
        <div class="modal-body">
            <img id="modalImage" class="modal-image" src="" alt="Gambar alat">
            <div class="modal-info-grid">
                <div class="modal-info-item">
                    <span class="label">Nama Alat</span>
                    <span class="value" id="modalName">-</span>
                </div>
                <div class="modal-info-item">
                    <span class="label">Harga Sewa</span>
                    <span class="value price" id="modalPrice">-</span>
                </div>
                <div class="modal-info-item">
                    <span class="label">Stok</span>
                    <span class="value" id="modalStock">-</span>
                </div>
                <div class="modal-info-item">
                    <span class="label">Kategori</span>
                    <span class="value">Alat Pertanian</span>
                </div>
            </div>
            <div class="modal-desc" id="modalDesc">-</div>
            <div class="modal-footer">
                <a href="#" id="modalSewaBtn" class="btn-sewa" onclick="event.stopPropagation();">Sewa Sekarang</a>
                <button class="btn-close" onclick="closeDetail()">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- SIDEBAR TOGGLE JAVASCRIPT                    -->
<!-- ============================================ -->
<script>
// ===== SIDEBAR TOGGLE =====
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
        if (e.key === 'Escape' && document.getElementById('detailModal').classList.contains('active')) closeDetail();
    });

    loadSidebarState();
});

// ============================================
// PRODUCT DETAIL MODAL
// ============================================
function openDetail(id, name, desc, price, stock, image, rawPrice) {
    const modal = document.getElementById('detailModal');
    document.getElementById('modalTitle').textContent = 'Detail Alat';
    document.getElementById('modalImage').src = image;
    document.getElementById('modalName').textContent = name;
    document.getElementById('modalPrice').textContent = 'Rp ' + price + ' / hari';
    document.getElementById('modalDesc').textContent = desc;
    
    const stockEl = document.getElementById('modalStock');
    if (stock > 5) {
        stockEl.textContent = stock + ' unit (Tersedia)';
        stockEl.className = 'value stock-ok';
    } else if (stock > 0) {
        stockEl.textContent = stock + ' unit (Stok Terbatas)';
        stockEl.className = 'value stock-limited';
    } else {
        stockEl.textContent = 'Habis';
        stockEl.className = 'value stock-empty';
    }
    
    const sewaBtn = document.getElementById('modalSewaBtn');
    if (stock > 0) {
        sewaBtn.href = 'pinjam.php?id=' + id;
        sewaBtn.textContent = '🛒 Sewa Sekarang';
        sewaBtn.className = 'btn-sewa';
        sewaBtn.style.display = 'block';
    } else {
        sewaBtn.textContent = 'Stok Habis';
        sewaBtn.className = 'btn-sewa disabled';
        sewaBtn.style.display = 'block';
        sewaBtn.href = '#';
        sewaBtn.onclick = function(e) { e.preventDefault(); };
    }
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeDetail() {
    document.getElementById('detailModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetail();
    }
});

// ============================================
// FILTER & SEARCH - DIPERBAIKI
// ============================================
function filterProducts() {
    const query = document.getElementById('searchInput').value.toLowerCase().trim();
    const sort = document.getElementById('sortSelect').value;
    const cards = Array.from(document.querySelectorAll('.product-card'));
    const grid = document.getElementById('productGrid');
    const itemCount = document.getElementById('itemCount');
    const clearBtn = document.getElementById('clearBtn');

    // Tampilkan/sembunyikan tombol clear
    if (query.length > 0) {
        clearBtn.classList.add('show');
    } else {
        clearBtn.classList.remove('show');
    }

    // Filter
    let filtered = cards;
    if (query.length > 0) {
        filtered = cards.filter(card => {
            const name = card.dataset.name || '';
            return name.includes(query);
        });
    }

    // Sort
    const sorted = [...filtered];
    switch (sort) {
        case 'name':
            sorted.sort((a, b) => (a.dataset.name || '').localeCompare(b.dataset.name || ''));
            break;
        case 'price-low':
            sorted.sort((a, b) => (parseFloat(a.dataset.price) || 0) - (parseFloat(b.dataset.price) || 0));
            break;
        case 'price-high':
            sorted.sort((a, b) => (parseFloat(b.dataset.price) || 0) - (parseFloat(a.dataset.price) || 0));
            break;
        case 'stock':
            sorted.sort((a, b) => (parseInt(b.dataset.stock) || 0) - (parseInt(a.dataset.stock) || 0));
            break;
        default:
            break;
    }

    // Update count
    itemCount.textContent = sorted.length + ' item';

    // Render
    grid.innerHTML = '';
    if (sorted.length === 0) {
        grid.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <div class="empty-state-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </div>
                <h3 class="empty-state-title">Tidak Ditemukan</h3>
                <p class="empty-state-desc">Tidak ada alat yang sesuai dengan pencarian "<strong>${document.getElementById('searchInput').value}</strong>".</p>
            </div>
        `;
        return;
    }
    sorted.forEach(card => grid.appendChild(card));
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('clearBtn').classList.remove('show');
    filterProducts();
    document.getElementById('searchInput').focus();
}

// ============================================
// VIEW TOGGLE (Grid / List)
// ============================================
function setView(view) {
    const grid = document.getElementById('productGrid');
    const btns = document.querySelectorAll('.toolbar-view button');
    btns.forEach(btn => btn.classList.remove('active'));

    if (view === 'grid') {
        grid.classList.remove('list-view');
        btns[0].classList.add('active');
    } else {
        grid.classList.add('list-view');
        btns[1].classList.add('active');
    }
}

// ============================================
// KEYBOARD SHORTCUT
// ============================================
document.addEventListener('keydown', function(e) {
    // Ctrl+F atau / untuk fokus ke search
    if ((e.ctrlKey && e.key === 'f') || (e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey)) {
        e.preventDefault();
        document.getElementById('searchInput').focus();
        document.getElementById('searchInput').select();
    }
    // Escape untuk clear search
    if (e.key === 'Escape') {
        const search = document.getElementById('searchInput');
        if (document.activeElement === search && search.value.length > 0) {
            clearSearch();
        }
    }
});
</script>

</body>
</html>