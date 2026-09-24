<?php
require __DIR__ . '/config/koneksi.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_hotel = trim($_POST['nama_hotel'] ?? '');
    $kota       = trim($_POST['kota'] ?? '');
    $alamat     = trim($_POST['alamat'] ?? '');
    $deskripsi  = trim($_POST['deskripsi'] ?? '');

    if ($nama_hotel === '' || $kota === '' || $alamat === '') {
        $error = 'Nama hotel, kota, dan alamat wajib diisi.';
    } else {
        $stmt = $conn->prepare("INSERT INTO hotels (nama_hotel, alamat, kota, deskripsi) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $nama_hotel, $alamat, $kota, $deskripsi);
        if ($stmt->execute()) {
            $stmt->close();
            header('Location: hotels.php?status=tambah');
            exit;
        } else {
            $error = 'Gagal menyimpan: ' . htmlspecialchars($stmt->error);
            $stmt->close();
        }
    }
}
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Hotel | Kolika</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar_admin.php'; ?>

    <main>
        <div class="page-header">
            <div>
                <h1>Tambah Hotel</h1>
                <p>Isi data hotel baru. Kolom bertanda <span style="color:var(--danger-clr)">*</span> wajib diisi.</p>
            </div>
        </div>

        <div class="container">
            <?php if ($error): ?>
                <div class="alert" style="background:#fdeaea;color:#b32d2d;border:1px solid #f5c2c2;padding:.8em 1.1em;border-radius:.5em;margin-bottom:1em;"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="" class="form-admin">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nama_hotel">Nama hotel <span class="req">*</span></label>
                        <input type="text" id="nama_hotel" name="nama_hotel" maxlength="100"
                               placeholder="Contoh: Hotel Kolika Bandung" required value="<?= e($_POST['nama_hotel'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="kota">Kota <span class="req">*</span></label>
                        <input type="text" id="kota" name="kota" maxlength="50"
                               placeholder="Contoh: Bandung" required value="<?= e($_POST['kota'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat <span class="req">*</span></label>
                    <input type="text" id="alamat" name="alamat"
                           placeholder="Nama jalan, nomor, kecamatan" required value="<?= e($_POST['alamat'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="4"
                              placeholder="Fasilitas, lokasi strategis, keunggulan hotel..."><?= e($_POST['deskripsi'] ?? '') ?></textarea>
                    <span class="hint">Opsional. Ditampilkan ke pelanggan di halaman detail hotel.</span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Simpan hotel</button>
                    <a href="hotels.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
