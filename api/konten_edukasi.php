<?php
// api/konten_edukasi.php
// ======================================================
// HALAMAN KONTEN EDUKASI - LADUSYNC
// ======================================================

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

// Ambil data user
$nama_user = $namaDepan ?? 'User';
$role = $role ?? 'user';
$nama_lengkap = $namaLengkap ?? 'User';

// Data video edukasi (bisa diambil dari database nanti)
$videos = [
    [
        'id' => 1,
        'title' => '4 Sistem Hidroponik untuk Pemula',
        'youtube_id' => 'F802LVONUvA',
        'description' => 'Penjelasan mendalam tentang cara memulai sistem hidroponik secara efisien untuk pemula.',
        'category' => 'Hidroponik',
        'duration' => '15:20'
    ],
    [
        'id' => 2,
        'title' => 'Manajemen Pupuk Organik yang Efektif',
        'youtube_id' => '1NzSYee053U',
        'description' => 'Tips dan trik menggunakan pupuk organik untuk hasil panen maksimal.',
        'category' => 'Pupuk',
        'duration' => '12:45'
    ],
    [
        'id' => 3,
        'title' => 'Tips Pengendalian Hama Padi Secara Alami',
        'youtube_id' => 'BdD3y7Ese_g',
        'description' => 'Cara mengendalikan hama pada tanaman padi tanpa menggunakan bahan kimia berbahaya.',
        'category' => 'Hama',
        'duration' => '18:30'
    ],
    [
        'id' => 4,
        'title' => 'Sistem Irigasi Tetes untuk Lahan Kering',
        'youtube_id' => 'r5Kj0iDl6xY',
        'description' => 'Solusi irigasi hemat air untuk lahan pertanian di daerah kering.',
        'category' => 'Irigasi',
        'duration' => '20:15'
    ],
    [
        'id' => 5,
        'title' => 'Cara Menanam Cabai di Polybag',
        'youtube_id' => 'kVl5yZl5gvU',
        'description' => 'Panduan lengkap menanam cabai di polybag untuk hasil melimpah.',
        'category' => 'Tanaman',
        'duration' => '10:50'
    ],
    [
        'id' => 6,
        'title' => 'Teknik Panen dan Pascapanen yang Benar',
        'youtube_id' => 'q2yUqKk0lVo',
        'description' => 'Cara panen yang benar dan penanganan pascapanen untuk menjaga kualitas hasil.',
        'category' => 'Panen',
        'duration' => '14:00'
    ]
];

// Video yang ditampilkan utama (pertama)
$main_video = $videos[0] ?? null;
$other_videos = array_slice($videos, 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Konten Edukasi — Ladusync</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
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

    * { margin:0; padding:0; box-sizing:border-box; }
    html { scroll-behavior: smooth; }
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
    .app-shell { display: flex; min-height: 100vh; }

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

    .sidebar.collapsed {
        transform: translateX(-100%);
        width: 0;
    }

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

    /* CONTENT */
    .content {
        flex: 1;
        padding: 1.5rem;
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
    }

    /* ===== VIDEO CARDS ===== */
    .video-card {
        background: white;
        border-radius: 16px;
        border: 1px solid rgba(138,115,87,0.12);
        box-shadow: 0 1px 3px rgba(28,43,30,0.05), 0 8px 24px rgba(28,43,30,0.06);
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .video-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(28,43,30,0.10); }

    .video-card .thumbnail {
        position: relative;
        padding-bottom: 56.25%;
        background: #1a1a2e;
        overflow: hidden;
    }
    .video-card .thumbnail img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .video-card .thumbnail .play-btn {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 56px;
        height: 56px;
        background: rgba(255,255,255,0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #EF4444;
        transition: all 0.3s ease;
        font-size: 1.2rem;
    }
    .video-card .thumbnail .play-btn:hover {
        transform: translate(-50%, -50%) scale(1.1);
        background: white;
    }
    .video-card .thumbnail .duration {
        position: absolute;
        bottom: 8px;
        right: 8px;
        background: rgba(0,0,0,0.75);
        color: white;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 4px;
        font-family: 'JetBrains Mono', monospace;
    }
    .video-card .body {
        padding: 1rem 1.25rem 1.25rem;
    }
    .video-card .body .category {
        display: inline-block;
        font-size: 0.6rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 2px 10px;
        border-radius: 20px;
        background: rgba(47,82,51,0.08);
        color: var(--sawah);
        border: 1px solid rgba(47,82,51,0.12);
        margin-bottom: 6px;
    }
    .video-card .body .title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .video-card .body .desc {
        font-size: 0.75rem;
        color: #6B5F4F;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: 10px;
    }
    .video-card .body .btn-watch {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 16px;
        border-radius: 8px;
        font-size: 0.7rem;
        font-weight: 700;
        background: linear-gradient(135deg, var(--sawah), var(--sawah-light));
        color: white;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .video-card .body .btn-watch:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(47,82,51,0.25);
    }

    /* MAIN VIDEO */
    .main-video {
        background: white;
        border-radius: 16px;
        border: 1px solid rgba(138,115,87,0.12);
        box-shadow: 0 1px 3px rgba(28,43,30,0.05), 0 8px 24px rgba(28,43,30,0.06);
        overflow: hidden;
    }
    .main-video .thumbnail {
        position: relative;
        padding-bottom: 56.25%;
        background: #1a1a2e;
    }
    .main-video .thumbnail iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
    }
    .main-video .body {
        padding: 1.5rem;
    }
    .main-video .body .title {
        font-family: 'Fraunces', serif;
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: 6px;
    }
    .main-video .body .desc {
        font-size: 0.85rem;
        color: #6B5F4F;
        margin-bottom: 12px;
    }

    /* ===== RESPONSIVE ===== */
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
        .content { padding: 1rem; }
        .profil-name { display: none !important; }
        .sidebar-toggle-hamburger { width: 34px; height: 34px; }
        .main-video .body { padding: 1rem; }
        .main-video .body .title { font-size: 1.1rem; }
    }

    @media (max-width: 480px) {
        .topbar { padding: 0 10px; height: 50px; }
        .sidebar { width: 280px !important; }
        .content { padding: 0.75rem; }
    }

    /* Sidebar collapse animation */
    .sidebar.collapsed .nav-text,
    .sidebar.collapsed .sidebar-section-label,
    .sidebar.collapsed .logo-text { opacity: 0; transition: opacity 0.15s ease; }
    .sidebar:not(.collapsed) .nav-text,
    .sidebar:not(.collapsed) .sidebar-section-label,
    .sidebar:not(.collapsed) .logo-text { opacity: 1; transition: opacity 0.15s ease 0.1s; }

    .page-header {
        margin-bottom: 1.5rem;
    }
    .page-header h1 {
        font-family: 'Fraunces', serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--sawah);
    }
    .page-header p {
        font-size: 0.85rem;
        color: #6B5F4F;
        margin-top: 2px;
    }

    .badge-popular {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 0.6rem;
        font-weight: 700;
        background: #FEF3C7;
        color: #92400E;
        border: 1px solid #FDE68A;
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

    <!-- ===== MENU EDUKASI ===== -->
    <div class="sidebar-section-label">Edukasi</div>
    <a href="konten_edukasi.php" class="nav-link active">
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
  </nav>

  <!-- ===== TOMBOL KELUAR ===== -->
  <div class="sidebar-bottom">
    <?php if ($is_logged_in): ?>
      <a href="../logout.php" class="nav-link nav-link-logout">
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
      <!-- Tombol hamburger untuk membuka sidebar -->
      <button class="sidebar-toggle-hamburger" id="sidebarToggleHamburger" aria-label="Buka menu">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="12" x2="21" y2="12"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
          <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
      
      <!-- Tombol toggle untuk collapse/expand di desktop -->
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

    <!-- ===== KANAN ATAS: PROFIL USER ===== -->
    <div class="nav-right">
      <div class="profil-wrap relative">
        <button class="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium bg-transparent border-none cursor-pointer" style="color:var(--ink);">
          <div class="w-7 h-7 rounded-md flex items-center justify-center font-bold text-xs" style="background:rgba(47,82,51,0.12);color:var(--sawah);">
            <?= strtoupper(substr($nama_user, 0, 1)) ?>
          </div>
          <span class="profil-name hidden sm:inline"><?= htmlspecialchars($nama_user) ?></span>
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>

        <div class="profil-dropdown">
          <div class="px-4 py-3 border-b" style="background:linear-gradient(135deg,#F5F1E5,#ECE5D3);border-color:rgba(138,115,87,0.18);">
            <div class="font-bold text-sm font-display" style="color:var(--sawah);"><?= htmlspecialchars($nama_lengkap) ?></div>
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
            <a href="konten_edukasi.php" class="flex items-center gap-2 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition-colors no-underline">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 6h16M4 12h16M4 18h10"/>
                <rect x="2" y="2" width="20" height="20" rx="2"/>
              </svg>
              Edukasi
            </a>
            <a href="../logout.php" class="flex items-center gap-2 px-4 py-3 text-sm hover:bg-red-50 transition-colors no-underline" style="color:var(--kritis);">
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

    <!-- Header -->
    <div class="page-header">
      <h1>📚 Konten Edukasi Pertanian</h1>
      <p>Pelajari teknik pertanian modern melalui video tutorial pilihan</p>
    </div>

    <!-- ===== MAIN VIDEO ===== -->
    <?php if ($main_video): ?>
    <div class="main-video mb-6">
      <div class="thumbnail">
        <iframe 
          src="https://www.youtube.com/embed/<?= $main_video['youtube_id'] ?>" 
          title="<?= htmlspecialchars($main_video['title']) ?>"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
          allowfullscreen>
        </iframe>
      </div>
      <div class="body">
        <div class="flex items-center gap-2 mb-2 flex-wrap">
          <span class="category"><?= htmlspecialchars($main_video['category']) ?></span>
          <span class="badge-popular">🔥 Populer</span>
        </div>
        <h2 class="title"><?= htmlspecialchars($main_video['title']) ?></h2>
        <p class="desc"><?= htmlspecialchars($main_video['description']) ?></p>
        <a href="https://youtu.be/<?= $main_video['youtube_id'] ?>" target="_blank" class="btn-watch">
          <i class="fab fa-youtube"></i> Buka di YouTube
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- ===== DAFTAR VIDEO LAINNYA ===== -->
    <h3 class="font-display text-lg font-semibold mb-4" style="color:var(--sawah);">
      <i class="fas fa-play-circle text-gabah mr-2"></i> Video Lainnya
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ($other_videos as $video): ?>
      <div class="video-card">
        <div class="thumbnail">
          <img 
            src="https://img.youtube.com/vi/<?= $video['youtube_id'] ?>/mqdefault.jpg" 
            alt="<?= htmlspecialchars($video['title']) ?>"
            loading="lazy"
            onerror="this.src='https://placehold.co/320x180/1a1a2e/white?text=Video'"
          >
          <div class="play-btn">
            <i class="fas fa-play"></i>
          </div>
          <?php if (!empty($video['duration'])): ?>
            <span class="duration"><?= $video['duration'] ?></span>
          <?php endif; ?>
        </div>
        <div class="body">
          <span class="category"><?= htmlspecialchars($video['category']) ?></span>
          <h4 class="title"><?= htmlspecialchars($video['title']) ?></h4>
          <p class="desc"><?= htmlspecialchars($video['description']) ?></p>
          <a href="https://youtu.be/<?= $video['youtube_id'] ?>" target="_blank" class="btn-watch">
            <i class="fab fa-youtube"></i> Tonton
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Jika tidak ada video -->
    <?php if (empty($videos)): ?>
    <div class="text-center py-12">
      <div class="text-6xl mb-4">📹</div>
      <h3 class="text-xl font-bold text-slate-500">Belum Ada Video</h3>
      <p class="text-slate-400 text-sm">Konten edukasi akan segera ditambahkan</p>
    </div>
    <?php endif; ?>

  </div>

  <!-- ===== FOOTER ===== -->
  <footer style="background:var(--tanah);color:rgba(245,241,229,0.55);">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-8 pb-6">
      <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-xs">
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
    
    const isMobile = window.innerWidth <= 992;
    const STORAGE_KEY = 'sidebar_collapsed';

    function isSidebarOpen() { return sidebar.classList.contains('open'); }

    function openSidebar() {
        sidebar.classList.add('open');
        sidebar.classList.remove('collapsed');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
        if (!isMobile) localStorage.setItem(STORAGE_KEY, 'false');
        updateMainArea();
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        sidebar.classList.add('collapsed');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
        if (!isMobile) localStorage.setItem(STORAGE_KEY, 'true');
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
        overlay.addEventListener('click', function() { closeSidebar(); });
    }

    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            const width = window.innerWidth;
            const isDesktop = width > 992;
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
        if (e.key === 'Escape' && isSidebarOpen()) { closeSidebar(); }
    });

    loadSidebarState();
});
</script>

</body>
</html>