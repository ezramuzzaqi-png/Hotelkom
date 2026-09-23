<?php
// index.php
// Halaman utama (landing page) website Hotel Booking

require_once 'config/koneksi.php';
include 'includes/header.php';
include 'includes/navbar.php';

// Ambil beberapa hotel unggulan untuk ditampilkan di halaman utama
// $query = "SELECT * FROM hotels ORDER BY id_hotel DESC LIMIT 6";
// $result = mysqli_query($koneksi, $query);
?>
<main>
    <div class="left">
        <h1>Selamat Datang di HotelKom!</h1>
        <p class="sub-heading">Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptatum perspiciatis aspernatur alias?</p>
        <div class="buttons">
            <a href="form.html" class="btn">Cari hotel</a>
            <a href="" class="btn-outline">Booking kamar</a>
                <div class="bg-color">
                <img src="" alt="">
            </div>
        </div>
    </div>
    <div class="right">
        <div class="bg-img"></div>
        <img src="assets/images/GambarHotel.png" alt="Hotel">
    </div>
</main>
<section class="statistik">
  <div class="stat-item">
    <div class="stat-angka">1.250</div>
    <div class="stat-label">Judul buku</div>
  </div>
  <div class="stat-item">
    <div class="stat-angka">830</div>
    <div class="stat-label">Anggota aktif</div>
  </div>
  <div class="stat-item">
    <div class="stat-angka">7 hari</div>
    <div class="stat-label">Lama peminjaman</div>
  </div>
</section>
<?php
include 'includes/footer.php';
?>