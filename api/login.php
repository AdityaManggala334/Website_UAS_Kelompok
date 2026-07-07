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
        
        /* Tab Button Styles */
        .tab-btn {
            flex: 1;
            padding: 10px 8px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .tab-btn-inactive {
            background: transparent !important;
            color: #94A3B8 !important;
            box-shadow: none !important;
        }
        .tab-btn-inactive:hover {
            background: rgba(47,82,51,0.04) !important;
            color: #23301F !important;
        }
        .tab-btn-active {
            background: white !important;
            color: #2F5233 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
        }
        
        input:focus {
            border-color: #2F5233 !important;
            box-shadow: 0 0 0 3px rgba(47,82,51,0.08) !important;
        }
        
        @keyframes pulseDot {
            0%, 100% { box-shadow: 0 0 0 3px rgba(211,168,104,0.25); }
            50% { box-shadow: 0 0 0 7px rgba(211,168,104,0.08); }
        }
        @keyframes floatSlow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-22px); }
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen flex overflow-hidden" style="background:#F5F1E5;">

    <div class="fixed inset-0 z-0 pointer-events-none"
         style="background: radial-gradient(ellipse 70% 70% at 20% -10%, rgba(47,82,51,0.12) 0%, transparent 60%), radial-gradient(ellipse 55% 55% at 85% 100%, rgba(185,132,58,0.10) 0%, transparent 55%), #F5F1E5;">
    </div>

    <div class="orb-1 fixed -top-24 -left-20 w-80 h-80 rounded-full pointer-events-none z-0" style="background:rgba(47,82,51,0.08);filter:blur(60px);"></div>
    <div class="orb-2 fixed -bottom-16 right-3 w-64 h-64 rounded-full pointer-events-none z-0" style="background:rgba(185,132,58,0.07);filter:blur(60px);"></div>

    <div class="hidden md:flex w-5/12 flex-col justify-between p-12 relative overflow-hidden"
         style="background: linear-gradient(150deg, #0F1D16 0%, #0A1410 100%);">
        <div class="absolute inset-0 bg-grid-white opacity-100 pointer-events-none"></div>
        <div class="absolute inset-0 pointer-events-none"
             style="background: radial-gradient(ellipse 60% 60% at 30% 30%, rgba(185,132,58,0.12) 0%,transparent 65%), radial-gradient(ellipse 40% 40% at 75% 75%, rgba(211,168,104,0.06) 0%,transparent 60%);"></div>

        <div class="relative z-10 flex flex-col h-full justify-between">
            <div>
                <a href="../index.html" class="inline-flex items-center gap-3 mb-10 no-underline group">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 bg-white/10 border border-white/20 group-hover:border-white/40 transition-colors">
                        <svg width="24" height="24" viewBox="0 0 44 44" fill="none">
                            <path d="M22 7C22 7 13 18 13 24C13 29.52 17.03 34 22 34C26.97 34 31 29.52 31 24C31 18 22 7 22 7Z" fill="#B6FF5E"/>
                            <line x1="18" y1="24" x2="26" y2="24" stroke="white" stroke-width="1.8" stroke-linecap="round"/>
                            <circle cx="18" cy="24" r="1.5" fill="white"/>
                            <circle cx="26" cy="24" r="1.5" fill="white"/>
                            <line x1="22" y1="20" x2="22" y2="28" stroke="white" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xl font-extrabold text-white tracking-tight leading-none">Ladusync</div>
                        <div class="text-xs font-semibold mt-1 flex items-center gap-1" style="color:#D3A868;">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg> Kembali ke Landing page
                        </div>
                    </div>
                </a>

                <h1 class="text-3xl font-extrabold text-white leading-snug tracking-tight mb-3">
                    Ekosistem Tani<br><span style="color:#D3A868;">Terintegrasi</span>
                </h1>
                <p class="text-sm leading-relaxed max-w-xs" style="color:rgba(245,241,229,0.5);">
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
                    <div class="text-2xl font-extrabold leading-none" style="color:#D3A868;"><?= $n ?></div>
                    <div class="text-xs font-medium mt-1" style="color:rgba(245,241,229,0.40);"><?= $l ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="flex items-center gap-2">
                <span class="live-pulse inline-block w-2 h-2 rounded-full flex-shrink-0" style="background:#D3A868;"></span>
                <span class="text-xs font-medium" style="color:rgba(245,241,229,0.35);">Sistem Secure · Data Terenkripsi</span>
            </div>
        </div>
    </div>

    <div class="flex-1 flex items-center justify-center p-6 relative z-10">
        <div class="card-in w-full max-w-sm sm:max-w-md rounded-3xl p-8 sm:p-10"
             style="background:rgba(255,255,255,0.92);backdrop-filter:blur(28px);border:1px solid rgba(138,115,87,0.12);box-shadow:0 4px 6px rgba(15,29,22,0.04),0 24px 60px rgba(15,29,22,0.11),inset 0 1px 0 rgba(255,255,255,0.9);">

            <div class="flex md:hidden items-center justify-between mb-5">
                <a href="../index.php" class="flex items-center gap-2 no-underline">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center" style="background:#2F5233;">
                        <svg width="18" height="18" viewBox="0 0 44 44" fill="none">
                            <path d="M22 7C22 7 13 18 13 24C13 29.52 17.03 34 22 34C26.97 34 31 29.52 31 24C31 18 22 7 22 7Z" fill="#B6FF5E"/>
                        </svg>
                    </div>
                    <span class="text-lg font-extrabold" style="color:#2F5233;">Ladusync</span>
                </a>
                <a href="../index.php" class="text-xs font-bold hover:text-slate-600" style="color:#8A7357;">Beranda ←</a>
            </div>

            <h2 class="text-2xl font-extrabold tracking-tight mb-1" style="color:#23301F;">Selamat Datang</h2>
            <p class="text-sm mb-5" style="color:#8A7357;">Masuk atau daftar untuk mengakses sistem</p>

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

            <!-- TABS - Masuk & Daftar Bergantian -->
            <div class="flex gap-1 p-1 rounded-xl mb-6" style="background:rgba(47,82,51,0.06);">
                <button id="tab-login-btn" onclick="switchTab('login')"
                    class="tab-btn <?= $tab === 'login' ? 'tab-btn-active' : 'tab-btn-inactive' ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Masuk
                </button>
                <button id="tab-reg-btn" onclick="switchTab('register')"
                    class="tab-btn <?= $tab === 'register' ? 'tab-btn-active' : 'tab-btn-inactive' ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    Daftar
                </button>
            </div>

            <!-- ===== LOGIN PANEL ===== -->
            <div id="panel-login" class="<?= $tab === 'login' ? '' : 'hidden' ?>">
                <form action="proseslogin.php" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:#8A7357;">Alamat Email</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none flex" style="color:#A79A85;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </span>
                            <input type="email" name="email" placeholder="email@contoh.com" required
                                class="w-full pl-10 pr-4 py-2.5 bg-white border rounded-xl text-sm outline-none transition-all duration-200 placeholder:text-slate-300"
                                style="border-color:rgba(138,115,87,0.25);color:#23301F;">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:#8A7357;">Password</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none flex" style="color:#A79A85;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <input type="password" id="lp" name="password" placeholder="Masukkan password" required
                                class="w-full pl-10 pr-10 py-2.5 bg-white border rounded-xl text-sm outline-none transition-all duration-200 placeholder:text-slate-300"
                                style="border-color:rgba(138,115,87,0.25);color:#23301F;">
                            <button type="button" onclick="toggleVis('lp',this)" class="absolute right-3 top-1/2 -translate-y-1/2 transition-colors p-0.5 bg-transparent border-none cursor-pointer" style="color:#A79A85;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" name="login" class="w-full flex items-center justify-center gap-2 py-3 mt-1 rounded-xl text-sm font-bold text-white transition-all duration-200 hover:-translate-y-0.5" style="background:linear-gradient(135deg,#2F5233 0%,#4A7050 100%);box-shadow:0 4px 16px rgba(47,82,51,0.28);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        Masuk ke Dashboard
                    </button>
                </form>
                
                <div class="flex items-center gap-3 my-4"><div class="flex-1 h-px" style="background:rgba(138,115,87,0.09);"></div><span class="text-xs" style="color:#A79A85;">atau</span><div class="flex-1 h-px" style="background:rgba(138,115,87,0.09);"></div></div>
                <p class="text-center text-sm" style="color:#8A7357;">Belum punya akun? <button onclick="switchTab('register')" class="font-bold bg-transparent border-none cursor-pointer text-sm" style="color:#2F5233;">Daftar sekarang</button></p>
            </div>

            <!-- ===== REGISTER PANEL ===== -->
            <div id="panel-register" class="<?= $tab === 'register' ? '' : 'hidden' ?>">
                <form action="prosesregistrasi.php" method="POST" id="regfrm" class="space-y-3">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:#8A7357;">Nama Depan</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none flex" style="color:#A79A85;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </span>
                                <input type="text" name="nama_depan" placeholder="Budi" required
                                    class="w-full pl-9 pr-3 py-2.5 bg-white border rounded-xl text-sm outline-none transition-all duration-200 placeholder:text-slate-300"
                                    style="border-color:rgba(138,115,87,0.25);color:#23301F;">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:#8A7357;">Nama Belakang</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none flex" style="color:#A79A85;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </span>
                                <input type="text" name="nama_belakang" placeholder="Santoso"
                                    class="w-full pl-9 pr-3 py-2.5 bg-white border rounded-xl text-sm outline-none transition-all duration-200 placeholder:text-slate-300"
                                    style="border-color:rgba(138,115,87,0.25);color:#23301F;">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:#8A7357;">Username</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none flex" style="color:#A79A85;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </span>
                            <input type="text" name="username" placeholder="username_unik" required
                                class="w-full pl-9 pr-3 py-2.5 bg-white border rounded-xl text-sm outline-none transition-all duration-200 placeholder:text-slate-300"
                                style="border-color:rgba(138,115,87,0.25);color:#23301F;">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:#8A7357;">Email</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none flex" style="color:#A79A85;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </span>
                            <input type="email" name="email" placeholder="email@contoh.com" required
                                class="w-full pl-9 pr-3 py-2.5 bg-white border rounded-xl text-sm outline-none transition-all duration-200 placeholder:text-slate-300"
                                style="border-color:rgba(138,115,87,0.25);color:#23301F;">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:#8A7357;">Password</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none flex" style="color:#A79A85;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <input type="password" id="rp" name="password" placeholder="Min. 6 karakter" oninput="chkStr(this.value)" required
                                class="w-full pl-9 pr-10 py-2.5 bg-white border rounded-xl text-sm outline-none transition-all duration-200 placeholder:text-slate-300"
                                style="border-color:rgba(138,115,87,0.25);color:#23301F;">
                            <button type="button" onclick="toggleVis('rp',this)" class="absolute right-3 top-1/2 -translate-y-1/2 transition-colors p-0.5 bg-transparent border-none cursor-pointer" style="color:#A79A85;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <div class="strack"><div id="sf" class="sfill"></div></div>
                        <p id="sl" class="text-xs mt-1" style="color:#8A7357;"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:#8A7357;">Konfirmasi Password</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none flex" style="color:#A79A85;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <input type="password" id="rk" name="konfirm" placeholder="Ulangi password" required
                                class="w-full pl-9 pr-3 py-2.5 bg-white border rounded-xl text-sm outline-none transition-all duration-200 placeholder:text-slate-300"
                                style="border-color:rgba(138,115,87,0.25);color:#23301F;">
                        </div>
                        <p id="mh" class="text-xs mt-1"></p>
                    </div>

                    <div class="p-3 rounded-xl border text-[11px]" style="background:rgba(185,132,58,0.08);border-color:rgba(185,132,58,0.16);color:#B9843A;">
                        <i class="fas fa-info-circle mr-1"></i> Penentuan Hak Akses Berputar Pintar Terjadwal (Admin, Petugas, Petugas Lapangan, User).
                    </div>

                    <button type="submit" name="register" class="w-full flex items-center justify-center gap-2 py-3 mt-1 rounded-xl text-sm font-bold text-white transition-all duration-200 hover:-translate-y-0.5" style="background:linear-gradient(135deg,#2F5233 0%,#4A7050 100%);box-shadow:0 4px 16px rgba(47,82,51,0.28);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                        Buat Akun Sekarang
                    </button>
                </form>
                
                <div class="flex items-center gap-3 my-4"><div class="flex-1 h-px" style="background:rgba(138,115,87,0.09);"></div><span class="text-xs" style="color:#A79A85;">atau</span><div class="flex-1 h-px" style="background:rgba(138,115,87,0.09);"></div></div>
                <p class="text-center text-sm" style="color:#8A7357;">Sudah punya akun? <button onclick="switchTab('login')" class="font-bold bg-transparent border-none cursor-pointer font-sans text-sm" style="color:#2F5233;">Masuk di sini</button></p>
            </div>
        </div>
    </div>

    <script>
        function switchTab(t) {
            var isLogin = t === 'login';
            
            // Toggle panels
            document.getElementById('panel-login').classList.toggle('hidden', !isLogin);
            document.getElementById('panel-register').classList.toggle('hidden', isLogin);
            
            // Toggle button styles
            var lb = document.getElementById('tab-login-btn');
            var rb = document.getElementById('tab-reg-btn');
            
            // Reset semua class
            lb.className = lb.className.replace(/tab-btn-active/g, '').replace(/tab-btn-inactive/g, '').trim();
            rb.className = rb.className.replace(/tab-btn-active/g, '').replace(/tab-btn-inactive/g, '').trim();
            
            // Tambah class yang sesuai
            lb.classList.add('tab-btn', isLogin ? 'tab-btn-active' : 'tab-btn-inactive');
            rb.classList.add('tab-btn', !isLogin ? 'tab-btn-active' : 'tab-btn-inactive');
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
                h.textContent = '✓ Password cocok';
                h.style.color = '#059669';
            } else {
                h.textContent = 'Belum cocok';
                h.style.color = '#EF4444';
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
