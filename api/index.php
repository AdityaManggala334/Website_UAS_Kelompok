<?php
// KONFIGURASI AWAL DAN INISIALISASI
ob_start();  
error_reporting(E_ALL);  
ini_set('display_errors', '1');

// Memanggil konfigurasi sesi global Ladusync dari subfolder api
require_once __DIR__ . '/koneksi.php'; 
require_once __DIR__ . '/auth_helper.php';

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;

if ($is_logged_in) {
    $namaDepan   = $_SESSION['nama_depan'] ?? 'User';
    $namaBelakang = $_SESSION['nama_belakang'] ?? '';
    $namaLengkap = trim($namaDepan . ' ' . $namaBelakang) ?: $namaDepan;
    $role        = $_SESSION['role'] ?? 'guest';
    $bio         = $_SESSION['bio'] ?? '';
} else {
    $namaDepan   = 'Guest';
    $namaLengkap = 'Pengunjung Umum';
    $role        = 'guest';
    $bio         = '';
}

$pesan_laporan = $_SESSION['pesan_laporan'] ?? '';
$pesan_warna   = $_SESSION['pesan_warna'] ?? '';
unset($_SESSION['pesan_laporan'], $_SESSION['pesan_warna']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">                    
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">  
    <title>Ladusync — Solusi Pertanian Digital Terintegrasi</title>

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
    }
    h1, h2, h3, .font-display { font-family: 'Fraunces', serif; }
    .font-mono-data { font-family: 'JetBrains Mono', monospace; }

    @keyframes livePulse { 0%,100%{opacity:1} 50%{opacity:0.25} }
    .live-dot { animation: livePulse 2.2s ease-in-out infinite; }

    .status-pill {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 4px; font-size: 0.68rem;
        font-weight: 700; white-space: nowrap; font-family: 'JetBrains Mono', monospace;
        letter-spacing: 0.02em;
    }
    .sp-normal { background:#EEF4EA; color:#2F5233; border:1px solid #C9DABF; }
    .sp-rendah { background:#FBF1E1; color:#8A5A1E; border:1px solid #E9CE9E; }
    .sp-tinggi { background:#EAF1F6; color:#2C567D; border:1px solid #BDD4E4; }
    .sp-kritis { background:#FBEAE7; color:#8A2E1F; border:1px solid #E7B9AE; }

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
    .app-shell { display: flex; min-height: 100vh; width: 100%; }

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

    /* State collapsed untuk desktop */
    .sidebar.collapsed {
        transform: translateX(-100%);
        width: 0;
    }

    /* State open untuk mobile */
    .sidebar.open {
        transform: translateX(0);
        width: var(--sidebar-w);
        box-shadow: 20px 0 60px rgba(10,20,16,0.35);
    }

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

    /* Tombol toggle di topbar (hamburger) */
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
    .nav-link:hover {
        color: #fff;
        background: rgba(245,241,229,0.06);
    }
    .nav-link.active {
        color: var(--pop);
        background: var(--pop-dim);
        border-left: 2px solid var(--pop);
    }
    .nav-link .nav-icon {
        width: 17px;
        height: 17px;
        flex-shrink: 0;
    }
    .nav-link .nav-text {
        font-size: 0.84rem;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .nav-link-admin {
        background: rgba(211,168,104,0.12);
        color: #D3A868;
        border-left: 2px solid #D3A868;
    }
    .nav-link-admin:hover {
        background: rgba(211,168,104,0.22);
        color: #D3A868;
    }
    .nav-link-cs {
        color: #D3A868;
        border-left: 2px solid #D3A868;
        background: rgba(211,168,104,0.08);
    }
    .nav-link-cs:hover {
        background: rgba(211,168,104,0.18);
        color: #D3A868;
    }

    .sidebar-bottom {
        flex-shrink: 0;
        padding: 12px;
        border-top: 1px solid rgba(245,241,229,0.08);
    }
    .nav-link-logout {
        color: rgba(255,138,120,0.85);
    }
    .nav-link-logout:hover {
        color: #fff;
        background: rgba(156,65,48,0.35);
    }

    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(10,20,16,0.55);
        z-index: 65;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .sidebar-overlay.open {
        display: block;
        opacity: 1;
    }

    /* MAIN AREA (kanan dari sidebar) */
    .main-area {
        margin-left: var(--sidebar-w);
        width: calc(100% - var(--sidebar-w));
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .main-area.sidebar-collapsed {
        margin-left: 0;
        width: 100%;
    }

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

    /* Tabel sensor */
    #tabel-sensor th, #tabel-sensor td { 
        padding: 10px 12px; 
        white-space: nowrap; 
        text-align: center; 
    }
    #tabel-sensor th {
        font-size: 0.66rem;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: rgba(245,241,229,0.55);
        background: var(--tanah);
        border-bottom: 1px solid rgba(211,168,104,0.18);
        font-weight: 700;
        font-family: 'JetBrains Mono', monospace;
        text-align: center;
    }
    #tabel-sensor td {
        font-size: 0.82rem;
        border-bottom: 1px solid rgba(138,115,87,0.12);
        color: #4B4032;
        font-family: 'JetBrains Mono', monospace;
        text-align: center;
    }
    #tabel-sensor tbody tr:hover { background: rgba(47,82,51,0.05); }
    #tabel-sensor td:nth-child(1) { color: #A79A85; width: 40px; }
    #tabel-sensor td:nth-child(2) { font-weight: 700; color: var(--sawah); }
    #tabel-sensor td:nth-child(3) { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink); font-weight: 500; }

    .gauge-tick text { font-family: 'JetBrains Mono', monospace; }

    /* Forum Bubble - Responsive */
    .forum-bubble {
        position: fixed;
        bottom: 28px;
        right: 28px;
        z-index: 999;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--sawah), var(--sawah-light));
        color: white;
        box-shadow: 0 6px 24px rgba(47, 82, 51, 0.35);
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        text-decoration: none;
        border: none;
    }
    .forum-bubble:hover {
        transform: scale(1.08) translateY(-3px);
        box-shadow: 0 10px 32px rgba(47, 82, 51, 0.45);
    }
    .forum-bubble:active { transform: scale(0.95); }
    .forum-bubble .bubble-icon {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .forum-bubble .bubble-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: var(--kritis);
        color: white;
        font-size: 0.6rem;
        font-weight: 700;
        font-family: 'JetBrains Mono', monospace;
        min-width: 22px;
        height: 22px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 6px;
        border: 2px solid var(--kertas);
        box-shadow: 0 2px 8px rgba(156, 65, 48, 0.3);
    }
    .forum-bubble .bubble-tooltip {
        position: absolute;
        right: calc(100% + 14px);
        top: 50%;
        transform: translateY(-50%);
        background: rgba(28, 43, 30, 0.92);
        backdrop-filter: blur(8px);
        color: rgba(245, 241, 229, 0.9);
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.72rem;
        font-weight: 500;
        padding: 6px 14px;
        border-radius: 6px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: all 0.3s ease;
        border: 1px solid rgba(211, 168, 104, 0.15);
    }
    .forum-bubble:hover .bubble-tooltip { opacity: 1; }

    /* ============================================
       RESPONSIVE - DESKTOP FIRST
       ============================================ */

    /* Desktop (993px ke atas) */
    @media (min-width: 993px) {
        .sidebar {
            transform: translateX(0) !important;
        }
        .sidebar.collapsed {
            transform: translateX(0) !important;
            width: 0 !important;
            border-right: none;
        }
        .sidebar:not(.collapsed) {
            width: var(--sidebar-w) !important;
        }
        .sidebar-overlay {
            display: none !important;
        }
        .sidebar-close-btn {
            display: flex !important;
        }
        .sidebar-toggle-hamburger {
            display: flex !important;
        }
        .main-area.sidebar-collapsed {
            margin-left: 0;
            width: 100%;
        }
        .main-area:not(.sidebar-collapsed) {
            margin-left: var(--sidebar-w);
            width: calc(100% - var(--sidebar-w));
        }
        .content-container {
            padding: 2rem 2rem 1.5rem;
        }
        .hero-section {
            min-height: 350px !important;
            padding: 2.5rem 3rem !important;
        }
        .hero-title {
            font-size: 2.8rem !important;
        }
        .hero-gauge {
            display: flex !important;
        }
        .kpi-grid {
            grid-template-columns: repeat(5, 1fr) !important;
        }
        .bento-grid {
            grid-template-columns: repeat(4, 1fr) !important;
        }
        .card-sistem {
            grid-template-columns: repeat(3, 1fr) !important;
        }
        .footer-grid {
            grid-template-columns: repeat(4, 1fr) !important;
        }
    }

    /* Tablet (769px - 992px) */
    @media (max-width: 992px) {
        .sidebar {
            transform: translateX(-100%);
            width: var(--sidebar-w) !important;
        }
        .sidebar.open {
            transform: translateX(0);
            box-shadow: 20px 0 60px rgba(10,20,16,0.35);
        }
        .sidebar.collapsed {
            transform: translateX(-100%) !important;
        }
        .sidebar-overlay.open {
            display: block;
            opacity: 1;
        }
        .main-area {
            margin-left: 0;
            width: 100%;
        }
        .sidebar-close-btn {
            display: flex !important;
        }
        .sidebar-toggle-hamburger {
            display: flex !important;
        }
        .sidebar.collapsed .sidebar-close-btn,
        .sidebar:not(.open) .sidebar-close-btn {
            display: none !important;
        }
        
        .content-container {
            padding: 1.5rem 1.25rem 1rem;
        }
        .hero-section {
            min-height: 280px !important;
            padding: 2rem 1.5rem !important;
        }
        .hero-title {
            font-size: 2rem !important;
        }
        .hero-gauge {
            display: none !important;
        }
        .kpi-grid {
            grid-template-columns: repeat(3, 1fr) !important;
        }
        .bento-grid {
            grid-template-columns: repeat(3, 1fr) !important;
        }
        .card-sistem {
            grid-template-columns: repeat(2, 1fr) !important;
        }
        .footer-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
        .topbar {
            padding: 0 16px !important;
            height: 58px !important;
        }
        .topbar-brand .live-dot {
            display: none !important;
        }
        .topbar-brand .text {
            font-size: 0.7rem !important;
        }
        .profil-name {
            display: none !important;
        }
        .cs-topbar-btn span {
            display: none !important;
        }
        .cs-topbar-btn {
            padding: 6px 10px !important;
        }
    }

    /* Mobile Large (481px - 768px) */
    @media (max-width: 768px) {
        .topbar { 
            padding: 0 12px !important; 
            height: 54px !important; 
        }
        .topbar-brand.hidden-sm { 
            display: none !important; 
        }
        .sidebar-toggle-hamburger { 
            width: 34px !important; 
            height: 34px !important; 
        }
        .sidebar-toggle-hamburger svg {
            width: 18px !important;
            height: 18px !important;
        }
        
        .content-container {
            padding: 1rem 0.75rem 0.75rem !important;
        }
        
        .hero-section {
            min-height: 200px !important;
            padding: 1.25rem 1rem !important;
            border-radius: 12px !important;
        }
        .hero-title {
            font-size: 1.5rem !important;
        }
        .hero-title br {
            display: none !important;
        }
        .hero-desc {
            font-size: 0.7rem !important;
            margin-bottom: 1rem !important;
        }
        .hero-buttons {
            flex-direction: column !important;
            gap: 8px !important;
        }
        .hero-buttons a {
            width: 100% !important;
            justify-content: center !important;
            padding: 10px 16px !important;
            font-size: 0.75rem !important;
        }
        .hero-stats {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 6px !important;
            margin-top: 12px !important;
        }
        .hero-stats .stat-box {
            padding: 8px 12px !important;
        }
        .hero-stats .stat-box .number {
            font-size: 1rem !important;
        }
        .hero-stats .stat-box .label {
            font-size: 0.55rem !important;
        }
        .hero-gauge {
            display: none !important;
        }

        .kpi-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 8px !important;
        }
        .kpi-card {
            padding: 10px 12px !important;
        }
        .kpi-card .kpi-num {
            font-size: 1.1rem !important;
        }
        .kpi-card .kpi-label {
            font-size: 0.6rem !important;
        }
        .kpi-card .kpi-sub {
            font-size: 0.55rem !important;
        }
        .kpi-card .kpi-icon {
            width: 28px !important;
            height: 28px !important;
        }
        .kpi-card .kpi-icon svg {
            width: 14px !important;
            height: 14px !important;
        }

        .bento-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 8px !important;
        }
        .sensor-tile {
            padding: 10px !important;
        }
        .sensor-tile .tile-id {
            font-size: 0.5rem !important;
        }
        .sensor-tile .tile-loc {
            font-size: 0.6rem !important;
        }
        .sensor-tile .tile-stats {
            grid-template-columns: 1fr 1fr !important;
            gap: 4px !important;
        }
        .sensor-tile .tile-stat {
            padding: 4px !important;
        }
        .sensor-tile .tile-stat-val {
            font-size: 0.6rem !important;
        }
        .sensor-tile .tile-stat-lbl {
            font-size: 0.5rem !important;
        }
        .sensor-tile .status-pill {
            font-size: 0.55rem !important;
            padding: 2px 8px !important;
        }

        .card-sistem {
            grid-template-columns: 1fr !important;
            gap: 12px !important;
        }
        .card-sistem .card-item {
            padding: 16px !important;
        }
        .card-sistem .card-item h3 {
            font-size: 0.95rem !important;
        }
        .card-sistem .card-item p {
            font-size: 0.7rem !important;
        }
        .card-sistem .card-item .icon-box {
            width: 36px !important;
            height: 36px !important;
        }
        .card-sistem .card-item .icon-box svg {
            width: 16px !important;
            height: 16px !important;
        }

        .tentang-grid {
            grid-template-columns: 1fr !important;
            gap: 12px !important;
        }

        .monitoring-table table {
            min-width: 500px !important;
        }
        .monitoring-table th,
        .monitoring-table td {
            padding: 6px 8px !important;
            font-size: 0.65rem !important;
        }
        .monitoring-stats {
            grid-template-columns: repeat(3, 1fr) !important;
        }
        .monitoring-stats .stat-item {
            padding: 6px 8px !important;
        }
        .monitoring-stats .stat-item .value {
            font-size: 0.85rem !important;
        }
        .monitoring-stats .stat-item .label {
            font-size: 0.5rem !important;
        }

        .footer-grid {
            grid-template-columns: 1fr 1fr !important;
            gap: 16px !important;
            padding-bottom: 16px !important;
        }
        .footer-grid .footer-col {
            text-align: left !important;
        }
        .footer-grid .footer-col .title {
            font-size: 0.65rem !important;
        }
        .footer-grid .footer-col ul li {
            font-size: 0.6rem !important;
        }
        .footer-bottom {
            flex-direction: column !important;
            text-align: center !important;
            gap: 6px !important;
            font-size: 0.55rem !important;
        }

        .forum-bubble {
            bottom: 16px !important;
            right: 16px !important;
            width: 50px !important;
            height: 50px !important;
        }
        .forum-bubble .bubble-icon {
            width: 22px !important;
            height: 22px !important;
        }
        .forum-bubble .bubble-badge {
            min-width: 18px !important;
            height: 18px !important;
            font-size: 0.5rem !important;
            top: -2px !important;
            right: -2px !important;
        }
        .forum-bubble .bubble-tooltip {
            display: none !important;
        }

        .profil-dropdown {
            min-width: 180px !important;
            right: -10px !important;
        }
        .profil-dropdown a {
            font-size: 0.7rem !important;
            padding: 10px 14px !important;
        }
        .profil-dropdown .header {
            padding: 10px 14px !important;
        }
        .profil-dropdown .header .name {
            font-size: 0.75rem !important;
        }
        .profil-dropdown .header .role {
            font-size: 0.6rem !important;
        }

        .laporan-form {
            padding: 12px !important;
        }
        .laporan-form .form-grid {
            grid-template-columns: 1fr !important;
            gap: 8px !important;
        }
        .laporan-form .form-grid label {
            font-size: 0.6rem !important;
        }
        .laporan-form .form-grid input,
        .laporan-form .form-grid select {
            padding: 8px 10px !important;
            font-size: 0.7rem !important;
        }
        .laporan-form .btn-submit {
            width: 100% !important;
            justify-content: center !important;
        }
    }

    /* Mobile Small (max 480px) */
    @media (max-width: 480px) {
        .topbar { 
            padding: 0 8px !important; 
            height: 48px !important; 
        }
        .sidebar { 
            width: 280px !important; 
        }
        .sidebar-logo {
            padding: 14px 16px !important;
        }
        .sidebar-logo .logo-text {
            font-size: 0.9rem !important;
        }
        .sidebar-logo .logo-icon {
            width: 32px !important;
            height: 32px !important;
        }
        .sidebar-nav {
            padding: 10px 8px !important;
        }
        .nav-link {
            padding: 8px 10px !important;
            font-size: 0.75rem !important;
            min-height: 36px !important;
        }
        .nav-link .nav-icon {
            width: 14px !important;
            height: 14px !important;
        }
        .nav-link .nav-text {
            font-size: 0.75rem !important;
        }
        .sidebar-section-label {
            font-size: 0.5rem !important;
            padding: 10px 8px 4px !important;
        }

        .content-container {
            padding: 0.5rem 0.5rem 0.5rem !important;
        }

        .hero-section {
            min-height: 160px !important;
            padding: 1rem 0.75rem !important;
            border-radius: 8px !important;
        }
        .hero-title {
            font-size: 1.2rem !important;
        }
        .hero-desc {
            font-size: 0.6rem !important;
        }
        .hero-buttons a {
            font-size: 0.65rem !important;
            padding: 8px 12px !important;
        }
        .hero-stats {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 4px !important;
        }
        .hero-stats .stat-box {
            padding: 4px 8px !important;
        }
        .hero-stats .stat-box .number {
            font-size: 0.8rem !important;
        }
        .hero-stats .stat-box .label {
            font-size: 0.5rem !important;
        }

        .kpi-grid {
            grid-template-columns: 1fr 1fr !important;
            gap: 4px !important;
        }
        .kpi-card {
            padding: 8px 10px !important;
            border-radius: 10px !important;
        }
        .kpi-card .kpi-num {
            font-size: 0.9rem !important;
        }
        .kpi-card .kpi-label {
            font-size: 0.55rem !important;
        }
        .kpi-card .kpi-sub {
            font-size: 0.5rem !important;
        }
        .kpi-card .kpi-icon {
            width: 24px !important;
            height: 24px !important;
        }
        .kpi-card .kpi-icon svg {
            width: 12px !important;
            height: 12px !important;
        }

        .bento-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 4px !important;
        }

        .section-title {
            font-size: 0.8rem !important;
        }
        .section-sub {
            font-size: 0.55rem !important;
        }

        .laporan-form {
            padding: 8px !important;
        }
        .laporan-form .form-grid input,
        .laporan-form .form-grid select {
            padding: 6px 8px !important;
            font-size: 0.6rem !important;
        }
        .laporan-form .btn-submit {
            padding: 8px 12px !important;
            font-size: 0.65rem !important;
        }

        .monitoring-stats {
            grid-template-columns: repeat(3, 1fr) !important;
        }
        .monitoring-stats .stat-item .value {
            font-size: 0.7rem !important;
        }
        .monitoring-stats .stat-item .label {
            font-size: 0.45rem !important;
        }

        .footer-grid {
            grid-template-columns: 1fr !important;
            gap: 12px !important;
        }
        .footer-grid .footer-col .title {
            font-size: 0.6rem !important;
        }
        .footer-grid .footer-col ul li {
            font-size: 0.55rem !important;
        }

        .forum-bubble {
            bottom: 12px !important;
            right: 12px !important;
            width: 44px !important;
            height: 44px !important;
        }
        .forum-bubble .bubble-icon {
            width: 18px !important;
            height: 18px !important;
        }
        .forum-bubble .bubble-badge {
            min-width: 16px !important;
            height: 16px !important;
            font-size: 0.45rem !important;
            top: -2px !important;
            right: -2px !important;
        }

        .profil-wrap button {
            padding: 2px 6px !important;
        }
        .profil-wrap button .w-7 {
            width: 24px !important;
            height: 24px !important;
            font-size: 0.5rem !important;
        }
        .profil-wrap button svg {
            width: 8px !important;
            height: 8px !important;
        }

        .cs-topbar-btn {
            padding: 4px 6px !important;
        }
        .cs-topbar-btn svg {
            width: 14px !important;
            height: 14px !important;
        }
    }

    /* Utility Classes */
    .content-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 2rem 1.5rem;
        width: 100%;
    }

    .kpi-grid {
        display: grid;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .kpi-card {
        background: white;
        border-radius: 14px;
        padding: 1rem 1.2rem;
        border: 1px solid rgba(138,115,87,0.12);
        box-shadow: 0 1px 3px rgba(28,43,30,0.05), 0 8px 24px rgba(28,43,30,0.06);
        display: flex;
        align-items: center;
        gap: 12px;
        transition: transform 0.2s ease;
    }
    .kpi-card:hover { transform: translateY(-2px); }
    .kpi-card .kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .kpi-card .kpi-info { flex: 1; min-width: 0; }
    .kpi-card .kpi-num { 
        font-size: 1.3rem; 
        font-weight: 800; 
        color: var(--ink); 
        letter-spacing: -0.03em; 
        line-height: 1.1; 
    }
    .kpi-card .kpi-label { 
        font-size: 0.65rem; 
        font-weight: 600; 
        color: #4B7563; 
        margin-top: 1px; 
    }
    .kpi-card .kpi-sub { 
        font-size: 0.6rem; 
        color: #94A3B8; 
    }

    .bento-grid {
        display: grid;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .sensor-tile {
        background: white;
        border-radius: 14px;
        padding: 1rem;
        border: 1px solid rgba(138,115,87,0.12);
        box-shadow: 0 1px 3px rgba(28,43,30,0.05);
        position: relative;
        overflow: hidden;
        transition: transform 0.2s;
    }
    .sensor-tile:hover { transform: translateY(-2px); }
    .sensor-tile::before { 
        content: ''; 
        position: absolute; 
        top: 0; 
        left: 0; 
        right: 0; 
        height: 3px; 
    }
    .tile-normal::before { background: linear-gradient(90deg, #10B981, #34D399); }
    .tile-rendah::before { background: linear-gradient(90deg, #F97316, #FDBA74); }
    .tile-tinggi::before { background: linear-gradient(90deg, #3B82F6, #93C5FD); }
    .tile-kritis::before { background: linear-gradient(90deg, #EF4444, #FCA5A5); }
    .sensor-tile .tile-id { 
        font-size: 0.6rem; 
        font-weight: 700; 
        letter-spacing: 0.06em; 
        text-transform: uppercase; 
        color: #94A3B8; 
        margin-bottom: 4px; 
    }
    .sensor-tile .tile-loc { 
        font-size: 0.7rem; 
        font-weight: 600; 
        color: var(--ink); 
        margin-bottom: 8px; 
        line-height: 1.3; 
    }
    .sensor-tile .tile-stats { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 6px; 
    }
    .sensor-tile .tile-stat { 
        background: rgba(6,78,59,0.04); 
        border-radius: 6px; 
        padding: 5px 8px; 
    }
    .sensor-tile .tile-stat-val { 
        font-size: 0.75rem; 
        font-weight: 700; 
        color: var(--ink); 
    }
    .sensor-tile .tile-stat-lbl { 
        font-size: 0.55rem; 
        color: #94A3B8; 
        font-weight: 500; 
        margin-top: 1px; 
    }

    .card-sistem {
        display: grid;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .section-title {
        font-family: 'Fraunces', serif;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--sawah);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title .bar {
        width: 4px;
        height: 18px;
        border-radius: 2px;
        background: var(--gabah);
        display: inline-block;
    }
    .section-sub {
        font-size: 0.7rem;
        color: #94A3B8;
        margin-top: 2px;
        margin-bottom: 1rem;
    }

    .monitoring-table { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .monitoring-table table { min-width: 600px; width: 100%; border-collapse: collapse; }

    .monitoring-stats {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        border-top: 1px solid rgba(138,115,87,0.14);
    }
    .monitoring-stats .stat-item {
        padding: 10px 8px;
        text-align: center;
        border-right: 1px solid rgba(138,115,87,0.14);
    }
    .monitoring-stats .stat-item:last-child { border-right: none; }
    .monitoring-stats .stat-item .value {
        font-size: 0.95rem;
        font-weight: 700;
        font-family: 'JetBrains Mono', monospace;
        color: var(--ink);
    }
    .monitoring-stats .stat-item .label {
        font-size: 0.6rem;
        color: #94A3B8;
    }

    /* ===== RESPONSIVE OVERRIDE UNTUK MONITORING STATS ===== */
    @media (max-width: 768px) {
        .monitoring-stats {
            grid-template-columns: repeat(3, 1fr) !important;
        }
        .monitoring-stats .stat-item:nth-child(4),
        .monitoring-stats .stat-item:nth-child(5) {
            border-right: none !important;
        }
    }

    @media (max-width: 480px) {
        .monitoring-stats {
            grid-template-columns: repeat(3, 1fr) !important;
        }
        .monitoring-stats .stat-item {
            padding: 6px 4px !important;
        }
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

    <a href="index.php" class="nav-link active">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7" rx="1"/>
        <rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/>
        <rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      <span class="nav-text">Beranda</span>
    </a>

    <a href="#sistem-terintegrasi" class="nav-link">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/>
      </svg>
      <span class="nav-text">Layanan</span>
    </a>

    <a href="#monitoring" class="nav-link">
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
        Portal Ekosistem Ladusync
      </span>
    </div>

    <!-- ===== KANAN ATAS: PROFIL USER + CUSTOMER SERVICE ===== -->
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
            <?php if (!empty($bio)): ?>
              <div class="text-xs text-slate-400 mt-1 truncate max-w-[180px]"><?= htmlspecialchars(substr($bio, 0, 60)) . (strlen($bio) > 60 ? '...' : '') ?></div>
            <?php endif; ?>
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
<div class="content-container">

    <!-- HERO SECTION -->
    <div class="hero-section relative rounded-lg overflow-hidden mb-6 sm:mb-8" style="min-height:300px;">
        <img src="https://images.unsplash.com/photo-1516253593875-bd7ba052fbc5?auto=format&fit=crop&q=80&w=1600" alt="Irigasi Sawah" class="absolute inset-0 w-full h-full object-cover" style="object-position:center 40%;filter:saturate(0.85);">
        <div class="absolute inset-0" style="background:linear-gradient(100deg,rgba(20,32,25,0.94) 0%,rgba(28,43,30,0.82) 48%,rgba(47,82,51,0.35) 100%);"></div>
        <div class="absolute inset-0 opacity-[0.06]" style="background-image:linear-gradient(rgba(245,241,229,1) 1px,transparent 1px),linear-gradient(90deg,rgba(245,241,229,1) 1px,transparent 1px);background-size:44px 44px;"></div>

        <div class="relative z-10 flex flex-col sm:flex-row items-start justify-between gap-6 sm:gap-8 p-6 sm:p-10" style="min-height:300px;">
            <div class="max-w-lg text-left w-full">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-sm text-xs font-mono-data font-semibold mb-4" style="background:rgba(185,132,58,0.18);border:1px solid rgba(211,168,104,0.32);color:#D3A868;">
                    <span class="live-dot inline-block w-1.5 h-1.5 rounded-full" style="background:#D3A868;"></span>
                    PORTAL EKOSISTEM AGRIKULTUR TERINTEGRASI
                </div>
                <h1 class="hero-title font-display text-2xl sm:text-3xl md:text-4xl font-semibold text-white leading-tight tracking-tight mb-3">
                    Sinergi Solusi Pertanian<br>Modern <span style="color:#D3A868;">Ladusync</span>
                </h1>
                <p class="hero-desc text-xs sm:text-sm leading-relaxed mb-6 max-w-md" style="color:rgba(245,241,229,0.62);">
                    Sentralisasi kendali sirkulasi pengairan pintar, pengarsipan kapasitas hasil bumi, serta akomodasi fasilitas alat tani terpadu dalam satu gerbang utama.
                </p>
                <div class="hero-buttons flex flex-col sm:flex-row gap-3 flex-wrap">
                    <a href="#sistem-terintegrasi" class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2 rounded-sm text-sm font-bold transition-all hover:-translate-y-0.5"
                       style="background:linear-gradient(135deg,#C9964C,#B9843A);color:#1C2B1E;box-shadow:0 4px 16px rgba(185,132,58,0.35);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                        Pilih Menu Sistem
                    </a>
                    <a href="#monitoring" class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2 rounded-sm text-sm font-bold transition-all hover:bg-white/10"
                       style="background:rgba(245,241,229,0.06);color:rgba(245,241,229,0.85);border:1px solid rgba(245,241,229,0.20);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        Monitor Live Sensor
                    </a>
                </div>
            </div>

            <div class="hero-gauge flex items-stretch gap-5 flex-shrink-0">
                <svg width="46" height="220" viewBox="0 0 46 220" class="gauge-tick">
                    <line x1="30" y1="6" x2="30" y2="214" stroke="rgba(245,241,229,0.28)" stroke-width="1"/>
                    <?php foreach ([0,20,40,60,80,100] as $i => $cm): $y = 210 - ($cm * 2); ?>
                    <line x1="22" y1="<?= $y ?>" x2="30" y2="<?= $y ?>" stroke="rgba(245,241,229,0.45)" stroke-width="1"/>
                    <text x="16" y="<?= $y + 3 ?>" text-anchor="end" font-size="9" fill="rgba(245,241,229,0.45)"><?= $cm ?></text>
                    <?php endforeach; ?>
                    <circle cx="30" cy="126" r="4" fill="#D3A868"/>
                    <text x="16" y="129" text-anchor="end" font-size="9" font-weight="700" fill="#D3A868">42</text>
                </svg>
                <div class="flex flex-col justify-end pb-1">
                    <div class="text-[10px] uppercase tracking-widest font-mono-data" style="color:rgba(245,241,229,0.40);writing-mode:vertical-rl;">TMA · CM</div>
                </div>
            </div>

            <div class="hero-stats grid grid-cols-2 gap-2 sm:gap-3 w-full sm:w-auto sm:ml-auto">
                <?php foreach ([['8','Sensor IoT'],['3 Sistem','Ekosistem'],['4 dtk','Update'],['99.8%','Uptime']] as [$n,$l]): ?>
                <div class="stat-box px-3 sm:px-5 py-2 sm:py-3 rounded-sm text-left" style="background:rgba(245,241,229,0.06);border:1px solid rgba(245,241,229,0.14);">
                    <div class="number text-lg sm:text-xl font-bold font-mono-data leading-none" style="color:#D3A868;"><?= $n ?></div>
                    <div class="label text-[10px] sm:text-xs font-medium mt-1" style="color:rgba(245,241,229,0.45);"><?= $l ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ===== MENU SISTEM TERINTEGRASI ===== -->
    <div id="sistem-terintegrasi" class="mb-4 pt-4">
        <h2 class="section-title">
            <span class="bar"></span> Menu Sistem Terintegrasi Ladusync
        </h2>
        <p class="section-sub">Pilih modul fungsional penunjang mobilitas pertanian Anda di bawah ini</p>
    </div>

    <!-- ===== 3 CARD SISTEM ===== -->
    <div class="card-sistem grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

        <div class="card-item bg-white rounded-lg p-6 border flex flex-col justify-between transition-all hover:-translate-y-1 relative overflow-hidden"
             style="border-color:rgba(138,115,87,0.18);box-shadow:0 10px 30px -18px rgba(28,43,30,0.30);">
            <div class="absolute top-0 left-0 right-0 h-1" style="background:linear-gradient(90deg,var(--sawah),var(--sawah-light));"></div>
            <div>
                <div class="flex justify-between items-start mb-4">
                    <span class="font-display text-2xl font-semibold" style="color:rgba(47,82,51,0.20);">01</span>
                    <div class="icon-box w-10 h-10 rounded-md flex items-center justify-center border shadow-sm" style="background:rgba(47,82,51,0.07);color:var(--sawah);border-color:rgba(47,82,51,0.16);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                </div>
                <h3 class="font-display font-semibold text-base" style="color:var(--ink);">Sistem Irigasi Pintar</h3>
                <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">Pemantauan volume aliran, temperatur sekitar, tinggi muka saluran air, serta peta geolokasi sebaran nodus sensor fisik lapangan.</p>
            </div>
            <div class="mt-6 pt-4 border-t flex items-center justify-between" style="border-color:rgba(138,115,87,0.14);">
                <span class="text-[10px] font-mono-data font-bold px-2.5 py-1 rounded-sm uppercase tracking-wider border" style="background:rgba(47,82,51,0.06);color:var(--sawah);border-color:rgba(47,82,51,0.18);">Real-Time</span>
                <a href="#monitoring" class="text-xs font-bold flex items-center gap-1" style="color:var(--sawah);">
                    Buka Monitoring <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
            </div>
        </div>

        <div class="card-item bg-white rounded-lg p-6 border flex flex-col justify-between transition-all hover:-translate-y-1 relative overflow-hidden"
             style="border-color:rgba(138,115,87,0.18);box-shadow:0 10px 30px -18px rgba(28,43,30,0.30);">
            <div class="absolute top-0 left-0 right-0 h-1" style="background:linear-gradient(90deg,var(--gabah),var(--gabah-light));"></div>
            <div>
                <div class="flex justify-between items-start mb-4">
                    <span class="font-display text-2xl font-semibold" style="color:rgba(185,132,58,0.22);">02</span>
                    <div class="icon-box w-10 h-10 rounded-md flex items-center justify-center border shadow-sm" style="background:rgba(185,132,58,0.08);color:var(--gabah);border-color:rgba(185,132,58,0.20);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </div>
                </div>
                <h3 class="font-display font-semibold text-base" style="color:var(--ink);">Pencatatan Kapasitas Panen</h3>
                <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">Pengarsipan kuantitas tonase hasil bumi tiap petak lahan regional, pelacakan fluktuasi komoditas agraria, serta kalkulasi estimasi laba kotor.</p>
            </div>
            <div class="mt-6 pt-4 border-t flex items-center justify-between" style="border-color:rgba(138,115,87,0.14);">
                <span class="text-[10px] font-mono-data font-bold px-2.5 py-1 rounded-sm uppercase tracking-wider border" style="background:rgba(185,132,58,0.08);color:var(--gabah);border-color:rgba(185,132,58,0.22);">Data Terpusat</span>
                <a href="data_lahan.php" class="text-xs font-bold flex items-center gap-1" style="color:var(--gabah);">
                    Kelola Panen <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
            </div>
        </div>

        <div class="card-item bg-white rounded-lg p-6 border flex flex-col justify-between transition-all hover:-translate-y-1 relative overflow-hidden"
             style="border-color:rgba(138,115,87,0.18);box-shadow:0 10px 30px -18px rgba(28,43,30,0.30);">
            <div class="absolute top-0 left-0 right-0 h-1" style="background:linear-gradient(90deg,var(--lempung),#A6906F);"></div>
            <div>
                <div class="flex justify-between items-start mb-4">
                    <span class="font-display text-2xl font-semibold" style="color:rgba(138,115,87,0.25);">03</span>
                    <div class="icon-box w-10 h-10 rounded-md flex items-center justify-center border shadow-sm" style="background:rgba(138,115,87,0.09);color:var(--lempung);border-color:rgba(138,115,87,0.22);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                </div>
                <h3 class="font-display font-semibold text-base" style="color:var(--ink);">Peminjaman Alat Modern</h3>
                <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">Layanan inventarisasi pinjam-pakai traktor, mesin penggiling padi, drone penyemprot hama cair, serta sistem logbook antrean terjadwal.</p>
            </div>
            <div class="mt-6 pt-4 border-t flex items-center justify-between" style="border-color:rgba(138,115,87,0.14);">
                <span class="text-[10px] font-mono-data font-bold px-2.5 py-1 rounded-sm uppercase tracking-wider border" style="background:rgba(138,115,87,0.09);color:var(--lempung);border-color:rgba(138,115,87,0.24);">Fasilitas Bersama</span>
                <a href="daftar_alat.php" class="text-xs font-bold flex items-center gap-1" style="color:var(--lempung);">
                    Cek Inventaris <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
            </div>
        </div>

    </div>

    <!-- ===== TENTANG LADUSYNC ===== -->
    <div id="tentang" class="tentang-grid grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
        <div class="bg-white rounded-lg p-5 sm:p-6 border" style="border-color:rgba(138,115,87,0.18);box-shadow:0 1px 3px rgba(28,43,30,0.05),0 8px 24px rgba(28,43,30,0.06);">
            <h2 class="font-display text-base font-semibold mb-3 pb-2 inline-block border-b-2" style="color:var(--sawah);border-color:var(--gabah);">Mengenal Ladusync</h2>
            <p class="text-sm text-slate-500 leading-relaxed mb-4">Platform terpadu agrikultur yang mengintegrasikan komponen sensor fisik pintar dengan tata kelola administrasi modern kelompok tani.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ([
                    ['Monitor Debit','Sensor flow meter otomatis'],
                    ['TMA Presisi','Sensor ultrasonik akurasi tinggi'],
                    ['Notifikasi Sesi','Peringatan ambang batas aman'],
                    ['Peta Geografis','Posisi riil titik koordinat'],
                ] as [$t,$d]): ?>
                <div class="flex gap-2 p-2 rounded-md" style="background:rgba(47,82,51,0.04);border:1px solid rgba(47,82,51,0.12);">
                    <div class="w-1.5 h-1.5 rounded-full flex-shrink-0 mt-1.5" style="background:var(--gabah);"></div>
                    <div class="text-xs text-slate-500"><strong class="text-slate-700"><?= $t ?></strong><br><?= $d ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg p-5 sm:p-6 border" style="border-color:rgba(138,115,87,0.18);box-shadow:0 1px 3px rgba(28,43,30,0.05),0 8px 24px rgba(28,43,30,0.06);">
            <h2 class="font-display text-base font-semibold mb-4 pb-2 inline-block border-b-2" style="color:var(--sawah);border-color:var(--gabah);">Spesifikasi Ekosistem</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse min-w-[300px]">
                    <thead>
                        <tr>
                            <th class="py-2 px-2 text-left text-xs font-bold uppercase text-white" style="background:var(--tanah);">#</th>
                            <th class="py-2 px-2 text-left text-xs font-bold uppercase text-white" style="background:var(--tanah);">Keterangan Arsitektur</th>
                            <th class="py-2 px-2 text-left text-xs font-bold uppercase text-white" style="background:var(--tanah);">Detail Core</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ([
                            ['1','Nama Utama Platform','Ladusync Terintegrasi'],
                            ['2','Titik Sensor IoT','8 Titik Aktif Mandiri'],
                            ['3','Jenis Sensor Fisik','Ultrasonik, DHT22, Flow Meter'],
                            ['4','Modul Rantai Ekosistem','Irigasi, Panen, Pinjam Alat'],
                            ['5','Interval Refresh State','Sinkronisasi data 4 detik'],
                            ['6','Cakupan Area Lahan','±240 Hektar Regional'],
                        ] as [$no,$k,$v]): ?>
                        <tr class="transition-colors" style="background:transparent;">
                            <td class="py-2.5 px-2 font-mono-data font-bold border-b" style="color:var(--gabah);border-color:rgba(138,115,87,0.14);"><?= $no ?></td>
                            <td class="py-2.5 px-2 text-slate-500 border-b" style="border-color:rgba(138,115,87,0.14);"><?= $k ?></td>
                            <td class="py-2.5 px-2 font-medium border-b" style="color:var(--ink);border-color:rgba(138,115,87,0.14);"><?= $v ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== MONITORING SENSOR ===== -->
    <div id="monitoring" class="bg-white rounded-lg border overflow-hidden mb-6 sm:mb-8" style="border-color:rgba(138,115,87,0.18);box-shadow:0 1px 3px rgba(28,43,30,0.05),0 8px 24px rgba(28,43,30,0.06);">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 px-4 sm:px-5 py-3 sm:py-4 border-b" style="border-color:rgba(138,115,87,0.14);background:linear-gradient(135deg,#F5F1E5,#ECE5D3);">
            <div>
                <div class="font-display font-semibold flex items-center gap-2 text-sm sm:text-base" style="color:var(--ink);">
                    <span class="live-dot inline-block w-2 h-2 rounded-full" style="background:var(--sawah);"></span>
                    Data Monitoring Sensor Real-Time Irigasi
                </div>
                <div class="text-xs text-slate-400 mt-0.5">Update 4 detik · 8 titik sensor aktif</div>
            </div>
            <a href="peta.php" class="flex items-center gap-1 text-xs font-bold transition-colors no-underline px-3 py-1.5 rounded-md" style="color:var(--sawah);">
                Lihat Peta <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>

        <div class="monitoring-table overflow-x-auto" style="-webkit-overflow-scrolling: touch;">
            <table class="w-full border-collapse" id="tabel-sensor" style="min-width:600px;">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th style="width:80px;">ID</th>
                        <th>Lokasi</th>
                        <th style="width:100px;">Debit (L/dtk)</th>
                        <th style="width:80px;">TMA (cm)</th>
                        <th style="width:80px;">Suhu (°C)</th>
                        <th style="width:80px;">Lembap (%)</th>
                        <th style="width:90px;">Status</th>
                        <th style="width:70px;">Waktu</th>
                    </tr>
                </thead>
                <tbody id="isi-tabel"></tbody>
            </table>
        </div>

        <div class="monitoring-stats grid grid-cols-5 divide-x border-t" style="border-color:rgba(138,115,87,0.14);">
            <div class="stat-item px-3 sm:px-5 py-2 sm:py-3 text-center">
                <div class="label text-[10px] sm:text-xs text-slate-400">Rata-rata Debit</div>
                <div class="value text-sm sm:text-base font-bold font-mono-data mt-0.5" style="color:var(--ink);">
                    <span id="rata-debit">—</span> <span class="text-[10px] sm:text-xs font-normal text-slate-400">L/dtk</span>
                </div>
            </div>
            <div class="stat-item px-3 sm:px-5 py-2 sm:py-3 text-center">
                <div class="label text-[10px] sm:text-xs text-slate-400">Rata-rata TMA</div>
                <div class="value text-sm sm:text-base font-bold font-mono-data mt-0.5" style="color:var(--ink);">
                    <span id="rata-tma">—</span> <span class="text-[10px] sm:text-xs font-normal text-slate-400">cm</span>
                </div>
            </div>
            <div class="stat-item px-3 sm:px-5 py-2 sm:py-3 text-center">
                <div class="label text-[10px] sm:text-xs text-slate-400">Rata-rata Suhu</div>
                <div class="value text-sm sm:text-base font-bold font-mono-data mt-0.5" style="color:var(--ink);">
                    <span id="rata-suhu">—</span> <span class="text-[10px] sm:text-xs font-normal text-slate-400">°C</span>
                </div>
            </div>
            <div class="stat-item px-3 sm:px-5 py-2 sm:py-3 text-center">
                <div class="label text-[10px] sm:text-xs text-slate-400">Rata-rata Lembap</div>
                <div class="value text-sm sm:text-base font-bold font-mono-data mt-0.5" style="color:var(--ink);">
                    <span id="rata-lembap">—</span> <span class="text-[10px] sm:text-xs font-normal text-slate-400">%</span>
                </div>
            </div>
            <div class="stat-item px-3 sm:px-5 py-2 sm:py-3 text-center">
                <div class="label text-[10px] sm:text-xs text-slate-400">Status Normal</div>
                <div class="value text-sm sm:text-base font-bold font-mono-data mt-0.5" style="color:var(--sawah);">
                    <span id="sensor-aman">—</span>
                </div>
            </div>
        </div>
    </div>

   <!-- ===== LAPORAN KENDALA ===== -->
<div id="lapor" class="bg-white rounded-lg border overflow-hidden mb-6 sm:mb-8" style="border-color:rgba(138,115,87,0.18);box-shadow:0 1px 3px rgba(28,43,30,0.05),0 8px 24px rgba(28,43,30,0.06);">
    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b" style="border-color:rgba(138,115,87,0.14);background:linear-gradient(135deg,#F5F1E5,#ECE5D3);">
        <div class="font-display font-semibold flex items-center gap-2 text-sm sm:text-base" style="color:var(--ink);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sawah)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Laporan Kendala Lapangan
        </div>
        <div class="text-xs text-slate-400 mt-0.5">Petani atau petugas dapat melaporkan masalah irigasi melalui formulir berikut</div>
    </div>

    <div class="laporan-form p-4 sm:p-6">
        <form id="formLaporan" method="POST">
            <div class="form-grid grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Nama Pelapor</label>
                    <input type="text" id="nama_pelapor" name="nama_pelapor" placeholder="Nama lengkap Anda" value="<?= htmlspecialchars($namaLengkap) ?>" required class="px-3 py-2.5 border rounded-md text-sm outline-none transition-all placeholder:text-slate-300" style="border-color:rgba(138,115,87,0.30);color:var(--ink);">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Lokasi Kendala</label>
                    <input type="text" id="lokasi_kendala" name="lokasi_kendala" placeholder="Contoh: Saluran Ngalor D" required class="px-3 py-2.5 border rounded-md text-sm outline-none transition-all placeholder:text-slate-300" style="border-color:rgba(138,115,87,0.30);color:var(--ink);">
                </div>
            </div>

            <div class="flex flex-col gap-1.5 mb-5">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Jenis Kendala</label>
                <select id="jenis_kendala" name="jenis_kendala" required class="px-3 py-2.5 border rounded-md text-sm outline-none transition-all" style="border-color:rgba(138,115,87,0.30);color:var(--ink);">
                    <option value="">— Pilih Jenis Kendala —</option>
                    <option>Debit air terlalu kecil</option>
                    <option>Debit air terlalu besar / banjir</option>
                    <option>Sensor tidak terbaca</option>
                    <option>Saluran tersumbat</option>
                    <option>Pintu air rusak</option>
                    <option>Lainnya</option>
                </select>
            </div>

            <div id="laporanMessage" class="hidden mb-4"></div>

            <button type="submit" id="btnKirimLaporan" name="kirim_laporan" class="btn-submit inline-flex items-center justify-center gap-2 w-full sm:w-auto px-6 py-2.5 rounded-md text-sm font-bold text-white transition-all hover:-translate-y-0.5 active:translate-y-0" style="background:linear-gradient(135deg,var(--sawah-light),var(--sawah));box-shadow:0 4px 16px rgba(47,82,51,0.30);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                <span id="btnText">Kirim Laporan</span>
            </button>
        </form>
    </div>
</div>

</div>
<!-- akhir konten -->

<!-- ===== FOOTER ===== -->
<footer style="background:var(--tanah);color:rgba(245,241,229,0.55);">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-10 sm:pt-14 pb-6">
    <div class="footer-grid grid grid-cols-1 md:grid-cols-4 gap-8 sm:gap-10 pb-10 border-b" style="border-color:rgba(211,168,104,0.16);">

      <div class="footer-col">
        <div class="flex items-center gap-2.5 mb-3">
          <div class="w-8 h-8 rounded-md flex items-center justify-center" style="background:rgba(185,132,58,0.16);border:1px solid rgba(211,168,104,0.30);">
            <svg width="16" height="16" viewBox="0 0 44 44" fill="none">
              <path d="M22 7C22 7 13 18 13 24C13 29.52 17.03 34 22 34C26.97 34 31 29.52 31 24C31 18 22 7 22 7Z" fill="#D3A868"/>
              <line x1="18" y1="24" x2="26" y2="24" stroke="#1C2B1E" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
          </div>
          <span class="font-display text-base font-bold text-white">Ladusync</span>
        </div>
        <p class="text-xs leading-relaxed max-w-xs" style="color:rgba(245,241,229,0.45);">Platform ekosistem digital agrikultur — menyatukan irigasi pintar, pencatatan hasil panen, dan peminjaman alat tani dalam satu sistem terpadu.</p>
        <div class="flex items-center gap-2 mt-4">
          <a href="#" class="w-8 h-8 rounded-md flex items-center justify-center transition-colors" style="background:rgba(245,241,229,0.06);border:1px solid rgba(245,241,229,0.12);color:rgba(245,241,229,0.55);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
          </a>
          <a href="#" class="w-8 h-8 rounded-md flex items-center justify-center transition-colors" style="background:rgba(245,241,229,0.06);border:1px solid rgba(245,241,229,0.12);color:rgba(245,241,229,0.55);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.42a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.42 8.6.42 8.6.42s6.88 0 8.6-.42a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.33 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>
          </a>
          <a href="#" class="w-8 h-8 rounded-md flex items-center justify-center transition-colors" style="background:rgba(245,241,229,0.06);border:1px solid rgba(245,241,229,0.12);color:rgba(245,241,229,0.55);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.5 8.5 0 0 1-12.36 7.56L3 21l1.94-5.64A8.5 8.5 0 1 1 21 11.5z"/></svg>
          </a>
        </div>
      </div>

      <div class="footer-col">
        <div class="title text-xs font-bold uppercase tracking-wider mb-4" style="color:#D3A868;">Navigasi Cepat</div>
        <ul class="space-y-2.5 text-xs">
          <li><a href="index.php" class="hover:text-white transition-colors no-underline" style="color:rgba(245,241,229,0.55);">Beranda</a></li>
          <li><a href="#sistem-terintegrasi" class="hover:text-white transition-colors no-underline" style="color:rgba(245,241,229,0.55);">Layanan Sistem</a></li>
          <li><a href="#monitoring" class="hover:text-white transition-colors no-underline" style="color:rgba(245,241,229,0.55);">Monitoring Real-Time</a></li>
          <li><a href="#tentang" class="hover:text-white transition-colors no-underline" style="color:rgba(245,241,229,0.55);">Mengenal Ladusync</a></li>
          <li><a href="#lapor" class="hover:text-white transition-colors no-underline" style="color:rgba(245,241,229,0.55);">Laporan Kendala</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <div class="title text-xs font-bold uppercase tracking-wider mb-4" style="color:#D3A868;">Layanan</div>
        <ul class="space-y-2.5 text-xs">
          <li><a href="peta.php" class="hover:text-white transition-colors no-underline" style="color:rgba(245,241,229,0.55);">Peta Sensor</a></li>
          <li><a href="bps.php" class="hover:text-white transition-colors no-underline" style="color:rgba(245,241,229,0.55);">Data BPS</a></li>
          <li><a href="riwayat.php" class="hover:text-white transition-colors no-underline" style="color:rgba(245,241,229,0.55);">Riwayat Data</a></li>
          <li><a href="daftar_alat.php" class="hover:text-white transition-colors no-underline" style="color:rgba(245,241,229,0.55);">Sewa Alat Tani</a></li>
          <li><a href="data_lahan.php" class="hover:text-white transition-colors no-underline" style="color:rgba(245,241,229,0.55);">Hasil Panen</a></li>
          <li><a href="api/konten_edukasi.php" class="hover:text-white transition-colors no-underline" style="color:rgba(245,241,229,0.55);">Konten Edukasi</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <div class="title text-xs font-bold uppercase tracking-wider mb-4" style="color:#D3A868;">Kontak & Alamat</div>
        <ul class="space-y-3 text-xs" style="color:rgba(245,241,229,0.55);">
          <li class="flex items-start gap-2">
            <svg class="flex-shrink-0 mt-0.5" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#D3A868" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span>Fakultas Pertanian, Universitas Sebelas Maret<br>Jl. Ir. Sutami No.36A, Kentingan, Surakarta, Jawa Tengah 57126</span>
          </li>
          <li class="flex items-center gap-2">
            <svg class="flex-shrink-0" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#D3A868" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            <span>(0271) 000-000</span>
          </li>
          <li class="flex items-center gap-2">
            <svg class="flex-shrink-0" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#D3A868" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <span>kontak@ladusync.id</span>
          </li>
        </ul>
      </div>

    </div>

    <div class="footer-bottom flex flex-col sm:flex-row items-center justify-between gap-2 pt-6 text-xs" style="color:rgba(245,241,229,0.35);">
      <div>© 2026 Sistem Integrasi Agrikultur Ladusync — Universitas Sebelas Maret</div>
      <div class="flex items-center gap-4">
        <a href="#" class="hover:text-white transition-colors no-underline" style="color:rgba(245,241,229,0.35);">Kebijakan Privasi</a>
        <a href="#" class="hover:text-white transition-colors no-underline" style="color:rgba(245,241,229,0.35);">Syarat Layanan</a>
      </div>
    </div>
  </div>
</footer>

</div><!-- /.main-area -->
</div><!-- /.app-shell -->

<!-- ============================================ -->
<!-- CUSTOMER SERVICE MODAL                       -->
<!-- ============================================ -->
<div id="csModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:none;align-items:center;justify-content:center;padding:20px;">
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
// ===== SIDEBAR TOGGLE =====
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainArea = document.getElementById('mainArea');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleHamburger = document.getElementById('sidebarToggleHamburger');
    const toggleCollapse = document.getElementById('sidebarToggleCollapse');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    
    const STORAGE_KEY = 'sidebar_collapsed';

    function isSidebarOpen() {
        return sidebar.classList.contains('open');
    }

    function openSidebar() {
        sidebar.classList.add('open');
        sidebar.classList.remove('collapsed');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
        if (window.innerWidth > 992) {
            localStorage.setItem(STORAGE_KEY, 'false');
        }
        updateMainArea();
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        sidebar.classList.add('collapsed');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
        if (window.innerWidth > 992) {
            localStorage.setItem(STORAGE_KEY, 'true');
        }
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
            if (window.innerWidth <= 992) {
                openSidebar();
            } else {
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
            if (window.innerWidth <= 992) {
                closeSidebar();
            } else {
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
            if (window.innerWidth <= 992) {
                closeSidebar();
            } else {
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('open');
                localStorage.setItem(STORAGE_KEY, 'true');
                updateMainArea();
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            closeSidebar();
        });
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
        if (e.key === 'Escape' && isSidebarOpen()) {
            closeSidebar();
        }
    });

    loadSidebarState();
});

// ===== CUSTOMER SERVICE MODAL =====
function openCSModal() {
    const modal = document.getElementById('csModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeCSModal() {
    const modal = document.getElementById('csModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

document.getElementById('csModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCSModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('csModal');
        if (modal.style.display === 'flex') {
            closeCSModal();
        }
    }
});
</script>

<!-- ============================================ -->
<!-- FORUM DISKUSI - FLOATING BUBBLE              -->
<!-- ============================================ -->
<a href="forum.php" class="forum-bubble" id="forumBubble" title="Forum Diskusi">
    <span class="bubble-tooltip">💬 Forum Diskusi</span>
    <span class="bubble-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            <path d="M8 10h.01"/>
            <path d="M12 10h.01"/>
            <path d="M16 10h.01"/>
        </svg>
    </span>
    <span class="bubble-badge" id="forumBadge">3</span>
</a>

<!-- ============================================ -->
<!-- AJAX LAPORAN KENDALA                         -->
<!-- ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formLaporan');
    const messageDiv = document.getElementById('laporanMessage');
    const btnKirim = document.getElementById('btnKirimLaporan');
    const btnText = document.getElementById('btnText');

    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        formData.append('kirim_laporan', '1');

        messageDiv.className = 'hidden';

        btnText.textContent = '⏳ Mengirim...';
        btnKirim.disabled = true;

        fetch('proses_laporan.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            messageDiv.className = 'block mt-4 px-4 py-3 rounded-md text-sm font-medium';

            if (data.status === 'success') {
                messageDiv.style.background = '#EEF4EA';
                messageDiv.style.border = '1px solid #C9DABF';
                messageDiv.style.color = '#2F5233';
                messageDiv.innerHTML = '✅ ' + data.message;

                const namaPelapor = document.getElementById('nama_pelapor').value;
                form.reset();
                document.getElementById('nama_pelapor').value = namaPelapor;
            } else {
                messageDiv.style.background = '#FBEAE7';
                messageDiv.style.border = '1px solid #E7B9AE';
                messageDiv.style.color = '#8A2E1F';
                messageDiv.innerHTML = '❌ ' + data.message;
            }
        })
        .catch(function() {
            messageDiv.className = 'block mt-4 px-4 py-3 rounded-md text-sm font-medium';
            messageDiv.style.background = '#FBEAE7';
            messageDiv.style.border = '1px solid #E7B9AE';
            messageDiv.style.color = '#8A2E1F';
            messageDiv.innerHTML = '❌ Terjadi kesalahan. Coba lagi.';
        })
        .finally(function() {
            btnText.textContent = 'Kirim Laporan';
            btnKirim.disabled = false;
        });
    });
});
</script>

<!-- ============================================ -->
<!-- FORUM BUBBLE - BADGE DINAMIS                 -->
<!-- ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const badge = document.getElementById('forumBadge');
    
    fetch('api/get_diskusi_count.php')
        .then(response => response.json())
        .then(data => {
            if (badge && data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'flex';
            } else if (badge) {
                badge.style.display = 'none';
            }
        })
        .catch(() => {
            if (badge) {
                badge.textContent = '3';
                badge.style.display = 'flex';
            }
        });
});
</script>

<!-- ============================================ -->
<!-- DATA SENSOR & RENDER TABEL                  -->
<!-- ============================================ -->
<script>
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
  return '<span class="status-pill ' + spClass[s] + '"><svg width="6" height="6" viewBox="0 0 6 6"><circle cx="3" cy="3" r="3" fill="' + dotColor[s] + '"/></svg>' + labelSt[s] + '</span>';
}

function renderTabel() {
  var html = '';
  dataSensor.forEach(function(s, i) {
    html += '<tr>';
    html += '<td style="text-align:center;">' + (i+1) + '</td>';
    html += '<td style="text-align:center;">' + s.id + '</td>';
    html += '<td style="text-align:center;">' + s.lokasi + '</td>';
    html += '<td style="font-variant-numeric:tabular-nums;text-align:center;">' + s.debit.toFixed(1) + '</td>';
    html += '<td style="font-variant-numeric:tabular-nums;text-align:center;">' + s.tma + '</td>';
    html += '<td style="font-variant-numeric:tabular-nums;text-align:center;">' + s.suhu.toFixed(1) + '</td>';
    html += '<td style="font-variant-numeric:tabular-nums;text-align:center;">' + s.lembap + '</td>';
    html += '<td style="text-align:center;">' + pill(s.status) + '</td>';
    html += '<td style="color:#A79A85;font-size:0.7rem;text-align:center;">' + waktu() + '</td>';
    html += '</tr>';
  });
  document.getElementById('isi-tabel').innerHTML = html;
  hitungRingkasan();
}

function hitungRingkasan() {
  var td = 0, tt = 0, ts = 0, tl = 0, n = 0, c = dataSensor.length;
  
  dataSensor.forEach(function(s) { 
    td += s.debit;
    tt += s.tma;
    ts += s.suhu;
    tl += s.lembap;
    if (s.status === 'normal') n++;
  });
  
  document.getElementById('rata-debit').textContent = (td / c).toFixed(1);
  document.getElementById('rata-tma').textContent = Math.round(tt / c);
  document.getElementById('rata-suhu').textContent = (ts / c).toFixed(1);
  document.getElementById('rata-lembap').textContent = Math.round(tl / c);
  document.getElementById('sensor-aman').textContent = n + ' dari ' + c + ' titik';
}

function perbaruiSensor() {
  dataSensor.forEach(function(s) {
    s.debit  = Math.max(0.5, s.debit + (Math.random() - 0.5));
    s.tma    = Math.max(5,   s.tma    + Math.round((Math.random() - 0.5) * 3));
    s.lembap = Math.min(100, Math.max(10, s.lembap + Math.round((Math.random() - 0.5) * 2)));
    if      (s.tma < 15) s.status = 'kritis';
    else if (s.tma < 25) s.status = 'rendah';
    else if (s.tma > 65) s.status = 'tinggi';
    else                  s.status = 'normal';
  });
  renderTabel();
}

renderTabel();
setInterval(perbaruiSensor, 4000);
</script>

</body>
</html>
