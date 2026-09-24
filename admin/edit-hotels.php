<?php
require __DIR__ . '/config/koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: hotels.php');
    exit;
}
$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM hotels WHERE id_hotel=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$hotel = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$hotel) {
    header('Location: hotels.php');
    exit;
}

$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nama_hotel = trim($_POST['nama_hotel'] ?? '');
    $kota       = trim($_POST['kota'] ?? '');
    $alamat     = trim($_POST['alamat'] ?? '');
    $deskripsi  = trim($_POST['deskripsi'] ?? '');

    if ($nama_hotel==='' || $kota==='' || $alamat==='') {
        $error='Nama hotel, kota, dan alamat wajib diisi.';
    } else {
        $upd = $conn->prepare("UPDATE hotels SET nama_hotel=?, alamat=?, kota=?, deskripsi=? WHERE id_hotel=?");
        $upd->bind_param("ssssi", $nama_hotel, $alamat, $kota, $deskripsi, $id);
        if ($upd->execute()) {
            $upd->close();
            header('Location: hotels.php?status=edit');
            exit;
        } else {
            $error='Gagal update: '.htmlspecialchars($upd->error);
            $upd->close();
        }
    }
    // refresh var for form
    $hotel['nama_hotel']=$nama_hotel; $hotel['kota']=$kota; $hotel['alamat']=$alamat; $hotel['deskripsi']=$deskripsi;
}
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Hotel | Kolika</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar_admin.php'; ?>
    <main>
        <div class="page-header">
            <div>
                <h1>Edit Hotel</h1>
                <p>Perbarui data hotel #<?= (int)$id ?>.</p>
            </div>
        </div>
        <div class="container">
            <?php if($error): ?><div class="alert" style="background:#fdeaea;color:#b32d2d;border:1px solid #f5c2c2;padding:.8em 1.1em;border-radius:.5em;margin-bottom:1em;"><?= e($error) ?></div><?php endif; ?>
            <form method="post" class="form-admin">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama hotel <span class="req">*</span></label>
                        <input type="text" name="nama_hotel" maxlength="100" required value="<?= e($hotel['nama_hotel']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Kota <span class="req">*</span></label>
                        <input type="text" name="kota" maxlength="50" required value="<?= e($hotel['kota']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Alamat <span class="req">*</span></label>
                    <input type="text" name="alamat" required value="<?= e($hotel['alamat']) ?>">
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" rows="4"><?= e($hotel['deskripsi']) ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn">Simpan Perubahan</button>
                    <a href="hotels.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
