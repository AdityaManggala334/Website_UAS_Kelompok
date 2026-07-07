<?php
// ============================================================
// PETA SENSOR - LADUSYNC
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

// Ambil data user dari auth_helper
$userData = getCurrentUser();
$user_id = $userData['id'];
$username = $userData['username'];
$namaDepan = $userData['nama_depan'] ?? $username;
$role = $userData['role'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Peta Sensor — Ladusync</title>

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
        --topbar-h:     64px;
    }

    * { margin:0; padding:0; box-sizing:border-box; }
    html { scroll-behavior: smooth; height: 100%; }
    body { 
        font-family: 'Sora', sans-serif; 
        background: var(--kertas); 
        color: var(--ink);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
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
    .sp-normal { background:#D1FAE5; color:#065F46; border:1px solid #6EE7B7; }
    .sp-rendah { background:#FEF3C7; color:#92400E; border:1px solid #FCD34D; }
    .sp-tinggi { background:#DBEAFE; color:#1E40AF; border:1px solid #93C5FD; }
    .sp-kritis { background:#FEE2E2; color:#991B1B; border:1px solid #FCA5A5; }

    .profil-wrap:hover .profil-dropdown { display: block; }
    .profil-dropdown {
        display: none; position: absolute; right: 0; top: 100%; margin-top: 8px;
        background: white; border-radius: 4px; min-width: 210px;
        box-shadow: 0 12px 34px rgba(20,32,25,0.20); z-index: 50; overflow: hidden;
        border: 1px solid rgba(138,115,87,0.18);
    }

    /* ============================================
       APP SHELL — SIDEBAR KIRI + TOPBAR
       ============================================ */
    .app-shell { display: flex; min-height: 100vh; flex: 1; }

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
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        padding: 20px 20px 16px; flex-shrink: 0;
        border-bottom: 1px solid rgba(245,241,229,0.08);
    }
    .sidebar-logo .logo-wrap { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }

    .sidebar-close-btn {
        display: none; align-items: center; justify-content: center;
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
        cursor: pointer;
        background: none; border-right: none; width: 100%; text-align: left;
        font-family: inherit;
    }
    .nav-link:hover { color: #fff; background: rgba(245,241,229,0.06); }
    .nav-link.active { color: var(--pop); background: var(--pop-dim); border-left: 2px solid var(--pop); }
    .nav-link .nav-icon { width: 17px; height: 17px; flex-shrink: 0; }
    .nav-link .nav-text { font-size: 0.84rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nav-link-admin { background: rgba(211,168,104,0.12); color: #D3A868; border-left: 2px solid #D3A868; }
    .nav-link-admin:hover { background: rgba(211,168,104,0.22); color: #D3A868; }

    .sidebar-bottom {
        flex-shrink: 0;
        padding: 12px;
        border-top: 1px solid rgba(245,241,229,0.08);
    }
    .nav-link-logout { color: rgba(255,138,120,0.85); }
    .nav-link-logout:hover { color: #fff; background: rgba(156,65,48,0.35); }

    .sidebar-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(10,20,16,0.55); z-index: 65;
        opacity: 0; transition: opacity 0.3s ease;
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
        flex-shrink: 0;
        margin-left: auto;
    }

    /* ===== CONTENT ===== */
    .content-wrapper {
        flex: 1;
        max-width: 1200px;
        width: 100%;
        margin: 0 auto;
        padding: 1.5rem 1.5rem 1rem;
    }

    /* ===== MAP STYLES ===== */
    .map-wrap {
        position: relative; overflow: hidden;
        background: #1a2e1a;
        border-radius: 12px;
    }
    .map-wrap img.aerial {
        width: 100%; display: block;
        max-height: 350px; object-fit: cover; object-position: center;
        opacity: 0.88;
    }
    @media (min-width: 640px) { .map-wrap img.aerial { max-height: 420px; } }
    @media (min-width: 1024px) { .map-wrap img.aerial { max-height: 500px; } }
    
    .map-overlay {
        position: absolute; inset: 0;
        background: rgba(0,0,0,.18);
        pointer-events: none;
    }
    .sensor-svg {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
    }

    @keyframes sensorPulse {
        0%   { r: 10; opacity: .7; }
        50%  { r: 18; opacity: .3; }
        100% { r: 10; opacity: .7; }
    }
    .sensor-ring { animation: sensorPulse 2.5s ease-in-out infinite; }

    .sensor-click { cursor: pointer; transition: transform .2s; }
    .sensor-click:hover { transform: scale(1.12); }

    .kpi-row { display: grid; grid-template-columns: repeat(2,1fr); gap: 0.75rem; margin-bottom: 1rem; }
    @media (min-width: 640px) { .kpi-row { grid-template-columns: repeat(4,1fr); gap: 1rem; } }
    
    .kpi-chip {
        background: white; border: 1px solid rgba(138,115,87,0.18);
        border-radius: 12px; padding: 0.75rem;
        box-shadow: 0 1px 3px rgba(28,43,30,0.05), 0 8px 24px rgba(28,43,30,0.06);
        display: flex; align-items: center; gap: 8px;
    }
    @media (min-width: 640px) { .kpi-chip { padding: 1rem 1.1rem; gap: 10px; } }
    
    .kpi-dot  { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    @media (min-width: 640px) { .kpi-dot { width: 10px; height: 10px; } }
    
    .kpi-num  { font-size: 1.1rem; font-weight: 800; color: #022C22; line-height: 1; }
    @media (min-width: 640px) { .kpi-num { font-size: 1.5rem; } }
    
    .kpi-lbl  { font-size: 0.55rem; font-weight: 600; color: #94A3B8; margin-top: 2px; }
    @media (min-width: 640px) { .kpi-lbl { font-size: .7rem; } }

    .panel {
        background: white; border: 1px solid rgba(138,115,87,0.18);
        border-radius: 16px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(28,43,30,0.05), 0 8px 24px rgba(28,43,30,0.06);
    }
    .panel-head {
        padding: 0.75rem 1rem; border-bottom: 1px solid rgba(138,115,87,0.14);
        display: flex; align-items: center; justify-content: space-between;
        font-size: 0.8rem; font-weight: 700; color: #022C22;
        background: linear-gradient(135deg,#F5F1E5,#ECE5D3);
        flex-wrap: wrap;
        gap: 4px;
    }
    @media (min-width: 640px) { .panel-head { padding: .9rem 1.2rem; font-size: .85rem; } }
    
    .panel-sub { font-size: 0.6rem; font-weight: 500; color: #94A3B8; }
    @media (min-width: 640px) { .panel-sub { font-size: .7rem; } }

    .map-layout { display: grid; grid-template-columns: 1fr; gap: 1rem; align-items: start; }
    @media(min-width:900px){ .map-layout{ grid-template-columns: 1fr 320px; gap: 1.25rem; } }

    .detail-empty {
        padding: 1.5rem 1rem; text-align: center; color: #CBD5E1;
    }
    .detail-card { padding: 0.75rem 1rem; }
    @media (min-width: 640px) { .detail-card { padding: 1.1rem 1.2rem; } }
    
    .detail-id   { font-size: 0.6rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: #94A3B8; }
    .detail-name { font-size: 0.85rem; font-weight: 700; color: #022C22; margin: 4px 0 6px; }
    @media (min-width: 640px) { .detail-name { font-size: 1rem; } }
    
    .detail-pill {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 8px; border-radius: 20px;
        font-size: 0.65rem; font-weight: 700; margin-bottom: 10px;
    }
    @media (min-width: 640px) { .detail-pill { padding: 3px 10px; font-size: .7rem; margin-bottom: 12px; } }
    
    .detail-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
    @media (min-width: 640px) { .detail-stats { gap: 8px; } }
    
    .detail-stat { background: #F8FAFC; border-radius: 10px; padding: 6px 8px; }
    @media (min-width: 640px) { .detail-stat { padding: 9px 10px; } }
    
    .detail-stat-val { font-size: 0.8rem; font-weight: 700; color: #022C22; }
    @media (min-width: 640px) { .detail-stat-val { font-size: 1rem; } }
    
    .detail-stat-lbl { font-size: 0.5rem; color: #94A3B8; font-weight: 500; margin-top: 2px; }
    @media (min-width: 640px) { .detail-stat-lbl { font-size: .65rem; } }

    .sensor-list-item {
        display: flex; align-items: center; gap: 8px;
        padding: 7px 0.8rem; border-bottom: 1px solid rgba(6,78,59,.04);
        cursor: pointer; transition: background .15s;
    }
    @media (min-width: 640px) { .sensor-list-item { padding: 8px 1.1rem; gap: 9px; } }
    
    .sensor-list-item:hover { background: #F0FDF4; }
    .sensor-list-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .sensor-list-id  { font-size: 0.55rem; font-weight: 700; color: #94A3B8; }
    .sensor-list-loc { font-size: 0.7rem; font-weight: 500; color: #022C22; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    @media (min-width: 640px) { .sensor-list-loc { font-size: .8rem; } }
    
    .sensor-pill {
        margin-left: auto; flex-shrink: 0;
        padding: 2px 6px; border-radius: 10px;
        font-size: 0.5rem; font-weight: 700;
    }
    @media (min-width: 640px) { .sensor-pill { padding: 2px 8px; font-size: .6rem; } }

    .legend-wrap {
        display: flex; gap: 0.75rem; padding: 0.5rem 0.8rem;
        border-top: 1px solid rgba(138,115,87,0.14);
        background: #FAFAFA;
        flex-wrap: wrap; justify-content: center;
    }
    @media (min-width: 640px) { .legend-wrap { gap: 1.25rem; padding: .7rem 1.2rem; justify-content: flex-start; } }
    
    .legend-item {
        display: flex; align-items: center; gap: 4px;
        font-size: 0.55rem; font-weight: 600; color: #4B7563;
    }
    @media (min-width: 640px) { .legend-item { gap: 5px; font-size: .7rem; } }
    
    .legend-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
    @media (min-width: 640px) { .legend-dot { width: 8px; height: 8px; } }

    /* ============================================================ */
    /* FOOTER 3 KOLOM - PROFESIONAL & RINGKAS                        */
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

    /* ===== SIDEBAR RESPONSIVE ===== */
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
        .content-wrapper { padding: 1rem 0.75rem 0.5rem; }
        .kpi-row { grid-template-columns: repeat(2,1fr) !important; gap: 6px !important; }
        .kpi-chip { padding: 8px !important; }
        .kpi-num { font-size: 0.95rem !important; }
        .map-layout { grid-template-columns: 1fr !important; }
        .map-wrap img.aerial { max-height: 220px; }
        .profil-name { display: none !important; }
        .sidebar-toggle-hamburger { width: 34px; height: 34px; }
        .site-footer { padding: 1.5rem 1rem 1rem; }
        .footer-grid { gap: 1.5rem; }
        .footer-brand .desc { max-width: 100%; }
    }

    @media (max-width: 480px) {
        .topbar { padding: 0 8px; height: 48px; }
        .sidebar { width: 280px !important; }
        .content-wrapper { padding: 0.5rem 0.5rem 0.25rem; }
        .kpi-row { grid-template-columns: 1fr 1fr !important; gap: 4px !important; }
        .kpi-chip { padding: 6px 8px !important; border-radius: 8px !important; }
        .kpi-num { font-size: 0.8rem !important; }
        .kpi-lbl { font-size: 0.45rem !important; }
        .kpi-dot { width: 6px !important; height: 6px !important; }
        .map-wrap img.aerial { max-height: 160px; }
        .panel-head { padding: 6px 10px !important; font-size: 0.65rem !important; }
        .panel-head .panel-sub { font-size: 0.5rem !important; }
        .legend-wrap { gap: 0.4rem; padding: 0.3rem 0.5rem; }
        .legend-item { font-size: 0.45rem; }
        .legend-dot { width: 5px; height: 5px; }
        .site-footer { padding: 1rem 0.75rem; }
        .footer-grid { gap: 1.25rem; }
        .footer-brand .logo-text { font-size: 0.9rem; }
        .footer-brand .desc { font-size: 0.7rem; }
        .footer-col .title { font-size: 0.6rem; }
        .footer-col ul li a { font-size: 0.7rem; }
        .footer-col .contact-item { font-size: 0.7rem; }
        .footer-bottom { font-size: 0.55rem; gap: 4px; }
        .footer-bottom .links { gap: 0.75rem; }
        .footer-social a { width: 28px; height: 28px; }
        .footer-social a svg { width: 12px; height: 12px; }
    }

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

    <a href="peta.php" class="nav-link active">
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
        Peta Sensor Ladusync
      </span>
    </div>

    <!-- ===== KANAN ATAS: PROFIL USER ===== -->
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
            <div class="font-bold text-sm font-display" style="color:var(--sawah);"><?= htmlspecialchars($namaDepan) ?></div>
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

<!-- ===== KONTEN UTAMA ===== -->
<main class="content-wrapper">

    <!-- HEADER HALAMAN -->
    <div class="mb-4 sm:mb-6">
        <h1 class="font-display text-xl sm:text-2xl font-bold" style="color:var(--sawah);">Peta Sensor Interaktif</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
            <span class="live-dot inline-block w-2 h-2 rounded-full mr-1" style="background:#D3A868;"></span>
            Visualisasi posisi 8 sensor aktif · update setiap 4 detik
        </p>
    </div>

    <!-- KPI ROW -->
    <div class="kpi-row">
        <div class="kpi-chip">
            <div class="kpi-dot" style="background:#10B981;"></div>
            <div><div class="kpi-num" id="cnt-normal">0</div><div class="kpi-lbl">Normal</div></div>
        </div>
        <div class="kpi-chip">
            <div class="kpi-dot" style="background:#F59E0B;"></div>
            <div><div class="kpi-num" id="cnt-rendah">0</div><div class="kpi-lbl">Rendah</div></div>
        </div>
        <div class="kpi-chip">
            <div class="kpi-dot" style="background:#3B82F6;"></div>
            <div><div class="kpi-num" id="cnt-tinggi">0</div><div class="kpi-lbl">Tinggi</div></div>
        </div>
        <div class="kpi-chip">
            <div class="kpi-dot" style="background:#EF4444;"></div>
            <div><div class="kpi-num" id="cnt-kritis">0</div><div class="kpi-lbl">Kritis</div></div>
        </div>
    </div>

    <!-- MAP + SIDEBAR -->
    <div class="map-layout">

        <!-- MAP CARD -->
        <div class="panel">
            <div class="panel-head">
                <span>Denah Jaringan Irigasi</span>
                <span class="panel-sub" id="waktu-peta">--:--:--</span>
            </div>
            <div class="map-wrap">
                <!-- GAMBAR PETA SAWAH -->
                <img class="aerial" src="https://imgur.com/dmDQGaw.png" alt="Peta Irigasi Sawah" onerror="this.src='data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20400%20300%22%3E%3Crect%20width%3D%22400%22%20height%3D%22300%22%20fill%3D%22%231a2e1a%22%2F%3E%3Ctext%20x%3D%22200%22%20y%3D%22150%22%20text-anchor%3D%22middle%22%20fill%3D%22%2334D399%22%20font-size%3D%2214%22%3EPeta%20Irigasi%3C%2Ftext%3E%3C%2Fsvg%3E'">
                <div class="map-overlay"></div>

                <!-- SVG OVERLAY SENSOR (UKURAN KECIL + BULAT) -->
                <svg class="sensor-svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <!-- Efek glow untuk titik sensor -->
                        <filter id="glow" x="-50%" y="-50%" width="200%" height="200%">
                            <feGaussianBlur stdDeviation="1.5" result="blur"/>
                            <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                    </defs>

                    <!-- Sensor SNS-01 -->
                    <g class="sensor-click" onclick="pilihSensor('SNS-01')">
                        <circle id="ring-SNS-01" cx="14" cy="10" r="4" fill="none" stroke="#10B981" stroke-width="1" opacity=".5" class="sensor-ring"/>
                        <circle id="dot-SNS-01" cx="14" cy="10" r="3.5" fill="#10B981" stroke="white" stroke-width="1.2" filter="url(#glow)"/>
                        <text x="14" y="10.8" text-anchor="middle" dominant-baseline="middle" font-size="2.2" fill="white" font-weight="700">S1</text>
                    </g>

                    <!-- Sensor SNS-02 -->
                    <g class="sensor-click" onclick="pilihSensor('SNS-02')">
                        <circle id="ring-SNS-02" cx="26" cy="10" r="4" fill="none" stroke="#10B981" stroke-width="1" opacity=".5" class="sensor-ring"/>
                        <circle id="dot-SNS-02" cx="26" cy="10" r="3.5" fill="#10B981" stroke="white" stroke-width="1.2" filter="url(#glow)"/>
                        <text x="26" y="10.8" text-anchor="middle" dominant-baseline="middle" font-size="2.2" fill="white" font-weight="700">S2</text>
                    </g>

                    <!-- Sensor SNS-03 - Rendah -->
                    <g class="sensor-click" onclick="pilihSensor('SNS-03')">
                        <circle id="ring-SNS-03" cx="47" cy="10" r="4" fill="none" stroke="#F59E0B" stroke-width="1" opacity=".5" class="sensor-ring"/>
                        <circle id="dot-SNS-03" cx="47" cy="10" r="3.5" fill="#F59E0B" stroke="white" stroke-width="1.2" filter="url(#glow)"/>
                        <text x="47" y="10.8" text-anchor="middle" dominant-baseline="middle" font-size="2.2" fill="white" font-weight="700">S3</text>
                    </g>

                    <!-- Sensor SNS-04 - Tinggi -->
                    <g class="sensor-click" onclick="pilihSensor('SNS-04')">
                        <circle id="ring-SNS-04" cx="69" cy="10" r="4" fill="none" stroke="#3B82F6" stroke-width="1" opacity=".5" class="sensor-ring"/>
                        <circle id="dot-SNS-04" cx="69" cy="10" r="3.5" fill="#3B82F6" stroke="white" stroke-width="1.2" filter="url(#glow)"/>
                        <text x="69" y="10.8" text-anchor="middle" dominant-baseline="middle" font-size="2.2" fill="white" font-weight="700">S4</text>
                    </g>

                    <!-- Sensor SNS-05 -->
                    <g class="sensor-click" onclick="pilihSensor('SNS-05')">
                        <circle id="ring-SNS-05" cx="15" cy="55" r="4" fill="none" stroke="#10B981" stroke-width="1" opacity=".5" class="sensor-ring"/>
                        <circle id="dot-SNS-05" cx="15" cy="55" r="3.5" fill="#10B981" stroke="white" stroke-width="1.2" filter="url(#glow)"/>
                        <text x="15" y="55.8" text-anchor="middle" dominant-baseline="middle" font-size="2.2" fill="white" font-weight="700">S5</text>
                    </g>

                    <!-- Sensor SNS-06 - Kritis -->
                    <g class="sensor-click" onclick="pilihSensor('SNS-06')">
                        <circle id="ring-SNS-06" cx="46" cy="50" r="4" fill="none" stroke="#EF4444" stroke-width="1" opacity=".5" class="sensor-ring"/>
                        <circle id="dot-SNS-06" cx="46" cy="50" r="3.5" fill="#EF4444" stroke="white" stroke-width="1.2" filter="url(#glow)"/>
                        <text x="46" y="50.8" text-anchor="middle" dominant-baseline="middle" font-size="2.2" fill="white" font-weight="700">S6</text>
                    </g>

                    <!-- Sensor SNS-07 -->
                    <g class="sensor-click" onclick="pilihSensor('SNS-07')">
                        <circle id="ring-SNS-07" cx="68" cy="50" r="4" fill="none" stroke="#10B981" stroke-width="1" opacity=".5" class="sensor-ring"/>
                        <circle id="dot-SNS-07" cx="68" cy="50" r="3.5" fill="#10B981" stroke="white" stroke-width="1.2" filter="url(#glow)"/>
                        <text x="68" y="50.8" text-anchor="middle" dominant-baseline="middle" font-size="2.2" fill="white" font-weight="700">S7</text>
                    </g>

                    <!-- Sensor SNS-08 -->
                    <g class="sensor-click" onclick="pilihSensor('SNS-08')">
                        <circle id="ring-SNS-08" cx="85" cy="80" r="4" fill="none" stroke="#10B981" stroke-width="1" opacity=".5" class="sensor-ring"/>
                        <circle id="dot-SNS-08" cx="85" cy="80" r="3.5" fill="#10B981" stroke="white" stroke-width="1.2" filter="url(#glow)"/>
                        <text x="85" y="80.8" text-anchor="middle" dominant-baseline="middle" font-size="2.2" fill="white" font-weight="700">S8</text>
                    </g>
                </svg>
            </div>

            <!-- LEGEND -->
            <div class="legend-wrap">
                <div class="legend-item"><div class="legend-dot" style="background:#10B981;"></div>Normal</div>
                <div class="legend-item"><div class="legend-dot" style="background:#F59E0B;"></div>Rendah</div>
                <div class="legend-item"><div class="legend-dot" style="background:#3B82F6;"></div>Tinggi</div>
                <div class="legend-item"><div class="legend-dot" style="background:#EF4444;"></div>Kritis</div>
            </div>
        </div>

        <!-- SIDEBAR -->
        <div style="display:flex;flex-direction:column;gap:1rem;">

            <!-- DETAIL SENSOR -->
            <div class="panel">
                <div class="panel-head">Detail Sensor</div>
                <div id="detail-sensor">
                    <div class="detail-empty">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>
                        <p style="font-size:0.7rem;color:#94A3B8;margin-top:8px;">Klik titik sensor pada peta</p>
                    </div>
                </div>
            </div>

            <!-- DAFTAR SENSOR -->
            <div class="panel">
                <div class="panel-head">Semua Sensor</div>
                <div id="daftar-sensor" style="overflow-y:auto;max-height:240px;"></div>
            </div>

        </div>
    </div>

</main>

<!-- ============================================================ -->
<!-- FOOTER 3 KOLOM - PROFESIONAL & RINGKAS                        -->
<!-- ============================================================ -->
<footer class="site-footer">
    <div class="footer-container">

        <!-- 3 Kolom -->
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
                    <span class="logo-text">Ladusync</span>
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
</script>

<!-- ============================================ -->
<!-- JAVASCRIPT (Data Sensor, Update Real-Time, Interaksi) -->
<!-- ============================================ -->
<script>
// DATA SENSOR (8 titik sensor dengan nilai default)
var dataSensor = [
    { id:"SNS-01", lokasi:"Saluran Induk Ngidul",  debit:12.4, tma:42, suhu:26.8, lembap:68, status:"normal" },
    { id:"SNS-02", lokasi:"Percabangan Blok A",    debit:8.7,  tma:35, suhu:27.1, lembap:72, status:"normal" },
    { id:"SNS-03", lokasi:"Saluran Blok B",        debit:3.2,  tma:18, suhu:28.3, lembap:45, status:"rendah" },
    { id:"SNS-04", lokasi:"Bak Penampungan C1",    debit:18.9, tma:71, suhu:26.2, lembap:80, status:"tinggi" },
    { id:"SNS-05", lokasi:"Saluran Ngalor D",      debit:6.5,  tma:28, suhu:27.8, lembap:63, status:"normal" },
    { id:"SNS-06", lokasi:"Saluran Ngetan E",      debit:1.1,  tma:10, suhu:29.0, lembap:31, status:"kritis" },
    { id:"SNS-07", lokasi:"Saluran Petak 12",      debit:9.3,  tma:38, suhu:26.5, lembap:70, status:"normal" },
    { id:"SNS-08", lokasi:"Embung Ngulon",         debit:7.8,  tma:32, suhu:27.4, lembap:66, status:"normal" },
];

// WARNA CERAH untuk setiap status
var WARNA = { 
    normal: "#10B981",   // Hijau Cerah
    rendah: "#F59E0B",   // Kuning Cerah
    tinggi: "#3B82F6",   // Biru Cerah
    kritis: "#EF4444"    // Merah Cerah
};
var LABEL = { normal:"Normal", rendah:"Rendah", tinggi:"Tinggi", kritis:"Kritis" };

function pillStyle(status) {
    var s = {
        normal: "background:#D1FAE5;color:#065F46;border:1px solid #6EE7B7;",
        rendah: "background:#FEF3C7;color:#92400E;border:1px solid #FCD34D;",
        tinggi: "background:#DBEAFE;color:#1E40AF;border:1px solid #93C5FD;",
        kritis: "background:#FEE2E2;color:#991B1B;border:1px solid #FCA5A5;"
    };
    return s[status] || s.normal;
}

function updateWaktu() {
    var n = new Date();
    var pad = function(x){ return String(x).padStart(2,'0'); };
    var waktuElem = document.getElementById('waktu-peta');
    if (waktuElem) waktuElem.textContent = pad(n.getHours())+':'+pad(n.getMinutes())+':'+pad(n.getSeconds());
}

function renderDaftar() {
    var cnt = { normal:0, rendah:0, tinggi:0, kritis:0 };
    var html = '';
    dataSensor.forEach(function(s) {
        cnt[s.status] = (cnt[s.status] || 0) + 1;
        html += '<div class="sensor-list-item" onclick="pilihSensor(\''+s.id+'\')">';
        html += '<div class="sensor-list-dot" style="background:'+WARNA[s.status]+';"></div>';
        html += '<div style="flex:1;min-width:0;">';
        html += '<div class="sensor-list-id">'+s.id+'</div>';
        html += '<div class="sensor-list-loc">'+s.lokasi+'</div>';
        html += '</div>';
        html += '<span class="sensor-pill" style="'+pillStyle(s.status)+'">'+LABEL[s.status]+'</span>';
        html += '</div>';
    });
    var daftarElem = document.getElementById('daftar-sensor');
    if (daftarElem) daftarElem.innerHTML = html;
    
    ['normal','rendah','tinggi','kritis'].forEach(function(k) {
        var elem = document.getElementById('cnt-'+k);
        if (elem) elem.textContent = cnt[k] || 0;
    });
}

function pilihSensor(id) {
    var s = dataSensor.find(function(x){ return x.id === id; });
    if (!s) return;
    var w = WARNA[s.status];
    var dot = '<svg width="6" height="6" viewBox="0 0 6 6"><circle cx="3" cy="3" r="3" fill="'+w+'"/></svg>';

    var html = '<div class="detail-card">';
    html += '<div class="detail-id">'+s.id+'</div>';
    html += '<div class="detail-name">'+s.lokasi+'</div>';
    html += '<span class="detail-pill" style="'+pillStyle(s.status)+'">'+dot+LABEL[s.status]+'</span>';
    html += '<div class="detail-stats">';
    html += '<div class="detail-stat"><div class="detail-stat-val">'+s.debit.toFixed(1)+'</div><div class="detail-stat-lbl">Debit L/dtk</div></div>';
    html += '<div class="detail-stat"><div class="detail-stat-val">'+s.tma+'</div><div class="detail-stat-lbl">TMA cm</div></div>';
    html += '<div class="detail-stat"><div class="detail-stat-val">'+s.suhu.toFixed(1)+'°</div><div class="detail-stat-lbl">Suhu C</div></div>';
    html += '<div class="detail-stat"><div class="detail-stat-val">'+s.lembap+'%</div><div class="detail-stat-lbl">Kelembapan</div></div>';
    html += '</div></div>';

    var detailElem = document.getElementById('detail-sensor');
    if (detailElem) detailElem.innerHTML = html;

    dataSensor.forEach(function(x) {
        var el = document.getElementById('dot-'+x.id);
        if (el) el.setAttribute('stroke-width', x.id === id ? '2.5' : '1.2');
    });
}

function simulasiUpdate() {
    dataSensor.forEach(function(s) {
        s.debit  = Math.max(0.5, s.debit + (Math.random() - 0.5));
        s.tma    = Math.max(5, s.tma + Math.round((Math.random() - 0.5) * 3));
        s.lembap = Math.min(100, Math.max(10, s.lembap + Math.round((Math.random() - 0.5) * 2)));
        
        if      (s.tma < 15) s.status = 'kritis';
        else if (s.tma < 25) s.status = 'rendah';
        else if (s.tma > 65) s.status = 'tinggi';
        else                  s.status = 'normal';

        var dot = document.getElementById('dot-'+s.id);
        if (dot) dot.setAttribute('fill', WARNA[s.status]);
        var ring = document.getElementById('ring-'+s.id);
        if (ring) ring.setAttribute('stroke', WARNA[s.status]);
    });
    renderDaftar();
}

renderDaftar();
setInterval(simulasiUpdate, 4000);
setInterval(updateWaktu, 1000);
updateWaktu();
</script>

</body>
</html>
