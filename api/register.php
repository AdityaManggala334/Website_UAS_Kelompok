<?php
// register.php
// Hanya panel form register (tanpa panel kiri/branding)

require_once __DIR__ . '/config.php';
global $conn;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $nama_depan   = trim($_POST['nama_depan'] ?? '');
    $nama_belakang = trim($_POST['nama_belakang'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm      = $_POST['confirm'] ?? '';

    if ($nama_depan === '' || $nama_belakang === '' || $username === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'Semua kolom wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        // Cek apakah email atau username sudah terdaftar
        $cek = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
        mysqli_stmt_bind_param($cek, 'ss', $email, $username);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if (mysqli_stmt_num_rows($cek) > 0) {
            $error = 'Email atau username sudah terdaftar.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);

            $insert = mysqli_prepare($conn,
                'INSERT INTO users (nama_depan, nama_belakang, username, email, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
            );
            mysqli_stmt_bind_param($insert, 'sssss', $nama_depan, $nama_belakang, $username, $email, $hashed);

            if (mysqli_stmt_execute($insert)) {
                $success = 'Akun berhasil dibuat! Silakan masuk.';
            } else {
                $error = 'Gagal membuat akun. Silakan coba lagi.';
            }
            mysqli_stmt_close($insert);
        }
        mysqli_stmt_close($cek);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Akun - SM Irigasi</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 bg-emerald-50">

    <div class="w-full max-w-md bg-white rounded-3xl p-8 sm:p-10 shadow-xl border border-slate-100">

        <h2 class="text-2xl font-extrabold text-emerald-950 tracking-tight mb-1">Selamat Datang</h2>
        <p class="text-sm text-slate-400 mb-6">Masuk atau daftar untuk mengakses sistem</p>

        <?php if ($error): ?>
            <div class="text-sm font-medium rounded-xl p-3 mb-4" style="background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="text-sm font-medium rounded-xl p-3 mb-4" style="background:#F0FDF4;border:1px solid #BBF7D0;color:#166534;">
                <?= htmlspecialchars($success) ?> <a href="login.php" class="underline font-bold">Masuk di sini</a>
            </div>
        <?php endif; ?>

        <div class="flex gap-1 p-1 rounded-xl mb-6" style="background:rgba(6,78,59,0.06);">
            <a href="login.php" class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg text-sm font-semibold text-slate-500 hover:text-slate-700 no-underline">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Masuk
            </a>
            <span class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg text-sm font-semibold bg-white text-emerald-900 shadow-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                Daftar
            </span>
        </div>

        <form action="register.php" method="POST" id="regfrm" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Nama Depan</label>
                    <input type="text" name="nama_depan" placeholder="Budi" required
                        class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 placeholder:text-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Nama Belakang</label>
                    <input type="text" name="nama_belakang" placeholder="Santoso" required
                        class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 placeholder:text-slate-300">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Username</label>
                <input type="text" name="username" placeholder="username_unik" required
                    class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 placeholder:text-slate-300">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Email</label>
                <input type="email" name="email" placeholder="email@domain.com" required
                    class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 placeholder:text-slate-300">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Password</label>
                <div class="relative">
                    <input type="password" id="rp" name="password" placeholder="Min. 6 karakter" required
                        class="w-full px-3 pr-10 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 placeholder:text-slate-300">
                    <button type="button" onclick="toggleVis('rp')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 hover:text-emerald-600 bg-transparent border-none cursor-pointer">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Konfirmasi Password</label>
                <input type="password" id="rk" name="confirm" placeholder="Ulangi password" required
                    class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 placeholder:text-slate-300">
                <p id="mh" class="text-xs mt-1"></p>
            </div>

            <button type="submit" name="register"
                class="w-full flex items-center justify-center gap-2 py-3 mt-1 rounded-xl text-sm font-bold text-white transition-all hover:-translate-y-0.5"
                style="background:linear-gradient(135deg,#065F46 0%,#064E3B 100%);box-shadow:0 4px 16px rgba(6,78,59,0.28);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                Buat Akun Sekarang
            </button>
        </form>

        <div class="flex items-center gap-3 my-4">
            <div class="flex-1 h-px" style="background:rgba(6,78,59,0.09);"></div>
            <span class="text-xs text-slate-400">atau</span>
            <div class="flex-1 h-px" style="background:rgba(6,78,59,0.09);"></div>
        </div>
        <p class="text-center text-sm text-slate-400">Sudah punya akun? <a href="login.php" class="font-bold text-emerald-600 hover:text-emerald-700 no-underline">Masuk di sini</a></p>
    </div>

    <script>
        function toggleVis(id) {
            var el = document.getElementById(id);
            el.type = el.type === 'password' ? 'text' : 'password';
        }

        var rk = document.getElementById('rk');
        var rp = document.getElementById('rp');
        rk.addEventListener('input', function () {
            var h = document.getElementById('mh');
            if (!this.value) { h.textContent = ''; return; }
            if (this.value === rp.value) {
                h.textContent = '✓ Password cocok'; h.style.color = '#059669';
            } else {
                h.textContent = 'Belum cocok'; h.style.color = '#EF4444';
            }
        });

        document.getElementById('regfrm').addEventListener('submit', function (e) {
            if (rp.value !== rk.value) {
                e.preventDefault();
                alert('Password dan konfirmasi tidak cocok!');
            }
        });
    </script>
</body>
</html>
