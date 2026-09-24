<?php
// profile.php - Lihat & edit profil user
session_start();
require_once 'config/koneksi.php';
require_once 'includes/auth_check.php';

$id_user = (int)$_SESSION['id_user'];
$error=''; $success='';

// Ambil data user terbaru
$stmt = mysqli_prepare($koneksi, "SELECT id_user, nama, email, no_telepon, alamat, created_at FROM users WHERE id_user=?");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) { header('Location: logout.php'); exit; }

// Handle update profil
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['aksi']) && $_POST['aksi']=='update_profil') {
    $nama       = trim($_POST['nama'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $alamat     = trim($_POST['alamat'] ?? '');

    if ($nama=='' || $email=='') {
        $error='Nama dan email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error='Format email tidak valid.';
    } else {
        // Cek email duplikat selain diri sendiri
        $cek = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE email=? AND id_user<>?");
        mysqli_stmt_bind_param($cek, "si", $email, $id_user);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);
        if (mysqli_stmt_num_rows($cek) > 0) {
            $error='Email sudah dipakai akun lain.';
            mysqli_stmt_close($cek);
        } else {
            mysqli_stmt_close($cek);
            $upd = mysqli_prepare($koneksi, "UPDATE users SET nama=?, email=?, no_telepon=?, alamat=? WHERE id_user=?");
            mysqli_stmt_bind_param($upd, "ssssi", $nama, $email, $no_telepon, $alamat, $id_user);
            if (mysqli_stmt_execute($upd)) {
                $_SESSION['nama']  = $nama;
                $_SESSION['email'] = $email;
                $success='Profil berhasil diperbarui.';
                $user['nama']=$nama; $user['email']=$email; $user['no_telepon']=$no_telepon; $user['alamat']=$alamat;
            } else {
                $error='Gagal menyimpan: '.mysqli_error($koneksi);
            }
            mysqli_stmt_close($upd);
        }
    }
}

// Handle ganti password
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['aksi']) && $_POST['aksi']=='ganti_password') {
    $pass_lama   = $_POST['password_lama'] ?? '';
    $pass_baru   = $_POST['password_baru'] ?? '';
    $konfirmasi  = $_POST['konfirmasi'] ?? '';

    if ($pass_lama=='' || $pass_baru=='' || $konfirmasi=='') {
        $error='Lengkapi semua field password.';
    } elseif (strlen($pass_baru) < 6) {
        $error='Password baru minimal 6 karakter.';
    } elseif ($pass_baru !== $konfirmasi) {
        $error='Konfirmasi password tidak cocok.';
    } else {
        $stmtP = mysqli_prepare($koneksi, "SELECT password FROM users WHERE id_user=?");
        mysqli_stmt_bind_param($stmtP, "i", $id_user);
        mysqli_stmt_execute($stmtP);
        $rowP = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtP));
        mysqli_stmt_close($stmtP);
        if (!$rowP || !password_verify($pass_lama, $rowP['password'])) {
            $error='Password lama salah.';
        } else {
            $hash = password_hash($pass_baru, PASSWORD_DEFAULT);
            $upd2 = mysqli_prepare($koneksi, "UPDATE users SET password=? WHERE id_user=?");
            mysqli_stmt_bind_param($upd2, "si", $hash, $id_user);
            if (mysqli_stmt_execute($upd2)) $success='Password berhasil diganti.';
            else $error='Gagal mengganti password.';
            mysqli_stmt_close($upd2);
        }
    }
}

// Hitung statistik booking user untuk info
$stat = $koneksi->query("SELECT COUNT(*) AS total,
                                SUM(status_booking='Pending') AS pending,
                                SUM(status_booking='Confirmed') AS confirmed
                         FROM bookings WHERE id_user=$id_user")->fetch_assoc();

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }

include 'includes/header.php';
include 'includes/navbar.php';
?>
<style>
.profile-wrap{ max-width:1000px; margin:30px auto; padding:0 20px 60px; display:grid; grid-template-columns:320px 1fr; gap:20px; }
.profile-card, .form-card{ background:#fff; border-radius:18px; padding:22px; box-shadow:0 6px 24px rgba(0,0,0,.06); border:1px solid #eef1eb; }
.avatar{ width:86px; height:86px; border-radius:50%; background:linear-gradient(135deg,#A3B18A,#4A533C); color:#fff; display:grid; place-items:center; font-size:34px; font-weight:800; margin:0 auto 12px; }
.profile-card h2{ text-align:center; font-size:18px; color:#2F3526; margin:0; }
.profile-card p.email{ text-align:center; color:#7a8a6e; font-size:13px; margin:4px 0 14px; }
.info-row{ display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px dashed #eef1eb; font-size:13px; }
.info-row:last-child{ border:none; }
.info-row span:first-child{ color:#7a8a6e; }
.info-row span:last-child{ color:#2F3526; font-weight:600; max-width:160px; text-align:right; word-break:break-word; }
.stats-mini{ display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-top:14px; }
.stat-mini{ background:#fbf5f5; border-radius:12px; padding:10px; text-align:center; }
.stat-mini strong{ display:block; color:#4A533C; font-size:18px; }
.stat-mini span{ font-size:11px; color:#7a8a6e; }
.form-card h3{ margin:0 0 14px; color:#2F3526; font-size:18px; border-bottom:2px solid #f0f3eb; padding-bottom:10px; }
.form-group{ margin-bottom:12px; }
.form-group label{ display:block; font-weight:600; color:#4A533C; font-size:13px; margin-bottom:6px; }
.form-group input, .form-group textarea{ width:100%; box-sizing:border-box; padding:12px 14px; border:1px solid #d9e2d0; border-radius:12px; font:inherit; font-size:14px; outline:none; }
.form-group textarea{ min-height:86px; resize:vertical; }
.form-group input:focus, .form-group textarea:focus{ border-color:#A3B18A; box-shadow:0 0 0 3px rgba(163,177,138,.2); }
.btn{ padding:12px 18px; border-radius:999px; font-weight:700; font-size:14px; border:none; cursor:pointer; transition:all .2s; }
.btn-primary{ background:#A3B18A; color:#2F3526; }
.btn-primary:hover{ background:#4A533C; color:#fff; }
.btn-outline{ background:#fff; color:#4A533C; border:1px solid #d9e2d0; }
.alert{ padding:12px 14px; border-radius:12px; font-size:13px; margin-bottom:12px; }
.alert-error{ background:#fdeaea; color:#7A2E2E; border:1px solid #f5c2c2; }
.alert-success{ background:#e8f6ee; color:#1f7a45; border:1px solid #c5e8d3; }
.tabs{ display:flex; gap:8px; margin-bottom:16px; }
.tab-btn{ padding:8px 14px; border-radius:999px; font-size:13px; font-weight:600; border:1px solid #d9e2d0; background:#fff; color:#4A533C; cursor:pointer; }
.tab-btn.active{ background:#4A533C; color:#fff; border-color:#4A533C; }
.tab-pane{ display:none; }
.tab-pane.active{ display:block; }
@media(max-width:860px){ .profile-wrap{grid-template-columns:1fr;} }
</style>

<div class="profile-wrap">
    <div class="profile-card">
        <div class="avatar"><?php echo e(strtoupper(mb_substr($user['nama'],0,1))); ?></div>
        <h2><?php echo e($user['nama']); ?></h2>
        <p class="email"><?php echo e($user['email']); ?></p>

        <div class="info-row"><span>User ID</span><span>#<?php echo (int)$user['id_user']; ?></span></div>
        <div class="info-row"><span>No. Telepon</span><span><?php echo $user['no_telepon'] ? e($user['no_telepon']) : '-'; ?></span></div>
        <div class="info-row"><span>Alamat</span><span><?php echo $user['alamat'] ? e(mb_strimwidth($user['alamat'],0,40,'...')) : '-'; ?></span></div>
        <div class="info-row"><span>Bergabung</span><span><?php echo date('d M Y', strtotime($user['created_at'])); ?></span></div>

        <div class="stats-mini">
            <div class="stat-mini"><strong><?php echo (int)($stat['total'] ?? 0); ?></strong><span>Total Booking</span></div>
            <div class="stat-mini"><strong><?php echo (int)($stat['pending'] ?? 0); ?></strong><span>Pending</span></div>
            <div class="stat-mini"><strong><?php echo (int)($stat['confirmed'] ?? 0); ?></strong><span>Confirmed</span></div>
        </div>

        <div style="margin-top:16px; display:grid; gap:8px;">
            <a href="my_bookings.php" style="text-align:center; padding:10px; background:#4A533C; color:#fff; border-radius:999px; text-decoration:none; font-weight:600; font-size:13px;"><i class="fa-solid fa-clock-rotate-left"></i> Lihat History</a>
            <a href="logout.php" onclick="return confirm('Yakin ingin logout?')" style="text-align:center; padding:10px; background:#fdeaea; color:#b32d2d; border:1px solid #f5c2c2; border-radius:999px; text-decoration:none; font-weight:600; font-size:13px;">Logout</a>
        </div>
    </div>

    <div>
        <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>

        <div class="tabs">
            <button type="button" class="tab-btn active" onclick="switchTab('profil')">Edit Profil</button>
            <button type="button" class="tab-btn" onclick="switchTab('password')">Ganti Password</button>
        </div>

        <div id="tab-profil" class="tab-pane active">
            <div class="form-card">
                <h3><i class="fa-solid fa-user-pen" style="color:#A3B18A;"></i> Edit Profil</h3>
                <form method="POST">
                    <input type="hidden" name="aksi" value="update_profil">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" required value="<?php echo e($user['nama']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required value="<?php echo e($user['email']); ?>">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label>No. Telepon</label>
                            <input type="text" name="no_telepon" maxlength="15" value="<?php echo e($user['no_telepon']); ?>" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="form-group">
                            <label>ID User</label>
                            <input type="text" value="#<?php echo (int)$user['id_user']; ?>" disabled style="background:#fbf5f5;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" placeholder="Alamat lengkap"><?php echo e($user['alamat']); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa-solid fa-save"></i> Simpan Perubahan</button>
                </form>
            </div>
        </div>

        <div id="tab-password" class="tab-pane">
            <div class="form-card">
                <h3><i class="fa-solid fa-key" style="color:#A3B18A;"></i> Ganti Password</h3>
                <form method="POST">
                    <input type="hidden" name="aksi" value="ganti_password">
                    <div class="form-group">
                        <label>Password Lama</label>
                        <input type="password" name="password_lama" required placeholder="Masukkan password lama">
                    </div>
                    <div class="form-group">
                        <label>Password Baru (min. 6 karakter)</label>
                        <input type="password" name="password_baru" required minlength="6" placeholder="Password baru">
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password Baru</label>
                        <input type="password" name="konfirmasi" required minlength="6" placeholder="Ulangi password baru">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa-solid fa-lock"></i> Ganti Password</button>
                    <p style="font-size:12px;color:#7a8a6e;text-align:center;margin-top:10px;">Password disimpan ter-hash, tidak ada yang bisa melihat password aslimu.</p>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(name){
    document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById('tab-'+name).classList.add('active');
    event.target.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>
