<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar_admin.php'; ?>

    <main>
    <div class="container">
      <div class="page-header">
        <h1>Input Data Hotel</h1>
      </div>

      <form method="post" action="simpan.php" class="form-hotel">
        <div class="form-group">
          <label for="nama_hotel">Nama Hotel</label>
          <input type="text" id="nama_hotel" name="nama_hotel" required>
        </div>

        <div class="form-group">
          <label for="alamat">Alamat</label>
          <input type="text" id="alamat" name="alamat" required>
        </div>

        <div class="form-group">
          <label for="kota">Kota</label>
          <input type="text" id="kota" name="kota" required>
        </div>

        <div class="form-group">
          <label for="deskripsi">Deskripsi</label>
          <textarea id="deskripsi" name="deskripsi" rows="4" required></textarea>
        </div>

        <div class="form-actions">
          <button type="submit" name="tombolsubmit" class="btn">Simpan</button>
          <a href="hotels.php" class="btn btn-secondary">Lihat Tabel</a>
        </div>
      </form>
    </div>
</main>
</body>
</html>