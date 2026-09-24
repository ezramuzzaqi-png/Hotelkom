<?php
// room_detail.php - Detail satu kamar + form booking
session_start();
require_once 'config/koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: hotels.php');
    exit;
}
$id_kamar = (int)$_GET['id'];

$stmt = mysqli_prepare($koneksi, "SELECT r.*, h.nama_hotel, h.kota, h.alamat
                                   FROM rooms r
                                   JOIN hotels h ON h.id_hotel = r.id_hotel
                                   WHERE r.id_kamar = ?");
mysqli_stmt_bind_param($stmt, "i", $id_kamar);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$room = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$room) {
    header('Location: hotels.php');
    exit;
}

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
function rupiah($n){ return 'Rp '.number_format((float)$n,0,',','.'); }

// Hitung berapa booking aktif yang overlap hari ini untuk cek ketersediaan kasar
// (stok vs jumlah booking yang masih Pending/Confirmed)
$qCek = mysqli_prepare($koneksi, "SELECT COUNT(*) AS total FROM bookings WHERE id_kamar=? AND status_booking IN ('Pending','Confirmed')");
mysqli_stmt_bind_param($qCek, "i", $id_kamar);
mysqli_stmt_execute($qCek);
$cekRes = mysqli_stmt_get_result($qCek);
$cekRow = mysqli_fetch_assoc($cekRes);
$terpakai = (int)$cekRow['total'];
mysqli_stmt_close($qCek);

$sisa = (int)$room['stok_kamar'] - $terpakai;
if ($sisa < 0) $sisa = 0;

$imgClass = 'superior';
$tipeLower = strtolower($room['tipe_kamar']);
if (strpos($tipeLower,'deluxe')!==false) $imgClass='deluxe';
if (strpos($tipeLower,'suite')!==false) $imgClass='suite';

include 'includes/header.php';
include 'includes/navbar.php';
?>
<style>
.room-detail-wrap { max-width:1100px; margin:30px auto; padding:0 20px 60px; }
.breadcrumb { font-size:13px; color:#7a8a6e; margin-bottom:16px; }
.breadcrumb a { color:#4A533C; text-decoration:none; font-weight:600; }
.detail-grid { display:grid; grid-template-columns:1.1fr .9fr; gap:26px; }
.room-gallery { background:#fff; border-radius:20px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.06); border:1px solid #eef1eb; }
.room-gallery-img { height:380px; background:#e9efe0 center/cover no-repeat; }
.room-gallery-img.superior{ background-image:url('assets/images/superior.jpe'); }
.room-gallery-img.deluxe{ background-image:url('assets/images/deluxe.jpeg'); }
.room-gallery-img.suite{ background-image:url('assets/images/suite.jpe'); }
.room-gallery-thumbs { display:flex; gap:8px; padding:12px; background:#fbf5f5; }
.room-gallery-thumbs span { flex:1; height:60px; border-radius:10px; background:#e9efe0 center/cover; opacity:.7; border:2px solid transparent; }
.room-gallery-thumbs span.active{ opacity:1; border-color:#A3B18A; }
.room-info { background:#fff; border-radius:20px; padding:24px; box-shadow:0 6px 24px rgba(0,0,0,.06); border:1px solid #eef1eb; }
.room-info h1 { font-size:26px; color:#2F3526; margin:0 0 4px; background:none; -webkit-text-fill-color:#2F3526; }
.room-info .hotel-name { color:#7a8a6e; font-size:14px; margin-bottom:10px; display:flex; gap:6px; align-items:center; }
.room-price-big { font-size:26px; font-weight:800; color:#4A533C; margin:14px 0 4px; }
.room-price-big small{ font-size:13px; font-weight:400; color:#7a8a6e; }
.room-meta-row { display:flex; gap:10px; flex-wrap:wrap; margin:12px 0; }
.room-meta-row span{ padding:6px 12px; border-radius:999px; font-size:13px; font-weight:600; }
.meta-stok{ background:#e8f6ee; color:#1f7a45; }
.meta-stok.habis{ background:#fdeaea; color:#b32d2d; }
.meta-hotel{ background:#f0f3eb; color:#4A533C; }
.room-desc-box{ background:#fbf5f5; border-radius:12px; padding:14px; color:#5a6650; font-size:14px; line-height:1.7; margin-top:12px; }
.booking-card { background:#fff; border-radius:20px; padding:22px; box-shadow:0 6px 24px rgba(0,0,0,.06); border:1px solid #eef1eb; position:sticky; top:20px; }
.booking-card h3{ margin:0 0 14px; font-size:18px; color:#2F3526; }
.booking-card label{ display:block; font-size:13px; font-weight:600; color:#4A533C; margin:12px 0 6px; }
.booking-card input[type=date]{
    width:100%; padding:12px 14px; border:1px solid #d9e2d0; border-radius:12px; font:inherit; font-size:14px; outline:none; box-sizing:border-box;
}
.booking-card input:focus{ border-color:#A3B18A; box-shadow:0 0 0 3px rgba(163,177,138,.2); }
.total-preview{ background:#f0f3eb; border-radius:12px; padding:14px; margin:16px 0; display:flex; justify-content:space-between; align-items:center; }
.total-preview strong{ color:#4A533C; }
.btn-book-now{
    width:100%; padding:14px; background:#A3B18A; color:#2F3526; border:none; border-radius:999px; font-weight:700; font-size:15px; cursor:pointer; transition:all .2s;
}
.btn-book-now:hover{ background:#4A533C; color:#fff; transform:translateY(-1px); }
.btn-book-now:disabled{ background:#ddd; color:#888; cursor:not-allowed; transform:none; }
.alert{ padding:12px 14px; border-radius:12px; font-size:13px; margin-bottom:12px; }
.alert-warn{ background:#fff7dc; color:#7a5a00; border:1px solid #ffeaa0; }
.alert-info{ background:#eef6ff; color:#1a5a8a; border:1px solid #cfe2ff; }
@media(max-width:900px){ .detail-grid{grid-template-columns:1fr;} .booking-card{position:static;} }
</style>

<div class="room-detail-wrap">
    <div class="breadcrumb">
        <a href="hotels.php">Hotel</a> &rsaquo;
        <a href="hotel_detail.php?id=<?php echo (int)$room['id_hotel']; ?>"><?php echo e($room['nama_hotel']); ?></a> &rsaquo;
        <span><?php echo e($room['tipe_kamar']); ?></span>
    </div>

    <div class="detail-grid">
        <div>
            <div class="room-gallery">
                <div class="room-gallery-img <?php echo $imgClass; ?>"></div>
                <div class="room-gallery-thumbs">
                    <span class="active" style="background-image:url('assets/images/superior.jpe')"></span>
                    <span style="background-image:url('assets/images/deluxe.jpeg')"></span>
                    <span style="background-image:url('assets/images/suite.jpe')"></span>
                </div>
            </div>
            <div class="room-info" style="margin-top:18px;">
                <h1><?php echo e($room['tipe_kamar']); ?></h1>
                <div class="hotel-name"><i class="fa-solid fa-hotel"></i> <?php echo e($room['nama_hotel']); ?> &middot; <?php echo e($room['kota']); ?></div>
                <div class="hotel-name" style="font-size:13px;"><i class="fa-solid fa-location-dot"></i> <?php echo e($room['alamat']); ?></div>

                <div class="room-price-big"><?php echo rupiah($room['harga_per_malam']); ?> <small>/ malam</small></div>

                <div class="room-meta-row">
                    <span class="meta-hotel"><i class="fa-solid fa-bed"></i> Stok <?php echo (int)$room['stok_kamar']; ?> kamar</span>
                    <span class="meta-stok <?php echo $sisa<=0?'habis':''; ?>">
                        <?php echo $sisa<=0 ? 'Stok Habis' : 'Sisa '.$sisa.' kamar (est.)'; ?>
                    </span>
                    <span class="meta-hotel"><i class="fa-solid fa-door-open"></i> <?php echo e($room['tipe_kamar']); ?></span>
                </div>

                <div class="room-desc-box">
                    <strong style="color:#4A533C;">Deskripsi Kamar</strong><br>
                    <?php echo $room['deskripsi_kamar'] ? nl2br(e($room['deskripsi_kamar'])) : 'Kamar nyaman dengan fasilitas lengkap, cocok untuk liburan maupun perjalanan bisnis. Dilengkapi AC, WiFi, dan layanan kebersihan harian.'; ?>
                </div>

                <div style="margin-top:16px; display:flex; gap:8px;">
                    <a href="hotel_detail.php?id=<?php echo (int)$room['id_hotel']; ?>" style="padding:10px 18px; background:#fff; border:1px solid #d9e2d0; border-radius:999px; text-decoration:none; color:#4A533C; font-weight:600; font-size:13px;"><i class="fa-solid fa-arrow-left"></i> Kembali ke Hotel</a>
                    <a href="hotels.php" style="padding:10px 18px; background:#f0f3eb; border-radius:999px; text-decoration:none; color:#4A533C; font-weight:600; font-size:13px;">Lihat Hotel Lain</a>
                </div>
            </div>
        </div>

        <div>
            <div class="booking-card">
                <h3><i class="fa-solid fa-calendar-check" style="color:#A3B18A;"></i> Booking Kamar Ini</h3>

                <?php if ($sisa <= 0): ?>
                    <div class="alert alert-warn">Maaf, stok kamar untuk tipe ini sedang habis. Silakan pilih tipe lain.</div>
                    <button class="btn-book-now" disabled>Stok Habis</button>
                <?php elseif (!isset($_SESSION['id_user'])): ?>
                    <div class="alert alert-info">Silakan <a href="login.php" style="color:#1a5a8a;font-weight:700;">login</a> terlebih dahulu untuk melakukan booking.</div>
                    <a href="login.php" style="display:block;text-align:center;padding:14px;background:#4A533C;color:#fff;border-radius:999px;text-decoration:none;font-weight:700;">Login untuk Booking</a>
                <?php else: ?>
                    <form id="formBooking" action="booking.php" method="POST">
                        <input type="hidden" name="id_kamar" value="<?php echo (int)$room['id_kamar']; ?>">
                        <label for="checkin">Tanggal Check-in</label>
                        <input type="date" id="checkin" name="tanggal_checkin" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">

                        <label for="checkout">Tanggal Check-out</label>
                        <input type="date" id="checkout" name="tanggal_checkout" required>

                        <div class="total-preview">
                            <span style="font-size:13px;color:#5a6650;">
                                <span id="malamText">0 malam</span><br>
                                <small id="hargaSatuan"><?php echo rupiah($room['harga_per_malam']); ?> / malam</small>
                            </span>
                            <strong id="totalPreview"><?php echo rupiah(0); ?></strong>
                        </div>

                        <button type="submit" class="btn-book-now" id="btnSubmit">Booking Sekarang</button>
                        <p style="font-size:11px;color:#7a8a6e;text-align:center;margin-top:10px;">Pembayaran dilakukan di langkah selanjutnya. Status awal: Pending.</p>
                    </form>
                <?php endif; ?>
            </div>

            <div style="background:#fbf5f5;border:1px solid #eef1eb;border-radius:16px;padding:16px;margin-top:16px;">
                <h4 style="margin:0 0 8px;color:#4A533C;font-size:14px;"><i class="fa-solid fa-circle-info"></i> Kebijakan</h4>
                <ul style="margin:0;padding-left:18px;color:#5a6650;font-size:13px;line-height:1.7;">
                    <li>Check-in pukul 14:00, Check-out pukul 12:00</li>
                    <li>Pembatalan gratis hingga 24 jam sebelum check-in</li>
                    <li>Pembayaran dapat via Transfer / E-Wallet</li>
                    <li>Harga sudah termasuk pajak</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
const harga = <?php echo (int)$room['harga_per_malam']; ?>;
const checkin = document.getElementById('checkin');
const checkout = document.getElementById('checkout');
const malamText = document.getElementById('malamText');
const totalPreview = document.getElementById('totalPreview');

function rupiah(n){ return 'Rp ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."); }

function hitung(){
    if(!checkin || !checkout) return;
    const ci = new Date(checkin.value);
    const co = new Date(checkout.value);
    if(!checkin.value || !checkout.value || co <= ci){
        if(malamText) malamText.textContent = '0 malam';
        if(totalPreview) totalPreview.textContent = rupiah(0);
        return;
    }
    const diff = Math.round((co - ci)/(1000*60*60*24));
    if(malamText) malamText.textContent = diff + ' malam';
    if(totalPreview) totalPreview.textContent = rupiah(diff * harga);
}

if(checkin) checkin.addEventListener('change', function(){
    if(checkout){
        let min = new Date(this.value);
        min.setDate(min.getDate()+1);
        checkout.min = min.toISOString().split('T')[0];
        if(checkout.value && new Date(checkout.value) <= new Date(this.value)){
            checkout.value = '';
        }
    }
    hitung();
});
if(checkout) checkout.addEventListener('change', hitung);
</script>

<?php include 'includes/footer.php'; ?>
