<?php
// my_bookings.php - Riwayat booking milik user yang login
session_start();
require_once 'config/koneksi.php';
require_once 'includes/auth_check.php';

$id_user = (int)$_SESSION['id_user'];
$msg = '';

// Handle cancel booking (POST)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cancel_id'])) {
    $cancel_id = (int)$_POST['cancel_id'];
    // Pastikan booking milik user dan masih Pending
    $stmt = mysqli_prepare($koneksi, "SELECT status_booking FROM bookings WHERE id_booking=? AND id_user=?");
    mysqli_stmt_bind_param($stmt, "ii", $cancel_id, $id_user);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($row && $row['status_booking']=='Pending') {
        $stmt2 = mysqli_prepare($koneksi, "UPDATE bookings SET status_booking='Cancelled' WHERE id_booking=?");
        mysqli_stmt_bind_param($stmt2, "i", $cancel_id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
        $msg = 'Booking #'. $cancel_id .' berhasil dibatalkan.';
    } else {
        $msg = 'Gagal membatalkan — hanya booking Pending yang bisa dibatalkan.';
    }
}

// Ambil semua booking user, join kamar & hotel & agregat pembayaran
$sql = "SELECT b.*, r.tipe_kamar, r.harga_per_malam, h.nama_hotel, h.kota,
               DATEDIFF(b.tanggal_checkout, b.tanggal_checkin) AS malam,
               COALESCE((SELECT SUM(p.jumlah_bayar) FROM payments p WHERE p.id_booking=b.id_booking AND p.status_pembayaran='Success'),0) AS dibayar
        FROM bookings b
        JOIN rooms r ON r.id_kamar=b.id_kamar
        JOIN hotels h ON h.id_hotel=r.id_hotel
        WHERE b.id_user=?
        ORDER BY b.tanggal_booking DESC";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$bookings = [];
while($r=mysqli_fetch_assoc($res)) $bookings[]=$r;
mysqli_stmt_close($stmt);

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
function rupiah($n){ return 'Rp '.number_format((float)$n,0,',','.'); }

include 'includes/header.php';
include 'includes/navbar.php';
?>
<style>
.history-wrap{ max-width:1100px; margin:30px auto; padding:0 20px 60px; }
.history-wrap h1{ font-size:26px; color:#2F3526; background:none; -webkit-text-fill-color:#2F3526; margin:0 0 6px; }
.history-wrap p.sub{ color:#7a8a6e; font-size:14px; margin-bottom:20px; }
.alert{ padding:12px 14px; border-radius:12px; font-size:13px; margin-bottom:14px; }
.alert-success{ background:#e8f6ee; color:#1f7a45; border:1px solid #c5e8d3; }
.bookings-grid{ display:grid; gap:16px; }
.booking-card{ background:#fff; border-radius:16px; border:1px solid #eef1eb; box-shadow:0 4px 16px rgba(0,0,0,.05); overflow:hidden; display:grid; grid-template-columns:1fr auto; }
.booking-left{ padding:18px; }
.booking-right{ padding:18px; background:#fbf5f5; border-left:1px solid #eef1eb; display:flex; flex-direction:column; justify-content:space-between; min-width:200px; }
.hotel-name{ font-weight:700; color:#2F3526; font-size:16px; }
.kota{ font-size:13px; color:#7a8a6e; }
.meta{ display:flex; gap:10px; flex-wrap:wrap; margin:10px 0; }
.meta span{ font-size:12px; padding:5px 10px; border-radius:999px; font-weight:600; }
.meta-tipe{ background:#f0f3eb; color:#4A533C; }
.meta-malam{ background:#fff7dc; color:#7a5a00; }
.dates{ font-size:13px; color:#5a6650; line-height:1.6; }
.dates strong{ color:#2F3526; }
.badge{ display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; }
.badge-pending{ background:#fff4dc; color:#7a5a00; }
.badge-confirmed{ background:#e8f6ee; color:#1f7a45; }
.badge-cancelled{ background:#fdeaea; color:#b32d2d; }
.progress{ height:6px; background:#eef1eb; border-radius:999px; overflow:hidden; margin:8px 0; }
.progress span{ display:block; height:100%; background:linear-gradient(90deg,#A3B18A,#4A533C); }
.price{ font-weight:800; color:#4A533C; font-size:18px; }
.price small{ font-weight:400; color:#7a8a6e; font-size:12px; }
.actions{ display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
.btn{ padding:8px 14px; border-radius:999px; font-size:13px; font-weight:600; text-decoration:none; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .2s; }
.btn-pay{ background:#A3B18A; color:#2F3526; }
.btn-pay:hover{ background:#4A533C; color:#fff; }
.btn-detail{ background:#fff; color:#4A533C; border:1px solid #d9e2d0; }
.btn-detail:hover{ background:#f0f3eb; }
.btn-cancel{ background:#fdeaea; color:#b32d2d; border:1px solid #f5c2c2; }
.btn-cancel:hover{ background:#b32d2d; color:#fff; }
.empty{ background:#fff; border:1px dashed #cfd8c5; border-radius:16px; padding:40px; text-align:center; }
.empty h3{ color:#4A533C; margin-bottom:6px; }
@media(max-width:760px){ .booking-card{grid-template-columns:1fr;} .booking-right{border-left:none;border-top:1px solid #eef1eb;} }
</style>

<div class="history-wrap">
    <h1>Riwayat Booking</h1>
    <p class="sub">Pantau status pemesanan, lakukan pembayaran, atau batalkan booking yang masih pending.</p>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?php echo e($msg); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Booking berhasil dibuat.</div>
    <?php endif; ?>

    <?php if (empty($bookings)): ?>
        <div class="empty">
            <h3>Belum ada booking</h3>
            <p style="color:#7a8a6e;font-size:14px;">Kamu belum melakukan pemesanan apapun. Jelajahi hotel dan booking kamar favoritmu.</p>
            <br>
            <a href="hotels.php" style="display:inline-block;padding:12px 22px;background:#4A533C;color:#fff;border-radius:999px;text-decoration:none;font-weight:600;">Cari Hotel Sekarang</a>
        </div>
    <?php else: ?>
        <div class="bookings-grid">
            <?php foreach ($bookings as $b):
                $persen = $b['total_biaya']>0 ? min(100, round($b['dibayar']/$b['total_biaya']*100)) : 0;
                $lunas = $persen >= 100;
            ?>
            <div class="booking-card">
                <div class="booking-left">
                    <div style="display:flex;justify-content:space-between;align-items:start;gap:10px;">
                        <div>
                            <div class="hotel-name"><?php echo e($b['nama_hotel']); ?></div>
                            <div class="kota"><i class="fa-solid fa-location-dot" style="color:#A3B18A;"></i> <?php echo e($b['kota']); ?> &middot; <?php echo e($b['tipe_kamar']); ?></div>
                        </div>
                        <span class="badge <?php echo $b['status_booking']=='Confirmed'?'badge-confirmed':($b['status_booking']=='Cancelled'?'badge-cancelled':'badge-pending'); ?>">
                            <?php echo e($b['status_booking']); ?>
                        </span>
                    </div>

                    <div class="meta">
                        <span class="meta-tipe"><i class="fa-solid fa-bed"></i> <?php echo e($b['tipe_kamar']); ?></span>
                        <span class="meta-malam"><?php echo (int)$b['malam']; ?> malam &times; <?php echo rupiah($b['harga_per_malam']); ?></span>
                        <span class="meta-tipe">ID #<?php echo (int)$b['id_booking']; ?></span>
                    </div>

                    <div class="dates">
                        <div><strong>Check-in:</strong> <?php echo date('d M Y', strtotime($b['tanggal_checkin'])); ?> &nbsp; <strong>Check-out:</strong> <?php echo date('d M Y', strtotime($b['tanggal_checkout'])); ?></div>
                        <div style="font-size:12px;color:#7a8a6e;">Dipesan: <?php echo date('d M Y H:i', strtotime($b['tanggal_booking'])); ?></div>
                    </div>

                    <div class="progress"><span style="width:<?php echo $persen; ?>%"></span></div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;color:#7a8a6e;">
                        <span>Dibayar <?php echo rupiah($b['dibayar']); ?></span>
                        <span><?php echo $persen; ?>% &middot; Sisa <?php echo rupiah($b['total_biaya'] - $b['dibayar']); ?></span>
                    </div>
                </div>

                <div class="booking-right">
                    <div>
                        <div class="price"><?php echo rupiah($b['total_biaya']); ?><small> total</small></div>
                        <div style="font-size:12px;color:#7a8a6e;margin-top:4px;"><?php echo e($b['tipe_kamar']); ?> &middot; <?php echo (int)$b['malam']; ?> malam</div>
                    </div>
                    <div class="actions">
                        <?php if ($b['status_booking']=='Pending' && !$lunas): ?>
                            <a href="payment.php?id_booking=<?php echo (int)$b['id_booking']; ?>" class="btn btn-pay"><i class="fa-solid fa-credit-card"></i> Bayar</a>
                        <?php elseif ($lunas && $b['status_booking']=='Pending'): ?>
                            <a href="payment.php?id_booking=<?php echo (int)$b['id_booking']; ?>" class="btn btn-detail"><i class="fa-solid fa-receipt"></i> Lihat Pembayaran</a>
                        <?php else: ?>
                            <a href="payment.php?id_booking=<?php echo (int)$b['id_booking']; ?>" class="btn btn-detail"><i class="fa-solid fa-eye"></i> Detail</a>
                        <?php endif; ?>

                        <?php if ($b['status_booking']=='Pending'): ?>
                            <form method="POST" onsubmit="return confirm('Batalkan booking #<?php echo (int)$b['id_booking']; ?>? Tindakan tidak bisa dibatalkan.')" style="display:inline;">
                                <input type="hidden" name="cancel_id" value="<?php echo (int)$b['id_booking']; ?>">
                                <button type="submit" class="btn btn-cancel"><i class="fa-solid fa-xmark"></i> Batalkan</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
