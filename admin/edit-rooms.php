<?php
require __DIR__ . '/config/koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header('Location: rooms.php'); exit; }
$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM rooms WHERE id_kamar=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$room){ header('Location: rooms.php'); exit; }

$hotels = $conn->query("SELECT id_hotel, nama_hotel, kota FROM hotels ORDER BY nama_hotel ASC");
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $id_hotel = (int)($_POST['id_hotel'] ?? 0);
    $tipe = trim($_POST['tipe_kamar'] ?? '');
    $harga = trim($_POST['harga_per_malam'] ?? '');
    $stok = trim($_POST['stok_kamar'] ?? '');
    $desk = trim($_POST['deskripsi_kamar'] ?? '');

    if(!$id_hotel || $tipe==='' || $harga==='' || $stok===''){
        $error='Hotel, tipe, harga, stok wajib diisi.';
    } elseif(!is_numeric($harga) || (float)$harga <=0){
        $error='Harga harus angka positif.';
    } elseif(!ctype_digit($stok) || (int)$stok<0){
        $error='Stok harus angka bulat >=0.';
    } else {
        $upd = $conn->prepare("UPDATE rooms SET id_hotel=?, tipe_kamar=?, harga_per_malam=?, stok_kamar=?, deskripsi_kamar=? WHERE id_kamar=?");
        $hargaF=(float)$harga; $stokI=(int)$stok;
        $upd->bind_param("isdisi", $id_hotel, $tipe, $hargaF, $stokI, $desk, $id);
        if($upd->execute()){
            $upd->close();
            header('Location: rooms.php?status=edit');
            exit;
        } else {
            $error='Gagal update: '.htmlspecialchars($upd->error);
            $upd->close();
        }
    }
    $room['id_hotel']=$id_hotel; $room['tipe_kamar']=$tipe; $room['harga_per_malam']=$harga; $room['stok_kamar']=$stok; $room['deskripsi_kamar']=$desk;
}
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kamar | Kolika</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar_admin.php'; ?>
    <main>
        <div class="page-header">
            <div>
                <h1>Edit Kamar</h1>
                <p>Perbarui data kamar #<?= (int)$id ?>.</p>
            </div>
        </div>
        <div class="container">
            <?php if($error): ?><div class="alert" style="background:#fdeaea;color:#b32d2d;border:1px solid #f5c2c2;padding:.8em 1.1em;border-radius:.5em;margin-bottom:1em;"><?= e($error) ?></div><?php endif; ?>
            <form method="post" class="form-admin">
                <div class="form-group">
                    <label>Hotel <span class="req">*</span></label>
                    <select name="id_hotel" required>
                        <option value="">-- Pilih Hotel --</option>
                        <?php
                        $h2 = $conn->query("SELECT id_hotel, nama_hotel, kota FROM hotels ORDER BY nama_hotel ASC");
                        while($h=$h2->fetch_assoc()): ?>
                            <option value="<?= (int)$h['id_hotel'] ?>" <?= (int)$room['id_hotel']===(int)$h['id_hotel']?'selected':'' ?>><?= e($h['nama_hotel']) ?> &mdash; <?= e($h['kota']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipe Kamar <span class="req">*</span></label>
                        <input type="text" name="tipe_kamar" maxlength="50" required value="<?= e($room['tipe_kamar']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Harga per Malam (Rp) <span class="req">*</span></label>
                        <input type="number" name="harga_per_malam" step="0.01" min="0" required value="<?= e($room['harga_per_malam']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Stok Kamar <span class="req">*</span></label>
                    <input type="number" name="stok_kamar" min="0" step="1" required value="<?= e($room['stok_kamar']) ?>">
                </div>
                <div class="form-group">
                    <label>Deskripsi Kamar</label>
                    <textarea name="deskripsi_kamar" rows="4"><?= e($room['deskripsi_kamar']) ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn">Simpan Perubahan</button>
                    <a href="rooms.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
