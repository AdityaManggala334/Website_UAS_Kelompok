<?php
// dashboard.php - ADMIN PANEL LADUSYNC
// ======================================================

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/auth_helper.php';

// Cek apakah user yang login adalah administrator
if ($role !== 'administrator') {
    header("Location: index.php");
    exit();
}

$adminNama = $namaLengkap;
$adminId   = (int)$user_id;

// ============================================================
// FUNGSI GENERATE QR CODE
// ============================================================
function generateQRCode($conn, $id_peminjaman) {
    $max_attempts = 10;
    $attempt = 0;
    
    while ($attempt < $max_attempts) {
        $qr_code = 'QR-' . date('Ymd') . '-' . substr(md5(uniqid() . rand(1000, 9999) . microtime(true)), 0, 8);
        $check = mysqli_query($conn, "SELECT id FROM peminjaman WHERE qr_code = '$qr_code'");
        if (mysqli_num_rows($check) == 0) {
            return $qr_code;
        }
        $attempt++;
    }
    return 'QR-' . date('Ymd') . '-' . $id_peminjaman . '-' . time();
}

// ============================================================
// PROSES POST (Aksi dari form)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    
    // Aksi 1: Ubah role pengguna
    if ($_POST['aksi'] === 'ubah_role') {
        $id_t = (int)($_POST['id_user'] ?? 0);
        $r = $_POST['role'] ?? '';
        $ok = ['petani','petugas_lapangan','koordinator_irigasi','administrator'];
        if ($id_t > 0 && in_array($r, $ok)) {
            $st = mysqli_prepare($conn, "UPDATE users SET role=? WHERE id_users=?");
            mysqli_stmt_bind_param($st, 'si', $r, $id_t);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
        }
        header("Location: dashboard.php?msg=role_ok#users");
        exit();
    }
    
    // Aksi 2: Hapus user
    if ($_POST['aksi'] === 'hapus_user') {
        $id_t = (int)($_POST['id_user'] ?? 0);
        if ($id_t === $adminId) {
            header("Location: dashboard.php?msg=self_err#users");
            exit();
        }
        if ($id_t > 0) {
            $st = mysqli_prepare($conn, "DELETE FROM users WHERE id_users=?");
            mysqli_stmt_bind_param($st, 'i', $id_t);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
        }
        header("Location: dashboard.php?msg=del_ok#users");
        exit();
    }
    
    // Aksi 3: Ubah status laporan
    if ($_POST['aksi'] === 'ubah_status_laporan') {
        $id_l = (int)($_POST['id_laporan'] ?? 0);
        $s = $_POST['status'] ?? '';
        if ($id_l > 0 && in_array($s, ['baru','ditangani','selesai'])) {
            $st = mysqli_prepare($conn, "UPDATE laporan_kendala SET status=? WHERE id_laporan=?");
            mysqli_stmt_bind_param($st, 'si', $s, $id_l);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
        }
        header("Location: dashboard.php?msg=status_ok#laporan");
        exit();
    }

    // Aksi 4: Tambah alat (tanpa upload file - pakai URL)
    if ($_POST['aksi'] === 'tambah_alat') {
        $nama = mysqli_real_escape_string($conn, trim($_POST['nama_alat'] ?? ''));
        $harga = (float)($_POST['harga'] ?? 0);
        $stok = (int)($_POST['stok'] ?? 0);
        $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi'] ?? ''));
        $gambar_url = mysqli_real_escape_string($conn, trim($_POST['gambar_url'] ?? ''));

        if (empty($nama)) {
            header("Location: dashboard.php?msg=alat_err#alat");
            exit();
        }

        // Gunakan gambar default jika URL kosong
        if (empty($gambar_url)) {
            $gambar_url = 'https://placehold.co/600x400/e2e8f0/64748b?text=' . urlencode($nama);
        }

        $st = mysqli_prepare($conn, "INSERT INTO alat (nama_alat, harga, stok, deskripsi, gambar) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($st, 'sdiss', $nama, $harga, $stok, $deskripsi, $gambar_url);
        
        if (mysqli_stmt_execute($st)) {
            mysqli_stmt_close($st);
            header("Location: dashboard.php?msg=alat_ok#alat");
        } else {
            mysqli_stmt_close($st);
            header("Location: dashboard.php?msg=alat_err#alat");
        }
        exit();
    }

    // Aksi 5: Edit alat (tanpa upload file - pakai URL)
    if ($_POST['aksi'] === 'edit_alat') {
        $id = (int)($_POST['id_alat'] ?? 0);
        $nama = mysqli_real_escape_string($conn, trim($_POST['nama_alat'] ?? ''));
        $harga = (float)($_POST['harga'] ?? 0);
        $stok = (int)($_POST['stok'] ?? 0);
        $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi'] ?? ''));
        $gambar_url = mysqli_real_escape_string($conn, trim($_POST['gambar_url'] ?? ''));

        if ($id <= 0 || empty($nama)) {
            header("Location: dashboard.php?msg=alat_err#alat");
            exit();
        }

        if (!empty($gambar_url)) {
            $st = mysqli_prepare($conn, "UPDATE alat SET nama_alat=?, harga=?, stok=?, deskripsi=?, gambar=? WHERE id=?");
            mysqli_stmt_bind_param($st, 'sdissi', $nama, $harga, $stok, $deskripsi, $gambar_url, $id);
        } else {
            $st = mysqli_prepare($conn, "UPDATE alat SET nama_alat=?, harga=?, stok=?, deskripsi=? WHERE id=?");
            mysqli_stmt_bind_param($st, 'sdissi', $nama, $harga, $stok, $deskripsi, $id);
        }
        
        if (mysqli_stmt_execute($st)) {
            mysqli_stmt_close($st);
            header("Location: dashboard.php?msg=alat_ok#alat");
        } else {
            mysqli_stmt_close($st);
            header("Location: dashboard.php?msg=alat_err#alat");
        }
        exit();
    }

    // Aksi 6: Hapus alat
    if ($_POST['aksi'] === 'hapus_alat') {
        $id = (int)($_POST['id_alat'] ?? 0);
        if ($id > 0) {
            $st = mysqli_prepare($conn, "DELETE FROM alat WHERE id=?");
            mysqli_stmt_bind_param($st, 'i', $id);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
        }
        header("Location: dashboard.php?msg=alat_del#alat");
        exit();
    }

    // Aksi 7: Verifikasi pembayaran + QR Code
    if ($_POST['aksi'] === 'verifikasi_pembayaran') {
        $id = (int)($_POST['id_peminjaman'] ?? 0);
        
        if ($id > 0) {
            $qr_code = generateQRCode($conn, $id);
            
            $sql = "UPDATE peminjaman SET 
                        status = 'lunas',
                        qr_code = '$qr_code',
                        qr_code_generated_at = NOW(),
                        dikonfirmasi_oleh = $adminId,
                        catatan_admin = CONCAT(IFNULL(catatan_admin, ''), '\nPembayaran diverifikasi oleh admin pada ', NOW())
                    WHERE id = $id";
            
            if (mysqli_query($conn, $sql)) {
                header("Location: dashboard.php?msg=verifikasi_ok#verifikasi");
            } else {
                header("Location: dashboard.php?msg=verifikasi_err#verifikasi");
            }
        } else {
            header("Location: dashboard.php?msg=verifikasi_err#verifikasi");
        }
        exit();
    }

    // Aksi 8: Tolak pembayaran
    if ($_POST['aksi'] === 'tolak_pembayaran') {
        $id = (int)($_POST['id_peminjaman'] ?? 0);
        
        if ($id > 0) {
            $query_alat = "SELECT id_alat FROM peminjaman WHERE id = $id";
            $result = mysqli_query($conn, $query_alat);
            $data = mysqli_fetch_assoc($result);
            if ($data) {
                mysqli_query($conn, "UPDATE alat SET stok = stok + 1 WHERE id = " . $data['id_alat']);
            }
            
            $sql = "UPDATE peminjaman SET 
                        status = 'dibatalkan',
                        catatan_admin = CONCAT(IFNULL(catatan_admin, ''), '\nPembayaran ditolak pada ', NOW())
                    WHERE id = $id";
            
            if (mysqli_query($conn, $sql)) {
                header("Location: dashboard.php?msg=tolak_ok#verifikasi");
            } else {
                header("Location: dashboard.php?msg=verifikasi_err#verifikasi");
            }
        }
        exit();
    }

    // Aksi 9: Konfirmasi pengambilan alat
    if ($_POST['aksi'] === 'konfirmasi_ambil') {
        $id = (int)($_POST['id_peminjaman'] ?? 0);
        
        if ($id > 0) {
            $sql = "UPDATE peminjaman SET 
                        status = 'dipinjam',
                        waktu_ambil = NOW(),
                        tanggal_pinjam = CURDATE(),
                        catatan_admin = CONCAT(IFNULL(catatan_admin, ''), '\nAlat diambil pada ', NOW(), ' oleh admin')
                    WHERE id = $id";
            
            if (mysqli_query($conn, $sql)) {
                header("Location: dashboard.php?msg=ambil_ok#verifikasi");
            } else {
                header("Location: dashboard.php?msg=verifikasi_err#verifikasi");
            }
        }
        exit();
    }

    // Aksi 10: Konfirmasi pengembalian alat
    if ($_POST['aksi'] === 'konfirmasi_kembali') {
        $id = (int)($_POST['id_peminjaman'] ?? 0);
        $kondisi_alat = mysqli_real_escape_string($conn, $_POST['kondisi_alat'] ?? 'baik');
        $catatan = mysqli_real_escape_string($conn, trim($_POST['catatan_kembali'] ?? ''));
        
        if ($id > 0) {
            $query = "SELECT * FROM peminjaman WHERE id = $id";
            $result = mysqli_query($conn, $query);
            $data = mysqli_fetch_assoc($result);
            
            if ($data) {
                $estimasi = strtotime($data['tanggal_kembali_estimasi']);
                $sekarang = time();
                $hari_terlambat = floor(($sekarang - $estimasi) / (60 * 60 * 24));
                $harga_per_hari = $data['total_bayar'] / $data['durasi'];
                $denda = $hari_terlambat > 0 ? $hari_terlambat * $harga_per_hari : 0;
                
                $sql = "UPDATE peminjaman SET 
                            status = 'dikembalikan',
                            tanggal_kembali_aktual = CURDATE(),
                            waktu_kembali = NOW(),
                            kondisi_alat = '$kondisi_alat',
                            catatan_pengembalian = '$catatan',
                            denda = $denda,
                            dikembalikan_oleh = $adminId,
                            catatan_admin = CONCAT(IFNULL(catatan_admin, ''), '\nAlat dikembalikan pada ', NOW(), ' - Kondisi: $kondisi_alat')
                        WHERE id = $id";
                
                if (mysqli_query($conn, $sql)) {
                    mysqli_query($conn, "UPDATE alat SET stok = stok + 1 WHERE id = " . $data['id_alat']);
                    header("Location: dashboard.php?msg=kembali_ok#verifikasi");
                } else {
                    header("Location: dashboard.php?msg=verifikasi_err#verifikasi");
                }
            } else {
                header("Location: dashboard.php?msg=verifikasi_err#verifikasi");
            }
        }
        exit();
    }

    // Aksi 11: Set Lunas transaksi
    if ($_POST['aksi'] === 'set_lunas') {
        $id = (int)($_POST['id_transaksi'] ?? 0);
        if ($id > 0) {
            $st = mysqli_prepare($conn, "UPDATE peminjaman SET status='lunas' WHERE id=?");
            mysqli_stmt_bind_param($st, 'i', $id);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
        }
        header("Location: dashboard.php?msg=transaksi_ok#transaksi");
        exit();
    }
}

// ============================================================
// AMBIL DATA DARI DATABASE
// ============================================================
$users    = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
$laporan  = mysqli_query($conn, "SELECT lk.*,u.username FROM laporan_kendala lk LEFT JOIN users u ON lk.id_users=u.id_users ORDER BY lk.created_at DESC");
$alat     = mysqli_query($conn, "SELECT * FROM alat ORDER BY id DESC");
$transaksi = mysqli_query($conn, "SELECT * FROM peminjaman ORDER BY id DESC");

// ============================================================
// DATA UNTUK VERIFIKASI PEMBAYARAN
// ============================================================
$query_verifikasi = "SELECT p.*, u.nama_depan, u.nama_belakang, u.email 
                     FROM peminjaman p 
                     LEFT JOIN users u ON p.id_users = u.id_users 
                     WHERE p.status = 'menunggu_verifikasi' 
                     ORDER BY p.created_at ASC";
$verifikasi_data = mysqli_query($conn, $query_verifikasi);
$total_verifikasi = mysqli_num_rows($verifikasi_data);

// ============================================================
// DATA UNTUK PENGAMBILAN ALAT (status = lunas)
// ============================================================
$query_ambil = "SELECT p.*, u.nama_depan, u.nama_belakang, u.email 
                FROM peminjaman p 
                LEFT JOIN users u ON p.id_users = u.id_users 
                WHERE p.status = 'lunas' 
                ORDER BY p.created_at ASC";
$ambil_data = mysqli_query($conn, $query_ambil);
$total_ambil = mysqli_num_rows($ambil_data);

// ============================================================
// DATA UNTUK PENGEMBALIAN ALAT (status = dipinjam)
// ============================================================
$query_kembali = "SELECT p.*, u.nama_depan, u.nama_belakang, u.email 
                  FROM peminjaman p 
                  LEFT JOIN users u ON p.id_users = u.id_users 
                  WHERE p.status = 'dipinjam' 
                  ORDER BY p.tanggal_kembali_estimasi ASC";
$kembali_data = mysqli_query($conn, $query_kembali);
$total_kembali = mysqli_num_rows($kembali_data);

// ============================================================
// STATISTIK
// ============================================================
$totalU   = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'];
$totalL   = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM laporan_kendala"))['c'];
$newL     = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM laporan_kendala WHERE status='baru'"))['c'];
$admCnt   = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE role='administrator'"))['c'];
$totalAlat = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM alat"))['c'];
$totalTransaksi = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM peminjaman"))['c'];

$roleList = ['petani','petugas_lapangan','koordinator_irigasi','administrator'];

function lRole(string $r): string {
    return match($r) {
        'petani' => 'Petani',
        'petugas_lapangan' => 'Petugas Lapangan',
        'koordinator_irigasi' => 'Koordinator Irigasi',
        'administrator' => 'Administrator',
        default => $r
    };
}

function bRole(string $r): string {
    return match($r) {
        'petani' => 'badge-petani',
        'petugas_lapangan' => 'badge-petugas',
        'koordinator_irigasi' => 'badge-koordinator',
        'administrator' => 'badge-admin',
        default => ''
    };
}

// Data sensor (hardcoded)
$sensors = [
    ['id'=>'SNS-01','lokasi'=>'Saluran Induk Ngidul','debit'=>12.4,'tma'=>42,'suhu'=>26.8,'lembap'=>68,'status'=>'normal'],
    ['id'=>'SNS-02','lokasi'=>'Percabangan Blok A','debit'=>8.7,'tma'=>35,'suhu'=>27.1,'lembap'=>72,'status'=>'normal'],
    ['id'=>'SNS-03','lokasi'=>'Saluran Blok B','debit'=>3.2,'tma'=>18,'suhu'=>28.3,'lembap'=>45,'status'=>'rendah'],
    ['id'=>'SNS-04','lokasi'=>'Bak Penampungan C1','debit'=>18.9,'tma'=>71,'suhu'=>26.2,'lembap'=>80,'status'=>'tinggi'],
    ['id'=>'SNS-05','lokasi'=>'Saluran Ngalor D','debit'=>6.5,'tma'=>28,'suhu'=>27.8,'lembap'=>63,'status'=>'normal'],
    ['id'=>'SNS-06','lokasi'=>'Saluran Ngetan E','debit'=>1.1,'tma'=>10,'suhu'=>29.0,'lembap'=>31,'status'=>'kritis'],
    ['id'=>'SNS-07','lokasi'=>'Saluran Petak 12','debit'=>9.3,'tma'=>38,'suhu'=>26.5,'lembap'=>70,'status'=>'normal'],
    ['id'=>'SNS-08','lokasi'=>'Embung Ngulon','debit'=>7.8,'tma'=>32,'suhu'=>27.4,'lembap'=>66,'status'=>'normal'],
];

$avgDebit = round(array_sum(array_column($sensors, 'debit')) / count($sensors), 1);
$avgTMA   = round(array_sum(array_column($sensors, 'tma')) / count($sensors));
$normalCnt = count(array_filter($sensors, fn($s) => $s['status'] === 'normal'));
$kritisCnt = count(array_filter($sensors, fn($s) => $s['status'] === 'kritis'));

$edit_alat = null;
if (isset($_GET['edit_alat_id'])) {
    $id = (int)$_GET['edit_alat_id'];
    $q = mysqli_query($conn, "SELECT * FROM alat WHERE id='$id'");
    $edit_alat = mysqli_fetch_assoc($q);
}

// Deteksi section dari hash URL
$activeSection = 'overview';
$hash = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($hash, '#users') !== false) $activeSection = 'users';
elseif (strpos($hash, '#laporan') !== false) $activeSection = 'laporan';
elseif (strpos($hash, '#alat') !== false) $activeSection = 'alat';
elseif (strpos($hash, '#verifikasi') !== false) $activeSection = 'verifikasi';
elseif (strpos($hash, '#transaksi') !== false) $activeSection = 'transaksi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Dashboard Admin — LaduSync</title>
    
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
    
    /* Badge notifikasi di sidebar */
    .sb-badge {
        margin-left: auto;
        font-size: 0.6rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 20px;
        background: #EF4444;
        color: white;
        flex-shrink: 0;
    }
    .sb-badge-green { background: #10B981; }
    .sb-badge-blue { background: #3B82F6; }

    .sidebar-bottom { flex-shrink: 0; padding: 12px; border-top: 1px solid rgba(245,241,229,0.08); }
    .nav-link-logout { 
        color: #EF4444 !important;
        background: rgba(239,68,68,0.12) !important;
        border-left: 2px solid #EF4444 !important;
    }
    .nav-link-logout:hover { 
        color: #fff !important;
        background: rgba(239,68,68,0.35) !important;
    }

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
        .kpi-grid { grid-template-columns: repeat(2,1fr) !important; gap: 6px !important; }
        .kpi-card { padding: 0.75rem !important; }
        .kpi-num { font-size: 1rem !important; }
        .bento-grid { grid-template-columns: repeat(2,1fr) !important; gap: 6px !important; }
        .form-grid { grid-template-columns: 1fr !important; }
        .data-table,.data-table thead,.data-table tbody,.data-table tr,.data-table td { display: block; }
        .data-table thead { display: none; }
        .data-table tr { border: 1px solid rgba(138,115,87,0.12); border-radius: 12px; margin-bottom: 12px; padding: 10px; background: white; }
        .data-table td { display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; border: none; }
        .data-table td:before { content: attr(data-label); font-weight: 700; font-size: 0.68rem; color: #94A3B8; margin-right: 16px; flex-shrink: 0; width: 100px; }
        .data-table td div { text-align: right; flex: 1; }
        .sc-head { flex-direction: column; align-items: flex-start !important; }
        .crop-box { max-width: 95%; }
        .verif-grid { grid-template-columns: 1fr !important; }
    }

    @media (max-width: 480px) {
        .topbar { padding: 0 10px; height: 50px; }
        .sidebar { width: 280px !important; }
        .kpi-grid { grid-template-columns: 1fr 1fr !important; gap: 4px !important; }
        .content { padding: 0.5rem; }
        .form-grid { gap: 8px; }
        .form-grid input, .form-grid textarea, .form-grid select { font-size: 0.75rem; padding: 6px 10px; }
    }

    /* ===== ADMIN STYLES ===== */
    .flash {
        padding: 10px 14px; border-radius: 12px; font-size: 0.8rem; font-weight: 500;
        margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;
    }
    .flash-ok { background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534; }
    .flash-err { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; }
    .flash-warning { background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; }

    .kpi-grid {
        display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-bottom: 1rem;
    }
    @media (min-width: 640px) { .kpi-grid { gap: 1rem; margin-bottom: 1.5rem; } }
    @media (min-width: 1024px) { .kpi-grid { grid-template-columns: repeat(6, 1fr); } }

    .kpi-card {
        background: white; border-radius: 14px; padding: 0.9rem;
        border: 1px solid rgba(138,115,87,0.12);
        box-shadow: 0 1px 3px rgba(28,43,30,0.05), 0 8px 24px rgba(28,43,30,0.06);
        display: flex; flex-direction: column; gap: 8px;
        transition: transform 0.2s ease;
    }
    .kpi-card:hover { transform: translateY(-2px); }
    .kpi-icon { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
    .kpi-num { font-size: 1.4rem; font-weight: 800; color: var(--ink); letter-spacing: -0.03em; line-height: 1; }
    .kpi-label { font-size: 0.7rem; font-weight: 600; color: #4B7563; margin-top: 2px; }
    .kpi-sub { font-size: 0.65rem; color: #94A3B8; }

    .section-card {
        background: white; border-radius: 16px; border: 1px solid rgba(138,115,87,0.12);
        box-shadow: 0 1px 3px rgba(28,43,30,0.05), 0 8px 24px rgba(28,43,30,0.06);
        margin-bottom: 1rem; overflow: hidden;
    }
    .sc-head {
        padding: 0.9rem 1rem; border-bottom: 1px solid rgba(138,115,87,0.08);
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;
        background: linear-gradient(135deg, #F5F1E5, #ECE5D3);
    }
    .sc-title { font-size: 0.85rem; font-weight: 700; color: var(--ink); font-family: 'Fraunces', serif; }
    .sc-sub { font-size: 0.7rem; color: #94A3B8; margin-top: 2px; }
    .sc-badge { padding: 3px 10px; border-radius: 20px; font-size: 0.68rem; font-weight: 700; }
    .badge-red { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
    .badge-green { background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0; }
    .badge-blue { background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; }
    .badge-yellow { background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A; }
    .badge-purple { background: #FDF4FF; color: #7E22CE; border: 1px solid #E9D5FF; }

    .data-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
    .data-table th {
        padding: 10px 12px; text-align: left; font-size: 0.68rem; font-weight: 700;
        color: #94A3B8; text-transform: uppercase; background: #FAFAFA;
        border-bottom: 1px solid rgba(138,115,87,0.08);
    }
    .data-table td { padding: 10px 12px; border-bottom: 1px solid rgba(6,78,59,0.05); vertical-align: middle; }

    .role-badge {
        display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 0.68rem; font-weight: 700;
    }
    .badge-petani { background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0; }
    .badge-petugas { background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; }
    .badge-koordinator { background: #FFF7ED; color: #C2410C; border: 1px solid #FED7AA; }
    .badge-admin { background: #FDF4FF; color: #7E22CE; border: 1px solid #E9D5FF; }

    .status-pill {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 20px; font-size: 0.65rem; font-weight: 700;
    }
    .sp-normal { background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0; }
    .sp-rendah { background: #FFF7ED; color: #C2410C; border: 1px solid #FED7AA; }
    .sp-tinggi { background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; }
    .sp-kritis { background: #FEF2F2; color: #B91C1C; border: 1px solid #FCA5A5; }
    .ls-baru { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
    .ls-ditangani { background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A; }
    .ls-selesai { background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0; }

    .status-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 700; }
    .status-lunas { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .status-dipinjam { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
    .status-menunggu { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .status-selesai { background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }
    .status-belum { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }

    .tbl-select {
        font-family: inherit; font-size: 0.7rem; border: 1px solid rgba(138,115,87,0.18);
        border-radius: 8px; padding: 4px 8px; background: white; color: var(--ink); outline: none;
    }
    .tbl-btn {
        padding: 4px 10px; border-radius: 8px; border: none; font-family: inherit;
        font-size: 0.7rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;
        text-decoration: none; transition: all 0.2s ease;
    }
    .tbl-btn:hover { transform: translateY(-1px); }
    .tbl-btn-green { background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0; }
    .tbl-btn-red { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
    .tbl-btn-blue { background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; }
    .tbl-btn-yellow { background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A; }
    .tbl-btn-purple { background: #FDF4FF; color: #7E22CE; border: 1px solid #E9D5FF; }

    .avatar {
        width: 28px; height: 28px; border-radius: 8px;
        background: linear-gradient(135deg, #2F5233, #4A7050);
        display: flex; align-items: center; justify-content: center;
        font-size: 0.7rem; font-weight: 700; color: white; flex-shrink: 0;
    }
    .you-tag { background: #F1F5F9; color: #64748B; font-size: 0.6rem; font-weight: 700; padding: 2px 6px; border-radius: 20px; margin-left: 6px; }

    .img-preview {
        width: 60px;
        height: 45px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid rgba(138,115,87,0.12);
    }
    .bukti-preview {
        width: 80px;
        height: 60px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid rgba(138,115,87,0.12);
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    .bukti-preview:hover { transform: scale(1.05); }

    footer {
        background: white; border-top: 1px solid rgba(138,115,87,0.12);
        padding: 0.75rem; text-align: center; font-size: 0.65rem; color: #94A3B8;
        margin-top: auto;
    }

    .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 12px; }
    @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
    .form-grid label { font-size: 0.7rem; font-weight: 600; color: #4B7563; display: block; margin-bottom: 4px; }
    .form-grid input, .form-grid textarea, .form-grid select {
        width: 100%; padding: 8px 12px; border: 1px solid rgba(138,115,87,0.18);
        border-radius: 10px; font-size: 0.8rem; outline: none; font-family: inherit;
        transition: border-color 0.2s ease;
        background: white;
    }
    .form-grid input:focus, .form-grid textarea:focus, .form-grid select:focus { border-color: var(--sawah); }
    .form-grid textarea { resize: vertical; min-height: 60px; }

    .img-preview-container { margin-bottom: 12px; }
    .img-preview-container label { font-size: 0.7rem; font-weight: 600; color: #4B7563; display: block; margin-bottom: 4px; }
    .current-image { width: 100%; max-height: 200px; object-fit: cover; border-radius: 10px; border: 2px dashed rgba(138,115,87,0.18); }

    .verif-grid {
        display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;
    }
    @media (max-width: 1024px) { .verif-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 768px) { .verif-grid { grid-template-columns: 1fr; } }

    .verif-card {
        background: white; border-radius: 14px; padding: 1rem;
        border: 1px solid rgba(138,115,87,0.12);
        box-shadow: 0 1px 3px rgba(28,43,30,0.05);
    }
    .verif-card .badge-count {
        font-size: 1.8rem; font-weight: 800; color: var(--ink);
    }
    .verif-card .badge-label {
        font-size: 0.7rem; color: #94A3B8;
    }

    .bento-grid {
        display: grid; grid-template-columns: repeat(2,1fr); gap: 0.75rem; margin-bottom: 1rem;
    }
    @media (min-width: 640px) { .bento-grid { gap: 1rem; margin-bottom: 1.5rem; } }
    @media (min-width: 1024px) { .bento-grid { grid-template-columns: repeat(4,1fr); } }

    .sensor-tile {
        background: white; border-radius: 14px; padding: 0.8rem;
        border: 1px solid rgba(138,115,87,0.12); box-shadow: 0 1px 3px rgba(28,43,30,0.05);
        position: relative; overflow: hidden; transition: transform 0.2s;
    }
    .sensor-tile:hover { transform: translateY(-2px); }
    .sensor-tile::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; }
    .tile-normal::before { background: linear-gradient(90deg, #10B981, #34D399); }
    .tile-rendah::before { background: linear-gradient(90deg, #F97316, #FDBA74); }
    .tile-tinggi::before { background: linear-gradient(90deg, #3B82F6, #93C5FD); }
    .tile-kritis::before { background: linear-gradient(90deg, #EF4444, #FCA5A5); }
    .tile-id { font-size: 0.65rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #94A3B8; margin-bottom: 5px; }
    .tile-loc { font-size: 0.75rem; font-weight: 600; color: var(--ink); margin-bottom: 8px; line-height: 1.3; }
    .tile-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 5px; }
    .tile-stat { background: rgba(6,78,59,0.04); border-radius: 7px; padding: 5px 6px; }
    .tile-stat-val { font-size: 0.8rem; font-weight: 700; color: var(--ink); }
    .tile-stat-lbl { font-size: 0.6rem; color: #94A3B8; font-weight: 500; margin-top: 1px; }

    /* Modal Detail */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9998;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
        background: white;
        border-radius: 16px;
        max-width: 700px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        padding: 24px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    .modal-box .close-btn {
        float: right;
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #94A3B8;
        transition: color 0.2s;
    }
    .modal-box .close-btn:hover { color: #EF4444; }

    .invoice-code {
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.7rem;
        background: #f1f5f9;
        padding: 2px 8px;
        border-radius: 4px;
        color: #475569;
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

        <button class="nav-link <?= $activeSection === 'overview' ? 'active' : '' ?>" onclick="showSection('overview')">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span class="nav-text">Dashboard</span>
        </button>

        <div class="sidebar-section-label">Manajemen</div>

        <button class="nav-link <?= $activeSection === 'verifikasi' ? 'active' : '' ?>" onclick="showSection('verifikasi')" style="position:relative">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span class="nav-text">Verifikasi</span>
            <?php if ($total_verifikasi > 0): ?>
                <span class="sb-badge"><?= $total_verifikasi ?></span>
            <?php endif; ?>
        </button>

        <button class="nav-link <?= $activeSection === 'users' ? 'active' : '' ?>" onclick="showSection('users')">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span class="nav-text">Kelola Pengguna</span>
        </button>

        <button class="nav-link <?= $activeSection === 'laporan' ? 'active' : '' ?>" onclick="showSection('laporan')" style="position:relative">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <span class="nav-text">Laporan Kendala</span>
            <?php if ($newL > 0): ?>
                <span class="sb-badge"><?= $newL ?></span>
            <?php endif; ?>
        </button>

        <button class="nav-link <?= $activeSection === 'alat' ? 'active' : '' ?>" onclick="showSection('alat')">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <span class="nav-text">Kelola Alat</span>
            <span class="sb-badge sb-badge-blue"><?= $totalAlat ?></span>
        </button>

        <button class="nav-link <?= $activeSection === 'transaksi' ? 'active' : '' ?>" onclick="showSection('transaksi')">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M3 9h18"/>
            </svg>
            <span class="nav-text">Daftar Transaksi</span>
        </button>

        <div class="sidebar-section-label">Sensor</div>

        <a href="peta.php" class="nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
            </svg>
            <span class="nav-text">Peta Sensor</span>
        </a>

        <a href="riwayat.php" class="nav-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
            <span class="nav-text">Riwayat Data</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <a href="logout.php" class="nav-link nav-link-logout">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span class="nav-text">Keluar</span>
        </a>
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
                <span id="topbar-title">Dashboard Admin</span>
            </span>
        </div>

        <div class="nav-right">
            <div class="profil-wrap relative">
                <button class="flex items-center gap-1 sm:gap-2 px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium bg-transparent border-none cursor-pointer" style="color:var(--ink);">
                    <div class="w-7 h-7 rounded-md flex items-center justify-center font-bold text-xs" style="background:rgba(47,82,51,0.12);color:var(--sawah);">
                        <?= strtoupper(substr($adminNama, 0, 1)) ?>
                    </div>
                    <span class="profil-name hidden sm:inline"><?= htmlspecialchars($adminNama) ?></span>
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </button>

                <div class="profil-dropdown">
                    <div class="px-4 py-3 border-b" style="background:linear-gradient(135deg,#F5F1E5,#ECE5D3);border-color:rgba(138,115,87,0.18);">
                        <div class="font-bold text-sm font-display" style="color:var(--sawah);"><?= htmlspecialchars($adminNama) ?></div>
                        <div class="text-xs text-slate-500 mt-0.5 capitalize">Administrator</div>
                    </div>
                    <a href="index.php" class="flex items-center gap-2 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition-colors no-underline">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg> Beranda
                    </a>
                    <a href="logout.php" class="flex items-center gap-2 px-4 py-3 text-sm hover:bg-red-50 transition-colors no-underline" style="color:var(--kritis);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Keluar
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- ===== CONTENT ===== -->
    <div class="content">

        <!-- FLASH MESSAGES -->
        <?php
        $msgs = [
            'role_ok' => ['ok', 'Role pengguna berhasil diperbarui.'],
            'del_ok' => ['ok', 'Pengguna berhasil dihapus.'],
            'status_ok' => ['ok', 'Status laporan berhasil diperbarui.'],
            'self_err' => ['err', 'Tidak dapat menghapus akun sendiri.'],
            'alat_ok' => ['ok', 'Data alat berhasil disimpan!'],
            'alat_del' => ['ok', 'Alat berhasil dihapus!'],
            'verifikasi_ok' => ['ok', '✅ Pembayaran berhasil diverifikasi! QR Code telah digenerate.'],
            'verifikasi_err' => ['err', '❌ Gagal memverifikasi pembayaran. Coba lagi.'],
            'tolak_ok' => ['ok', 'Pembayaran ditolak. Stok dikembalikan.'],
            'ambil_ok' => ['ok', '✅ Alat berhasil dikonfirmasi pengambilannya! Status berubah menjadi dipinjam.'],
            'kembali_ok' => ['ok', '✅ Alat berhasil dikonfirmasi pengembaliannya!'],
            'transaksi_ok' => ['ok', 'Status transaksi berhasil diperbarui menjadi LUNAS!'],
            'alat_err' => ['err', 'Gagal menyimpan data alat. Periksa kembali data yang diisi.'],
        ];
        if (isset($_GET['msg']) && isset($msgs[$_GET['msg']])) {
            [$type, $text] = $msgs[$_GET['msg']];
            echo '<div class="flash '.($type === 'ok' ? 'flash-ok' : 'flash-err').'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'.
                ($type === 'ok' ? '<polyline points="20 6 9 17 4 12"/>' : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>')
                .'</svg>'.htmlspecialchars($text).'</div>';
        }
        ?>

        <!-- ===== SECTION: OVERVIEW ===== -->
        <div id="sec-overview" style="display:<?= $activeSection === 'overview' ? 'block' : 'none' ?>">
            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:#F0FDF4;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#15803D" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div><div class="kpi-num"><?= $totalU ?></div><div class="kpi-label">Total Pengguna</div><div class="kpi-sub"><?= $admCnt ?> Administrator</div></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:#FEF2F2;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#B91C1C" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <div><div class="kpi-num"><?= $totalL ?></div><div class="kpi-label">Total Laporan</div><div class="kpi-sub"><?= $newL ?> laporan baru</div></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:#FEF3C7;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2"><path d="M12 2v4M12 22v-4M4 12H2M22 12h-2M19.07 4.93l-2.83 2.83M6.34 17.66l-2.83 2.83M17.66 6.34l2.83-2.83M6.34 4.93l-2.83 2.83"/><circle cx="12" cy="12" r="3"/></svg>
                    </div>
                    <div><div class="kpi-num"><?= $total_verifikasi ?></div><div class="kpi-label">Menunggu Verifikasi</div><div class="kpi-sub">Perlu cek bukti</div></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:#EFF6FF;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1D4ED8" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                    </div>
                    <div><div class="kpi-num"><?= $total_ambil ?></div><div class="kpi-label">Siap Diambil</div><div class="kpi-sub">User menunggu konfirmasi</div></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:#FDF4FF;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7E22CE" stroke-width="2"><path d="M20 12H4"/><path d="M12 4v16"/></svg>
                    </div>
                    <div><div class="kpi-num"><?= $total_kembali ?></div><div class="kpi-label">Sedang Dipinjam</div><div class="kpi-sub">Menunggu pengembalian</div></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:<?= $kritisCnt > 0 ? '#FEF2F2' : '#F0FDF4' ?>;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= $kritisCnt > 0 ? '#B91C1C' : '#15803D' ?>" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </div>
                    <div><div class="kpi-num" style="color:<?= $kritisCnt > 0 ? '#B91C1C' : '#15803D' ?>"><?= $normalCnt ?>/8</div><div class="kpi-label">Sensor Normal</div><div class="kpi-sub"><?= $kritisCnt ?> kritis</div></div>
                </div>
            </div>

            <!-- Sensor Grid -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;flex-wrap:wrap;gap:8px;">
                <div><h2 style="font-size:0.85rem;font-weight:700;color:var(--ink);font-family:'Fraunces',serif;">Status Sensor Real-Time</h2><p style="font-size:0.7rem;color:#94A3B8;margin-top:2px;">8 titik pantau aktif · update 4 detik</p></div>
                <a href="peta.php" style="display:flex;align-items:center;gap:5px;font-size:0.7rem;font-weight:600;color:var(--sawah);text-decoration:none;">Lihat Peta<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></a>
            </div>

            <div class="bento-grid">
                <?php foreach ($sensors as $s):
                    $spClass = 'sp-'.$s['status'];
                    $tileClass = 'tile-'.$s['status'];
                    $statusLabel = ['normal'=>'Normal','rendah'=>'Rendah','tinggi'=>'Tinggi','kritis'=>'Kritis'][$s['status']] ?? $s['status'];
                    $dotColor = ['normal'=>'#10B981','rendah'=>'#F97316','tinggi'=>'#3B82F6','kritis'=>'#EF4444'][$s['status']];
                ?>
                <div class="sensor-tile <?= $tileClass ?>">
                    <div class="tile-id"><?= $s['id'] ?></div>
                    <div class="tile-loc"><?= htmlspecialchars($s['lokasi']) ?></div>
                    <div class="tile-stats">
                        <div class="tile-stat"><div class="tile-stat-val"><?= number_format($s['debit'], 1) ?></div><div class="tile-stat-lbl">Debit</div></div>
                        <div class="tile-stat"><div class="tile-stat-val"><?= $s['tma'] ?></div><div class="tile-stat-lbl">TMA</div></div>
                        <div class="tile-stat"><div class="tile-stat-val"><?= number_format($s['suhu'], 1) ?>°</div><div class="tile-stat-lbl">Suhu</div></div>
                        <div class="tile-stat"><div class="tile-stat-val"><?= $s['lembap'] ?>%</div><div class="tile-stat-lbl">Lembap</div></div>
                    </div>
                    <span class="status-pill <?= $spClass ?>"><svg width="5" height="5" viewBox="0 0 6 6"><circle cx="3" cy="3" r="3" fill="<?= $dotColor ?>"/></svg><?= $statusLabel ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ===== SECTION: VERIFIKASI ===== -->
        <div id="sec-verifikasi" style="display:<?= $activeSection === 'verifikasi' ? 'block' : 'none' ?>">
            
            <!-- Badge Status -->
            <div class="verif-grid">
                <div class="verif-card" style="border-left:4px solid #F59E0B;">
                    <div class="badge-count"><?= $total_verifikasi ?></div>
                    <div class="badge-label">🟡 Menunggu Verifikasi Pembayaran</div>
                </div>
                <div class="verif-card" style="border-left:4px solid #3B82F6;">
                    <div class="badge-count"><?= $total_ambil ?></div>
                    <div class="badge-label">🔵 Siap Diambil (Lunas)</div>
                </div>
                <div class="verif-card" style="border-left:4px solid #8B5CF6;">
                    <div class="badge-count"><?= $total_kembali ?></div>
                    <div class="badge-label">🟣 Sedang Dipinjam</div>
                </div>
            </div>

            <!-- 1. VERIFIKASI PEMBAYARAN -->
            <div class="section-card">
                <div class="sc-head">
                    <div>
                        <div class="sc-title">🟡 Verifikasi Pembayaran</div>
                        <div class="sc-sub">Cek bukti transfer user, lalu verifikasi atau tolak</div>
                    </div>
                    <span class="sc-badge <?= $total_verifikasi > 0 ? 'badge-red' : 'badge-green' ?>">
                        <?= $total_verifikasi ?> menunggu
                    </span>
                </div>
                
                <?php if ($total_verifikasi > 0): ?>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>User</th>
                                <th>Alat</th>
                                <th>Total</th>
                                <th>Bukti</th>
                                <th style="min-width:180px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php mysqli_data_seek($verifikasi_data, 0); while ($row = mysqli_fetch_assoc($verifikasi_data)): ?>
                        <tr>
                            <td data-label="Invoice">
                                <span class="invoice-code"><?= $row['no_invoice'] ?></span>
                            </td>
                            <td data-label="User">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div class="avatar"><?= strtoupper(substr($row['nama_depan'], 0, 1)) ?></div>
                                    <div>
                                        <div style="font-weight:600;font-size:0.75rem;"><?= htmlspecialchars($row['nama_depan'] . ' ' . $row['nama_belakang']) ?></div>
                                        <div style="font-size:0.6rem;color:#94A3B8;"><?= $row['email'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Alat"><?= htmlspecialchars($row['nama_alat']) ?></td>
                            <td data-label="Total" style="font-weight:700;color:#059669;">Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></td>
                            <td data-label="Bukti">
                                <?php if (!empty($row['bukti_transfer'])): ?>
                                    <img src="<?= htmlspecialchars($row['bukti_transfer']) ?>" class="bukti-preview" 
                                         onclick="window.open('<?= htmlspecialchars($row['bukti_transfer']) ?>', '_blank')"
                                         title="Klik untuk lihat bukti" onerror="this.src='https://placehold.co/80x60?text=Error'">
                                <?php else: ?>
                                    <span style="font-size:0.65rem;color:#94A3B8;">Belum upload</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Aksi">
                                <div style="display:flex;gap:5px;flex-wrap:wrap;">
                                    <form method="POST" onsubmit="return confirm('Verifikasi pembayaran ini dan generate QR Code?')">
                                        <input type="hidden" name="aksi" value="verifikasi_pembayaran">
                                        <input type="hidden" name="id_peminjaman" value="<?= $row['id'] ?>">
                                        <button type="submit" class="tbl-btn tbl-btn-green" style="padding:5px 12px;">Verifikasi</button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Tolak pembayaran ini? Stok akan dikembalikan.')">
                                        <input type="hidden" name="aksi" value="tolak_pembayaran">
                                        <input type="hidden" name="id_peminjaman" value="<?= $row['id'] ?>">
                                        <button type="submit" class="tbl-btn tbl-btn-red" style="padding:5px 12px;">Tolak</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="padding:1.5rem;text-align:center;color:#94A3B8;">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;opacity:0.3;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <p style="font-weight:600;margin-bottom:4px;">Tidak ada pembayaran yang menunggu verifikasi</p>
                    <p style="font-size:0.7rem;">Semua pembayaran sudah diverifikasi</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- 2. KONFIRMASI PENGAMBILAN ALAT -->
            <div class="section-card">
                <div class="sc-head">
                    <div>
                        <div class="sc-title">🔵 Konfirmasi Pengambilan Alat</div>
                        <div class="sc-sub">User sudah bayar dan siap mengambil alat</div>
                    </div>
                    <span class="sc-badge <?= $total_ambil > 0 ? 'badge-blue' : 'badge-green' ?>">
                        <?= $total_ambil ?> siap diambil
                    </span>
                </div>
                
                <?php if ($total_ambil > 0): ?>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>User</th>
                                <th>Alat</th>
                                <th>QR Code</th>
                                <th style="min-width:150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php mysqli_data_seek($ambil_data, 0); while ($row = mysqli_fetch_assoc($ambil_data)): ?>
                        <tr>
                            <td data-label="Invoice"><span class="invoice-code"><?= $row['no_invoice'] ?></span></td>
                            <td data-label="User">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div class="avatar"><?= strtoupper(substr($row['nama_depan'], 0, 1)) ?></div>
                                    <div>
                                        <div style="font-weight:600;font-size:0.75rem;"><?= htmlspecialchars($row['nama_depan'] . ' ' . $row['nama_belakang']) ?></div>
                                        <div style="font-size:0.6rem;color:#94A3B8;"><?= $row['email'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Alat"><?= htmlspecialchars($row['nama_alat']) ?></td>
                            <td data-label="QR Code">
                                <?php if (!empty($row['qr_code'])): ?>
                                    <span style="font-size:0.65rem;font-family:'JetBrains Mono',monospace;background:#f1f5f9;padding:2px 8px;border-radius:4px;"><?= $row['qr_code'] ?></span>
                                <?php else: ?>
                                    <span style="font-size:0.65rem;color:#94A3B8;">Belum digenerate</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Aksi">
                                <form method="POST" onsubmit="return confirm('Konfirmasi pengambilan alat oleh user?')">
                                    <input type="hidden" name="aksi" value="konfirmasi_ambil">
                                    <input type="hidden" name="id_peminjaman" value="<?= $row['id'] ?>">
                                    <button type="submit" class="tbl-btn tbl-btn-blue" style="padding:5px 12px;">
                                        Konfirmasi Ambil
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="padding:1.5rem;text-align:center;color:#94A3B8;">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;opacity:0.3;"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                    <p style="font-weight:600;margin-bottom:4px;">Tidak ada alat yang siap diambil</p>
                    <p style="font-size:0.7rem;">Verifikasi pembayaran terlebih dahulu</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- 3. KONFIRMASI PENGEMBALIAN ALAT -->
            <div class="section-card">
                <div class="sc-head">
                    <div>
                        <div class="sc-title">🟣 Konfirmasi Pengembalian Alat</div>
                        <div class="sc-sub">User mengembalikan alat, cek kondisi dan denda</div>
                    </div>
                    <span class="sc-badge <?= $total_kembali > 0 ? 'badge-purple' : 'badge-green' ?>">
                        <?= $total_kembali ?> dipinjam
                    </span>
                </div>
                
                <?php if ($total_kembali > 0): ?>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>User</th>
                                <th>Alat</th>
                                <th>Estimasi Kembali</th>
                                <th>Status</th>
                                <th style="min-width:200px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php mysqli_data_seek($kembali_data, 0); while ($row = mysqli_fetch_assoc($kembali_data)):
                            $is_terlambat = strtotime($row['tanggal_kembali_estimasi']) < time();
                            $hari_terlambat = 0;
                            $denda = 0;
                            if ($is_terlambat) {
                                $estimasi = strtotime($row['tanggal_kembali_estimasi']);
                                $sekarang = time();
                                $hari_terlambat = floor(($sekarang - $estimasi) / (60 * 60 * 24));
                                $harga_per_hari = $row['total_bayar'] / $row['durasi'];
                                $denda = $hari_terlambat * $harga_per_hari;
                            }
                        ?>
                        <tr style="<?= $is_terlambat ? 'background:#FEF2F2;' : '' ?>">
                            <td data-label="Invoice"><span class="invoice-code"><?= $row['no_invoice'] ?></span></td>
                            <td data-label="User">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div class="avatar"><?= strtoupper(substr($row['nama_depan'], 0, 1)) ?></div>
                                    <div>
                                        <div style="font-weight:600;font-size:0.75rem;"><?= htmlspecialchars($row['nama_depan'] . ' ' . $row['nama_belakang']) ?></div>
                                        <div style="font-size:0.6rem;color:#94A3B8;"><?= $row['email'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Alat"><?= htmlspecialchars($row['nama_alat']) ?></td>
                            <td data-label="Estimasi Kembali">
                                <?= date('d M Y', strtotime($row['tanggal_kembali_estimasi'])) ?>
                                <?php if ($is_terlambat): ?>
                                    <br><span style="font-size:0.6rem;color:#EF4444;font-weight:700;">Terlambat <?= $hari_terlambat ?> hari</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Status">
                                <span class="status-badge status-dipinjam">Dipinjam</span>
                                <?php if ($is_terlambat): ?>
                                    <span class="status-badge" style="background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;">Terlambat</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Aksi">
                                <button onclick="openKembaliModal(<?= $row['id'] ?>, '<?= addslashes($row['nama_alat']) ?>', <?= $denda ?>, <?= $hari_terlambat ?>, <?= $row['total_bayar'] ?>, <?= $row['durasi'] ?>)" 
                                        class="tbl-btn tbl-btn-purple" style="padding:5px 12px;cursor:pointer;">
                                    Konfirmasi Kembali
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="padding:1.5rem;text-align:center;color:#94A3B8;">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;opacity:0.3;"><path d="M20 12H4"/><path d="M12 4v16"/></svg>
                    <p style="font-weight:600;margin-bottom:4px;">Tidak ada alat yang sedang dipinjam</p>
                    <p style="font-size:0.7rem;">Semua alat sudah dikembalikan</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== SECTION: USERS ===== -->
        <div id="sec-users" style="display:<?= $activeSection === 'users' ? 'block' : 'none' ?>">
            <div class="section-card">
                <div class="sc-head">
                    <div><div class="sc-title">Kelola Pengguna</div><div class="sc-sub">Ubah role atau hapus pengguna terdaftar</div></div>
                    <span class="sc-badge badge-green"><?= $totalU ?> pengguna</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr><th style="width:40px;">Pengguna</th><th style="width:120px;">Username</th><th style="width:180px;">Email</th><th style="width:140px;">Role</th><th style="width:110px;">Terdaftar</th><th style="width:250px;">Aksi</th></tr>
                        </thead>
                        <tbody>
                        <?php mysqli_data_seek($users, 0); while ($u = mysqli_fetch_assoc($users)): ?>
                        <tr>
                            <td data-label="Pengguna">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="avatar"><?= strtoupper(substr($u['nama_depan'], 0, 1)) ?></div>
                                    <div>
                                        <div style="font-weight:600;font-size:0.8rem;">
                                            <?= htmlspecialchars(trim($u['nama_depan'].' '.$u['nama_belakang'])) ?>
                                            <?php if ((int)$u['id_users'] === $adminId): ?><span class="you-tag">ANDA</span><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Username"><?= htmlspecialchars($u['username']) ?></td>
                            <td data-label="Email"><?= htmlspecialchars($u['email']) ?></td>
                            <td data-label="Role"><span class="role-badge <?= bRole($u['role']) ?>"><?= lRole($u['role']) ?></span></td>
                            <td data-label="Terdaftar"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td data-label="Aksi">
                                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                    <form method="POST" style="display:flex;align-items:center;gap:5px;">
                                        <input type="hidden" name="aksi" value="ubah_role">
                                        <input type="hidden" name="id_user" value="<?= $u['id_users'] ?>">
                                        <select name="role" class="tbl-select">
                                            <?php foreach ($roleList as $rl): ?>
                                            <option value="<?= $rl ?>" <?= $u['role'] === $rl ? 'selected' : '' ?>><?= lRole($rl) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="tbl-btn tbl-btn-green"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Simpan</button>
                                    </form>
                                    <?php if ((int)$u['id_users'] !== $adminId): ?>
                                    <form method="POST" onsubmit="return confirm('Hapus pengguna <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>?')">
                                        <input type="hidden" name="aksi" value="hapus_user">
                                        <input type="hidden" name="id_user" value="<?= $u['id_users'] ?>">
                                        <button type="submit" class="tbl-btn tbl-btn-red"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>Hapus</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== SECTION: LAPORAN ===== -->
        <div id="sec-laporan" style="display:<?= $activeSection === 'laporan' ? 'block' : 'none' ?>">
            <div class="section-card">
                <div class="sc-head">
                    <div><div class="sc-title">Laporan Kendala dari Petani</div><div class="sc-sub">Pantau dan tangani masalah yang dilaporkan</div></div>
                    <?php if ($newL > 0): ?><span class="sc-badge badge-red"><?= $newL ?> laporan baru</span><?php else: ?><span class="sc-badge badge-green">Semua tertangani</span><?php endif; ?>
                </div>

                <?php if (mysqli_num_rows($laporan) === 0): ?>
                <div style="padding:2rem;text-align:center;color:#94A3B8;">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;opacity:0.3;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <p style="font-weight:600;margin-bottom:4px;">Belum ada laporan</p>
                    <p style="font-size:0.7rem;">Laporan dari petani akan muncul di sini</p>
                </div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr><th style="width:120px;">Pelapor</th><th style="width:180px;">Lokasi</th><th style="width:180px;">Kendala</th><th style="width:130px;">Tanggal</th><th style="width:110px;">Status</th><th style="width:200px;">Aksi</th></tr>
                        </thead>
                        <tbody>
                        <?php while ($lp = mysqli_fetch_assoc($laporan)):
                            $lsClass = 'ls-'.$lp['status'];
                            $lsLabel = ['baru'=>'Baru','ditangani'=>'Ditangani','selesai'=>'Selesai'][$lp['status']] ?? $lp['status'];
                            $lsDot = ['baru'=>'#EF4444','ditangani'=>'#F59E0B','selesai'=>'#10B981'][$lp['status']];
                        ?>
                        <tr>
                            <td data-label="Pelapor">
                                <div style="font-weight:600;font-size:0.8rem;"><?= htmlspecialchars($lp['nama_pelapor']) ?></div>
                                <?php if ($lp['username']): ?><div style="font-size:0.65rem;color:#94A3B8;">@<?= htmlspecialchars($lp['username']) ?></div><?php endif; ?>
                            </td>
                            <td data-label="Lokasi"><?= htmlspecialchars($lp['lokasi']) ?></td>
                            <td data-label="Kendala"><?= htmlspecialchars($lp['jenis_kendala']) ?></td>
                            <td data-label="Tanggal"><?= date('d M Y H:i', strtotime($lp['created_at'])) ?></td>
                            <td data-label="Status"><span class="status-pill <?= $lsClass ?>" style="margin:0;"><svg width="5" height="5" viewBox="0 0 6 6"><circle cx="3" cy="3" r="3" fill="<?= $lsDot ?>"/></svg><?= $lsLabel ?></span></td>
                            <td data-label="Aksi">
                                <form method="POST" style="display:flex;align-items:center;gap:5px;">
                                    <input type="hidden" name="aksi" value="ubah_status_laporan">
                                    <input type="hidden" name="id_laporan" value="<?= $lp['id_laporan'] ?>">
                                    <select name="status" class="tbl-select">
                                        <option value="baru" <?= $lp['status'] === 'baru' ? 'selected' : '' ?>>Baru</option>
                                        <option value="ditangani" <?= $lp['status'] === 'ditangani' ? 'selected' : '' ?>>Ditangani</option>
                                        <option value="selesai" <?= $lp['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                    </select>
                                    <button type="submit" class="tbl-btn tbl-btn-blue"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 11 16 11"/><path d="M20.49 15a9 9 0 1 1-.18-4.96"/></svg>Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== SECTION: KELOLA ALAT ===== -->
        <div id="sec-alat" style="display:<?= $activeSection === 'alat' ? 'block' : 'none' ?>">
            <div class="section-card">
                <div class="sc-head">
                    <div><div class="sc-title">Kelola Alat Pertanian</div><div class="sc-sub">Tambah, edit, atau hapus data alat pertanian</div></div>
                    <span class="sc-badge badge-blue"><?= $totalAlat ?> alat</span>
                </div>

                <div style="padding:1rem 1.4rem;">
                    <!-- FORM TAMBAH/EDIT ALAT -->
                    <?php $isEdit = $edit_alat !== null; ?>
                    <div style="background:<?= $isEdit ? '#FFFBEB' : '#F8FAFC' ?>;border:1px solid <?= $isEdit ? '#FDE68A' : 'rgba(138,115,87,0.12)' ?>;border-radius:12px;padding:1rem;margin-bottom:1.5rem;">
                        <?php if ($isEdit): ?>
                            <div style="font-weight:600;color:#92400E;margin-bottom:0.5rem;"> Mode Edit: <span style="font-weight:700;"><?= htmlspecialchars($edit_alat['nama_alat']) ?></span></div>
                        <?php endif; ?>
                        <form method="POST" id="formAlat">
                            <input type="hidden" name="aksi" value="<?= $isEdit ? 'edit_alat' : 'tambah_alat' ?>">
                            <?php if ($isEdit): ?>
                            <input type="hidden" name="id_alat" value="<?= $edit_alat['id'] ?>">
                            <?php endif; ?>

                            <div class="form-grid">
                                <div>
                                    <label>Nama Alat</label>
                                    <input type="text" name="nama_alat" placeholder="Contoh: Traktor Modern" required value="<?= $isEdit ? htmlspecialchars($edit_alat['nama_alat']) : '' ?>">
                                </div>
                                <div>
                                    <label>Harga Sewa / Hari</label>
                                    <input type="number" name="harga" placeholder="500000" step="0.01" required value="<?= $isEdit ? $edit_alat['harga'] : '' ?>">
                                </div>
                                <div>
                                    <label>Stok</label>
                                    <input type="number" name="stok" placeholder="5" required value="<?= $isEdit ? $edit_alat['stok'] : '' ?>">
                                </div>
                            </div>

                            <div style="margin-bottom:12px;">
                                <label style="font-size:0.7rem;font-weight:600;color:#4B7563;display:block;margin-bottom:4px;">Deskripsi</label>
                                <textarea name="deskripsi" rows="2" placeholder="Deskripsi alat..." style="width:100%;padding:8px 12px;border:1px solid rgba(138,115,87,0.18);border-radius:10px;font-size:0.8rem;outline:none;font-family:inherit;background:white;"><?= $isEdit ? htmlspecialchars($edit_alat['deskripsi']) : '' ?></textarea>
                            </div>

                            <!-- URL GAMBAR -->
                            <div style="margin-bottom:12px;">
                                <label style="font-size:0.7rem;font-weight:600;color:#4B7563;display:block;margin-bottom:4px;">URL Gambar</label>
                                <input type="text" name="gambar_url" id="gambarUrl" placeholder="https://images.unsplash.com/photo-..." style="width:100%;padding:8px 12px;border:1px solid rgba(138,115,87,0.18);border-radius:10px;font-size:0.8rem;outline:none;font-family:inherit;background:white;" value="<?= $isEdit ? htmlspecialchars($edit_alat['gambar'] ?? '') : '' ?>">
                                <div style="font-size:0.6rem;color:#94A3B8;margin-top:4px;">Masukkan URL gambar dari internet (contoh: images.unsplash.com)</div>
                            </div>

                            <!-- PREVIEW GAMBAR -->
                            <?php if ($isEdit && !empty($edit_alat['gambar'])): ?>
                            <div style="margin-bottom:12px;">
                                <label style="font-size:0.7rem;font-weight:600;color:#4B7563;display:block;margin-bottom:4px;">Preview Gambar Saat Ini</label>
                                <img src="<?= htmlspecialchars($edit_alat['gambar']) ?>" class="current-image" onerror="this.style.display='none'">
                            </div>
                            <?php endif; ?>

                            <div id="previewUrl" style="margin-bottom:12px;display:none;">
                                <label style="font-size:0.7rem;font-weight:600;color:#4B7563;display:block;margin-bottom:4px;">Preview Gambar:</label>
                                <img id="previewUrlImage" src="" style="width:100%;max-height:200px;object-fit:cover;border-radius:10px;border:1px solid #e2e8f0;">
                            </div>

                            <div style="display:flex;gap:8px;">
                                <button type="submit" class="tbl-btn <?= $isEdit ? 'tbl-btn-yellow' : 'tbl-btn-green' ?>" style="padding:8px 20px;font-size:0.8rem;">
                                    <?= $isEdit ? '💾 Simpan Perubahan' : '➕ Tambah Alat' ?>
                                </button>
                                <?php if ($isEdit): ?>
                                <a href="dashboard.php#alat" class="tbl-btn" style="padding:8px 20px;font-size:0.8rem;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;text-decoration:none;border-radius:8px;">Batal</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- TABEL DAFTAR ALAT -->
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr><th style="width:80px;">Foto</th><th style="width:200px;">Nama Alat</th><th style="width:150px;">Harga / Hari</th><th style="width:120px;">Stok</th><th style="width:200px;">Aksi</th></tr>
                            </thead>
                            <tbody>
                            <?php mysqli_data_seek($alat, 0); while ($row = mysqli_fetch_assoc($alat)): ?>
                            <tr>
                                <td data-label="Foto">
                                    <?php if (!empty($row['gambar'])): ?>
                                        <img src="<?= htmlspecialchars($row['gambar']) ?>" class="img-preview" onerror="this.src='https://placehold.co/60x45?text=No+Img'">
                                    <?php else: ?>
                                        <img src="https://placehold.co/60x45?text=No+Img" class="img-preview">
                                    <?php endif; ?>
                                </td>
                                <td data-label="Nama Alat" style="font-weight:600;"><?= htmlspecialchars($row['nama_alat']) ?></td>
                                <td data-label="Harga / Hari">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                                <td data-label="Stok">
                                    <span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:0.68rem;font-weight:700;<?= $row['stok'] > 0 ? 'background:#F0FDF4;color:#15803D;border:1px solid #BBF7D0;' : 'background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;' ?>">
                                        <?= (int)$row['stok'] ?> unit
                                    </span>
                                </td>
                                <td data-label="Aksi">
                                    <div style="display:flex;gap:5px;flex-wrap:wrap;">
                                        <a href="?edit_alat_id=<?= $row['id'] ?>#alat" class="tbl-btn tbl-btn-yellow" style="padding:4px 10px;font-size:0.65rem;text-decoration:none;">Edit</a>
                                        <form method="POST" onsubmit="return confirm('Hapus alat <?= htmlspecialchars($row['nama_alat'], ENT_QUOTES) ?>?')" style="display:inline;">
                                            <input type="hidden" name="aksi" value="hapus_alat">
                                            <input type="hidden" name="id_alat" value="<?= $row['id'] ?>">
                                            <button type="submit" class="tbl-btn tbl-btn-red" style="padding:4px 10px;font-size:0.65rem;">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($totalAlat == 0): ?>
                            <tr>
                                <td colspan="5" style="text-align:center;padding:2rem;color:#94A3B8;font-size:0.8rem;">
                                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;opacity:0.3;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <p style="font-weight:600;margin-bottom:4px;">Belum ada alat</p>
                                    <p style="font-size:0.7rem;">Tambahkan alat pertanian menggunakan form di atas</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== SECTION: TRANSAKSI ===== -->
        <div id="sec-transaksi" style="display:<?= $activeSection === 'transaksi' ? 'block' : 'none' ?>">
            <div class="section-card">
                <div class="sc-head">
                    <div><div class="sc-title">Daftar Transaksi User</div><div class="sc-sub">Kelola transaksi penyewaan alat pertanian</div></div>
                    <span class="sc-badge badge-blue"><?= $totalTransaksi ?> transaksi</span>
                </div>
                <div style="padding:0 0 1rem 0;overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr><th style="width:50px;">No</th><th style="width:110px;">Tanggal</th><th style="width:120px;">User</th><th style="width:150px;">Alat</th><th style="width:80px;">Durasi</th><th style="width:140px;">Total Bayar</th><th style="width:130px;">Metode</th><th style="width:110px;">Status</th><th style="width:160px;">Aksi</th></tr>
                        </thead>
                        <tbody>
                        <?php mysqli_data_seek($transaksi, 0); $no = 1; while ($row = mysqli_fetch_assoc($transaksi)): ?>
                        <tr>
                            <td data-label="No" style="text-align:center;"><?= $no++ ?></td>
                            <td data-label="Tanggal"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                            <td data-label="User"><span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:0.65rem;font-weight:700;background:#F0FDF4;color:#15803D;border:1px solid #BBF7D0;"><?= htmlspecialchars($row['username']) ?></span></td>
                            <td data-label="Alat" style="font-weight:600;"><?= htmlspecialchars($row['nama_alat']) ?></td>
                            <td data-label="Durasi"><?= (int)$row['durasi'] ?> Hari</td>
                            <td data-label="Total Bayar" style="color:#059669;font-weight:700;">Rp <?= number_format((float)$row['total_bayar'], 0, ',', '.') ?></td>
                            <td data-label="Metode"><span style="font-size:0.7rem;color:#4B7563;"><?= htmlspecialchars($row['metode_bayar']) ?></span></td>
                            <td data-label="Status">
                                <?php if ($row['status'] === 'lunas'): ?>
                                    <span class="status-badge status-lunas">Lunas</span>
                                <?php elseif ($row['status'] === 'dipinjam'): ?>
                                    <span class="status-badge status-dipinjam">Dipinjam</span>
                                <?php elseif ($row['status'] === 'menunggu_verifikasi'): ?>
                                    <span class="status-badge status-menunggu">Menunggu Verifikasi</span>
                                <?php elseif ($row['status'] === 'dikembalikan' || $row['status'] === 'selesai'): ?>
                                    <span class="status-badge status-selesai">Selesai</span>
                                <?php else: ?>
                                    <span class="status-badge status-belum">⏳ <?= ucfirst(str_replace('_', ' ', $row['status'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Aksi">
                                <?php if ($row['status'] !== 'lunas' && $row['status'] !== 'dipinjam' && $row['status'] !== 'dikembalikan' && $row['status'] !== 'selesai'): ?>
                                    <form method="POST" onsubmit="return confirm('Set transaksi ini menjadi LUNAS?')" style="display:inline;">
                                        <input type="hidden" name="aksi" value="set_lunas">
                                        <input type="hidden" name="id_transaksi" value="<?= $row['id'] ?>">
                                        <button type="submit" class="tbl-btn tbl-btn-green" style="padding:4px 10px;font-size:0.65rem;">✓ Set Lunas</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size:0.65rem;color:#94A3B8;"><?= $row['status'] === 'lunas' ? 'Lunas' : ($row['status'] === 'dipinjam' ? 'Dipinjam' : 'Selesai') ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($totalTransaksi == 0): ?>
                        <tr>
                            <td colspan="9" style="text-align:center;padding:2rem;color:#94A3B8;font-size:0.8rem;">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;opacity:0.3;"><path d="M12 2v4M12 22v-4M4 12H2M22 12h-2M19.07 4.93l-2.83 2.83M6.34 17.66l-2.83 2.83M17.66 6.34l2.83-2.83M6.34 4.93l-2.83 2.83"/><circle cx="12" cy="12" r="3"/></svg>
                                <p style="font-weight:600;margin-bottom:4px;">Belum ada transaksi</p>
                                <p style="font-size:0.7rem;">Transaksi akan muncul ketika user melakukan peminjaman</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <footer>
        &copy; 2026 <span style="color:var(--sawah);font-weight:600;">Ladusync</span> — Panel Administrator · Universitas Sebelas Maret
    </footer>
</div>
</div>

<!-- ===== MODAL PENGEMBALIAN ALAT ===== -->
<div class="modal-overlay" id="kembaliModal">
    <div class="modal-box">
        <button class="close-btn" onclick="closeKembaliModal()">&times;</button>
        <h2 style="font-family:'Fraunces',serif;font-size:1.2rem;font-weight:700;color:var(--sawah);margin-bottom:1rem;">Konfirmasi Pengembalian Alat</h2>
        
        <form method="POST" id="formKembali">
            <input type="hidden" name="aksi" value="konfirmasi_kembali">
            <input type="hidden" name="id_peminjaman" id="kembaliId">
            
            <div style="background:#f8fafc;border-radius:10px;padding:12px;margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:0.85rem;">
                    <span style="color:#94A3B8;">Alat</span>
                    <span style="font-weight:600;" id="kembaliAlat">-</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:0.85rem;">
                    <span style="color:#94A3B8;">Estimasi Kembali</span>
                    <span style="font-weight:600;" id="kembaliEstimasi">-</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:0.85rem;">
                    <span style="color:#94A3B8;">Status</span>
                    <span style="font-weight:600;color:#1e40af;" id="kembaliStatus">Dipinjam</span>
                </div>
                <div id="kembaliDendaInfo" style="display:none;background:#FEF3C7;border-radius:8px;padding:10px;margin-top:8px;">
                    <span style="font-weight:700;color:#92400E;">⚠️ Denda</span>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:0.85rem;">
                        <span style="color:#92400E;">Terlambat</span>
                        <span style="font-weight:700;color:#92400E;" id="kembaliHariTerlambat">- hari</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:0.85rem;">
                        <span style="color:#92400E;">Total Denda</span>
                        <span style="font-weight:700;color:#92400E;" id="kembaliDenda">Rp 0</span>
                    </div>
                    <div style="margin-top:6px;">
                        <label style="font-size:0.7rem;display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="checkbox" name="denda_bayar" id="dendaBayar" value="1" checked>
                            <span>✅ Denda sudah dibayar oleh user</span>
                        </label>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:12px;">
                <label style="font-size:0.7rem;font-weight:600;color:#4B7563;display:block;margin-bottom:4px;">Kondisi Alat</label>
                <select name="kondisi_alat" class="tbl-select" style="width:100%;padding:8px;">
                    <option value="baik">✅ Baik</option>
                    <option value="rusak_ringan">⚠️ Rusak Ringan</option>
                    <option value="rusak_berat">❌ Rusak Berat</option>
                </select>
            </div>

            <div style="margin-bottom:12px;">
                <label style="font-size:0.7rem;font-weight:600;color:#4B7563;display:block;margin-bottom:4px;">Catatan Pengembalian</label>
                <textarea name="catatan_kembali" rows="2" style="width:100%;padding:8px 12px;border:1px solid rgba(138,115,87,0.18);border-radius:10px;font-size:0.8rem;outline:none;font-family:inherit;background:white;" placeholder="Catatan kondisi alat atau keterangan lain..."></textarea>
            </div>

            <div style="display:flex;gap:8px;">
                <button type="submit" class="tbl-btn tbl-btn-green" style="padding:8px 20px;font-size:0.8rem;">Konfirmasi Kembali</button>
                <button type="button" class="tbl-btn" style="padding:8px 20px;font-size:0.8rem;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;" onclick="closeKembaliModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- JAVASCRIPT                                   -->
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
    });

    loadSidebarState();

    // ===== SECTION SWITCH =====
    window.showSection = function(id) {
        const sections = ['overview', 'verifikasi', 'users', 'laporan', 'alat', 'transaksi'];
        sections.forEach(function(s) {
            const el = document.getElementById('sec-' + s);
            if (el) el.style.display = (s === id) ? 'block' : 'none';
        });
        const links = document.querySelectorAll('.nav-link');
        const map = { 
            'overview': 'Dashboard', 
            'verifikasi': 'Verifikasi',
            'users': 'Kelola Pengguna', 
            'laporan': 'Laporan Kendala', 
            'alat': 'Kelola Alat', 
            'transaksi': 'Daftar Transaksi' 
        };
        links.forEach(function(link) {
            const txt = link.textContent.trim();
            if (txt === map[id]) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
        const titleMap = { 
            'overview': 'Dashboard Admin', 
            'verifikasi': 'Verifikasi Pembayaran & Pengambilan',
            'users': 'Kelola Pengguna', 
            'laporan': 'Laporan Kendala', 
            'alat': 'Kelola Alat Pertanian', 
            'transaksi': 'Daftar Transaksi' 
        };
        document.getElementById('topbar-title').textContent = titleMap[id];
        if (history.pushState) {
            history.pushState(null, null, '#' + id);
        }
    };

    // ===== DETECT SECTION FROM HASH ON LOAD =====
    const hash = window.location.hash.replace('#', '');
    if (hash && ['overview', 'verifikasi', 'users', 'laporan', 'alat', 'transaksi'].includes(hash)) {
        showSection(hash);
    }
});

// ===== MODAL PENGEMBALIAN =====
function openKembaliModal(id, namaAlat, denda, hariTerlambat, totalBayar, durasi) {
    document.getElementById('kembaliId').value = id;
    document.getElementById('kembaliAlat').textContent = namaAlat;
    
    const estimasi = new Date();
    estimasi.setDate(estimasi.getDate() + durasi);
    document.getElementById('kembaliEstimasi').textContent = estimasi.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    
    if (hariTerlambat > 0 && denda > 0) {
        document.getElementById('kembaliDendaInfo').style.display = 'block';
        document.getElementById('kembaliHariTerlambat').textContent = hariTerlambat + ' hari';
        document.getElementById('kembaliDenda').textContent = 'Rp ' + denda.toLocaleString('id-ID');
    } else {
        document.getElementById('kembaliDendaInfo').style.display = 'none';
    }
    
    document.getElementById('kembaliModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeKembaliModal() {
    document.getElementById('kembaliModal').classList.remove('active');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeKembaliModal();
    }
});

// ===== PREVIEW URL GAMBAR =====
document.addEventListener('DOMContentLoaded', function() {
    const urlInput = document.getElementById('gambarUrl');
    const previewDiv = document.getElementById('previewUrl');
    const previewImg = document.getElementById('previewUrlImage');
    
    if (urlInput) {
        urlInput.addEventListener('input', function() {
            const url = this.value.trim();
            if (url) {
                previewDiv.style.display = 'block';
                previewImg.src = url;
                previewImg.onerror = function() {
                    this.src = 'https://placehold.co/600x400/e2e8f0/64748b?text=Invalid+URL';
                };
            } else {
                previewDiv.style.display = 'none';
            }
        });
    }
});
</script>

</body>
</html>
