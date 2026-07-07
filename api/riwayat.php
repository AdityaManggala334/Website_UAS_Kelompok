<?php
// api/riwayat.php
// ======================================================
// HALAMAN RIWAYAT GABUNGAN (Sensor + Transaksi + Hasil Panen)
// ======================================================

require_once 'koneksi.php';
require_once 'auth_helper.php';

// Menentukan tab aktif
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'sensor';

// ======================================================
// FILTER SENSOR (untuk tab sensor)
// ======================================================
$filter_sensor = isset($_GET['filter_sensor']) ? (int)$_GET['filter_sensor'] : 0;
$limit = 60;

// ======================================================
// AMBIL DATA RIWAYAT SENSOR DENGAN FILTER
// ======================================================
$data_sensor = [];
$query_sensor = "SELECT * FROM data_sensor";
if ($filter_sensor > 0) {
    $query_sensor .= " WHERE id_sensor = $filter_sensor";
}
$query_sensor .= " ORDER BY waktu_baca DESC LIMIT $limit";

$result_sensor = mysqli_query($conn, $query_sensor);
if (!$result_sensor) {
    $error_sensor = "Error query: " . mysqli_error($conn);
} else {
    $total_sensor = mysqli_num_rows($result_sensor);
}

// ======================================================
// AMBIL DAFTAR SENSOR UNTUK DROPDOWN FILTER
// ======================================================
$list_sensor = [];
$query_list = "SELECT DISTINCT id_sensor FROM data_sensor ORDER BY id_sensor ASC";
$result_list = mysqli_query($conn, $query_list);
if ($result_list) {
    while ($row = mysqli_fetch_assoc($result_list)) {
        $list_sensor[] = $row['id_sensor'];
    }
}

// ======================================================
// AMBIL DATA RIWAYAT TRANSAKSI
// ======================================================
$data_transaksi = [];
$query_transaksi = mysqli_query($conn, "SELECT * FROM peminjaman ORDER BY created_at DESC LIMIT 100");

// ======================================================
// AMBIL DATA RIWAYAT HASIL PANEN
// ======================================================
$data_panen = [];
$query_panen = mysqli_query($conn, "
    SELECT hp.*, u.username 
    FROM hasil_panen hp 
    LEFT JOIN users u ON hp.id_users = u.id_users 
    ORDER BY hp.tanggal_panen DESC 
    LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Riwayat Data — Ladusync</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
    :root {
        --tanah:        #0F1D16;
        --tanah-2:      #0A1410;
        --sawah:        #2F5233;
        --sawah-light:  #4A7050;
        --air:          #35648C;
        --air-light:    #5C87AD;
        --gabah:        #B9843A;
        --gabah-light:  #D3A868;
        --pop:          #B6FF5E;
        --pop-dim:      rgba(182,255,94,0.14);
        --kertas:       #F5F1E5;
        --kertas-2:     #ECE5D3;
        --lempung:      #8A7357;
        --ink:          #23301F;
        --kritis:       #9C4130;
        --sidebar-w:    248px;
        --sidebar-w-collapsed: 0px;
        --topbar-h:     64px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; -webkit-tap-highlight-color: transparent; }
    body { 
        font-family: 'Sora', sans-serif; 
        background: var(--kertas); 
        color: var(--ink);
        overflow-x: hidden;
        width: 100%;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    h1, h2, h3, .font-display { font-family: 'Fraunces', serif; }
    .font-mono-data { font-family: 'JetBrains Mono', monospace; }

    @keyframes livePulse { 0%,100%{opacity:1} 50%{opacity:0.25} }
    .live-dot { animation: livePulse 2.2s ease-in-out infinite; }

    /* Badge Status Sensor */
    .sp { display:inline-flex;align-items:center;gap:4px;padding:3px 12px;border-radius:4px;font-size:0.68rem;font-weight:700;font-family:'JetBrains Mono',monospace; }
    .sp-normal{background:#EEF4EA;color:#2F5233;border:1px solid #C9DABF;}
    .sp-rendah{background:#FBF1E1;color:#8A5A1E;border:1px solid #E9CE9E;}
    .sp-tinggi{background:#EAF1F6;color:#2C567D;border:1px solid #BDD4E4;}
    .sp-kritis{background:#FBEAE7;color:#8A2E1F;border:1px solid #E7B9AE;}
    
    /* Badge Status Transaksi */
    .sp-lunas{background:#EEF4EA;color:#2F5233;border:1px solid #C9DABF;}
    .sp-belum{background:#FBF1E1;color:#8A5A1E;border:1px solid #E9CE9E;}
    
    /* Badge Status Panen */
    .sp-rendeng{background:#FBF1E1;color:#8A5A1E;border:1px solid #E9CE9E;}
    .sp-gadu{background:#EEF4EA;color:#2F5233;border:1px solid #C9DABF;}

    .profil-wrap:hover .profil-dropdown { display: block; }
    .profil-dropdown {
        display: none; position: absolute; right: 0; top: 100%; margin-top: 8px;
        background: white; border-radius: 4px; min-width: 210px;
        box-shadow: 0 12px 34px rgba(20,32,25,0.20); z-index: 50; overflow: hidden;
        border: 1px solid rgba(138,115,87,0.18);
    }

    /* ============================================
       APP SHELL — SIDEBAR KIRI + TOPBAR KANAN ATAS
       ============================================ */
    .app-shell { display: flex; min-height: 100vh; width: 100%; flex: 1; }

    .sidebar {
        position: fixed;
        top: 0; left: 0;
        width: var(--sidebar-w);
        height: 100vh;
        background: linear-gradient(180deg, var(--tanah) 0%, var(--tanah-2) 100%);
        display: flex;
        flex-direction: column;
        z-index: 70;
        border-right: 1px solid rgba(182,255,94,0.08);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }

    .sidebar.collapsed { transform: translateX(-100%); width: 0; }
    .sidebar.open { transform: translateX(0); width: var(--sidebar-w); box-shadow: 20px 0 60px rgba(10,20,16,0.35); }

    .sidebar-logo {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 20px 20px 16px;
        flex-shrink: 0;
        border-bottom: 1px solid rgba(245,241,229,0.08);
    }

    .sidebar-logo .logo-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        min-width: 0;
    }

    .sidebar-close-btn {
        display: none;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: 1px solid rgba(245,241,229,0.12);
        background: rgba(245,241,229,0.06);
        color: rgba(245,241,229,0.5);
        cursor: pointer;
        transition: all 0.2s ease;
        flex-shrink: 0;
        padding: 0;
    }

    .sidebar-close-btn:hover {
        background: rgba(245,241,229,0.12);
        color: #fff;
        border-color: rgba(245,241,229,0.25);
    }

    .sidebar-toggle-hamburger {
        display: none;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 8px;
        border: 1px solid rgba(138,115,87,0.20);
        background: transparent;
        cursor: pointer;
        color: var(--ink);
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .sidebar-toggle-hamburger:hover {
        background: rgba(47,82,51,0.06);
        border-color: var(--sawah);
    }

    .sidebar-nav {
        flex: 1;
        overflow-y: auto;
        padding: 16px 12px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .sidebar-nav::-webkit-scrollbar {
        width: 4px;
    }
    .sidebar-nav::-webkit-scrollbar-track {
        background: rgba(245,241,229,0.05);
    }
    .sidebar-nav::-webkit-scrollbar-thumb {
        background: rgba(245,241,229,0.15);
        border-radius: 2px;
    }

    .sidebar-section-label {
        font-size: 0.62rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: rgba(245,241,229,0.30);
        padding: 14px 12px 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .nav-link {
        color: rgba(245,241,229,0.62);
        position: relative;
        font-size: 0.84rem;
        font-weight: 500;
        padding: 10px 12px;
        border-radius: 8px;
        transition: all 0.18s ease;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        white-space: nowrap;
        border-left: 2px solid transparent;
        min-height: 44px;
        cursor: pointer;
        background: none;
        border-right: none;
        width: 100%;
        text-align: left;
        font-family: inherit;
    }
    .nav-link:hover { color: #fff; background: rgba(245,241,229,0.06); }
    .nav-link.active { color: var(--pop); background: var(--pop-dim); border-left: 2px solid var(--pop); }
    .nav-link .nav-icon { width: 17px; height: 17px; flex-shrink: 0; }
    .nav-link .nav-text { font-size: 0.84rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nav-link-admin { background: rgba(211,168,104,0.12); color: #D3A868; border-left: 2px solid #D3A868; }
    .nav-link-admin:hover { background: rgba(211,168,104,0.22); color: #D3A868; }
    .nav-link-cs { color: #D3A868; border-left: 2px solid #D3A868; background: rgba(211,168,104,0.08); }
    .nav-link-cs:hover { background: rgba(211,168,104,0.18); color: #D3A868; }

    .sidebar-bottom {
        flex-shrink: 0;
        padding: 12px;
        border-top: 1px solid rgba(245,241,229,0.08);
    }
    .nav-link-logout { color: rgba(255,138,120,0.85); }
    .nav-link-logout:hover { color: #fff; background: rgba(156,65,48,0.35); }

    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(10,20,16,0.55);
        z-index: 65;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .sidebar-overlay.open { display: block; opacity: 1; }

    /* MAIN AREA */
    .main-area {
        margin-left: var(--sidebar-w);
        width: calc(100% - var(--sidebar-w));
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        flex: 1;
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .main-area.sidebar-collapsed { margin-left: 0; width: 100%; }

    .topbar {
        height: var(--topbar-h);
        flex-shrink: 0;
        background: rgba(245,241,229,0.92);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(138,115,87,0.16);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 0 20px;
        position: sticky;
        top: 0;
        z-index: 55;
    }

    .topbar-left {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .topbar-brand {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--lempung);
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .nav-right {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
        margin-left: auto;
    }

    .cs-topbar-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--sawah);
        background: rgba(47,82,51,0.08);
        border: 1px solid rgba(47,82,51,0.15);
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .cs-topbar-btn:hover {
        background: rgba(47,82,51,0.15);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(47,82,51,0.12);
    }
    .cs-topbar-btn svg {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
    }

    /* ===== TAB ===== */
    .tab-active {
        background: var(--sawah) !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(47,82,51,0.25);
    }
    .tab-inactive {
        background: #f1f5f9 !important;
        color: #475569 !important;
    }
    .tab-inactive:hover {
        background: #e2e8f0 !important;
    }

    .stat-card {
        background: white;
        border: 1px solid rgba(138,115,87,0.18);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        box-shadow: 0 1px 3px rgba(28,43,30,0.05), 0 8px 24px rgba(28,43,30,0.06);
    }
    .stat-card .stat-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: #8A7A66;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .stat-card .stat-value {
        font-size: 1.1rem;
        font-weight: 700;
        font-family: 'JetBrains Mono', monospace;
        color: var(--sawah);
    }

    .filter-select {
        background: white;
        border: 1px solid rgba(138,115,87,0.25);
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 0.78rem;
        font-weight: 500;
        color: var(--ink);
        outline: none;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 140px;
    }
    .filter-select:focus {
        border-color: var(--gabah);
        box-shadow: 0 0 0 3px rgba(185,132,58,0.15);
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .row-fade-in {
        animation: fadeIn 0.3s ease-out;
    }

    /* ===== TAB TRANSITION SMOOTH ===== */
    .tab-content {
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    .tab-content.fade-in {
        opacity: 1;
        transform: translateY(0);
    }
    .tab-content.fade-out {
        opacity: 0;
        transform: translateY(10px);
    }

    /* ===== MAIN CONTENT ===== */
    .main-content {
        flex: 1 0 auto;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px 16px 30px;
    }

    /* ===== TABLE STYLES ===== */
    .table-title {
        font-weight: 700;
        color: var(--ink);
        font-size: 0.9rem;
    }
    .table-header th {
        color: rgba(245,241,229,0.85) !important;
        font-size: 0.68rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        background: var(--tanah);
        border-bottom: 1px solid rgba(211,168,104,0.18);
        font-family: 'JetBrains Mono', monospace;
        padding: 10px 12px;
        text-align: left;
    }
    .table-row td {
        padding: 10px 12px;
        border-bottom: 1px solid rgba(138,115,87,0.08);
        font-size: 0.82rem;
        color: #3D3529;
    }
    .table-row:hover {
        background: rgba(47,82,51,0.04);
    }

    /* ============================================================ */
    /* FOOTER RINGKAS - SEPERTI INDEX.PHP                           */
    /* ============================================================ */
    .site-footer {
        flex-shrink: 0;
        background: var(--tanah);
        color: rgba(245,241,229,0.55);
        width: 100%;
        margin-top: auto;
        padding: 2rem 1.5rem 1.5rem;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid rgba(211,168,104,0.16);
    }

    @media (min-width: 640px) {
        .footer-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (min-width: 1024px) {
        .footer-grid {
            grid-template-columns: 1.2fr 1fr 1fr;
        }
        .site-footer {
            padding: 2.5rem 2rem 1.5rem;
        }
    }

    .footer-brand .logo {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .footer-brand .logo-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(185,132,58,0.16);
        border: 1px solid rgba(211,168,104,0.30);
        flex-shrink: 0;
    }

    .footer-brand .logo-text {
        font-family: 'Fraunces', serif;
        font-weight: 700;
        font-size: 1.1rem;
        color: white;
    }

    .footer-brand .logo-text span {
        color: var(--gabah-light);
    }

    .footer-brand .desc {
        font-size: 0.78rem;
        line-height: 1.6;
        color: rgba(245,241,229,0.45);
        max-width: 280px;
    }

    .footer-social {
        display: flex;
        gap: 8px;
        margin-top: 12px;
    }

    .footer-social a {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(245,241,229,0.06);
        border: 1px solid rgba(245,241,229,0.10);
        color: rgba(245,241,229,0.55);
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .footer-social a:hover {
        background: rgba(245,241,229,0.12);
        color: white;
        transform: translateY(-2px);
    }

    .footer-social a svg {
        width: 14px;
        height: 14px;
    }

    .footer-col .title {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--gabah-light);
        margin-bottom: 12px;
    }

    .footer-col ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .footer-col ul li a {
        color: rgba(245,241,229,0.5);
        text-decoration: none;
        font-size: 0.78rem;
        transition: color 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .footer-col ul li a:hover {
        color: white;
    }

    .footer-col .contact-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 0.78rem;
        color: rgba(245,241,229,0.5);
        margin-bottom: 6px;
    }

    .footer-col .contact-item svg {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
        margin-top: 2px;
        color: var(--gabah-light);
    }

    .footer-bottom {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding-top: 1.25rem;
        font-size: 0.65rem;
        color: rgba(245,241,229,0.3);
        text-align: center;
    }

    @media (min-width: 640px) {
        .footer-bottom {
            flex-direction: row;
            justify-content: space-between;
        }
    }

    .footer-bottom .links {
        display: flex;
        gap: 1.5rem;
    }

    .footer-bottom .links a {
        color: rgba(245,241,229,0.3);
        text-decoration: none;
        transition: color 0.2s;
    }

    .footer-bottom .links a:hover {
        color: rgba(245,241,229,0.7);
    }

    /* ===== RESPONSIVE SIDEBAR ===== */
    /* Desktop default (>= 1024px) */
    @media (min-width: 1024px) {
        .sidebar { 
            transform: translateX(0) !important; 
            width: var(--sidebar-w) !important;
        }
        .sidebar.collapsed { 
            transform: translateX(-100%) !important; 
            width: var(--sidebar-w) !important;
        }
        .sidebar:not(.collapsed) { 
            transform: translateX(0) !important; 
            width: var(--sidebar-w) !important;
        }
        .sidebar-overlay { display: none !important; }
        .sidebar-close-btn { display: flex !important; }
        .sidebar-toggle-hamburger { display: flex !important; }
        .main-area.sidebar-collapsed { margin-left: 0; width: 100%; }
        .main-area:not(.sidebar-collapsed) { margin-left: var(--sidebar-w); width: calc(100% - var(--sidebar-w)); }
        
        /* Stats grid desktop */
        .stats-grid { 
            display: grid !important; 
            grid-template-columns: repeat(4, 1fr) !important; 
            gap: 1rem !important; 
        }
        
        /* Table desktop */
        .table-container table { min-width: unset; width: 100%; }
    }

    /* Tablet (768px - 1023px) */
    @media (min-width: 768px) and (max-width: 1023px) {
        .sidebar { 
            transform: translateX(-100%); 
            width: var(--sidebar-w) !important;
        }
        .sidebar.open { 
            transform: translateX(0); 
            box-shadow: 20px 0 60px rgba(10,20,16,0.35);
        }
        .sidebar.collapsed { transform: translateX(-100%) !important; }
        .sidebar-overlay.open { display: block; opacity: 1; }
        .main-area { margin-left: 0; width: 100%; }
        .sidebar-close-btn { display: flex !important; }
        .sidebar-toggle-hamburger { display: flex !important; }
        .sidebar.collapsed .sidebar-close-btn { display: none !important; }
        .sidebar:not(.open) .sidebar-close-btn { display: none !important; }
        
        .stats-grid { 
            display: grid !important; 
            grid-template-columns: repeat(3, 1fr) !important; 
            gap: 0.75rem !important; 
        }
        
        .table-container table { min-width: 650px; }
        .topbar { padding: 0 16px; height: 60px; }
    }

    /* Mobile (< 768px) */
    @media (max-width: 767px) {
        .topbar { padding: 0 12px; height: 56px; }
        .topbar-brand .hidden-sm { display: none; }
        .table-container { 
            overflow-x: auto; 
            -webkit-overflow-scrolling: touch; 
            margin: 0 -4px;
            padding: 0 4px;
        }
        .table-container table { min-width: 600px; width: 100%; }
        
        .page-header h1 { font-size: 1.1rem; }
        .page-header p { font-size: 0.7rem; }
        
        .tab-wrapper { 
            overflow-x: auto; 
            -webkit-overflow-scrolling: touch;
            margin: 0 -4px;
            padding: 0 4px;
        }
        .tab-wrapper .flex { 
            flex-wrap: nowrap; 
            min-width: 360px;
            gap: 4px;
        }
        .tab-wrapper .flex a {
            font-size: 0.7rem !important;
            padding: 8px 12px !important;
            white-space: nowrap;
        }
        .tab-wrapper .flex a svg {
            width: 12px;
            height: 12px;
        }
        
        .stats-grid { 
            display: grid !important; 
            grid-template-columns: 1fr 1fr !important; 
            gap: 0.5rem !important; 
        }
        
        .main-content { padding: 12px 8px 20px; }
        .profil-name { display: none !important; }
        .sidebar-toggle-hamburger { width: 34px; height: 34px; }
        .cs-topbar-btn span { display: none; }
        .cs-topbar-btn { padding: 6px 10px; }
        .stat-card { padding: 0.5rem 0.75rem !important; }
        .stat-card .stat-value { font-size: 0.9rem !important; }
        .stat-card .stat-label { font-size: 0.55rem !important; }
        
        .site-footer { padding: 1.5rem 1rem 1rem; }
        .footer-grid { gap: 1.5rem; }
        .footer-brand .desc { max-width: 100%; }
        
        .table-row td { 
            font-size: 0.7rem !important; 
            padding: 6px 8px !important; 
        }
        .table-header th { 
            font-size: 0.6rem !important; 
            padding: 6px 8px !important; 
        }
        
        .filter-select { 
            width: 100%; 
            min-width: unset; 
            font-size: 0.7rem; 
            padding: 4px 8px;
        }
        
        .sidebar { 
            transform: translateX(-100%); 
            width: 280px !important;
        }
        .sidebar.open { 
            transform: translateX(0); 
            box-shadow: 20px 0 60px rgba(10,20,16,0.35);
        }
        .sidebar.collapsed { transform: translateX(-100%) !important; }
        .sidebar-overlay.open { display: block; opacity: 1; }
        .main-area { margin-left: 0; width: 100%; }
        .sidebar-close-btn { display: flex !important; }
        .sidebar-toggle-hamburger { display: flex !important; }
        .sidebar.collapsed .sidebar-close-btn { display: none !important; }
        .sidebar:not(.open) .sidebar-close-btn { display: none !important; }
    }

    /* Mobile kecil (< 480px) */
    @media (max-width: 479px) {
        .topbar { padding: 0 6px; height: 48px; }
        .page-header h1 { font-size: 0.95rem; }
        .page-header p { font-size: 0.55rem; }
        .stats-grid { 
            grid-template-columns: 1fr 1fr !important; 
            gap: 0.3rem !important; 
        }
        .main-content { padding: 6px 4px 12px; }
        .site-footer { padding: 1rem 0.5rem; }
        .footer-grid { gap: 1rem; }
        .footer-brand .logo-text { font-size: 0.85rem; }
        .footer-brand .desc { font-size: 0.65rem; }
        .footer-col .title { font-size: 0.55rem; }
        .footer-col ul li a { font-size: 0.65rem; }
        .footer-col .contact-item { font-size: 0.65rem; }
        .footer-bottom { font-size: 0.5rem; gap: 4px; }
        .footer-bottom .links { gap: 0.5rem; }
        .footer-social a { width: 26px; height: 26px; }
        .footer-social a svg { width: 11px; height: 11px; }
        
        .table-row td { 
            font-size: 0.55rem !important; 
            padding: 3px 4px !important; 
        }
        .table-header th { 
            font-size: 0.45rem !important; 
            padding: 3px 4px !important; 
        }
        .table-title { font-size: 0.65rem !important; }
        
        .tab-wrapper .flex { 
            min-width: 280px;
        }
        .tab-wrapper .flex a { 
            font-size: 0.55rem !important; 
            padding: 6px 8px !important; 
        }
        .tab-wrapper .flex a svg {
            width: 10px;
            height: 10px;
        }
        
        .stat-card { padding: 0.3rem 0.5rem !important; }
        .stat-card .stat-value { font-size: 0.7rem !important; }
        .stat-card .stat-label { font-size: 0.45rem !important; }
        
        .filter-select { 
            font-size: 0.6rem; 
            padding: 3px 6px;
        }
        
        .sidebar { width: 260px !important; }
    }

    .table-container { 
        overflow-x: auto; 
        -webkit-overflow-scrolling: touch; 
    }
    .max-w-6xl { max-width: 1200px; width: 100%; }

    .sidebar.collapsed .nav-text,
    .sidebar.collapsed .sidebar-section-label,
    .sidebar.collapsed .logo-text {
        opacity: 0;
        transition: opacity 0.15s ease;
    }

    .sidebar:not(.collapsed) .nav-text,
    .sidebar:not(.collapsed) .sidebar-section-label,
    .sidebar:not(.collapsed) .logo-text {
        opacity: 1;
        transition: opacity 0.15s ease 0.1s;
    }

    /* Smooth tab transition */
    .tab-content {
        display: block;
        transition: opacity 0.25s ease;
    }
    .tab-content.hidden {
        display: none;
    }
    </style>
</head>
<body>

<div class="app-shell">

<!-- ===== SIDEBAR KIRI ===== -->
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
    <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Tutup menu" title="Tutup menu">
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

    <a href="riwayat.php" class="nav-link active">
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

    <a href="data_lahan.php" class="nav-link">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
      </svg>
      <span class="nav-text">Hasil Panen</span>
    </a>

    <!-- ===== EDUKASI ===== -->
    <div class="sidebar-section-label">Edukasi</div>
    <a href="konten_edukasi.php" class="nav-link">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 6h16M4 12h16M4 18h10"/>
        <rect x="2" y="2" width="20" height="20" rx="2"/>
      </svg>
      <span class="nav-text">Konten Edukasi</span>
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

    <!-- ===== MENU CUSTOMER SERVICE DI SIDEBAR ===== -->
    <div class="sidebar-section-label">Bantuan</div>
    <a href="#" class="nav-link nav-link-cs" onclick="openCSModal(); return false;">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        <path d="M8 10h.01"/>
        <path d="M12 10h.01"/>
        <path d="M16 10h.01"/>
      </svg>
      <span class="nav-text">Customer Service</span>
    </a>
  </nav>

  <!-- ===== TOMBOL KELUAR ===== -->
  <div class="sidebar-bottom">
    <?php if ($is_logged_in): ?>
      <a href="logout.php" class="nav-link nav-link-logout">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span class="nav-text">Keluar</span>
      </a>
    <?php else: ?>
      <a href="login.php" class="nav-link" style="color:var(--pop);">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/></svg>
        <span class="nav-text">Masuk / Register</span>
      </a>
    <?php endif; ?>
  </div>
</aside>

<!-- ===== MAIN AREA: TOPBAR + KONTEN ===== -->
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
        Riwayat Sistem
      </span>
    </div>

    <!-- ===== KANAN ATAS: CS + PROFIL USER ===== -->
    <div class="nav-right">
      <a href="#" class="cs-topbar-btn" onclick="openCSModal(); return false;" title="Customer Service">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          <path d="M8 10h.01"/>
          <path d="M12 10h.01"/>
          <path d="M16 10h.01"/>
        </svg>
        <span>CS</span>
      </a>

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
            <a href="edit_profil.php" class="flex items-center gap-2 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition-colors no-underline">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 20h9"/>
                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
              </svg>
              Edit Profil
            </a>
            <a href="dashboard.php" class="flex items-center gap-2 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition-colors no-underline">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg> Dashboard
            </a>
            <a href="api/konten_edukasi.php" class="flex items-center gap-2 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition-colors no-underline">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 6h16M4 12h16M4 18h10"/>
                <rect x="2" y="2" width="20" height="20" rx="2"/>
              </svg>
              Konten Edukasi
            </a>
            <a href="#" class="flex items-center gap-2 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition-colors no-underline" onclick="openCSModal(); return false;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                <path d="M8 10h.01"/>
                <path d="M12 10h.01"/>
                <path d="M16 10h.01"/>
              </svg>
              Customer Service
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

<!-- ===== KONTEN UTAMA ===== -->
<main class="main-content">

    <!-- HEADER -->
    <div class="page-header mb-4 sm:mb-5">
        <h1 class="font-display text-2xl font-bold" style="color:var(--sawah);">📜 Riwayat Sistem</h1>
        <p class="text-sm text-slate-500 mt-1 flex items-center gap-1.5 flex-wrap">
            <span class="live-dot inline-block w-2 h-2 rounded-full" style="background:#D3A868;"></span>
            Rekaman historis sensor, transaksi, dan hasil panen · Update otomatis setiap 4 detik
        </p>
    </div>

    <!-- ===== TAB NAVIGASI ===== -->
    <div class="tab-wrapper mb-5">
        <div class="flex gap-1 p-1 rounded-xl" style="background:rgba(47,82,51,0.06);">
            <a href="?tab=sensor" 
               class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg text-sm font-semibold transition-all duration-200 tab-link <?= $tab === 'sensor' ? 'tab-active' : 'tab-inactive' ?>"
               data-tab="sensor">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v4M12 22v-4M4 12H2M22 12h-2M19.07 4.93l-2.83 2.83M6.34 17.66l-2.83 2.83M17.66 6.34l2.83-2.83M6.34 4.93l-2.83 2.83"/><circle cx="12" cy="12" r="3"/></svg>
                Riwayat Sensor
            </a>
            <a href="?tab=transaksi" 
               class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg text-sm font-semibold transition-all duration-200 tab-link <?= $tab === 'transaksi' ? 'tab-active' : 'tab-inactive' ?>"
               data-tab="transaksi">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                Riwayat Transaksi
            </a>
            <a href="?tab=panen" 
               class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg text-sm font-semibold transition-all duration-200 tab-link <?= $tab === 'panen' ? 'tab-active' : 'tab-inactive' ?>"
               data-tab="panen">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Riwayat Hasil Panen
            </a>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB 1: RIWAYAT SENSOR -->
    <!-- ============================================================ -->
    <div id="tab-sensor" class="tab-content <?= $tab === 'sensor' ? '' : 'hidden' ?>">
        
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5 stats-grid" id="stat-cards">
            <?php 
            $total_sensor_data = isset($total_sensor) ? $total_sensor : 0;
            $normal = 0; $kritis = 0; $rendah = 0; $tinggi = 0;
            
            if (isset($result_sensor) && $result_sensor) {
                mysqli_data_seek($result_sensor, 0);
                while($row = mysqli_fetch_assoc($result_sensor)) {
                    if($row['status'] == 'normal') $normal++;
                    elseif($row['status'] == 'kritis') $kritis++;
                    elseif($row['status'] == 'rendah') $rendah++;
                    elseif($row['status'] == 'tinggi') $tinggi++;
                }
                mysqli_data_seek($result_sensor, 0);
            }
            ?>
            <div class="stat-card"><div class="stat-label">Total Data</div><div class="stat-value" id="stat-total"><?= $total_sensor_data ?></div></div>
            <div class="stat-card"><div class="stat-label">Normal</div><div class="stat-value" style="color:#2F5233;" id="stat-normal"><?= $normal ?></div></div>
            <div class="stat-card"><div class="stat-label">Kritis</div><div class="stat-value" style="color:#9C4130;" id="stat-kritis"><?= $kritis ?></div></div>
            <div class="stat-card"><div class="stat-label">Update Terakhir</div><div class="stat-value" style="font-size:0.85rem;" id="stat-time"><?= date('H:i:s') ?></div></div>
        </div>

        <div class="bg-white rounded-xl border overflow-hidden" style="border-color:rgba(138,115,87,0.18);box-shadow:0 1px 3px rgba(28,43,30,0.05),0 8px 24px rgba(28,43,30,0.06);">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-4 sm:px-5 py-3 border-b" style="border-color:rgba(138,115,87,0.14);background:linear-gradient(135deg,#F5F1E5,#ECE5D3);">
                <span class="table-title">Log Data Sensor</span>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">Filter:</span>
                    <form method="GET" class="flex items-center gap-2" id="filter-form">
                        <input type="hidden" name="tab" value="sensor">
                        <select name="filter_sensor" class="filter-select" id="filter-sensor" onchange="this.form.submit()">
                            <option value="0" <?= $filter_sensor == 0 ? 'selected' : '' ?>>Semua Sensor</option>
                            <?php foreach ($list_sensor as $id): ?>
                            <option value="<?= $id ?>" <?= $filter_sensor == $id ? 'selected' : '' ?>>Sensor <?= $id ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($filter_sensor > 0): ?>
                        <a href="?tab=sensor" class="text-xs font-medium px-2 py-1 rounded" style="color:var(--kritis);background:rgba(156,65,48,0.08);">✕ Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="table-container">
                <table class="w-full border-collapse" style="min-width:650px;">
                    <thead class="table-header">
                        <tr>
                            <th>#</th>
                            <th>Waktu</th>
                            <th>Sensor</th>
                            <th style="text-align:right;">Debit</th>
                            <th style="text-align:right;">TMA</th>
                            <th style="text-align:right;">Suhu</th>
                            <th style="text-align:right;">Lembap</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="sensor-table-body">
                        <?php 
                        $no = 1;
                        if(isset($result_sensor) && $result_sensor && mysqli_num_rows($result_sensor) > 0):
                        while($row = mysqli_fetch_assoc($result_sensor)): 
                            $status_class = 'sp-' . $row['status'];
                            $status_label = ucfirst($row['status']);
                        ?>
                        <tr class="table-row row-fade-in">
                            <td style="color:#A79A85;font-weight:600;"><?= $no++ ?></td>
                            <td style="color:#6B5F4F;font-size:0.75rem;"><?= date('d M H:i', strtotime($row['waktu_baca'])) ?></td>
                            <td>
                                <span class="text-xs font-bold px-2 py-0.5 rounded" style="background:rgba(47,82,51,0.10);color:var(--sawah);border:1px solid rgba(47,82,51,0.18);font-family:'JetBrains Mono',monospace;">
                                    SNS-<?= $row['id_sensor'] ?>
                                </span>
                            </td>
                            <td style="text-align:right;font-weight:700;color:var(--ink);"><?= number_format($row['debit'], 1) ?></td>
                            <td style="text-align:right;color:var(--ink);"><?= $row['tma'] ?> cm</td>
                            <td style="text-align:right;color:var(--ink);"><?= number_format($row['suhu'], 1) ?>°C</td>
                            <td style="text-align:right;color:var(--ink);"><?= $row['lembap'] ?>%</td>
                            <td><span class="sp <?= $status_class ?>"><?= $status_label ?></span></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-10">
                                <div class="flex flex-col items-center gap-2">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                                    <span class="text-slate-400 text-sm font-medium">Belum ada data sensor.</span>
                                    <span class="text-slate-300 text-xs">Silakan tambahkan data sensor terlebih dahulu.</span>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-4 sm:px-5 py-2.5 border-t" style="border-color:rgba(138,115,87,0.14);background:#FAFAFA;">
                <span class="text-xs text-slate-500">Menampilkan <span class="font-bold text-slate-700" id="data-count"><?= isset($total_sensor) ? $total_sensor : 0 ?></span> data · <span id="auto-update-status" class="text-emerald-600 font-medium">🟢 Live</span></span>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB 2: RIWAYAT TRANSAKSI -->
    <!-- ============================================================ -->
    <div id="tab-transaksi" class="tab-content <?= $tab === 'transaksi' ? '' : 'hidden' ?>">
        
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5 stats-grid">
            <?php 
            $total_trx = mysqli_num_rows($query_transaksi);
            $lunas = 0; $belum = 0;
            while($row = mysqli_fetch_assoc($query_transaksi)) {
                if($row['status'] == 'lunas') $lunas++;
                else $belum++;
            }
            mysqli_data_seek($query_transaksi, 0);
            ?>
            <div class="stat-card"><div class="stat-label">Total Transaksi</div><div class="stat-value"><?= $total_trx ?></div></div>
            <div class="stat-card"><div class="stat-label">Lunas</div><div class="stat-value" style="color:#2F5233;"><?= $lunas ?></div></div>
            <div class="stat-card"><div class="stat-label">Belum Lunas</div><div class="stat-value" style="color:#B9843A;"><?= $belum ?></div></div>
            <div class="stat-card"><div class="stat-label">Total Pendapatan</div><div class="stat-value" style="font-size:0.85rem;color:var(--gabah);">
                <?php 
                $total_pendapatan = 0;
                mysqli_data_seek($query_transaksi, 0);
                while($row = mysqli_fetch_assoc($query_transaksi)) {
                    if($row['status'] == 'lunas') $total_pendapatan += $row['total_bayar'];
                }
                mysqli_data_seek($query_transaksi, 0);
                echo 'Rp ' . number_format($total_pendapatan, 0, ',', '.');
                ?>
            </div></div>
        </div>
        
        <div class="bg-white rounded-xl border overflow-hidden" style="border-color:rgba(138,115,87,0.18);box-shadow:0 1px 3px rgba(28,43,30,0.05),0 8px 24px rgba(28,43,30,0.06);">
            <div class="px-4 sm:px-5 py-3 border-b" style="border-color:rgba(138,115,87,0.14);background:linear-gradient(135deg,#F5F1E5,#ECE5D3);">
                <span class="table-title">Riwayat Transaksi</span>
            </div>
            <div class="table-container">
                <table class="w-full border-collapse" style="min-width:700px;">
                    <thead class="table-header">
                        <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>User</th>
                            <th>Alat</th>
                            <th style="text-align:right;">Durasi</th>
                            <th style="text-align:right;">Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if(mysqli_num_rows($query_transaksi) > 0):
                        while($row = mysqli_fetch_assoc($query_transaksi)): ?>
                        <tr class="table-row">
                            <td style="color:#A79A85;font-weight:600;"><?= $no++ ?></td>
                            <td style="color:#6B5F4F;font-size:0.75rem;"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                            <td style="font-weight:600;color:var(--ink);"><?= htmlspecialchars($row['username']) ?></td>
                            <td style="color:#4B4032;"><?= htmlspecialchars($row['nama_alat']) ?></td>
                            <td style="text-align:right;color:#4B4032;"><?= $row['durasi'] ?> hari</td>
                            <td style="text-align:right;font-weight:700;color:var(--gabah);">Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></td>
                            <td><span class="sp <?= $row['status'] == 'lunas' ? 'sp-lunas' : 'sp-belum' ?>"><?= ucfirst($row['status']) ?></span></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="7" class="text-center py-10 text-slate-400 text-sm">Belum ada transaksi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-4 sm:px-5 py-2.5 border-t" style="border-color:rgba(138,115,87,0.14);background:#FAFAFA;">
                <span class="text-xs text-slate-500">Menampilkan <span class="font-bold text-slate-700"><?= mysqli_num_rows($query_transaksi) ?></span> transaksi terakhir</span>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB 3: RIWAYAT HASIL PANEN -->
    <!-- ============================================================ -->
    <div id="tab-panen" class="tab-content <?= $tab === 'panen' ? '' : 'hidden' ?>">
        
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5 stats-grid">
            <?php 
            $total_panen = mysqli_num_rows($query_panen);
            $rendeng = 0; $gadu = 0;
            $total_hasil = 0;
            while($row = mysqli_fetch_assoc($query_panen)) {
                if($row['musim'] == 'rendeng') $rendeng++;
                else $gadu++;
                $total_hasil += $row['hasil_ton'];
            }
            mysqli_data_seek($query_panen, 0);
            ?>
            <div class="stat-card"><div class="stat-label">Total Panen</div><div class="stat-value"><?= $total_panen ?></div></div>
            <div class="stat-card"><div class="stat-label">Rendeng</div><div class="stat-value" style="color:#B9843A;"><?= $rendeng ?></div></div>
            <div class="stat-card"><div class="stat-label">Gadu</div><div class="stat-value" style="color:#2F5233;"><?= $gadu ?></div></div>
            <div class="stat-card"><div class="stat-label">Total Hasil</div><div class="stat-value" style="font-size:0.85rem;"><?= number_format($total_hasil, 1) ?> Ton</div></div>
        </div>
        
        <div class="bg-white rounded-xl border overflow-hidden" style="border-color:rgba(138,115,87,0.18);box-shadow:0 1px 3px rgba(28,43,30,0.05),0 8px 24px rgba(28,43,30,0.06);">
            <div class="px-4 sm:px-5 py-3 border-b" style="border-color:rgba(138,115,87,0.14);background:linear-gradient(135deg,#F5F1E5,#ECE5D3);">
                <span class="table-title">Riwayat Hasil Panen</span>
            </div>
            <div class="table-container">
                <table class="w-full border-collapse" style="min-width:700px;">
                    <thead class="table-header">
                        <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>Petani</th>
                            <th>Komoditas</th>
                            <th style="text-align:right;">Luas</th>
                            <th style="text-align:right;">Hasil</th>
                            <th>Musim</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if(mysqli_num_rows($query_panen) > 0):
                        while($row = mysqli_fetch_assoc($query_panen)): ?>
                        <tr class="table-row">
                            <td style="color:#A79A85;font-weight:600;"><?= $no++ ?></td>
                            <td style="color:#6B5F4F;font-size:0.75rem;"><?= date('d M Y', strtotime($row['tanggal_panen'])) ?></td>
                            <td style="font-weight:600;color:var(--ink);"><?= htmlspecialchars($row['username'] ?? 'Unknown') ?></td>
                            <td style="color:#4B4032;font-weight:500;"><?= htmlspecialchars($row['komoditas']) ?></td>
                            <td style="text-align:right;color:#4B4032;"><?= number_format($row['luas_lahan'], 2) ?> Ha</td>
                            <td style="text-align:right;font-weight:700;color:var(--gabah);"><?= number_format($row['hasil_ton'], 2) ?> Ton</td>
                            <td><span class="sp <?= $row['musim'] == 'rendeng' ? 'sp-rendeng' : 'sp-gadu' ?>"><?= ucfirst($row['musim']) ?></span></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="7" class="text-center py-10 text-slate-400 text-sm">Belum ada data panen.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-4 sm:px-5 py-2.5 border-t" style="border-color:rgba(138,115,87,0.14);background:#FAFAFA;">
                <span class="text-xs text-slate-500">Menampilkan <span class="font-bold text-slate-700"><?= mysqli_num_rows($query_panen) ?></span> data panen terakhir</span>
            </div>
        </div>
    </div>

</main>

<!-- ===== FOOTER RINGKAS ===== -->
<footer class="site-footer">
    <div class="footer-container">

        <div class="footer-grid">
            <!-- Kolom 1: Brand -->
            <div class="footer-brand">
                <div class="logo">
                    <div class="logo-icon">
                        <svg width="20" height="20" viewBox="0 0 44 44" fill="none">
                            <path d="M22 7C22 7 13 18 13 24C13 29.52 17.03 34 22 34C26.97 34 31 29.52 31 24C31 18 22 7 22 7Z" fill="#D3A868"/>
                            <line x1="18" y1="24" x2="26" y2="24" stroke="#1C2B1E" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span class="logo-text">Ladusync<span>.pro</span></span>
                </div>
                <p class="desc">Platform ekosistem digital agrikultur terpadu untuk petani modern.</p>
                <div class="footer-social">
                    <a href="#" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </a>
                    <a href="#" aria-label="YouTube">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.42a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.42 8.6.42 8.6.42s6.88 0 8.6-.42a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.33 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>
                    </a>
                    <a href="#" aria-label="Facebook">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.5 8.5 0 0 1-12.36 7.56L3 21l1.94-5.64A8.5 8.5 0 1 1 21 11.5z"/></svg>
                    </a>
                    <a href="#" aria-label="Twitter">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
                    </a>
                </div>
            </div>

            <!-- Kolom 2: Navigasi -->
            <div class="footer-col">
                <div class="title">Navigasi</div>
                <ul>
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="index.php#sistem-terintegrasi">Layanan</a></li>
                    <li><a href="peta.php">Peta Sensor</a></li>
                    <li><a href="daftar_alat.php">Sewa Alat</a></li>
                    <li><a href="konten_edukasi.php">Edukasi</a></li>
                    <li><a href="riwayat.php">Riwayat</a></li>
                </ul>
            </div>

            <!-- Kolom 3: Kontak -->
            <div class="footer-col">
                <div class="title">Kontak</div>
                <div class="contact-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span>UNS Surakarta, Jawa Tengah</span>
                </div>
                <div class="contact-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <span>(0271) 000-000</span>
                </div>
                <div class="contact-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <span>cs@ladusync.id</span>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="footer-bottom">
            <span>&copy; 2026 Ladusync — Universitas Sebelas Maret</span>
            <div class="links">
                <a href="#">Kebijakan Privasi</a>
                <a href="#">Syarat Layanan</a>
            </div>
        </div>

    </div>
</footer>

</div><!-- /.main-area -->
</div><!-- /.app-shell -->

<!-- ============================================ -->
<!-- CUSTOMER SERVICE MODAL                       -->
<!-- ============================================ -->
<div id="csModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:white;border-radius:16px;max-width:500px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="padding:20px 24px;border-bottom:1px solid rgba(138,115,87,0.15);display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--sawah-light),var(--sawah));display:flex;align-items:center;justify-content:center;color:white;font-size:18px;">💬</div>
                <div>
                    <h3 style="font-family:'Fraunces',serif;font-weight:700;font-size:1.1rem;margin:0;color:var(--ink);">Customer Service</h3>
                    <p style="font-size:0.7rem;color:#8A7A66;margin:0;">Kami siap membantu Anda</p>
                </div>
            </div>
            <button onclick="closeCSModal()" style="border:none;background:transparent;font-size:24px;cursor:pointer;color:#8A7A66;padding:4px 8px;">&times;</button>
        </div>
        <div style="padding:24px;">
            <div style="margin-bottom:20px;">
                <p style="font-size:0.85rem;font-weight:600;color:var(--ink);margin-bottom:12px;">Hubungi Kami</p>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:#f8f6f0;border-radius:8px;">
                        <span style="font-size:20px;">📞</span>
                        <div>
                            <div style="font-size:0.65rem;color:#8A7A66;font-weight:600;">TELEPON</div>
                            <div style="font-size:0.9rem;font-weight:600;color:var(--ink);">(0271) 000-000</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:#f8f6f0;border-radius:8px;">
                        <span style="font-size:20px;">📧</span>
                        <div>
                            <div style="font-size:0.65rem;color:#8A7A66;font-weight:600;">EMAIL</div>
                            <div style="font-size:0.9rem;font-weight:600;color:var(--ink);">cs@ladusync.id</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:#f8f6f0;border-radius:8px;">
                        <span style="font-size:20px;">💬</span>
                        <div>
                            <div style="font-size:0.65rem;color:#8A7A66;font-weight:600;">WHATSAPP</div>
                            <div style="font-size:0.9rem;font-weight:600;color:var(--ink);">+62 812-3456-7890</div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="background:linear-gradient(135deg,#f5f1e5,#ece5d3);border-radius:10px;padding:14px 18px;margin-bottom:20px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                    <span style="font-size:16px;">🕐</span>
                    <span style="font-size:0.8rem;font-weight:700;color:var(--sawah);">Jam Operasional</span>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:2px 16px;font-size:0.75rem;color:#4B4032;">
                    <span>Senin - Jumat</span>
                    <span style="font-weight:600;">08:00 - 17:00</span>
                    <span>Sabtu</span>
                    <span style="font-weight:600;">08:00 - 13:00</span>
                    <span>Minggu & Libur</span>
                    <span style="font-weight:600;color:var(--kritis);">Tutup</span>
                </div>
            </div>

            <button onclick="closeCSModal()" style="width:100%;padding:12px;border:none;border-radius:10px;background:linear-gradient(135deg,var(--sawah-light),var(--sawah));color:white;font-weight:700;font-size:0.9rem;cursor:pointer;transition:all 0.2s ease;">
                Tutup
            </button>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- SIDEBAR TOGGLE - JAVASCRIPT                  -->
<!-- ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainArea = document.getElementById('mainArea');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleHamburger = document.getElementById('sidebarToggleHamburger');
    const toggleCollapse = document.getElementById('sidebarToggleCollapse');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    
    const STORAGE_KEY = 'sidebar_collapsed_riwayat';

    // Load saved state or default
    function loadSidebarState() {
        const isDesktop = window.innerWidth >= 1024;
        const savedState = localStorage.getItem(STORAGE_KEY);
        
        if (savedState !== null) {
            if (savedState === 'true') {
                // Collapsed
                sidebar.classList.remove('open');
                sidebar.classList.add('collapsed');
                mainArea.classList.add('sidebar-collapsed');
            } else {
                // Open
                sidebar.classList.remove('collapsed');
                sidebar.classList.add('open');
                mainArea.classList.remove('sidebar-collapsed');
            }
        } else {
            // Default: open on desktop, collapsed on mobile
            if (isDesktop) {
                sidebar.classList.remove('collapsed');
                sidebar.classList.add('open');
                mainArea.classList.remove('sidebar-collapsed');
                localStorage.setItem(STORAGE_KEY, 'false');
            } else {
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('open');
                mainArea.classList.add('sidebar-collapsed');
                localStorage.setItem(STORAGE_KEY, 'true');
            }
        }
        
        // Ensure overlay is hidden on desktop
        if (isDesktop) {
            overlay.classList.remove('open');
            document.body.style.overflow = '';
        }
    }

    function updateMainArea() {
        const isDesktop = window.innerWidth >= 1024;
        const isCollapsed = sidebar.classList.contains('collapsed') || !sidebar.classList.contains('open');
        
        if (isDesktop && isCollapsed) {
            mainArea.classList.add('sidebar-collapsed');
        } else if (isDesktop && !isCollapsed) {
            mainArea.classList.remove('sidebar-collapsed');
        } else {
            // Mobile: always full width
            mainArea.classList.add('sidebar-collapsed');
        }
    }

    function openSidebar() {
        sidebar.classList.remove('collapsed');
        sidebar.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
        localStorage.setItem(STORAGE_KEY, 'false');
        updateMainArea();
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        sidebar.classList.add('collapsed');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
        localStorage.setItem(STORAGE_KEY, 'true');
        updateMainArea();
    }

    function toggleSidebar() {
        const isOpen = sidebar.classList.contains('open');
        const isDesktop = window.innerWidth >= 1024;
        
        if (isOpen) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    // Event Listeners
    if (toggleHamburger) {
        toggleHamburger.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }

    if (toggleCollapse) {
        toggleCollapse.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            closeSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            closeSidebar();
        });
    }

    // Handle resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            const isDesktop = window.innerWidth >= 1024;
            const savedState = localStorage.getItem(STORAGE_KEY);
            
            if (isDesktop) {
                // On desktop, respect saved state
                if (savedState === 'true') {
                    sidebar.classList.remove('open');
                    sidebar.classList.add('collapsed');
                    mainArea.classList.add('sidebar-collapsed');
                } else {
                    sidebar.classList.remove('collapsed');
                    sidebar.classList.add('open');
                    mainArea.classList.remove('sidebar-collapsed');
                }
                overlay.classList.remove('open');
                document.body.style.overflow = '';
            } else {
                // On mobile, always collapsed unless explicitly opened
                if (!sidebar.classList.contains('open')) {
                    sidebar.classList.add('collapsed');
                    mainArea.classList.add('sidebar-collapsed');
                    overlay.classList.remove('open');
                    document.body.style.overflow = '';
                }
            }
        }, 200);
    });

    // Keyboard shortcut: ESC to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });

    // Load state on page load
    loadSidebarState();
    
    // Ensure main area is correct
    updateMainArea();
});

// ===== CUSTOMER SERVICE MODAL =====
function openCSModal() {
    const modal = document.getElementById('csModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeCSModal() {
    const modal = document.getElementById('csModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Close modal on overlay click
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('csModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeCSModal();
            }
        });
    }
});

// ESC to close modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('csModal');
        if (modal && modal.style.display === 'flex') {
            closeCSModal();
        }
    }
});
</script>

<!-- ============================================ -->
<!-- AUTO UPDATE SENSOR - SINKRON DENGAN INDEX.PHP -->
<!-- ============================================ -->
<?php if ($tab === 'sensor'): ?>
<script>
// Data sensor yang sama dengan index.php
var dataSensor = [
  {id:"SNS-01", lokasi:"Saluran Induk Ngidul", debit:12.4, tma:42, suhu:26.8, lembap:68, status:"normal"},
  {id:"SNS-02", lokasi:"Percabangan Blok A",   debit:8.7,  tma:35, suhu:27.1, lembap:72, status:"normal"},
  {id:"SNS-03", lokasi:"Saluran Blok B",       debit:3.2,  tma:18, suhu:28.3, lembap:45, status:"rendah"},
  {id:"SNS-04", lokasi:"Bak Penampungan C1",   debit:18.9, tma:71, suhu:26.2, lembap:80, status:"tinggi"},
  {id:"SNS-05", lokasi:"Saluran Ngalor D",     debit:6.5,  tma:28, suhu:27.8, lembap:63, status:"normal"},
  {id:"SNS-06", lokasi:"Saluran Ngetan E",     debit:1.1,  tma:10, suhu:29.0, lembap:31, status:"kritis"},
  {id:"SNS-07", lokasi:"Saluran Petak 12",     debit:9.3,  tma:38, suhu:26.5, lembap:70, status:"normal"},
  {id:"SNS-08", lokasi:"Embung Ngulon",        debit:7.8,  tma:32, suhu:27.4, lembap:66, status:"normal"}
];

var dotColor = {normal:"#2F5233", rendah:"#B9843A", tinggi:"#35648C", kritis:"#9C4130"};
var labelSt  = {normal:"Normal",  rendah:"Rendah",  tinggi:"Tinggi",  kritis:"Kritis!"};
var spClass  = {normal:"sp-normal", rendah:"sp-rendah", tinggi:"sp-tinggi", kritis:"sp-kritis"};

function waktu() {
  var n = new Date();
  return String(n.getHours()).padStart(2,'0') + ':' + String(n.getMinutes()).padStart(2,'0') + ':' + String(n.getSeconds()).padStart(2,'0');
}

function pill(s) {
  return '<span class="sp ' + spClass[s] + '"><svg width="6" height="6" viewBox="0 0 6 6"><circle cx="3" cy="3" r="3" fill="' + dotColor[s] + '"/></svg>' + labelSt[s] + '</span>';
}

function renderTabelSensor() {
  var html = '';
  dataSensor.forEach(function(s, i) {
    html += '<tr class="table-row row-fade-in">';
    html += '<td style="color:#A79A85;font-weight:600;text-align:center;">' + (i+1) + '</td>';
    html += '<td style="color:#6B5F4F;font-size:0.75rem;text-align:center;">' + waktu() + '</td>';
    html += '<td style="text-align:center;"><span class="text-xs font-bold px-2 py-0.5 rounded" style="background:rgba(47,82,51,0.10);color:var(--sawah);border:1px solid rgba(47,82,51,0.18);font-family:\'JetBrains Mono\',monospace;">' + s.id + '</span></td>';
    html += '<td style="text-align:right;font-weight:700;color:var(--ink);">' + s.debit.toFixed(1) + '</td>';
    html += '<td style="text-align:right;color:var(--ink);">' + s.tma + ' cm</td>';
    html += '<td style="text-align:right;color:var(--ink);">' + s.suhu.toFixed(1) + '°C</td>';
    html += '<td style="text-align:right;color:var(--ink);">' + s.lembap + '%</td>';
    html += '<td style="text-align:center;">' + pill(s.status) + '</td>';
    html += '</tr>';
  });
  document.getElementById('sensor-table-body').innerHTML = html;
  hitungRingkasanSensor();
}

function hitungRingkasanSensor() {
  var td = 0, tt = 0, ts = 0, tl = 0, n = 0, k = 0, c = dataSensor.length;
  
  dataSensor.forEach(function(s) { 
    td += s.debit;
    tt += s.tma;
    ts += s.suhu;
    tl += s.lembap;
    if (s.status === 'normal') n++;
    else if (s.status === 'kritis') k++;
  });
  
  document.getElementById('stat-total').textContent = c;
  document.getElementById('stat-normal').textContent = n;
  document.getElementById('stat-kritis').textContent = k;
  document.getElementById('stat-time').textContent = waktu();
  document.getElementById('data-count').textContent = c;
}

function perbaruiSensorRiwayat() {
  dataSensor.forEach(function(s) {
    s.debit  = Math.max(0.5, s.debit + (Math.random() - 0.5) * 0.3);
    s.tma    = Math.max(5,   s.tma    + Math.round((Math.random() - 0.5) * 2));
    s.lembap = Math.min(100, Math.max(10, s.lembap + Math.round((Math.random() - 0.5) * 2)));
    if      (s.tma < 15) s.status = 'kritis';
    else if (s.tma < 25) s.status = 'rendah';
    else if (s.tma > 65) s.status = 'tinggi';
    else                  s.status = 'normal';
  });
  renderTabelSensor();
}

// Render awal
renderTabelSensor();

// Update setiap 4 detik (sinkron dengan index.php)
setInterval(perbaruiSensorRiwayat, 4000);
</script>
<?php endif; ?>

</body>
</html>