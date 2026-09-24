<?php
require __DIR__ . '/config/koneksi.php';

// Update status pembayaran
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['id_payment'], $_POST['status_pembayaran'])){
    $id = (int)$_POST['id_payment'];
    $status = $_POST['status_pembayaran'];
    $allowed=['Pending','Success','Failed'];
    if(in_array($status,$allowed,true)){
        $stmt=$conn->prepare("UPDATE payments SET status_pembayaran=? WHERE id_payment=?");
        $stmt->bind_param("si",$status,$id);
        $stmt->execute();
        $stmt->close();
        // Jika jadi Success, cek apakah booking lunas -> auto Confirmed
        if($status==='Success'){
            $row=$conn->query("SELECT id_booking FROM payments WHERE id_payment=$id")->fetch_assoc();
            if($row){
                $bid=(int)$row['id_booking'];
                $sum=$conn->query("SELECT COALESCE(SUM(jumlah_bayar),0) AS dibayar FROM payments WHERE id_booking=$bid AND status_pembayaran='Success'")->fetch_assoc()['dibayar'];
                $tot=$conn->query("SELECT total_biaya FROM bookings WHERE id_booking=$bid")->fetch_assoc()['total_biaya'] ?? 0;
                if((float)$sum >= (float)$tot - 0.01){
                    $conn->query("UPDATE bookings SET status_booking='Confirmed' WHERE id_booking=$bid");
                }
            }
        }
        header('Location: payments.php?updated=1');
        exit;
    }
}
// Hapus
if(isset($_GET['hapus']) && is_numeric($_GET['hapus'])){
    $id=(int)$_GET['hapus'];
    $conn->query("DELETE FROM payments WHERE id_payment=$id");
    header('Location: payments.php?deleted=1');
    exit;
}

$q=trim($_GET['q'] ?? '');
$status_filter=trim($_GET['status'] ?? '');
$booking_filter=isset($_GET['booking']) ? (int)$_GET['booking'] : 0;

$where=[]; $params=[]; $types='';
if($q!==''){
    $where[]="(u.nama LIKE ? OR h.nama_hotel LIKE ? OR p.metode_pembayaran LIKE ?)";
    $like="%$q%";
    $params[]=$like; $params[]=$like; $params[]=$like;
    $types.='sss';
}
if(in_array($status_filter,['Pending','Success','Failed'],true)){
    $where[]="p.status_pembayaran = ?";
    $params[]=$status_filter;
    $types.='s';
}
if($booking_filter>0){
    $where[]="p.id_booking = ?";
    $params[]=$booking_filter;
    $types.='i';
}

$sql="SELECT p.*, b.total_biaya, b.status_booking, b.tanggal_checkin, b.tanggal_checkout,
             u.nama, h.nama_hotel, r.tipe_kamar
      FROM payments p
      JOIN bookings b ON b.id_booking=p.id_booking
      JOIN users u ON u.id_user=b.id_user
      JOIN rooms r ON r.id_kamar=b.id_kamar
      JOIN hotels h ON h.id_hotel=r.id_hotel";
if($where) $sql.=" WHERE ".implode(" AND ", $where);
$sql.=" ORDER BY p.tanggal_bayar DESC";

$payments=[];
if($params){
    $stmt=$conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res=$stmt->get_result();
    while($row=$res->fetch_assoc()) $payments[]=$row;
    $stmt->close();
} else {
    $res=$conn->query($sql);
    if($res) while($row=$res->fetch_assoc()) $payments[]=$row;
}
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pembayaran | Kolika</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar_admin.php'; ?>
    <main>
        <div class="page-header">
            <div>
                <h1>Data Pembayaran</h1>
                <p>Kelola status pembayaran — verifikasi pembayaran tamu.</p>
            </div>
        </div>
        <?php if(isset($_GET['updated'])): ?><div class="alert alert-success">Status pembayaran diperbarui.</div><?php endif; ?>
        <?php if(isset($_GET['deleted'])): ?><div class="alert alert-success">Pembayaran dihapus.</div><?php endif; ?>
        <?php if($booking_filter>0): ?><div class="alert alert-success">Filter: Booking #<?= (int)$booking_filter ?> &mdash; <a href="payments.php" style="color:inherit;font-weight:600;">Tampilkan semua</a></div><?php endif; ?>

        <div class="container">
            <form method="GET" class="table-toolbar" style="margin-bottom:1em;">
                <input type="search" name="q" class="search-input" placeholder="Cari tamu, hotel, metode..." value="<?= e($q) ?>">
                <select name="status" style="padding:.7em 1em;border:1px solid var(--input-border-clr);border-radius:.5em;font:inherit;color:var(--secondary-text-clr);">
                    <option value="">Semua Status</option>
                    <option value="Pending" <?= $status_filter==='Pending'?'selected':'' ?>>Pending</option>
                    <option value="Success" <?= $status_filter==='Success'?'selected':'' ?>>Success</option>
                    <option value="Failed" <?= $status_filter==='Failed'?'selected':'' ?>>Failed</option>
                </select>
                <?php if($booking_filter>0): ?><input type="hidden" name="booking" value="<?= (int)$booking_filter ?>"><?php endif; ?>
                <button type="submit" class="btn btn-sm">Filter</button>
                <?php if($q!=='' || $status_filter!=='' || $booking_filter>0): ?><a href="payments.php" class="btn btn-sm btn-secondary">Reset</a><?php endif; ?>
                <span class="count" style="margin-left:auto;"><?= count($payments) ?> pembayaran</span>
            </form>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th class="no">ID</th>
                            <th>Booking</th>
                            <th>Tamu & Hotel</th>
                            <th>Metode</th>
                            <th>Jumlah</th>
                            <th>Status Bayar</th>
                            <th>Tanggal</th>
                            <th class="aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($payments)): ?>
                        <tr><td colspan="8" class="empty">Belum ada pembayaran.</td></tr>
                    <?php else: foreach($payments as $p): ?>
                        <tr>
                            <td class="no">#<?= (int)$p['id_payment'] ?></td>
                            <td>
                                <div style="font-weight:600;">#<?= (int)$p['id_booking'] ?></div>
                                <small style="color:var(--muted-clr);"><?= e($p['tanggal_checkin']) ?> s/d <?= e($p['tanggal_checkout']) ?></small><br>
                                <small style="color:var(--muted-clr);">Tagihan: <?= rupiah($p['total_biaya']) ?> &middot; <span class="badge <?= $p['status_booking']=='Confirmed'?'badge-success':($p['status_booking']=='Cancelled'?'badge-danger':'badge-pending') ?>" style="font-size:.75rem;"><?= e($p['status_booking']) ?></span></small>
                            </td>
                            <td>
                                <div style="font-weight:600;"><?= e($p['nama']) ?></div>
                                <small style="color:var(--muted-clr);"><?= e($p['nama_hotel']) ?> &middot; <?= e($p['tipe_kamar']) ?></small>
                            </td>
                            <td><span class="badge badge-info"><?= e($p['metode_pembayaran']) ?></span></td>
                            <td><?= rupiah($p['jumlah_bayar']) ?></td>
                            <td>
                                <?php
                                $bc = $p['status_pembayaran']=='Success' ? 'badge-success' : ($p['status_pembayaran']=='Failed' ? 'badge-danger' : 'badge-pending');
                                ?>
                                <span class="badge <?= $bc ?>"><?= e($p['status_pembayaran']) ?></span>
                            </td>
                            <td><small><?= date('d M Y H:i', strtotime($p['tanggal_bayar'])) ?></small></td>
                            <td class="aksi">
                                <form method="POST" style="display:flex;gap:6px;align-items:center;">
                                    <input type="hidden" name="id_payment" value="<?= (int)$p['id_payment'] ?>">
                                    <select name="status_pembayaran" style="padding:.35em .6em;border:1px solid var(--input-border-clr);border-radius:.4em;font:inherit;font-size:.85rem;">
                                        <option value="Pending" <?= $p['status_pembayaran']=='Pending'?'selected':'' ?>>Pending</option>
                                        <option value="Success" <?= $p['status_pembayaran']=='Success'?'selected':'' ?>>Success</option>
                                        <option value="Failed" <?= $p['status_pembayaran']=='Failed'?'selected':'' ?>>Failed</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-edit" style="padding:.35em .7em;">Update</button>
                                </form>
                                <div style="margin-top:6px;text-align:right;">
                                    <a href="payments.php?hapus=<?= (int)$p['id_payment'] ?>" class="btn btn-sm btn-delete" onclick="return confirm('Hapus pembayaran ini?')">Hapus</a>
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
