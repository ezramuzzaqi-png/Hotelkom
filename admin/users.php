<?php
require __DIR__ . '/config/koneksi.php';

if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // jangan hapus diri sendiri jika admin menghapus dirinya
    $conn->query("DELETE FROM users WHERE id_user=$id");
    header('Location: users.php?deleted=1');
    exit;
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT id_user, nama, email, no_telepon, alamat, role, created_at,
               (SELECT COUNT(*) FROM bookings b WHERE b.id_user=users.id_user) AS total_booking
        FROM users";
$params=[]; $types='';
if($q!==''){
    $sql .= " WHERE nama LIKE ? OR email LIKE ? OR kota LIKE ?";
    // kota tidak ada di users, tapi tetap cari nama/email
    $sql = "SELECT id_user, nama, email, no_telepon, alamat, role, created_at,
                   (SELECT COUNT(*) FROM bookings b WHERE b.id_user=users.id_user) AS total_booking
            FROM users WHERE nama LIKE ? OR email LIKE ?";
    $like="%$q%";
    $params[]=$like; $params[]=$like;
    $types='ss';
}
$sql .= " ORDER BY id_user DESC";

$users=[];
if($params){
    $stmt=$conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res=$stmt->get_result();
    while($row=$res->fetch_assoc()) $users[]=$row;
    $stmt->close();
} else {
    $res=$conn->query($sql);
    if($res) while($row=$res->fetch_assoc()) $users[]=$row;
}

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Users | Kolika</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar_admin.php'; ?>
    <main>
        <div class="page-header">
            <div>
                <h1>Data Users</h1>
                <p>Daftar semua pengguna terdaftar &mdash; tamu dan admin.</p>
            </div>
        </div>
        <?php if(isset($_GET['deleted'])): ?><div class="alert alert-success">User dihapus.</div><?php endif; ?>
        <div class="container">
            <form method="GET" class="table-toolbar" style="margin-bottom:1em;">
                <input type="search" name="q" class="search-input" placeholder="Cari nama atau email..." value="<?= e($q) ?>">
                <button type="submit" class="btn btn-sm">Cari</button>
                <?php if($q!==''): ?><a href="users.php" class="btn btn-sm btn-secondary">Reset</a><?php endif; ?>
                <span class="count" style="margin-left:auto;"><?= count($users) ?> user</span>
            </form>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th class="no">ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>No. Telepon</th>
                            <th>Alamat</th>
                            <th>Role</th>
                            <th>Booking</th>
                            <th>Tanggal Daftar</th>
                            <th class="aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($users)): ?>
                        <tr><td colspan="9" class="empty">Belum ada user.</td></tr>
                    <?php else: foreach($users as $u): ?>
                        <tr>
                            <td class="no">#<?= (int)$u['id_user'] ?></td>
                            <td class="nama"><?= e($u['nama']) ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td><?= e($u['no_telepon'] ?: '-') ?></td>
                            <td class="deskripsi"><span class="clamp"><?= e($u['alamat'] ?: '-') ?></span></td>
                            <td><span class="badge <?= $u['role']=='admin'?'badge-pending':'badge-info' ?>"><?= e($u['role'] ?: 'user') ?></span></td>
                            <td style="text-align:center;"><?= (int)$u['total_booking'] ?></td>
                            <td><small><?= date('d M Y', strtotime($u['created_at'])) ?></small></td>
                            <td class="aksi">
                                <a href="users.php?hapus=<?= (int)$u['id_user'] ?>" class="btn btn-sm btn-delete" onclick="return confirm('Hapus user ini? Semua booking & pembayarannya juga akan terhapus.')">Hapus</a>
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
