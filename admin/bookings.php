<?php
require __DIR__ . '/config/koneksi.php';

// Handle update status
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['id_booking'], $_POST['status_booking'])) {
    $id = (int)$_POST['id_booking'];
    $status = $_POST['status_booking'];
    $allowed = ['Pending','Confirmed','Cancelled'];
    if (in_array($status, $allowed, true)) {
        $stmt = $conn->prepare("UPDATE bookings SET status_booking=? WHERE id_booking=?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: bookings.php?updated=1');
        exit;
    }
}
// Handle hapus
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $conn->query("DELETE FROM bookings WHERE id_booking=$id");
    header('Location: bookings.php?deleted=1');
    exit;
}

$q = trim($_GET['q'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$where = [];
$params=[];
$types='';
if($q!==''){
    $where[]="(u.nama LIKE ? OR h.nama_hotel LIKE ? OR r.tipe_kamar LIKE ? OR b.id_booking LIKE ?)";
    $like="%$q%";
    $params[]=$like; $params[]=$like; $params[]=$like; $params[]=$like;
    $types.='ssss';
}
if(in_array($status_filter, ['Pending','Confirmed','Cancelled'], true)){
    $where[]="b.status_booking = ?";
    $params[]=$status_filter;
    $types.='s';
}

$sql="SELECT b.*, u.nama, u.email, r.tipe_kamar, h.nama_hotel, h.kota,
             DATEDIFF(b.tanggal_checkout,b.tanggal_checkin) AS malam
      FROM bookings b
      JOIN users u ON u.id_user=b.id_user
      JOIN rooms r ON r.id_kamar=b.id_kamar
      JOIN hotels h ON h.id_hotel=r.id_hotel";
if($where) $sql.=" WHERE ".implode(" AND ", $where);
$sql.=" ORDER BY b.tanggal_booking DESC";

$bookings=[];
if($params){
    $stmt=$conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res=$stmt->get_result();
    while($row=$res->fetch_assoc()) $bookings[]=$row;
    $stmt->close();
} else {
    $res=$conn->query($sql);
    if($res) while($row=$res->fetch_assoc()) $bookings[]=$row;
}

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Booking | Kolika</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar_admin.php'; ?>
    <main>
        <div class="page-header">
            <div>
                <h1>Data Booking</h1>
                <p>Kelola pemesanan tamu — konfirmasi, batalkan, atau hapus.</p>
            </div>
        </div>

        <?php if(isset($_GET['updated'])): ?><div class="alert alert-success">Status booking diperbarui.</div><?php endif; ?>
        <?php if(isset($_GET['deleted'])): ?><div class="alert alert-success">Booking dihapus.</div><?php endif; ?>

        <div class="container">
            <form method="GET" class="table-toolbar" style="margin-bottom:1em;">
                <input type="search" name="q" class="search-input" placeholder="Cari ID, nama tamu, hotel, tipe..." value="<?= e($q) ?>">
                <select name="status" style="padding:.7em 1em;border:1px solid var(--input-border-clr);border-radius:.5em;font:inherit;color:var(--secondary-text-clr);">
                    <option value="">Semua Status</option>
                    <option value="Pending" <?= $status_filter==='Pending'?'selected':'' ?>>Pending</option>
                    <option value="Confirmed" <?= $status_filter==='Confirmed'?'selected':'' ?>>Confirmed</option>
                    <option value="Cancelled" <?= $status_filter==='Cancelled'?'selected':'' ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn btn-sm">Filter</button>
                <?php if($q!=='' || $status_filter!==''): ?><a href="bookings.php" class="btn btn-sm btn-secondary">Reset</a><?php endif; ?>
                <span class="count" style="margin-left:auto;"><?= count($bookings) ?> booking</span>
            </form>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th class="no">ID</th>
                            <th>Tamu</th>
                            <th>Hotel & Kamar</th>
                            <th>Menginap</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($bookings)): ?>
                        <tr><td colspan="7" class="empty">Belum ada booking.</td></tr>
                    <?php else: foreach($bookings as $b): ?>
                        <tr>
                            <td class="no">#<?= (int)$b['id_booking'] ?></td>
                            <td>
                                <div style="font-weight:600;"><?= e($b['nama']) ?></div>
                                <small style="color:var(--muted-clr);"><?= e($b['email']) ?></small>
                            </td>
                            <td>
                                <div style="font-weight:600;"><?= e($b['nama_hotel']) ?></div>
                                <small style="color:var(--muted-clr);"><?= e($b['tipe_kamar']) ?> &middot; <?= e($b['kota']) ?></small>
                            </td>
                            <td>
                                <?= (int)$b['malam'] ?> malam<br>
                                <small style="color:var(--muted-clr);"><?= e($b['tanggal_checkin']) ?> s/d <?= e($b['tanggal_checkout']) ?></small><br>
                                <small style="color:var(--muted-clr);">Pesan: <?= date('d M Y H:i', strtotime($b['tanggal_booking'])) ?></small>
                            </td>
                            <td><?= rupiah($b['total_biaya']) ?></td>
                            <td>
                                <?php
                                $badge = $b['status_booking']=='Confirmed' ? 'badge-success' : ($b['status_booking']=='Cancelled' ? 'badge-danger' : 'badge-pending');
                                ?>
                                <span class="badge <?= $badge ?>"><?= e($b['status_booking']) ?></span>
                            </td>
                            <td class="aksi">
                                <form method="POST" style="display:flex;gap:6px;align-items:center;">
                                    <input type="hidden" name="id_booking" value="<?= (int)$b['id_booking'] ?>">
                                    <select name="status_booking" style="padding:.35em .6em;border:1px solid var(--input-border-clr);border-radius:.4em;font:inherit;font-size:.85rem;">
                                        <option value="Pending" <?= $b['status_booking']=='Pending'?'selected':'' ?>>Pending</option>
                                        <option value="Confirmed" <?= $b['status_booking']=='Confirmed'?'selected':'' ?>>Confirmed</option>
                                        <option value="Cancelled" <?= $b['status_booking']=='Cancelled'?'selected':'' ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-edit" style="padding:.35em .7em;">Update</button>
                                </form>
                                <div style="margin-top:6px;display:flex;gap:6px;justify-content:flex-end;">
                                    <a href="payments.php?booking=<?= (int)$b['id_booking'] ?>" class="btn btn-sm" style="background:rgba(14,143,214,.12);color:#0a6fa8;">Payments</a>
                                    <a href="bookings.php?hapus=<?= (int)$b['id_booking'] ?>" class="btn btn-sm btn-delete" onclick="return confirm('Hapus booking ini?')">Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
