<?php
// hotel_detail.php - Detail hotel + daftar kamar
session_start();
require_once 'config/koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: hotels.php');
    exit;
}
$id_hotel = (int)$_GET['id'];

// Ambil data hotel
$stmt = mysqli_prepare($koneksi, "SELECT * FROM hotels WHERE id_hotel = ?");
mysqli_stmt_bind_param($stmt, "i", $id_hotel);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$hotel = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$hotel) {
    header('Location: hotels.php');
    exit;
}

// Ambil kamar di hotel ini
$stmt2 = mysqli_prepare($koneksi, "SELECT * FROM rooms WHERE id_hotel = ? ORDER BY harga_per_malam ASC");
mysqli_stmt_bind_param($stmt2, "i", $id_hotel);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);
$rooms = [];
while ($r = mysqli_fetch_assoc($res2)) $rooms[] = $r;
mysqli_stmt_close($stmt2);

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
function rupiah($n){ return 'Rp '.number_format((float)$n,0,',','.'); }

include 'includes/header.php';
include 'includes/navbar.php';
?>
<style>
.detail-wrap { max-width:1100px; margin:30px auto; padding:0 20px 60px; }
.breadcrumb { font-size:13px; color:#7a8a6e; margin-bottom:16px; }
.breadcrumb a { color:#4A533C; text-decoration:none; font-weight:600; }
.breadcrumb a:hover { text-decoration:underline; }
.hotel-hero-detail {
    display:grid; grid-template-columns:1.2fr .8fr; gap:28px; background:#fff;
    border-radius:20px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.06); border:1px solid #eef1eb;
}
.hotel-hero-img { min-height:360px; background:#e9efe0 url('assets/images/lobby.jpe') center/cover no-repeat; }
.hotel-hero-info { padding:26px 24px; display:flex; flex-direction:column; }
.hotel-hero-info h1 { font-size:28px; color:#2F3526; margin:0 0 6px; background:none; -webkit-text-fill-color:#2F3526; line-height:1.2; }
.hotel-kota { display:inline-flex; align-items:center; gap:6px; background:#f0f3eb; color:#4A533C; padding:6px 12px; border-radius:999px; font-size:13px; font-weight:700; width:fit-content; }
.hotel-alamat { color:#5a6650; font-size:14px; margin:14px 0; line-height:1.6; display:flex; gap:8px; }
.hotel-alamat i { color:#A3B18A; margin-top:4px; }
.hotel-deskripsi { color:#5a6650; font-size:14.5px; line-height:1.7; margin-top:12px; }
.hotel-stats { display:flex; gap:14px; margin-top:18px; }
.stat-mini { background:#fbf5f5; border:1px solid #eef1eb; border-radius:12px; padding:12px 16px; flex:1; text-align:center; }
.stat-mini strong { display:block; font-size:20px; color:#4A533C; }
.stat-mini span { font-size:12px; color:#7a8a6e; }
.rooms-section { margin-top:32px; }
.rooms-section h2 { font-size:22px; color:#2F3526; margin-bottom:6px; }
.rooms-section p.sub { color:#7a8a6e; font-size:14px; margin-bottom:18px; }
.rooms-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(310px,1fr)); gap:20px; }
.room-card { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,.06); border:1px solid #eef1eb; display:flex; flex-direction:column; transition:transform .2s; }
.room-card:hover { transform:translateY(-3px); }
.room-card-top { height:160px; background:#e9efe0 url('assets/images/superior.jpe') center/cover no-repeat; position:relative; }
.room-card-top.deluxe { background-image:url('assets/images/deluxe.jpeg'); }
.room-card-top.suite { background-image:url('assets/images/suite.jpe'); }
.room-badge-stok { position:absolute; top:12px; right:12px; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:700; }
.stok-tersedia { background:#e8f6ee; color:#1f7a45; }
.stok-habis { background:#fdeaea; color:#b32d2d; }
.room-card-body { padding:16px; flex:1; display:flex; flex-direction:column; }
.room-card-body h3 { margin:0 0 6px; font-size:17px; color:#2F3526; }
.room-tipe-meta { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:8px; }
.room-tipe-meta span { font-size:12px; padding:4px 9px; border-radius:999px; font-weight:600; }
.badge-kamar { background:#f0f3eb; color:#4A533C; }
.badge-harga { background:#fff7dc; color:#7a5a00; }
.room-desc { font-size:13.5px; color:#5a6650; line-height:1.6; margin:8px 0 14px; flex:1; }
.room-card-foot { display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eef1eb; padding-top:12px; gap:10px; }
.room-harga { font-weight:700; color:#4A533C; font-size:16px; }
.room-harga small { font-weight:400; color:#7a8a6e; font-size:12px; }
.btn-booking, .btn-detail {
    padding:9px 16px; border-radius:999px; font-size:13px; font-weight:600; text-decoration:none; border:none; cursor:pointer; transition:all .2s; display:inline-flex; align-items:center; gap:6px;
}
.btn-booking { background:#A3B18A; color:#2F3526; }
.btn-booking:hover { background:#4A533C; color:#fff; }
.btn-booking:disabled, .btn-booking.disabled { background:#ddd; color:#888; cursor:not-allowed; }
.btn-detail { background:#fff; color:#4A533C; border:1px solid #d9e2d0; }
.btn-detail:hover { background:#f0f3eb; }
.empty-room { background:#fff; border:1px dashed #cfd8c5; border-radius:16px; padding:36px; text-align:center; color:#7a8a6e; }
@media(max-width:820px){ .hotel-hero-detail{grid-template-columns:1fr;} .hotel-hero-img{min-height:220px;} }
</style>

<div class="detail-wrap">
    <div class="breadcrumb">
        <a href="index.php">Home</a> &rsaquo;
        <a href="hotels.php">Hotel</a> &rsaquo;
        <span><?php echo e($hotel['nama_hotel']); ?></span>
    </div>

    <div class="hotel-hero-detail">
        <div class="hotel-hero-img"></div>
        <div class="hotel-hero-info">
            <span class="hotel-kota"><i class="fa-solid fa-location-dot"></i> <?php echo e($hotel['kota']); ?></span>
            <h1><?php echo e($hotel['nama_hotel']); ?></h1>
            <div class="hotel-alamat">
                <i class="fa-solid fa-map-pin"></i>
                <span><?php echo e($hotel['alamat']); ?></span>
            </div>
            <?php if (!empty($hotel['deskripsi'])): ?>
                <p class="hotel-deskripsi"><?php echo nl2br(e($hotel['deskripsi'])); ?></p>
            <?php else: ?>
                <p class="hotel-deskripsi" style="color:#999;font-style:italic;">Belum ada deskripsi untuk hotel ini.</p>
            <?php endif; ?>
            <div class="hotel-stats">
                <div class="stat-mini"><strong><?php echo count($rooms); ?></strong><span>Tipe Kamar</span></div>
                <div class="stat-mini"><strong><?php echo array_sum(array_column($rooms,'stok_kamar')); ?></strong><span>Total Stok</span></div>
                <div class="stat-mini"><strong><?php echo $rooms ? rupiah(min(array_column($rooms,'harga_per_malam'))) : '-'; ?></strong><span>Mulai / malam</span></div>
            </div>
        </div>
    </div>

    <div class="rooms-section">
        <h2>Daftar Kamar Tersedia</h2>
        <p class="sub">Pilih tipe kamar yang sesuai dengan kebutuhanmu. Harga sudah termasuk pajak & layanan.</p>

        <?php if (empty($rooms)): ?>
            <div class="empty-room">
                <h3 style="color:#4A533C;margin-bottom:6px;">Belum ada kamar di hotel ini</h3>
                <p>Silakan kembali lagi nanti atau hubungi admin untuk informasi ketersediaan.</p>
            </div>
        <?php else: ?>
            <div class="rooms-grid">
                <?php foreach ($rooms as $rm):
                    $imgClass = 'superior';
                    $tipeLower = strtolower($rm['tipe_kamar']);
                    if (strpos($tipeLower,'deluxe')!==false) $imgClass='deluxe';
                    if (strpos($tipeLower,'suite')!==false) $imgClass='suite';
                    $habis = (int)$rm['stok_kamar'] <= 0;
                ?>
                <div class="room-card">
                    <div class="room-card-top <?php echo $imgClass; ?>">
                        <span class="room-badge-stok <?php echo $habis?'stok-habis':'stok-tersedia'; ?>">
                            <?php echo $habis ? 'Stok Habis' : 'Sisa '.(int)$rm['stok_kamar'].' kamar'; ?>
                        </span>
                    </div>
                    <div class="room-card-body">
                        <h3><?php echo e($rm['tipe_kamar']); ?></h3>
                        <div class="room-tipe-meta">
                            <span class="badge-kamar"><i class="fa-solid fa-bed"></i> <?php echo (int)$rm['stok_kamar']; ?> kamar</span>
                            <span class="badge-harga"><?php echo rupiah($rm['harga_per_malam']); ?> / malam</span>
                        </div>
                        <p class="room-desc"><?php echo $rm['deskripsi_kamar'] ? e($rm['deskripsi_kamar']) : 'Kamar nyaman dengan fasilitas lengkap untuk istirahat maksimal.'; ?></p>
                        <div class="room-card-foot">
                            <div class="room-harga"><?php echo rupiah($rm['harga_per_malam']); ?><small> / malam</small></div>
                            <div style="display:flex;gap:8px;">
                                <a href="room_detail.php?id=<?php echo (int)$rm['id_kamar']; ?>" class="btn-detail">Detail</a>
                                <?php if ($habis): ?>
                                    <span class="btn-booking disabled">Habis</span>
                                <?php else: ?>
                                    <a href="room_detail.php?id=<?php echo (int)$rm['id_kamar']; ?>" class="btn-booking"><i class="fa-solid fa-calendar-check"></i> Booking</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
