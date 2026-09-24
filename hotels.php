<?php
// hotels.php - Daftar semua hotel dengan pencarian & filter kota
session_start();
require_once 'config/koneksi.php';

$q    = isset($_GET['q']) ? trim($_GET['q']) : '';
$kota = isset($_GET['kota']) ? trim($_GET['kota']) : '';

// Daftar kota untuk filter dropdown
$kota_result = $koneksi->query("SELECT DISTINCT kota FROM hotels ORDER BY kota ASC");
$daftar_kota = [];
if ($kota_result) {
    while ($r = $kota_result->fetch_assoc()) $daftar_kota[] = $r['kota'];
}

// Bangun query dengan prepared statement dinamis
$where  = [];
$params = [];
$types  = '';

if ($q !== '') {
    $where[] = "(h.nama_hotel LIKE ? OR h.alamat LIKE ? OR h.deskripsi LIKE ?)";
    $like = "%$q%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}
if ($kota !== '') {
    $where[] = "h.kota = ?";
    $params[] = $kota;
    $types .= 's';
}

$sql = "SELECT h.*,
               (SELECT COUNT(*) FROM rooms r WHERE r.id_hotel = h.id_hotel) AS jumlah_tipe,
               (SELECT COALESCE(SUM(r.stok_kamar),0) FROM rooms r WHERE r.id_hotel = h.id_hotel) AS total_stok,
               (SELECT MIN(r.harga_per_malam) FROM rooms r WHERE r.id_hotel = h.id_hotel) AS harga_termurah
        FROM hotels h";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY h.id_hotel DESC";

$hotels = [];
if (!empty($params)) {
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) $hotels[] = $row;
    mysqli_stmt_close($stmt);
} else {
    $res = $koneksi->query($sql);
    if ($res) while ($row = $res->fetch_assoc()) $hotels[] = $row;
}

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rupiah($n){ return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

include 'includes/header.php';
include 'includes/navbar.php';
?>
<style>
/* Hotels page - mengikuti palette #4A533C / #A3B18A tanpa mengubah style.css utama */
.hotels-hero {
    background: linear-gradient(135deg, #4A533C 0%, #748B6F 100%);
    color: #fff;
    padding: 42px 5% 36px;
    text-align: center;
}
.hotels-hero h1 { color:#fff; background:none; -webkit-text-fill-color:#fff; font-size:38px; margin:0 0 8px; }
.hotels-hero p { color:#E9EFE0; max-width:640px; margin:0 auto; font-size:15px; line-height:1.6; }
.filter-bar {
    max-width:1100px; margin:-22px auto 0; background:#fff; border-radius:16px;
    box-shadow:0 8px 30px rgba(0,0,0,.08); padding:18px 20px;
    display:flex; gap:12px; flex-wrap:wrap; align-items:center; position:relative; z-index:2;
}
.filter-bar input {
    flex:1; min-width:180px; padding:12px 16px; border:1px solid #d9e2d0; border-radius:999px;
    font:inherit; font-size:14px; outline:none; transition:border-color .2s, box-shadow .2s;
}
.filter-bar input:focus { border-color:#A3B18A; box-shadow:0 0 0 3px rgba(163,177,138,.25); }
/* === Custom select - rapih & sage === */
.select-wrap {
    position:relative;
    flex:1; min-width:180px;
    display:flex; align-items:center;
}
.select-wrap select {
    width:100%;
    appearance:none; -webkit-appearance:none; -moz-appearance:none;
    padding:12px 40px 12px 38px;
    border:1px solid #d9e2d0;
    border-radius:999px;
    background:#fff;
    color:#2F3526;
    font:inherit; font-size:14px;
    outline:none;
    cursor:pointer;
    transition:border-color .2s, box-shadow .2s, background .2s;
}
.select-wrap select:focus { border-color:#A3B18A; box-shadow:0 0 0 3px rgba(163,177,138,.25); }
.select-wrap select:hover { border-color:#A3B18A; }
.select-wrap .select-icon {
    position:absolute; left:14px; top:50%; transform:translateY(-50%);
    color:#A3B18A; font-size:14px; pointer-events:none;
}
.select-wrap .select-arrow {
    position:absolute; right:14px; top:50%; transform:translateY(-50%);
    color:#4A533C; font-size:12px; pointer-events:none; transition:transform .2s ease;
}
.select-wrap:focus-within .select-arrow { transform:translateY(-50%) rotate(180deg); }
/* Option styling - sage, bukan biru default */
.select-wrap select option {
    padding:10px;
    background:#fff;
    color:#2F3526;
}
.select-wrap select option:checked,
.select-wrap select option:hover {
    background:#A3B18A !important;
    color:#fff !important;
}
.select-wrap select option:active {
    background:#4A533C !important;
    color:#fff !important;
}
.filter-bar button, .filter-bar a.btn-reset {
    padding:12px 22px; border-radius:999px; font-weight:600; font-size:14px; text-decoration:none;
    border:none; cursor:pointer; transition:all .2s; display:inline-flex; align-items:center; gap:8px;
}
.filter-bar button { background:#4A533C; color:#fff; }
.filter-bar button:hover { background:#3A4230; transform:translateY(-1px); }
.filter-bar a.btn-reset { background:#f0f3eb; color:#4A533C; border:1px solid #d9e2d0; display:inline-flex; align-items:center; }
.filter-bar a.btn-reset:hover { background:#e8ecdF; }
/* ===== Custom dropdown - ganti biru default jadi sage & rapihkan ===== */
.custom-dropdown {
    position:relative;
    flex:1; min-width:180px;
}
.custom-dropdown-trigger {
    width:100%;
    display:flex; align-items:center; justify-content:space-between; gap:10px;
    padding:12px 14px;
    border:1px solid #d9e2d0;
    border-radius:999px;
    background:#fff;
    color:#2F3526;
    font:inherit; font-size:14px;
    cursor:pointer;
    transition:border-color .2s, box-shadow .2s;
    text-align:left;
    overflow:hidden;
}
.custom-dropdown-trigger:hover { border-color:#A3B18A; }
.custom-dropdown-trigger:focus { outline:none; border-color:#A3B18A; box-shadow:0 0 0 3px rgba(163,177,138,.25); }
.trigger-left { display:flex; align-items:center; gap:8px; flex:1; min-width:0; overflow:hidden; }
.trigger-left i { color:#A3B18A; font-size:14px; flex-shrink:0; }
.trigger-left span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.custom-dropdown-arrow { color:#4A533C; font-size:12px; transition:transform .2s; flex-shrink:0; }
.custom-dropdown.open .custom-dropdown-arrow { transform:rotate(180deg); }
.custom-dropdown.open .custom-dropdown-trigger { border-color:#A3B18A; box-shadow:0 0 0 3px rgba(163,177,138,.25); }
.custom-dropdown-menu {
    position:absolute; top:calc(100% + 8px); left:0; right:0;
    background:#fff;
    border:1px solid #d9e2d0;
    border-radius:16px;
    box-shadow:0 12px 30px rgba(74,83,60,.14);
    overflow:hidden;
    z-index:30;
    display:none;
    max-height:240px; overflow-y:auto;
}
.custom-dropdown.open .custom-dropdown-menu { display:block; }
.custom-option {
    padding:11px 16px;
    font-size:14px; color:#2F3526;
    cursor:pointer;
    display:flex; align-items:center; gap:10px;
    transition:background .15s, color .15s;
}
.custom-option:hover { background:#f0f3eb; color:#2F3526; }
.custom-option.active { background:#A3B18A; color:#fff; font-weight:600; }
.custom-option.active:hover { background:#4A533C; color:#fff; }
.custom-option i { font-size:12px; opacity:.7; }
.custom-option.active i { opacity:1; }
/* Native select disembunyikan tapi tetap ada untuk fallback */
.select-wrap.native-hidden { display:none !important; }
.hotels-section { max-width:1100px; margin:36px auto; padding:0 20px 60px; }
.hotels-grid {
    display:grid; grid-template-columns:repeat(auto-fill, minmax(300px,1fr)); gap:22px;
}
.hotel-card {
    background:#fff; border-radius:18px; overflow:hidden; box-shadow:0 4px 18px rgba(0,0,0,.06);
    display:flex; flex-direction:column; transition:transform .25s, box-shadow .25s; border:1px solid #eef1eb;
}
.hotel-card:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,0,0,.10); }
.hotel-img {
    height:190px; background:#e9efe0 url('assets/images/lobby.jpe') center/cover no-repeat;
    position:relative;
}
.hotel-img::after {
    content:""; position:absolute; inset:0; background:linear-gradient(to top, rgba(0,0,0,.35), transparent 60%);
}
.hotel-badge {
    position:absolute; top:12px; left:12px; z-index:1;
    background:rgba(255,255,255,.95); color:#4A533C; padding:5px 10px; border-radius:999px;
    font-size:12px; font-weight:700; letter-spacing:.3px;
}
.hotel-body { padding:18px 18px 16px; flex:1; display:flex; flex-direction:column; }
.hotel-body h3 { margin:0 0 4px; font-size:18px; color:#2F3526; line-height:1.3; }
.hotel-meta { font-size:13px; color:#6b7a5f; margin-bottom:8px; display:flex; gap:6px; align-items:center; }
.hotel-meta i { color:#A3B18A; }
.hotel-desc { font-size:13.5px; color:#5a6650; line-height:1.6; margin:8px 0 14px; flex:1;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.hotel-foot { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:auto; border-top:1px solid #eef1eb; padding-top:14px; }
.hotel-price { font-weight:700; color:#4A533C; font-size:15px; }
.hotel-price small { font-weight:400; color:#7a8a6e; font-size:12px; }
.hotel-foot a {
    background:#A3B18A; color:#2F3526; padding:9px 18px; border-radius:999px; text-decoration:none;
    font-weight:600; font-size:13px; transition:all .2s; white-space:nowrap;
}
.hotel-foot a:hover { background:#4A533C; color:#fff; }
.empty-state { text-align:center; padding:60px 20px; background:#fff; border-radius:18px; box-shadow:0 4px 18px rgba(0,0,0,.06); }
.empty-state h3 { color:#4A533C; margin-bottom:8px; }
.empty-state p { color:#7a8a6e; }
.info-bar { max-width:1100px; margin:18px auto 0; padding:0 20px; color:#6b7a5f; font-size:13px; }
</style>

<section class="hotels-hero">
    <h1>Jelajahi Hotel Pilihan</h1>
    <p>Temukan penginapan terbaik dari berbagai kota di Indonesia. Filter berdasarkan kota atau cari nama hotel favoritmu.</p>
</section>

<form method="GET" class="filter-bar">
    <input type="text" name="q" placeholder="Cari nama hotel, alamat, deskripsi..." value="<?php echo e($q); ?>">
    <div class="select-wrap" id="nativeSelectWrap">
        <i class="fa-solid fa-location-dot select-icon"></i>
        <select name="kota" id="kotaSelect" aria-label="Filter Kota">
            <option value="">Semua Kota</option>
            <?php foreach ($daftar_kota as $k): ?>
                <option value="<?php echo e($k); ?>" <?php echo $kota===$k?'selected':''; ?>><?php echo e($k); ?></option>
            <?php endforeach; ?>
        </select>
        <i class="fa-solid fa-chevron-down select-arrow"></i>
    </div>
    <!-- Custom dropdown rapih sage - ganti dropdown biru default -->
    <div class="custom-dropdown" id="kotaCustom" style="display:none;">
        <button type="button" class="custom-dropdown-trigger" id="kotaTrigger" aria-haspopup="listbox" aria-expanded="false">
            <span class="trigger-left"><i class="fa-solid fa-location-dot"></i><span id="kotaTriggerText">Semua Kota</span></span>
            <i class="fa-solid fa-chevron-down custom-dropdown-arrow"></i>
        </button>
        <div class="custom-dropdown-menu" role="listbox" id="kotaMenu"></div>
        <input type="hidden" name="kota" id="kotaHidden" value="<?php echo e($kota); ?>">
    </div>
    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
    <?php if ($q!=='' || $kota!==''): ?>
        <a href="hotels.php" class="btn-reset">Reset</a>
    <?php endif; ?>
</form>

<div class="info-bar">
    <?php echo count($hotels); ?> hotel ditemukan
    <?php if ($q!=='' ) echo ' untuk pencarian "'.e($q).'"'; ?>
    <?php if ($kota!=='' ) echo ' di '.e($kota); ?>
</div>

<section class="hotels-section">
    <?php if (empty($hotels)): ?>
        <div class="empty-state">
            <h3>Tidak ada hotel ditemukan</h3>
            <p>Coba ubah kata kunci pencarian atau pilih kota lain. Jika kamu admin, tambahkan hotel di panel admin.</p>
            <br>
            <a href="hotels.php" class="hotel-foot a" style="background:#4A533C;color:#fff;padding:10px 20px;border-radius:999px;text-decoration:none;font-weight:600;">Lihat Semua Hotel</a>
        </div>
    <?php else: ?>
        <div class="hotels-grid">
            <?php foreach ($hotels as $h): ?>
                <div class="hotel-card">
                    <div class="hotel-img">
                        <span class="hotel-badge"><i class="fa-solid fa-location-dot"></i> <?php echo e($h['kota']); ?></span>
                    </div>
                    <div class="hotel-body">
                        <h3><?php echo e($h['nama_hotel']); ?></h3>
                        <div class="hotel-meta">
                            <i class="fa-solid fa-map-pin"></i>
                            <span><?php echo e(mb_strimwidth($h['alamat'],0,42,'...')); ?></span>
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin:6px 0;">
                            <span style="background:#f0f3eb;color:#4A533C;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;">
                                <?php echo (int)$h['jumlah_tipe']; ?> tipe kamar
                            </span>
                            <span style="background:#fff7dc;color:#7a5a00;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;">
                                Stok <?php echo (int)$h['total_stok']; ?> kamar
                            </span>
                        </div>
                        <p class="hotel-desc"><?php echo $h['deskripsi'] ? e($h['deskripsi']) : 'Belum ada deskripsi untuk hotel ini.'; ?></p>
                        <div class="hotel-foot">
                            <div class="hotel-price">
                                <?php if ($h['harga_termurah']): ?>
                                    <?php echo rupiah($h['harga_termurah']); ?><small> / malam</small>
                                <?php else: ?>
                                    <small style="color:#999;">Belum ada kamar</small>
                                <?php endif; ?>
                            </div>
                            <a href="hotel_detail.php?id=<?php echo (int)$h['id_hotel']; ?>">Lihat Detail</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script>
(function(){
    const nativeWrap = document.getElementById('nativeSelectWrap');
    const nativeSelect = document.getElementById('kotaSelect');
    const custom = document.getElementById('kotaCustom');
    const trigger = document.getElementById('kotaTrigger');
    const triggerText = document.getElementById('kotaTriggerText');
    const menu = document.getElementById('kotaMenu');
    const hidden = document.getElementById('kotaHidden');
    if(!nativeSelect || !custom || !trigger || !menu || !hidden) return;

    // Build custom options dari native select
    const options = Array.from(nativeSelect.options);
    menu.innerHTML = '';
    options.forEach(opt => {
        const div = document.createElement('div');
        div.className = 'custom-option' + (opt.value === hidden.value ? ' active' : '');
        div.setAttribute('role','option');
        div.dataset.value = opt.value;
        // icon beda untuk Semua vs kota
        const icon = opt.value === '' ? 'fa-earth-asia' : 'fa-city';
        div.innerHTML = '<i class="fa-solid '+icon+'"></i> ' + opt.textContent;
        div.addEventListener('click', () => {
            hidden.value = opt.value;
            nativeSelect.value = opt.value;
            triggerText.textContent = opt.textContent;
            // update active
            menu.querySelectorAll('.custom-option').forEach(el=>el.classList.remove('active'));
            div.classList.add('active');
            close();
        });
        menu.appendChild(div);
    });
    // set trigger text awal
    const initial = options.find(o=>o.value===hidden.value);
    if(initial) triggerText.textContent = initial.textContent;

    // Tampilkan custom, sembunyikan native
    nativeWrap.classList.add('native-hidden');
    nativeWrap.querySelector('select').removeAttribute('name'); // biar tidak double submit
    custom.style.display = ''; // hapus display:none inline -> jadi block & tetap flex item
    // flex sudah diatur di CSS (.custom-dropdown flex:1)

    function open(){ custom.classList.add('open'); trigger.setAttribute('aria-expanded','true'); }
    function close(){ custom.classList.remove('open'); trigger.setAttribute('aria-expanded','false'); }
    function toggle(){ custom.classList.contains('open') ? close() : open(); }

    trigger.addEventListener('click', (e)=>{ e.stopPropagation(); toggle(); });
    document.addEventListener('click', (e)=>{ if(!custom.contains(e.target)) close(); });
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') close(); });
})();
</script>
<?php include 'includes/footer.php'; ?>
