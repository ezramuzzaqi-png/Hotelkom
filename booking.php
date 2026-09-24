<?php
// booking.php - Proses booking kamar (buat booking baru)
// Alur: user dari room_detail.php POST id_kamar + tanggal -> insert bookings -> redirect ke payment.php
session_start();
require_once 'config/koneksi.php';
require_once 'includes/auth_check.php'; // harus login

$id_user = (int)$_SESSION['id_user'];
$error = '';
$success = '';

// Jika GET dengan id_kamar, tampilkan form (fallback jika user langsung buka booking.php?id_kamar=5)
$prefill_kamar = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id_kamar']) && is_numeric($_GET['id_kamar'])) {
    $idk = (int)$_GET['id_kamar'];
    $stmt = mysqli_prepare($koneksi, "SELECT r.*, h.nama_hotel, h.kota FROM rooms r JOIN hotels h ON h.id_hotel=r.id_hotel WHERE r.id_kamar=?");
    mysqli_stmt_bind_param($stmt, "i", $idk);
    mysqli_stmt_execute($stmt);
    $prefill_kamar = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

// Proses POST booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kamar = isset($_POST['id_kamar']) ? (int)$_POST['id_kamar'] : 0;
    $checkin  = trim($_POST['tanggal_checkin'] ?? '');
    $checkout = trim($_POST['tanggal_checkout'] ?? '');

    if (!$id_kamar || !$checkin || !$checkout) {
        $error = 'Lengkapi tanggal check-in dan check-out.';
    } elseif (!strtotime($checkin) || !strtotime($checkout)) {
        $error = 'Format tanggal tidak valid.';
    } elseif (strtotime($checkout) <= strtotime($checkin)) {
        $error = 'Tanggal check-out harus setelah check-in.';
    } elseif (strtotime($checkin) < strtotime(date('Y-m-d'))) {
        $error = 'Tanggal check-in tidak boleh di masa lalu.';
    } else {
        // Ambil data kamar
        $stmt = mysqli_prepare($koneksi, "SELECT r.*, h.nama_hotel FROM rooms r JOIN hotels h ON h.id_hotel=r.id_hotel WHERE r.id_kamar=?");
        mysqli_stmt_bind_param($stmt, "i", $id_kamar);
        mysqli_stmt_execute($stmt);
        $kamar = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$kamar) {
            $error = 'Kamar tidak ditemukan.';
        } elseif ((int)$kamar['stok_kamar'] <= 0) {
            $error = 'Stok kamar habis.';
        } else {
            // Cek ketersediaan: hitung booking yang overlap dan belum cancelled
            $stmtC = mysqli_prepare($koneksi, "SELECT COUNT(*) AS jml FROM bookings
                                                WHERE id_kamar=? AND status_booking IN ('Pending','Confirmed')
                                                AND NOT (tanggal_checkout <= ? OR tanggal_checkin >= ?)");
            mysqli_stmt_bind_param($stmtC, "iss", $id_kamar, $checkin, $checkout);
            mysqli_stmt_execute($stmtC);
            $rowC = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtC));
            $overlap = (int)$rowC['jml'];
            mysqli_stmt_close($stmtC);

            if ($overlap >= (int)$kamar['stok_kamar']) {
                $error = 'Maaf, kamar penuh untuk tanggal tersebut. Silakan pilih tanggal lain atau tipe lain.';
            } else {
                $nights = (strtotime($checkout) - strtotime($checkin)) / 86400;
                $nights = (int)$nights;
                if ($nights < 1) $nights = 1;
                $harga = (float)$kamar['harga_per_malam'];
                $total = $harga * $nights;

                $stmtI = mysqli_prepare($koneksi, "INSERT INTO bookings (id_user, id_kamar, tanggal_checkin, tanggal_checkout, total_biaya, status_booking)
                                                    VALUES (?,?,?,?,?,'Pending')");
                // total_biaya decimal, bind as double (d)
                mysqli_stmt_bind_param($stmtI, "iissd", $id_user, $id_kamar, $checkin, $checkout, $total);
                if (mysqli_stmt_execute($stmtI)) {
                    $id_booking = mysqli_insert_id($koneksi);
                    mysqli_stmt_close($stmtI);
                    header("Location: payment.php?id_booking=" . $id_booking . "&new=1");
                    exit;
                } else {
                    $error = 'Gagal membuat booking: ' . mysqli_error($koneksi);
                    mysqli_stmt_close($stmtI);
                }
            }
        }
    }

    // Jika error saat POST dengan id_kamar, siapkan prefill lagi untuk tampilkan form ulang
    if ($error && isset($id_kamar) && $id_kamar) {
        $stmt = mysqli_prepare($koneksi, "SELECT r.*, h.nama_hotel, h.kota FROM rooms r JOIN hotels h ON h.id_hotel=r.id_hotel WHERE r.id_kamar=?");
        mysqli_stmt_bind_param($stmt, "i", $id_kamar);
        mysqli_stmt_execute($stmt);
        $prefill_kamar = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
    }
}

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
function rupiah($n){ return 'Rp '.number_format((float)$n,0,',','.'); }

include 'includes/header.php';
include 'includes/navbar.php';
?>
<style>
.booking-wrap { max-width:720px; margin:30px auto; padding:0 20px 60px; }
.booking-card-main { background:#fff; border-radius:20px; padding:26px; box-shadow:0 6px 24px rgba(0,0,0,.06); border:1px solid #eef1eb; }
.booking-card-main h1 { font-size:24px; color:#2F3526; margin:0 0 6px; background:none; -webkit-text-fill-color:#2F3526; }
.booking-card-main p.sub { color:#7a8a6e; font-size:14px; margin-bottom:18px; }
.alert{ padding:12px 14px; border-radius:12px; font-size:13px; margin-bottom:14px; }
.alert-error{ background:#fdeaea; color:#7A2E2E; border:1px solid #f5c2c2; }
.alert-success{ background:#e8f6ee; color:#1f7a45; border:1px solid #c5e8d3; }
.form-group{ margin-bottom:14px; }
.form-group label{ display:block; font-weight:600; color:#4A533C; font-size:13px; margin-bottom:6px; }
.form-group input, .form-group select{
    width:100%; box-sizing:border-box; padding:12px 14px; border:1px solid #d9e2d0; border-radius:12px; font:inherit; font-size:14px; outline:none;
}
.form-group input:focus, .form-group select:focus{ border-color:#A3B18A; box-shadow:0 0 0 3px rgba(163,177,138,.2); }
.kamar-preview{ background:#fbf5f5; border:1px solid #eef1eb; border-radius:14px; padding:16px; display:flex; gap:14px; align-items:center; margin-bottom:16px; }
.kamar-preview-img{ width:86px; height:86px; border-radius:12px; background:#e9efe0 url('assets/images/superior.jpe') center/cover no-repeat; flex-shrink:0; }
.btn-submit{ width:100%; padding:14px; background:#A3B18A; color:#2F3526; border:none; border-radius:999px; font-weight:700; font-size:15px; cursor:pointer; transition:all .2s; }
.btn-submit:hover{ background:#4A533C; color:#fff; }
.total-box{ background:#f0f3eb; border-radius:12px; padding:14px; margin:14px 0; display:flex; justify-content:space-between; align-items:center; }
</style>

<div class="booking-wrap">
    <div style="font-size:13px;color:#7a8a6e;margin-bottom:14px;">
        <a href="hotels.php" style="color:#4A533C;text-decoration:none;font-weight:600;">Hotels</a> &rsaquo; Booking
    </div>

    <div class="booking-card-main">
        <h1>Konfirmasi Booking</h1>
        <p class="sub">Periksa kembali detail pemesanan sebelum melanjutkan ke pembayaran.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <?php if ($prefill_kamar): ?>
            <div class="kamar-preview">
                <div class="kamar-preview-img"></div>
                <div>
                    <div style="font-weight:700;color:#2F3526;"><?php echo e($prefill_kamar['tipe_kamar']); ?></div>
                    <div style="font-size:13px;color:#7a8a6e;"><?php echo e($prefill_kamar['nama_hotel']); ?> &middot; <?php echo e($prefill_kamar['kota']); ?></div>
                    <div style="font-weight:700;color:#4A533C;margin-top:4px;"><?php echo rupiah($prefill_kamar['harga_per_malam']); ?> <small style="font-weight:400;color:#7a8a6e;">/ malam</small></div>
                </div>
            </div>

            <form method="POST" action="booking.php" id="bookingForm">
                <input type="hidden" name="id_kamar" value="<?php echo (int)$prefill_kamar['id_kamar']; ?>">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label>Tanggal Check-in</label>
                        <input type="date" name="tanggal_checkin" id="ci" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" value="<?php echo e($_POST['tanggal_checkin'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Check-out</label>
                        <input type="date" name="tanggal_checkout" id="co" required value="<?php echo e($_POST['tanggal_checkout'] ?? ''); ?>">
                    </div>
                </div>

                <div class="total-box">
                    <span style="font-size:13px;color:#5a6650;">
                        <span id="malamTxt">0 malam</span><br>
                        <small><?php echo rupiah($prefill_kamar['harga_per_malam']); ?> / malam</small>
                    </span>
                    <strong id="totalTxt" style="color:#4A533C;">Rp 0</strong>
                </div>

                <button type="submit" class="btn-submit"><i class="fa-solid fa-check"></i> Konfirmasi Booking</button>
                <p style="font-size:12px;color:#7a8a6e;text-align:center;margin-top:10px;">Dengan klik Konfirmasi, booking berstatus <strong>Pending</strong> dan bisa dibayar di langkah berikutnya.</p>
            </form>

            <script>
                const harga = <?php echo (int)$prefill_kamar['harga_per_malam']; ?>;
                const ci = document.getElementById('ci');
                const co = document.getElementById('co');
                function rupiah(n){ return 'Rp ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."); }
                function hitung(){
                    const v1 = ci.value, v2 = co.value;
                    if(!v1 || !v2){ document.getElementById('malamTxt').textContent='0 malam'; document.getElementById('totalTxt').textContent=rupiah(0); return; }
                    const d1 = new Date(v1), d2 = new Date(v2);
                    const diff = Math.round((d2-d1)/86400000);
                    if(isNaN(diff) || diff<=0){ document.getElementById('malamTxt').textContent='0 malam'; document.getElementById('totalTxt').textContent=rupiah(0); return; }
                    document.getElementById('malamTxt').textContent = diff + ' malam';
                    document.getElementById('totalTxt').textContent = rupiah(diff*harga);
                }
                ci.addEventListener('change', function(){
                    let min = new Date(this.value); min.setDate(min.getDate()+1);
                    co.min = min.toISOString().split('T')[0];
                    if(co.value && new Date(co.value) <= new Date(this.value)) co.value='';
                    hitung();
                });
                co.addEventListener('change', hitung);
                hitung();
            </script>

        <?php else: ?>
            <div style="text-align:center;padding:30px 10px;">
                <p style="color:#7a8a6e;">Pilih kamar terlebih dahulu untuk membuat booking.</p>
                <a href="hotels.php" style="display:inline-block;margin-top:12px;padding:12px 22px;background:#4A533C;color:#fff;border-radius:999px;text-decoration:none;font-weight:600;">Cari Hotel</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
