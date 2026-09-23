<?php
require_once 'config/koneksi.php';
include 'includes/header.php';
include 'includes/navbar.php';
?>
<main>
        <form class="login-box" action="proses_pinjam.php" method="post">
            <div class="login-header">
                <header>Login</header>
            </div>
            <div class="input-box">
                <!-- FIX: tambah atribut name, tanpa ini data tidak terkirim ke PHP -->
                <input type="text" name="nama" class="input-field" placeholder="Isi username-mu atau email@example.com" autocomplete="off" required>
            </div>
            <div class="input-box">
                <input type="text" name="judul_buku" class="input-field" placeholder="Password" autocomplete="off" required>
            </div>
            <div class="input-submit">
                <!-- FIX: tambah type="submit" agar jelas fungsinya -->
                <button type="submit" class="submit-btn" id="submit"></button>
                <label for="submit">Pinjam buku</label>
            </div>
        </form>
</main>
<?php
include 'includes/footer.php';
?>