<?php
require __DIR__ . '/config/koneksi.php';

$error=''; $success='';
$hotels = $conn->query("SELECT id_hotel, nama_hotel, kota FROM hotels ORDER BY nama_hotel ASC");

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $id_hotel = (int)($_POST['id_hotel'] ?? 0);
    $tipe_kamar = trim($_POST['tipe_kamar'] ?? '');
    $harga = trim($_POST['harga_per_malam'] ?? '');
    $stok = trim($_POST['stok_kamar'] ?? '');
    $deskripsi = trim($_POST['deskripsi_kamar'] ?? '');

    if (!$id_hotel || $tipe_kamar==='' || $harga==='' || $stok==='') {
        $error='Hotel, tipe kamar, harga, dan stok wajib diisi.';
    } elseif (!is_numeric($harga) || (float)$harga <= 0) {
        $error='Harga harus angka positif.';
    } elseif (!ctype_digit($stok) || (int)$stok < 0) {
        $error='Stok harus angka bulat >=0.';
    } else {
        // cek hotel ada
        $cek = $conn->prepare("SELECT id_hotel FROM hotels WHERE id_hotel=?");
        $cek->bind_param("i", $id_hotel);
        $cek->execute();
        $cek->store_result();
        if($cek->num_rows===0){
            $error='Hotel tidak ditemukan.';
            $cek->close();
        } else {
            $cek->close();
            $stmt = $conn->prepare("INSERT INTO rooms (id_hotel, tipe_kamar, harga_per_malam, stok_kamar, deskripsi_kamar) VALUES (?,?,?,?,?)");
            $hargaFloat = (float)$harga;
            $stokInt = (int)$stok;
            $stmt->bind_param("isdis", $id_hotel, $tipe_kamar, $hargaFloat, $stokInt, $deskripsi);
            if($stmt->execute()){
                $stmt->close();
                header('Location: rooms.php?status=tambah');
                exit;
            } else {
                $error='Gagal menyimpan: '.htmlspecialchars($stmt->error);
                $stmt->close();
            }
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
    <title>Tambah Kamar | Kolika</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar_admin.php'; ?>
    <main>
        <div class="page-header">
            <div>
                <h1>Tambah Kamar</h1>
                <p>Isi data kamar baru. Kolom bertanda <span style="color:var(--danger-clr)">*</span> wajib diisi.</p>
            </div>
        </div>

        <div class="container">
            <?php if($error): ?>
                <div class="alert" style="background:#fdeaea;color:#b32d2d;border:1px solid #f5c2c2;padding:.8em 1.1em;border-radius:.5em;margin-bottom:1em;"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if($hotels->num_rows===0): ?>
                <div class="alert" style="background:#fff4dc;color:#7a5a00;border:1px solid #ffe5a0;padding:.8em 1.1em;border-radius:.5em;margin-bottom:1em;">
                    Belum ada hotel. <a href="tambah-hotels.php" style="color:#4A533C;font-weight:600;">Tambah hotel dulu</a> sebelum menambahkan kamar.
                </div>
            <?php endif; ?>

            <form method="post" class="form-admin">
                <div class="form-group">
                    <label for="id_hotel">Hotel <span class="req">*</span></label>
                    <select id="id_hotel" name="id_hotel" required <?= $hotels->num_rows===0?'disabled':'' ?>>
                        <option value="">-- Pilih Hotel --</option>
                        <?php
                        $hotels2 = $conn->query("SELECT id_hotel, nama_hotel, kota FROM hotels ORDER BY nama_hotel ASC");
                        while($h=$hotels2->fetch_assoc()):
                        ?>
                            <option value="<?= (int)$h['id_hotel'] ?>" <?= (isset($_POST['id_hotel']) && (int)$_POST['id_hotel']===(int)$h['id_hotel'])?'selected':'' ?>>
                                <?= e($h['nama_hotel']) ?> &mdash; <?= e($h['kota']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tipe_kamar">Tipe Kamar <span class="req">*</span></label>
                        <input type="text" id="tipe_kamar" name="tipe_kamar" maxlength="50" placeholder="Contoh: Superior, Deluxe, Suite" required value="<?= e($_POST['tipe_kamar'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="harga_per_malam">Harga per Malam (Rp) <span class="req">*</span></label>
                        <input type="number" id="harga_per_malam" name="harga_per_malam" step="0.01" min="0" placeholder="Contoh: 350000" required value="<?= e($_POST['harga_per_malam'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="stok_kamar">Stok Kamar <span class="req">*</span></label>
                        <input type="number" id="stok_kamar" name="stok_kamar" min="0" step="1" placeholder="Contoh: 10" required value="<?= e($_POST['stok_kamar'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1.5;">
                        <label>&nbsp;</label>
                        <span class="hint" style="display:block;margin-top:6px;">Stok = jumlah kamar fisik yang tersedia untuk tipe ini.</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="deskripsi_kamar">Deskripsi Kamar</label>
                    <textarea id="deskripsi_kamar" name="deskripsi_kamar" rows="4" placeholder="Fasilitas: AC, WiFi, TV, kamar mandi dalam, sarapan..."><?= e($_POST['deskripsi_kamar'] ?? '') ?></textarea>
                    <span class="hint">Opsional. Ditampilkan di halaman detail kamar.</span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn" <?= $hotels->num_rows===0?'disabled style="opacity:.6;cursor:not-allowed;"':'' ?>>Simpan kamar</button>
                    <a href="rooms.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
