<?php
require_once 'config/koneksi.php';

$result      = $koneksi->query("SELECT COUNT(*) AS total_hotel, COUNT(DISTINCT kota) AS total_kota FROM hotels");
$row         = $result->fetch_assoc();
$total_hotel = $row['total_hotel'];
$total_kota  = $row['total_kota'];

include 'includes/header.php';
include 'includes/navbar.php';
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
    <div class="stat-angka"><?php echo $total_hotel; ?></div>
    <div class="stat-label">Hotel tersedia</div>
  </div>
  <div class="stat-item">
    <div class="stat-angka"><?php echo $total_kota; ?></div>
    <div class="stat-label">Kota tujuan di Indonesia</div>
  </div>
  <div class="stat-item">
    <div class="stat-angka">830</div>
    <div class="stat-label">Tamu puas</div>
  </div>
</section>
<section class="about-section">
  <div class="section-content">
      <div class="about-img-wrapper">
        <img src="assets/images/lobby.jpe" alt="" class="about-img">
      </div>
      <div class="about-details">
        <h2 class="section-tittle">About us</h2>
        <p class="text">Hotelkom adalah akomodasi modern pilihan utama yang menawarkan kenyamanan terbaik dan lokasi strategis di pusat kota. Kami memadukan fasilitas kamar yang lengkap, pelayanan profesional yang responsif, dan atmosfer yang menenangkan untuk menjamin kepuasan menginap Anda. Baik untuk urusan bisnis maupun liburan, Hotelkom adalah tempat terbaik untuk memulihkan energi Anda sepanjang hari.</p>
        <div class="social-link-list">
          <a href="#"><i class="fa-brands fa-facebook"></i></a>
          <a href="#"><i class="fa-brands fa-instagram"></i></a>
          <a href="#"><i class="fa-brands fa-x-twitter"></i></a>
        </div>
      </div>
  </div>
</section>
<?php
include 'includes/footer.php';
?>