<?php
// payment.php - Halaman pembayaran untuk satu booking
session_start();
require_once 'config/koneksi.php';
require_once 'includes/auth_check.php';

$id_user = (int)$_SESSION['id_user'];

// id_booking wajib ada
if (!isset($_GET['id_booking']) || !is_numeric($_GET['id_booking'])) {
    header('Location: my_bookings.php');
    exit;
}
$id_booking = (int)$_GET['id_booking'];
$is_new = isset($_GET['new']) && $_GET['new']=='1';

$error=''; $success='';

// Ambil booking milik user, join kamar & hotel
$stmt = mysqli_prepare($koneksi, "SELECT b.*, r.tipe_kamar, r.harga_per_malam, r.id_hotel,
                                         h.nama_hotel, h.kota, h.alamat,
                                         DATEDIFF(b.tanggal_checkout, b.tanggal_checkin) AS malam
                                  FROM bookings b
                                  JOIN rooms r ON r.id_kamar=b.id_kamar
                                  JOIN hotels h ON h.id_hotel=r.id_hotel
                                  WHERE b.id_booking=? AND b.id_user=?");
mysqli_stmt_bind_param($stmt, "ii", $id_booking, $id_user);
mysqli_stmt_execute($stmt);
$booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$booking) {
    header('Location: my_bookings.php');
    exit;
}

// Ambil riwayat pembayaran booking ini
$pays = [];
$resP = $koneksi->query("SELECT * FROM payments WHERE id_booking=$id_booking ORDER BY tanggal_bayar DESC");
if ($resP) while($r=$resP->fetch_assoc()) $pays[]=$r;

// Hitung total sudah dibayar sukses
$total_dibayar = 0;
foreach ($pays as $pp) if ($pp['status_pembayaran']=='Success') $total_dibayar += (float)$pp['jumlah_bayar'];
$sisa = (float)$booking['total_biaya'] - $total_dibayar;
if ($sisa < 0) $sisa = 0;

$lunas = $sisa <= 0.01;

// Proses submit pembayaran baru
if ($_SERVER['REQUEST_METHOD']==='POST' && !$lunas) {
    $metode = trim($_POST['metode_pembayaran'] ?? '');
    $jumlah = trim($_POST['jumlah_bayar'] ?? '');

    $allowed = ['Transfer Bank','E-Wallet','Kartu Kredit','Tunai'];
    if (!in_array($metode, $allowed, true)) {
        $error = 'Metode pembayaran tidak valid.';
    } elseif (!is_numeric($jumlah) || (float)$jumlah <= 0) {
        $error = 'Jumlah bayar harus angka positif.';
    } elseif ((float)$jumlah > $sisa + 0.01) {
        $error = 'Jumlah bayar melebihi sisa tagihan ('.number_format($sisa,0,',','.').').';
    } else {
        $jumlahFloat = (float)$jumlah;
        // status langsung Success untuk demo (bisa diganti Pending jika butuh konfirmasi admin)
        $stmtIns = mysqli_prepare($koneksi, "INSERT INTO payments (id_booking, metode_pembayaran, jumlah_bayar, status_pembayaran) VALUES (?,?,?,'Success')");
        mysqli_stmt_bind_param($stmtIns, "isd", $id_booking, $metode, $jumlahFloat);
        if (mysqli_stmt_execute($stmtIns)) {
            mysqli_stmt_close($stmtIns);
            // Jika sudah lunas, update booking jadi Confirmed
            $total_baru = $total_dibayar + $jumlahFloat;
            if ($total_baru >= (float)$booking['total_biaya'] - 0.01) {
                $koneksi->query("UPDATE bookings SET status_booking='Confirmed' WHERE id_booking=$id_booking");
            }
            $success = 'Pembayaran berhasil! Terima kasih.';
            // refresh data
            header("Location: payment.php?id_booking=$id_booking&paid=1");
            exit;
        } else {
            $error = 'Gagal menyimpan pembayaran: '.mysqli_error($koneksi);
            mysqli_stmt_close($stmtIns);
        }
    }
}

if (isset($_GET['paid']) && $_GET['paid']=='1') $success='Pembayaran berhasil disimpan.';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
function rupiah($n){ return 'Rp '.number_format((float)$n,0,',','.'); }

include 'includes/header.php';
include 'includes/navbar.php';
?>
<style>
.pay-wrap{ max-width:860px; margin:30px auto; padding:0 20px 60px; }
.pay-grid{ display:grid; grid-template-columns:1.1fr .9fr; gap:22px; }
.pay-card, .history-card{ background:#fff; border-radius:18px; padding:22px; box-shadow:0 6px 24px rgba(0,0,0,.06); border:1px solid #eef1eb; }
.pay-card h2, .history-card h3{ margin:0 0 14px; color:#2F3526; font-size:18px; }
.detail-row{ display:flex; justify-content:space-between; padding:9px 0; border-bottom:1px dashed #eef1eb; font-size:14px; }
.detail-row:last-child{ border:none; }
.detail-row span:first-child{ color:#7a8a6e; }
.detail-row span:last-child{ color:#2F3526; font-weight:600; }
.detail-row.total{ background:#f0f3eb; margin:12px -8px 0; padding:12px 16px; border-radius:12px; border:none; }
.detail-row.total span:last-child{ color:#4A533C; font-size:18px; }
.badge{ display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; }
.badge-pending{ background:#fff4dc; color:#7a5a00; }
.badge-confirmed{ background:#e8f6ee; color:#1f7a45; }
.badge-cancelled{ background:#fdeaea; color:#b32d2d; }
.alert{ padding:12px 14px; border-radius:12px; font-size:13px; margin-bottom:12px; }
.alert-error{ background:#fdeaea; color:#7A2E2E; border:1px solid #f5c2c2; }
.alert-success{ background:#e8f6ee; color:#1f7a45; border:1px solid #c5e8d3; }
.alert-info{ background:#eef6ff; color:#1a5a8a; border:1px solid #cfe2ff; }
.form-group{ margin-bottom:12px; }
.form-group label{ display:block; font-weight:600; color:#4A533C; font-size:13px; margin-bottom:6px; }
.form-group select, .form-group input{ width:100%; box-sizing:border-box; padding:12px 14px; border:1px solid #d9e2d0; border-radius:12px; font:inherit; font-size:14px; outline:none; }
.form-group select:focus, .form-group input:focus{ border-color:#A3B18A; box-shadow:0 0 0 3px rgba(163,177,138,.2); }
.btn-pay{ width:100%; padding:14px; background:#A3B18A; color:#2F3526; border:none; border-radius:999px; font-weight:700; font-size:15px; cursor:pointer; transition:all .2s; }
.btn-pay:hover{ background:#4A533C; color:#fff; }
.btn-pay:disabled{ background:#ddd; color:#888; cursor:not-allowed; }
.pay-item{ display:flex; justify-content:space-between; align-items:center; padding:12px; border:1px solid #eef1eb; border-radius:12px; margin-bottom:8px; }
.pay-item:last-child{ margin:0; }
.badge-pay-success{ background:#e8f6ee; color:#1f7a45; }
.badge-pay-pending{ background:#fff4dc; color:#7a5a00; }
.badge-pay-failed{ background:#fdeaea; color:#b32d2d; }
.progress{ height:8px; background:#eef1eb; border-radius:999px; overflow:hidden; margin:10px 0 6px; }
.progress span{ display:block; height:100%; background:linear-gradient(90deg,#A3B18A,#4A533C); border-radius:999px; }
@media(max-width:860px){ .pay-grid{grid-template-columns:1fr;} }
</style>

<div class="pay-wrap">
    <div style="font-size:13px;color:#7a8a6e;margin-bottom:14px;">
        <a href="my_bookings.php" style="color:#4A533C;text-decoration:none;font-weight:600;"><i class="fa-solid fa-arrow-left"></i> Kembali ke History</a>
    </div>

    <?php if ($is_new): ?>
        <div class="alert alert-info"><i class="fa-solid fa-circle-check"></i> Booking berhasil dibuat! Silakan lakukan pembayaran untuk mengkonfirmasi pesanan.</div>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>

    <div class="pay-grid">
        <div class="pay-card">
            <h2><i class="fa-solid fa-receipt" style="color:#A3B18A;"></i> Detail Booking #<?php echo (int)$booking['id_booking']; ?></h2>

            <div class="detail-row"><span>Hotel</span><span><?php echo e($booking['nama_hotel']); ?> &middot; <?php echo e($booking['kota']); ?></span></div>
            <div class="detail-row"><span>Alamat</span><span style="max-width:190px;text-align:right;font-weight:400;"><?php echo e($booking['alamat']); ?></span></div>
            <div class="detail-row"><span>Tipe Kamar</span><span><?php echo e($booking['tipe_kamar']); ?></span></div>
            <div class="detail-row"><span>Check-in</span><span><?php echo date('d M Y', strtotime($booking['tanggal_checkin'])); ?></span></div>
            <div class="detail-row"><span>Check-out</span><span><?php echo date('d M Y', strtotime($booking['tanggal_checkout'])); ?></span></div>
            <div class="detail-row"><span>Durasi</span><span><?php echo (int)$booking['malam']; ?> malam &times; <?php echo rupiah($booking['harga_per_malam']); ?></span></div>
            <div class="detail-row"><span>Status Booking</span><span>
                <span class="badge <?php echo $booking['status_booking']=='Confirmed'?'badge-confirmed':($booking['status_booking']=='Cancelled'?'badge-cancelled':'badge-pending'); ?>">
                    <?php echo e($booking['status_booking']); ?>
                </span>
            </span></div>
            <div class="detail-row"><span>Tanggal Booking</span><span style="font-weight:400;"><?php echo date('d M Y H:i', strtotime($booking['tanggal_booking'])); ?></span></div>

            <div class="progress"><span style="width:<?php echo $booking['total_biaya']>0 ? min(100, round($total_dibayar/$booking['total_biaya']*100)) : 0; ?>%"></span></div>
            <div style="display:flex;justify-content:space-between;font-size:12px;color:#7a8a6e;">
                <span>Dibayar: <?php echo rupiah($total_dibayar); ?></span>
                <span>Sisa: <?php echo rupiah($sisa); ?></span>
            </div>

            <div class="detail-row total"><span>Total Tagihan</span><span><?php echo rupiah($booking['total_biaya']); ?></span></div>
            <?php if ($lunas): ?>
                <div class="alert alert-success" style="margin-top:12px;text-align:center;"><i class="fa-solid fa-circle-check"></i> Lunas — booking sudah dikonfirmasi.</div>
            <?php endif; ?>
        </div>

        <div>
            <div class="pay-card">
                <?php if ($lunas): ?>
                    <h3><i class="fa-solid fa-circle-check" style="color:#22a45d;"></i> Pembayaran Lunas</h3>
                    <p style="color:#7a8a6e;font-size:13px;line-height:1.6;">Tidak perlu bayar lagi. Kamu bisa melihat riwayat pembayaran di bawah dan menunggu konfirmasi check-in.</p>
                    <a href="my_bookings.php" style="display:block;text-align:center;margin-top:14px;padding:12px;background:#4A533C;color:#fff;border-radius:999px;text-decoration:none;font-weight:700;">Lihat Semua Booking</a>
                <?php elseif ($booking['status_booking']=='Cancelled'): ?>
                    <h3>Pembayaran</h3>
                    <div class="alert alert-error">Booking ini sudah dibatalkan, tidak bisa melakukan pembayaran.</div>
                <?php else: ?>
                    <h3><i class="fa-solid fa-credit-card" style="color:#A3B18A;"></i> Bayar Tagihan</h3>
                    <p style="color:#7a8a6e;font-size:13px;margin-bottom:12px;">Sisa tagihan: <strong style="color:#4A533C;"><?php echo rupiah($sisa); ?></strong></p>
                    <form method="POST">
                        <div class="form-group">
                            <label>Metode Pembayaran</label>
                            <select name="metode_pembayaran" required>
                                <option value="">-- Pilih Metode --</option>
                                <option value="Transfer Bank">Transfer Bank</option>
                                <option value="E-Wallet">E-Wallet (OVO / GoPay / Dana)</option>
                                <option value="Kartu Kredit">Kartu Kredit</option>
                                <option value="Tunai">Tunai di Hotel</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Jumlah Bayar</label>
                            <input type="number" name="jumlah_bayar" step="0.01" min="0.01" max="<?php echo $sisa; ?>" required value="<?php echo $sisa; ?>" placeholder="Contoh: <?php echo $sisa; ?>">
                            <small style="color:#7a8a6e;font-size:11px;">Maksimal <?php echo rupiah($sisa); ?>. Kamu bisa bayar penuh sekaligus.</small>
                        </div>
                        <button type="submit" class="btn-pay"><i class="fa-solid fa-lock"></i> Bayar Sekarang</button>
                        <p style="font-size:11px;color:#7a8a6e;text-align:center;margin-top:8px;">Pembayaran demo — status langsung Success & booking jadi Confirmed jika lunas.</p>
                    </form>
                <?php endif; ?>
            </div>

            <div class="history-card" style="margin-top:16px;">
                <h3>Riwayat Pembayaran</h3>
                <?php if (empty($pays)): ?>
                    <p style="color:#7a8a6e;font-size:13px;text-align:center;padding:18px 0;">Belum ada pembayaran.</p>
                <?php else: ?>
                    <?php foreach ($pays as $pay): ?>
                        <div class="pay-item">
                            <div>
                                <div style="font-weight:600;color:#2F3526;font-size:14px;"><?php echo e($pay['metode_pembayaran']); ?></div>
                                <div style="font-size:12px;color:#7a8a6e;"><?php echo date('d M Y H:i', strtotime($pay['tanggal_bayar'])); ?> &middot; <?php echo rupiah($pay['jumlah_bayar']); ?></div>
                            </div>
                            <span class="badge <?php echo $pay['status_pembayaran']=='Success'?'badge-pay-success':($pay['status_pembayaran']=='Pending'?'badge-pay-pending':'badge-pay-failed'); ?>">
                                <?php echo e($pay['status_pembayaran']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
