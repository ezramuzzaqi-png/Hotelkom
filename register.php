<?php
require_once 'config/koneksi.php';
include 'includes/header.php';
include 'includes/navbar.php';
?>
<main>
        <form class="login-box" action="proses_pinjam.php" method="post">
            <div class="login-header">
                <header>Register</header>
            </div>
            <div class="input-box">
                <!-- FIX: tambah atribut name, tanpa ini data tidak terkirim ke PHP -->
                <input type="text" name="nama" class="input-field" placeholder="Isi nama-mu" autocomplete="off" required>
            </div>
            <div class="input-box">
                <input type="text" name="kelas" class="input-field" placeholder="email@example.com / no telp" autocomplete="off" required>
            </div>
            <div class="input-box">
                <input type="password" name="judul_buku" class="input-field" placeholder="Password" autocomplete="off" required>
            </div>
            <div class="input-box">
                <input type="password" name="judul_buku" class="input-field" placeholder="Confirm password" autocomplete="off" required>
            </div>
            <div class="input-submit">
                <!-- FIX: tambah type="submit" agar jelas fungsinya -->
                <button type="submit" class="submit-btn" id="submit"></button>
                <label for="submit">Daftar</label>
                <a href="login.php">Sudah punya akun? Masuk</a>
            </div>
        </form>
</main>
<?php
include 'includes/footer.php';
?>