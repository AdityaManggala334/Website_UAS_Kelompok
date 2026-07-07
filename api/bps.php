<?php
// bps.php
// ======================================================
// DATA BPS - LADUSYNC
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

// Fungsi untuk mengambil data dari API BPS menggunakan cURL
function fetchBPS(string $url): ?array {
    if (!function_exists('curl_init')) return null;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT      => 'Ladusync/1.0',
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$raw || $code !== 200) return null;
    $data = json_decode($raw, true);
    return json_last_error() === JSON_ERROR_NONE ? $data : null;
}

// Fungsi untuk mengekstrak baris data dari struktur response BPS
function parseBPS(array $response): array {
    $rows  = $response['data'][1]['data']  ?? [];
    $judul = $response['data'][1]['judul_tabel'] ?? '';
    $result = [];

    foreach ($rows as $row) {
        if (($row['kode_wilayah'] ?? '') === '3300000') continue;
        $clean = fn($v) => floatval(str_replace(['.', ','], ['', '.'], $v));
        $result[] = [
            'wilayah'       => $row['label'] ?? '-',
            'kode'          => $row['kode_wilayah'] ?? '',
            'luas_panen'    => $clean($row['variables']['qjt4tgvtld']['value'] ?? '0'),
            'produktivitas' => $clean($row['variables']['od6zj61thq']['value'] ?? '0'),
            'produksi'      => $clean($row['variables']['mtn492ybb1']['value'] ?? '0'),
        ];
    }
    usort($result, fn($a, $b) => $b['luas_panen'] <=> $a['luas_panen']);
    return ['rows' => $result, 'judul' => $judul];
}

// URL API BPS
$API_URL = 'https://webapi.bps.go.id/v1/api/interoperabilitas/datasource/simdasi/id/25/tahun/2025/id_tabel/ZjZ6MXlacGJNR0JaaHBPRSs0TzNUdz09/wilayah/3300000/key/cc819bdc45f65b22eebcb08f167d0e08';

// Eksekusi pengambilan dan parsing data
$raw      = fetchBPS($API_URL);
$parsed   = $raw ? parseBPS($raw) : ['rows' => [], 'judul' => ''];
$listData = $parsed['rows'];
$judul    = $parsed['judul'];
$hasData  = count($listData) > 0;

// Hitung statistik agregat
$totalLuas     = array_sum(array_column($listData, 'luas_panen'));
$totalProduksi = array_sum(array_column($listData, 'produksi'));
$jumlah        = count($listData);
$terluas       = $listData[0] ?? null;
$rataProduktiv = $jumlah > 0 ? array_sum(array_column($listData, 'produktivitas')) / $jumlah : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Data BPS - Ladusync</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --tanah: #0F1D16;
            --tanah-2: #0A1410;
            --sawah: #2F5233;
            --sawah-light: #4A7050;
            --gabah: #B9843A;
            --gabah-light: #D3A868;
            --kertas: #F5F1E5;
            --kertas-2: #ECE5D3;
            --lempung: #8A7357;
            --ink: #23301F;
            --kritis: #9C4130;
            --pop: #B6FF5E;
            --pop-dim: rgba(182,255,94,0.14);
            --sidebar-w: 248px;
            --topbar-h: 64px;
        }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--kertas);
            color: var(--ink);
            min-height: 100vh;
        }

        h1, h2, h3 {
            font-family: 'Fraunces', serif;
        }
        .font-mono-data { font-family: 'JetBrains Mono', monospace; }
        .live-dot { animation: livePulse 2.2s ease-in-out infinite; }
        @keyframes livePulse { 0%,100%{opacity:1} 50%{opacity:0.25} }

        .profil-wrap:hover .profil-dropdown { display: block; }
        .profil-dropdown {
            display: none; position: absolute; right: 0; top: 100%; margin-top: 8px;
            background: white; border-radius: 4px; min-width: 210px;
            box-shadow: 0 12px 34px rgba(20,32,25,0.20); z-index: 50; overflow: hidden;
            border: 1px solid rgba(138,115,87,0.18);
        }

        /* ===== APP SHELL ===== */
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
            display: flex;
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

        /* MAIN AREA */
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
            flex-shrink: 0;
            margin-left: auto;
        }

        /* ===== CONTENT BPS ===== */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            flex: 1;
            width: 100%;
        }

        /* HERO */
        .hero-section {
            background: linear-gradient(135deg, var(--tanah), var(--sawah));
            padding: 2rem 1.5rem;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
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
        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
        }
        .chip-ok {
            background: rgba(16,185,129,0.15);
            color: #34D399;
        }
        .chip-err {
            background: rgba(239,68,68,0.15);
            color: #F87171;
        }
        .pulse {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #34D399;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* KPI GRID */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        @media (min-width: 768px) {
            .kpi-grid { grid-template-columns: repeat(4, 1fr); gap: 1rem; }
        }
        .kpi-card {
            background: white;
            border: 1px solid rgba(138,115,87,0.12);
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(28,43,30,0.04);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(28,43,30,0.08);
        }
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 3px;
            height: 100%;
            background: var(--sawah);
        }
        .kpi-card.gold::before { background: var(--gabah); }
        .kpi-card.blue::before { background: #3B82F6; }
        .kpi-card.rose::before { background: #E11D48; }
        .kpi-label {
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #94A3B8;
        }
        .kpi-value {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--ink);
            margin: 4px 0 2px;
            line-height: 1;
            letter-spacing: -0.03em;
        }
        @media (min-width: 640px) {
            .kpi-value { font-size: 1.5rem; }
        }
        .kpi-sub {
            font-size: 0.6rem;
            color: var(--lempung);
        }

        /* PANEL */
        .panel {
            background: white;
            border: 1px solid rgba(138,115,87,0.12);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(28,43,30,0.04);
            margin-bottom: 1.25rem;
        }
        .panel-head {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(138,115,87,0.08);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        @media (min-width: 640px) {
            .panel-head { padding: 1rem 1.25rem; }
        }
        .panel-title {
            font-family: 'Fraunces', serif;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--ink);
        }
        .panel-sub {
            font-size: 0.65rem;
            color: #94A3B8;
            margin-top: 2px;
        }
        .panel-body {
            padding: 0.75rem;
        }
        @media (min-width: 640px) {
            .panel-body { padding: 1rem 1.25rem; }
        }

        /* MAIN GRID */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        @media (min-width: 900px) {
            .main-grid { grid-template-columns: 1fr 320px; gap: 1.25rem; }
        }

        /* BAR CHART */
        .bar-item { margin-bottom: 0.8rem; }
        .bar-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 4px;
            flex-wrap: wrap;
            gap: 4px;
        }
        .bar-name {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--ink);
        }
        .bar-val {
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--sawah);
        }
        .bar-track {
            height: 4px;
            background: #F1F5F9;
            border-radius: 4px;
        }
        .bar-fill {
            height: 4px;
            background: linear-gradient(90deg, var(--sawah), var(--sawah-light));
            border-radius: 4px;
            transition: width 0.7s ease;
        }

        /* TABEL */
        .tbl-wrap {
            background: white;
            border: 1px solid rgba(138,115,87,0.12);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(28,43,30,0.04);
            margin-bottom: 1rem;
        }
        .tbl-head {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(138,115,87,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        @media (min-width: 640px) {
            .tbl-head { padding: 1rem 1.25rem; }
        }
        .scroll-body {
            max-height: 400px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .scroll-body::-webkit-scrollbar {
            width: 4px;
        }
        .scroll-body::-webkit-scrollbar-thumb {
            background: rgba(138,115,87,0.3);
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }
        thead th {
            padding: 8px 12px;
            text-align: left;
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #94A3B8;
            background: #FAFAFA;
            border-bottom: 1px solid rgba(138,115,87,0.08);
            position: sticky;
            top: 0;
            z-index: 2;
        }
        @media (min-width: 640px) {
            thead th { padding: 10px 16px; font-size: 0.68rem; }
        }
        thead th.r { text-align: right; }
        tbody tr {
            border-bottom: 1px solid rgba(138,115,87,0.04);
            transition: background 0.15s;
        }
        tbody tr:hover {
            background: rgba(47,82,51,0.03);
        }
        tbody td {
            padding: 8px 12px;
            font-size: 0.7rem;
        }
        @media (min-width: 640px) {
            tbody td { padding: 10px 16px; font-size: 0.82rem; }
        }
        .mono {
            font-variant-numeric: tabular-nums;
            font-weight: 600;
            color: var(--sawah);
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
        }
        .idx {
            color: #CBD5E1;
            font-size: 0.65rem;
        }
        .rank-badge {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 5px;
            text-align: center;
            line-height: 20px;
            font-size: 0.6rem;
            font-weight: 800;
        }
        .rank-1 { background: #FEF3C7; color: #92400E; }
        .rank-2 { background: #F1F5F9; color: #475569; }
        .rank-3 { background: #FFF7ED; color: #9A3412; }

        /* ERROR BOX */
        .err-box {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
        }
        .err-box h3 {
            color: #991B1B;
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .err-box p {
            font-size: 0.82rem;
            color: #B91C1C;
        }

        /* CANVAS */
        canvas {
            width: 100% !important;
            height: auto !important;
            max-height: 220px;
        }

        /* FOOTER */
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

        /* ===== RESPONSIVE SIDEBAR ===== */
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
        }

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
            .sidebar.collapsed .sidebar-close-btn {
                display: none !important;
            }
            .sidebar:not(.open) .sidebar-close-btn {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .topbar { padding: 0 14px; height: 56px; }
            .topbar-brand.hidden-sm { display: none; }
            .kpi-grid { grid-template-columns: repeat(2,1fr) !important; gap: 6px !important; }
            .kpi-card { padding: 0.75rem !important; }
            .kpi-value { font-size: 1rem !important; }
            .main-grid { grid-template-columns: 1fr !important; }
            .profil-name { display: none !important; }
            .sidebar-toggle-hamburger { width: 34px; height: 34px; }
            .hero-title { font-size: 1.2rem; }
        }

        @media (max-width: 480px) {
            .topbar { padding: 0 10px; height: 50px; }
            .sidebar { width: 280px !important; }
            .kpi-grid { grid-template-columns: 1fr 1fr !important; gap: 4px !important; }
            table { min-width: 400px; }
            tbody td { padding: 6px 8px; font-size: 0.65rem; }
            thead th { padding: 6px 8px; font-size: 0.55rem; }
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

    <a href="peta.php" class="nav-link">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
      </svg>
      <span class="nav-text">Peta</span>
    </a>

    <a href="bps.php" class="nav-link active">
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

    <div class="sidebar-section-label">Edukasi</div>
    <a href="konten_edukasi.php" class="nav-link">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 6h16M4 12h16M4 18h10"/>
        <rect x="2" y="2" width="20" height="20" rx="2"/>
      </svg>
      <span class="nav-text">Konten Edukasi</span>
    </a>

    <?php if ($is_logged_in && $user_role === 'administrator'): ?>
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
        Data BPS Ladusync
      </span>
    </div>

    <!-- ===== KANAN ATAS: PROFIL USER ===== -->
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
            <div class="text-xs text-slate-500 mt-0.5 capitalize"><?= str_replace('_', ' ', $user_role ?? 'guest') ?></div>
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

<!-- ===== KONTEN BPS ===== -->
<main class="container">

    <!-- HERO -->
    <section class="hero-section">
        <div class="hero-content">
            <div>
                <h1 class="hero-title">Data <span>Pertanian BPS</span></h1>
                <p class="hero-subtitle"><?= htmlspecialchars($judul ?: 'Luas Panen, Produktivitas, dan Produksi Padi - Jawa Tengah 2025'); ?></p>
                <?php if ($hasData): ?>
                <span class="status-chip chip-ok">
                    <span class="pulse"></span>
                    Terhubung ke API BPS · <?= $jumlah; ?> wilayah
                </span>
                <?php else: ?>
                <span class="status-chip chip-err">❌ Tidak dapat memuat data BPS</span>
                <?php endif; ?>
            </div>
            <div style="color:rgba(255,255,255,0.3);font-size:0.7rem;text-align:right;">
                <div>Update: <?= date('d M Y'); ?></div>
                <div style="font-size:0.6rem;margin-top:2px;">Sumber: BPS Jawa Tengah</div>
            </div>
        </div>
    </section>

    <?php if (!$hasData): ?>
    <!-- ERROR STATE -->
    <div class="err-box">
        <h3>⚠️ Gagal Memuat Data</h3>
        <p>Tidak dapat terhubung ke API BPS. Periksa koneksi internet server atau coba lagi nanti.</p>
    </div>
    <?php else: ?>

    <!-- KPI CARDS -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Total Luas Panen</div>
            <div class="kpi-value"><?= number_format($totalLuas, 0, ',', '.'); ?></div>
            <div class="kpi-sub">Hektar (Ha)</div>
        </div>
        <div class="kpi-card rose">
            <div class="kpi-label">Total Produksi Padi</div>
            <div class="kpi-value"><?= number_format($totalProduksi, 0, ',', '.'); ?></div>
            <div class="kpi-sub">Ton GKG</div>
        </div>
        <div class="kpi-card gold">
            <div class="kpi-label">Rata-rata Produktivitas</div>
            <div class="kpi-value"><?= number_format($rataProduktiv, 2, ',', '.'); ?></div>
            <div class="kpi-sub">Ku / Ha</div>
        </div>
        <div class="kpi-card blue">
            <div class="kpi-label">Luas Panen Terbesar</div>
            <div class="kpi-value" style="font-size:0.9rem;margin-top:4px;"><?= htmlspecialchars($terluas['wilayah'] ?? '-'); ?></div>
            <div class="kpi-sub"><?= number_format($terluas['luas_panen'] ?? 0, 0, ',', '.'); ?> Ha</div>
        </div>
    </div>

    <!-- CHART + TOP 5 -->
    <div class="main-grid">
        <!-- Chart -->
        <div class="panel">
            <div class="panel-head">
                <div>
                    <div class="panel-title">Distribusi Luas Panen</div>
                    <div class="panel-sub">20 wilayah tertinggi · Jawa Tengah 2025</div>
                </div>
            </div>
            <div class="panel-body">
                <canvas id="bpsChart" height="200"></canvas>
            </div>
        </div>

        <!-- Top 5 -->
        <div class="panel">
            <div class="panel-head">
                <div>
                    <div class="panel-title">🏆 Peringkat Teratas</div>
                    <div class="panel-sub">5 kabupaten / kota terluas</div>
                </div>
            </div>
            <div class="panel-body">
                <?php
                $top5   = array_slice($listData, 0, 5);
                $maxVal = $top5[0]['luas_panen'] ?? 1;
                foreach ($top5 as $i => $item):
                    $pct = $maxVal > 0 ? ($item['luas_panen'] / $maxVal * 100) : 0;
                ?>
                <div class="bar-item">
                    <div class="bar-row">
                        <span class="bar-name"><?= $i+1 ?>. <?= htmlspecialchars($item['wilayah']); ?></span>
                        <span class="bar-val"><?= number_format($item['luas_panen'], 0, ',', '.'); ?> Ha</span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width:<?= round($pct) ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- TABEL DATA LENGKAP -->
    <div class="tbl-wrap">
        <div class="tbl-head">
            <div>
                <div class="panel-title">Data Lengkap Seluruh Kabupaten / Kota</div>
                <div class="panel-sub">Diurutkan berdasarkan luas panen terbesar · Sumber: BPS Jawa Tengah 2025</div>
            </div>
            <span style="font-size:0.6rem;color:#94A3B8;font-variant-numeric:tabular-nums;">
                <?= count($listData) ?> wilayah
            </span>
        </div>
        <div class="scroll-body">
            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table>
                    <thead>
                        <tr>
                            <th style="width:40px;">No</th>
                            <th>Kabupaten / Kota</th>
                            <th class="r">Luas Panen (Ha)</th>
                            <th class="r">Produktivitas (ku/ha)</th>
                            <th class="r">Produksi (Ton)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($listData as $i => $item): ?>
                    <tr>
                        <td>
                            <?php if ($i < 3): ?>
                            <span class="rank-badge rank-<?= $i+1 ?>"><?= $i+1 ?></span>
                            <?php else: ?>
                            <span class="idx"><?= $i+1 ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:600;"><?= htmlspecialchars($item['wilayah']); ?></td>
                        <td class="mono"><?= number_format($item['luas_panen'], 2, ',', '.'); ?></td>
                        <td class="mono"><?= number_format($item['produktivitas'], 2, ',', '.'); ?></td>
                        <td class="mono"><?= number_format($item['produksi'], 2, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php endif; ?>

</main>

<!-- ===== FOOTER ===== -->
<footer class="main-footer">
    &copy; 2026 <span>Ladusync</span> — Data Pertanian BPS Jawa Tengah
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

<!-- ===== JAVASCRIPT CHART ===== -->
<?php if ($hasData): ?>
<script>
    const chartData = <?= json_encode(array_slice($listData, 0, 20)); ?>;
    const labels    = chartData.map(d => d.wilayah);
    const values    = chartData.map(d => d.luas_panen);

    const ctx  = document.getElementById('bpsChart').getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 220);
    grad.addColorStop(0, '#2F5233');
    grad.addColorStop(1, '#D3A868');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Luas Panen (Ha)',
                data: values,
                backgroundColor: grad,
                borderRadius: 4,
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ' ' + ctx.parsed.y.toLocaleString('id-ID') + ' Ha'
                    },
                    titleFont: { size: 11 },
                    bodyFont: { size: 10 }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(47,82,51,0.06)' },
                    ticks: {
                        callback: (v) => v >= 1000 ? (v/1000).toLocaleString('id-ID') + 'rb' : v.toLocaleString('id-ID'),
                        font: { size: 9 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { 
                        font: { size: 8 }, 
                        maxRotation: 45, 
                        minRotation: 35 
                    }
                }
            },
            layout: {
                padding: { left: 5, right: 5, top: 10, bottom: 5 }
            }
        }
    });

    // Resize chart on window resize
    window.addEventListener('resize', function() {
        const chart = Chart.getChart('bpsChart');
        if (chart) chart.resize();
    });
</script>
<?php endif; ?>

</body>
</html>
