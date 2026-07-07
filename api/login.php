<?php
// KONFIGURASI AWAL DAN INISIALISASI
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Sembunyikan error langsung demi keamanan Vercel

// Memanggil konfigurasi sesi global Ladusync dari subfolder api
require_once __DIR__ . '/koneksi.php';
global $conn;

// Menentukan tab yang aktif berdasarkan parameter URL (register atau login, default login)
$tab = (isset($_GET['tab']) && $_GET['tab'] === 'register') ? 'register' : 'login';

// RESET ACTION (Bypass): Jika dipanggil untuk keluar (logout), hancurkan cookie/session
if (isset($_GET['bypass']) && $_GET['bypass'] === 'true') {
    session_unset();
    if (session_status() === PHP_SESSION_ACTIVE) { 
        session_destroy(); 
    }
    // ✅ PERBAIKAN 1: Gunakan cookie name yang sama dengan proseslogin.php
    setcookie('sm_uid', '', time() - 3600, '/');
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Ladusync — Gerbang Akses Masuk</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            950: '#022C22', 900: '#064E3B', 800: '#065F46',
                            700: '#047857', 600: '#059669', 500: '#10B981',
                            400: '#34D399', 300: '#6EE7B7',
                        }
                    },
                    fontFamily: {
                        sans: ["'Plus Jakarta Sans'", 'sans-serif'],
                    },
                    animation: {
                        'float-slow': 'floatSlow 9s ease-in-out infinite',
                        'float-rev':  'floatSlow 7s ease-in-out infinite reverse',
                        'pulse-dot':  'pulseDot 2.5s ease-in-out infinite',
                        'card-in':    'cardIn 0.5s cubic-bezier(0.22,1,0.36,1) both',
                    },
                    keyframes: {
                        floatSlow: { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-22px)' }},
                        pulseDot:  { '0%,100%': { boxShadow: '0 0 0 3px rgba(52,211,153,0.25)' }, '50%': { boxShadow: '0 0 0 7px rgba(52,211,153,0.08)' }},
                        cardIn:    { from: { opacity: '0', transform: 'translateY(20px)' }, to: { opacity: '1', transform: 'translateY(0)' }},
                    }
                }
            }
        }
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-in  { animation: cardIn 0.5s cubic-bezier(0.22,1,0.36,1) both; }
        .orb-1    { animation: floatSlow 9s ease-in-out infinite; }
        .orb-2    { animation: floatSlow 7s ease-in-out infinite reverse; }
        .live-pulse { animation: pulseDot 2.5s ease-in-out infinite; }
        .bg-grid-white { background-image: linear-gradient(rgba(255,255,255,0.05) 1px,transparent 1px), linear-gradient(90deg,rgba(255,255,255,0.05) 1px,transparent 1px); background-size:50px 50px; }
        .strack { height:4px; background:rgba(6,78,59,0.08); border-radius:9px; overflow:hidden; margin-top:7px; }
        .sfill  { height:100%; width:0; border-radius:9px; transition:all 0.4s ease; }
    </style>
</head>
<body class="min-h-screen flex overflow-hidden bg-emerald-50">

    <div class="fixed inset-0 z-0 pointer-events-none"
         style="background: radial-gradient(ellipse 70% 70% at 20% -10%, rgba(16,185,129,0.16) 0%, transparent 60%), radial-gradient(ellipse 55% 55% at 85% 100%, rgba(6,78,59,0.12) 0%, transparent 55%), #EEF8F2;">
    </div>

    <div class="orb-1 fixed -top-24 -left-20 w-80 h-80 rounded-full pointer-events-none z-0" style="background:rgba(16,185,129,0.09);filter:blur(60px);"></div>
    <div class="orb-2 fixed -bottom-16 right-3 w-64 h-64 rounded-full pointer-events-none z-0" style="background:rgba(6,78,59,0.08);filter:blur(60px);"></div>

    <div class="hidden md:flex w-5/12 flex-col justify-between p-12 relative overflow-hidden"
         style="background: linear-gradient(150deg, #064E3B 0%, #022C22 100%);">
        <div class="absolute inset-0 bg-grid-white opacity-100 pointer-events-none"></div>
        <div class="absolute inset-0 pointer-events-none"
             style="background: radial-gradient(ellipse 60% 60% at 30% 30%, rgba(16,185,129,0.16) 0%,transparent 65%), radial-gradient(ellipse 40% 40% at 75% 75%, rgba(52,211,153,0.08) 0%,transparent 60%);"></div>

        <div class="relative z-10 flex flex-col h-full justify-between">
            <div>
                <a href="../index.php" class="inline-flex items-center gap-3 mb-10 no-underline group">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 bg-white/10 border border-white/20 group-hover:border-white/40 transition-colors">
                        <svg width="24" height="24" viewBox="0 0 44 44" fill="none">
                            <path d="M22 7C22 7 13 18 13 24C13 29.52 17.03 34 22 34C26.97 34 31 29.52 31 24C31 18 22 7 22 7Z" fill="#10B981"/>
                            <line x1="18" y1="24" x2="26" y2="24" stroke="white" stroke-width="1.8" stroke-linecap="round"/>
                            <circle cx="18" cy="24" r="1.5" fill="white"/>
                            <circle cx="26" cy="24" r="1.5" fill="white"/>
                            <line x1="22" y1="20" x2="22" y2="28" stroke="white" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xl font-extrabold text-white tracking-tight leading-none">Ladusync</div>
                        <div class="text-xs font-semibold text-emerald-400 mt-1 flex items-center gap-1">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg> Kembali ke Beranda
                        </div>
                    </div>
                </a>

                <h1 class="text-3xl font-extrabold text-white leading-snug tracking-tight mb-3">
                    Ekosistem Tani<br><span class="text-emerald-400">Terintegrasi</span>
                </h1>
                <p class="text-sm leading-relaxed max-w-xs" style="color:rgba(255,255,255,0.5);">
                    Sentralisasi data sensor, kendali petak lahan, manajemen logbook, serta pemesanan alat tani modern.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 my-8">
                <?php foreach ([
                    ['8', 'Sensor IoT'],
                    ['4 Level', 'Akses Sesi'],
                    ['4 dtk', 'Interval Sinkron'],
                    ['99.8%', 'Uptime Node'],
                ] as [$n, $l]): ?>
                <div class="rounded-2xl p-4" style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.10);backdrop-filter:blur(8px);">
                    <div class="text-2xl font-extrabold text-emerald-400 leading-none"><?= $n ?></div>
                    <div class="text-xs font-medium mt-1" style="color:rgba(255,255,255,0.40);"><?= $l ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="flex items-center gap-2">
                <span class="live-pulse inline-block w-2 h-2 bg-emerald-400 rounded-full flex-shrink-0"></span>
                <span class="text-xs font-medium" style="color:rgba(255,255,255,0.35);">Sistem Secure · Data Terenkripsi</span>
            </div>
        </div>
    </div>

    <div class="flex-1 flex items-center justify-center p-6 relative z-10">
        <div class="card-in w-full max-w-sm sm:max-w-md rounded-3xl p-8 sm:p-10"
             style="background:rgba(255,255,255,0.80);backdrop-filter:blur(28px);border:1px solid rgba(255,255,255,0.85);box-shadow:0 4px 6px rgba(6,78,59,0.04),0 24px 60px rgba(6,78,59,0.11),inset 0 1px 0 rgba(255,255,255,0.9);">

            <div class="flex md:hidden items-center justify-between mb-5">
                <a href="../index.php" class="flex items-center gap-2 no-underline">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-emerald-900">
                        <svg width="18" height="18" viewBox="0 0 44 44" fill="none">
                            <path d="M22 7C22 7 13 18 13 24C13 29.52 17.03 34 22 34C26.97 34 31 29.52 31 24C31 18 22 7 22 7Z" fill="#10B981"/>
                        </svg>
                    </div>
                    <span class="text-lg font-extrabold text-emerald-900">Ladusync</span>
                </a>
                <a href="../index.php" class="text-xs font-bold text-slate-400 hover:text-slate-600">Beranda ←</a>
            </div>

            <h2 class="text-2xl font-extrabold text-emerald-950 tracking-tight mb-1">Selamat Datang</h2>
            <p class="text-sm text-slate-400 mb-5">Masuk atau daftar untuk mengakses sistem</p>

            <?php
            if (isset($_GET['error'])) {
                $msg = match($_GET['error']) {
                    'kosong' => 'Mohon isi email dan password.',
                    'salah'  => 'Email atau password salah. Coba lagi.',
                    default  => 'Terjadi kesalahan sistem database.',
                };
                echo '<div class="flex items-center gap-2 text-sm font-medium rounded-xl p-3 mb-4" style="background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
                    . htmlspecialchars($msg) . '</div>';
            }
            if (isset($_GET['sukses']) && $_GET['sukses'] === 'register') {
                echo '<div class="flex items-center gap-2 text-sm font-medium rounded-xl p-3 mb-4" style="background:#F0FDF4;border:1px solid #BBF7D0;color:#166534;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>Registrasi berhasil! Akun dialokasikan sesuai antrean.</div>';
            }
            if (isset($_GET['reg_error'])) {
                $re_msg = match($_GET['reg_error']) {
                    'kosong' => 'Semua kolom wajib diisi.',
                    'email_invalid' => 'Format email tidak valid.',
                    'pendek' => 'Password minimal 6 karakter.',
                    'beda' => 'Konfirmasi password tidak cocok.',
                    'duplikat' => 'Email sudah terdaftar sebelumnya.',
                    default => 'Gagal mendaftar, coba lagi.'
                };
                echo '<div class="flex items-center gap-2 text-sm font-medium rounded-xl p-3 mb-4" style="background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'.htmlspecialchars($re_msg).'</div>';
            }
            ?>

            <div class="flex gap-1 p-1 rounded-xl mb-6" style="background:rgba(6,78,59,0.06);">
                <button id="tab-login-btn" onclick="switchTab('login')"
                    class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg text-sm font-semibold transition-all duration-200
                        <?= $tab === 'login' ? 'bg-white text-emerald-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' ?>">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Masuk
                </button>
                <button id="tab-reg-btn" onclick="switchTab('register')"
                    class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg text-sm font-semibold transition-all duration-200
                        <?= $tab === 'register' ? 'bg-white text-emerald-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' ?>">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    Daftar
                </button>
            </div>

            <!-- ===== LOGIN PANEL ===== -->
            <div id="panel-login" class="<?= $tab === 'login' ? '' : 'hidden' ?>">
                <form action="proseslogin.php" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Alamat Email</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none flex">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </span>
                            <input type="email" name="email" placeholder="email@contoh.com" required
                                class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 outline-none transition-all duration-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 placeholder:text-slate-300">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Password</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none flex">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <input type="password" id="lp" name="password" placeholder="Masukkan password" required
                                class="w-full pl-10 pr-10 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 outline-none transition-all duration-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 placeholder:text-slate-300">
                            <button type="button" onclick="toggleVis('lp',this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 hover:text-emerald-600 transition-colors p-0.5 bg-transparent border-none cursor-pointer">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" name="login" class="w-full flex items-center justify-center gap-2 py-3 mt-1 rounded-xl text-sm font-bold text-white transition-all duration-200 hover:-translate-y-0.5" style="background:linear-gradient(135deg,#065F46 0%,#064E3B 100%);box-shadow:0 4px 16px rgba(6,78,59,0.28);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        Masuk ke Dashboard
                    </button>
                </form>
                
                <div class="flex items-center gap-3 my-4"><div class="flex-1 h-px" style="background:rgba(6,78,59,0.09);"></div><span class="text-xs text-slate-400">atau</span><div class="flex-1 h-px" style="background:rgba(6,78,59,0.09);"></div></div>
                <p class="text-center text-sm text-slate-400">Belum punya akun? <button onclick="switchTab('register')" class="font-bold text-emerald-600 hover:text-emerald-700 bg-transparent border-none cursor-pointer text-sm">Daftar sekarang</button></p>
            </div>

            <!-- ===== REGISTER PANEL (DIPERBAIKI) ===== -->
            <div id="panel-register" class="<?= $tab === 'register' ? '' : 'hidden' ?>">
                <form action="prosesregistrasi.php" method="POST" id="regfrm" class="space-y-3">
                    
                    <!-- ✅ PERBAIKAN 2: Nama Depan & Nama Belakang -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Nama Depan</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none flex">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </span>
                                <input type="text" name="nama_depan" placeholder="Budi" required
                                    class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 outline-none transition-all duration-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 placeholder:text-slate-300">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Nama Belakang</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none flex">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </span>
                                <input type="text" name="nama_belakang" placeholder="Santoso"
                                    class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 outline-none transition-all duration-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 placeholder:text-slate-300">
                            </div>
                        </div>
                    </div>

                    <!-- ✅ PERBAIKAN 3: Tambah field username -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Username</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none flex">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </span>
                            <input type="text" name="username" placeholder="username_unik" required
                                class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 outline-none transition-all duration-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 placeholder:text-slate-300">
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Email</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none flex">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </span>
                            <input type="email" name="email" placeholder="email@contoh.com" required
                                class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 outline-none transition-all duration-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 placeholder:text-slate-300">
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Password</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none flex">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <input type="password" id="rp" name="password" placeholder="Min. 6 karakter" oninput="chkStr(this.value)" required
                                class="w-full pl-9 pr-10 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 outline-none transition-all duration-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 placeholder:text-slate-300">
                            <button type="button" onclick="toggleVis('rp',this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 hover:text-emerald-600 transition-colors p-0.5 bg-transparent border-none cursor-pointer">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <div class="strack"><div id="sf" class="sfill"></div></div>
                        <p id="sl" class="text-xs text-slate-400 mt-1"></p>
                    </div>

                    <!-- ✅ PERBAIKAN 4: Konfirmasi Password (name="konfirm") -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Konfirmasi Password</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none flex">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <input type="password" id="rk" name="konfirm" placeholder="Ulangi password" required
                                class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 outline-none transition-all duration-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 placeholder:text-slate-300">
                        </div>
                        <p id="mh" class="text-xs mt-1"></p>
                    </div>

                    <div class="p-3 bg-emerald-500/10 rounded-xl border border-emerald-500/20 text-[11px] text-emerald-400">
                        <i class="fas fa-info-circle mr-1"></i> Penentuan Hak Akses Berputar Pintar Terjadwal (Admin, Petugas, Petugas Lapangan, User).
                    </div>

                    <button type="submit" name="register" class="w-full flex items-center justify-center gap-2 py-3 mt-1 rounded-xl text-sm font-bold text-white transition-all duration-200 hover:-translate-y-0.5" style="background:linear-gradient(135deg,#065F46 0%,#064E3B 100%);box-shadow:0 4px 16px rgba(6,78,59,0.28);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                        Buat Akun Sekarang
                    </button>
                </form>
                
                <div class="flex items-center gap-3 my-4"><div class="flex-1 h-px" style="background:rgba(6,78,59,0.09);"></div><span class="text-xs text-slate-400">atau</span><div class="flex-1 h-px" style="background:rgba(6,78,59,0.09);"></div></div>
                <p class="text-center text-sm text-slate-400">Sudah punya akun? <button onclick="switchTab('login')" class="font-bold text-emerald-600 hover:text-emerald-700 bg-transparent border-none cursor-pointer font-sans text-sm">Masuk di sini</button></p>
            </div>
        </div>
    </div>

    <script>
        function switchTab(t) {
            var isLogin = t === 'login';
            document.getElementById('panel-login').classList.toggle('hidden', !isLogin);
            document.getElementById('panel-register').classList.toggle('hidden', isLogin);
            var lb = document.getElementById('tab-login-btn');
            var rb = document.getElementById('tab-reg-btn');
            lb.className = lb.className.replace(/bg-white text-emerald-900 shadow-sm|text-slate-500 hover:text-slate-700/g, '');
            rb.className = rb.className.replace(/bg-white text-emerald-900 shadow-sm|text-slate-500 hover:text-slate-700/g, '');
            lb.classList.add(isLogin  ? 'bg-white' : 'text-slate-500', isLogin  ? 'text-emerald-900' : 'hover:text-slate-700', isLogin  ? 'shadow-sm' : '');
            rb.classList.add(!isLogin ? 'bg-white' : 'text-slate-500', !isLogin ? 'text-emerald-900' : 'hover:text-slate-700', !isLogin ? 'shadow-sm' : '');
        }

        function toggleVis(id, btn) {
            var el = document.getElementById(id);
            el.type = el.type === 'password' ? 'text' : 'password';
            btn.innerHTML = el.type === 'text'
                ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
                : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        }

        function chkStr(p) {
            var s = 0;
            if (p.length >= 6) s++;
            if (/[A-Z]/.test(p)) s++;
            if (/[0-9]/.test(p)) s++;
            if (/[^A-Za-z0-9]/.test(p)) s++;
            var c = ['', '#EF4444', '#F97316', '#EAB308', '#10B981'];
            var l = ['', 'Sangat Lemah', 'Lemah', 'Sedang', 'Kuat!'];
            var sfill = document.querySelector('.sfill');
            if (sfill) {
                sfill.style.width = ['0%', '25%', '50%', '75%', '100%'][s] || '0%';
                sfill.style.background = c[s] || 'transparent';
            }
            document.getElementById('sl').textContent = p.length ? (l[s] || '') : '';
        }

        document.getElementById('rk').addEventListener('input', function () {
            var h = document.getElementById('mh');
            if (!this.value) { h.textContent = ''; return; }
            if (this.value === document.getElementById('rp').value) {
                h.textContent = '✓ Password cocok'; h.style.color = '#059669';
            } else {
                h.textContent = 'Belum cocok'; h.style.color = '#EF4444';
            }
        });

        document.getElementById('regfrm').addEventListener('submit', function (e) {
            if (document.getElementById('rp').value !== document.getElementById('rk').value) {
                e.preventDefault();
                alert('Password dan konfirmasi tidak cocok!');
            }
        });
    </script>
</body>
</html>