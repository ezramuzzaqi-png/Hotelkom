<?php
require __DIR__ . '/config/koneksi.php';

// Hapus kamar (GET ?hapus=id)
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $conn->query("DELETE FROM rooms WHERE id_kamar=$id");
    header('Location: rooms.php?status=hapus');
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_hotel = isset($_GET['hotel']) ? (int)$_GET['hotel'] : 0;

$where = [];
$params = [];
$types = '';
if ($q !== '') {
    $where[] = "(r.tipe_kamar LIKE ? OR r.deskripsi_kamar LIKE ? OR h.nama_hotel LIKE ?)";
    $like = "%$q%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}
if ($filter_hotel > 0) {
    $where[] = "r.id_hotel = ?";
    $params[] = $filter_hotel;
    $types .= 'i';
}

$sql = "SELECT r.*, h.nama_hotel, h.kota FROM rooms r JOIN hotels h ON h.id_hotel=r.id_hotel";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY r.id_kamar DESC";

$rooms = [];
if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $rooms[] = $row;
    $stmt->close();
} else {
    $res = $conn->query($sql);
    if ($res) while ($row = $res->fetch_assoc()) $rooms[] = $row;
}

$hotels_list = $conn->query("SELECT id_hotel, nama_hotel FROM hotels ORDER BY nama_hotel ASC");

$pesan = ['tambah'=>'Kamar berhasil ditambahkan.','edit'=>'Kamar berhasil diperbarui.','hapus'=>'Kamar berhasil dihapus.'];
$status = $_GET['status'] ?? '';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kamar | Kolika</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar_admin.php'; ?>
    <main>
        <div class="page-header">
            <div>
                <h1>Data Kamar</h1>
                <p>Kelola tipe kamar tiap hotel &mdash; harga, stok, dan deskripsi.</p>
            </div>
            <a href="tambah-rooms.php" class="btn">+ Tambah kamar</a>
        </div>

        <?php if (isset($pesan[$status])): ?>
            <div class="alert alert-success"><?= e($pesan[$status]) ?></div>
        <?php endif; ?>

        <div class="container">
            <form method="GET" class="table-toolbar" style="margin-bottom:1em;">
                <input type="search" name="q" class="search-input" placeholder="Cari tipe, hotel, deskripsi..." value="<?= e($q) ?>">
                <select name="hotel" style="padding:.7em 1em;border:1px solid var(--input-border-clr);border-radius:.5em;font:inherit;color:var(--secondary-text-clr);">
                    <option value="0">Semua Hotel</option>
                    <?php if($hotels_list) while($hh=$hotels_list->fetch_assoc()): ?>
                        <option value="<?= (int)$hh['id_hotel'] ?>" <?= $filter_hotel===(int)$hh['id_hotel']?'selected':'' ?>><?= e($hh['nama_hotel']) ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-sm">Filter</button>
                <?php if($q!=='' || $filter_hotel>0): ?>
                    <a href="rooms.php" class="btn btn-sm btn-secondary">Reset</a>
                <?php endif; ?>
                <span class="count" style="margin-left:auto;"><?= count($rooms) ?> kamar</span>
            </form>

            <div class="table-wrapper">
                <table id="tabel-kamar">
                    <thead>
                        <tr>
                            <th class="no">No</th>
                            <th>Hotel</th>
                            <th>Tipe Kamar</th>
                            <th>Harga / malam</th>
                            <th>Stok</th>
                            <th>Deskripsi</th>
                            <th class="aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($rooms)): ?>
                        <tr><td colspan="7" class="empty">Belum ada data kamar. Klik "Tambah kamar" untuk menambahkan.</td></tr>
                    <?php else: ?>
                        <?php $no=1; foreach($rooms as $r): ?>
                        <tr>
                            <td class="no"><?= $no++ ?></td>
                            <td class="nama"><?= e($r['nama_hotel']) ?><br><small style="color:var(--muted-clr);"><?= e($r['kota']) ?></small></td>
                            <td><span class="badge badge-info"><?= e($r['tipe_kamar']) ?></span></td>
                            <td><?= rupiah($r['harga_per_malam']) ?></td>
                            <td>
                                <?php if((int)$r['stok_kamar']<=0): ?>
                                    <span class="badge badge-danger">Habis</span>
                                <?php elseif((int)$r['stok_kamar']<=3): ?>
                                    <span class="badge badge-pending"><?= (int)$r['stok_kamar'] ?> kamar</span>
                                <?php else: ?>
                                    <span class="badge badge-success"><?= (int)$r['stok_kamar'] ?> kamar</span>
                                <?php endif; ?>
                            </td>
                            <td class="deskripsi"><span class="clamp"><?= e($r['deskripsi_kamar'] ?: '-') ?></span></td>
                            <td class="aksi">
                                <div class="actions">
                                    <a href="edit-rooms.php?id=<?= (int)$r['id_kamar'] ?>" class="btn btn-sm btn-edit">Edit</a>
                                    <a href="rooms.php?hapus=<?= (int)$r['id_kamar'] ?>" class="btn btn-sm btn-delete" onclick="return confirm('Hapus kamar ini? Booking terkait juga akan terhapus.')">Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
