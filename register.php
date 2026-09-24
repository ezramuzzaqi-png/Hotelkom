<?php
// register.php
// Halaman daftar akun baru

session_start();
require_once 'config/koneksi.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama       = trim($_POST['nama']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi_password'];
    $no_telepon = trim($_POST['no_telepon']);
    $alamat     = trim($_POST['alamat']);

    // Validasi input dasar
    if (empty($nama) || empty($email) || empty($password) || empty($konfirmasi)) {
        $error = 'Nama, email, dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Cek apakah email sudah terdaftar
        $stmt = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'Email sudah terdaftar, silakan gunakan email lain.';
        } else {
            // Hash password sebelum disimpan — JANGAN PERNAH simpan password mentah
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmtInsert = mysqli_prepare(
                $koneksi,
                "INSERT INTO users (nama, email, password, no_telepon, alamat) VALUES (?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmtInsert, "sssss", $nama, $email, $password_hash, $no_telepon, $alamat);

            if (mysqli_stmt_execute($stmtInsert)) {
                $success = 'Registrasi berhasil! Silakan login.';
            } else {
                $error = 'Terjadi kesalahan, coba lagi nanti.';
            }
            mysqli_stmt_close($stmtInsert);
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<?php
include 'includes/navbar.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Hotel Booking</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Kecil saja, khusus pesan error/sukses — tidak ada di style.css utama */
        .msg-box {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: center;
        }
        .msg-error {
            background: #F5D6D6;
            color: #7A2E2E;
        }
        .msg-success {
            background: #DDEAD1;
            color: #38542A;
        }
        .msg-success a {
            color: #38542A;
            font-weight: 600;
        }
        .login-footer-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #555;
        }
        .login-footer-link a {
            color: #4A533C;
            font-weight: 600;
            text-decoration: none;
        }
        /* register butuh field lebih banyak, box dibuat auto height & sedikit lebih lebar */
        .register-box {
            width: 480px;
            height: auto;
            padding: 30px;
        }
        .input-box textarea.input-field {
            height: 80px;
            padding: 15px 25px;
            resize: none;
            font-family: inherit;
        }
    </style>
</head>
<body>

<main>
    <div class="login-box register-box">
        <div class="login-header">
            <header>Daftar Akun</header>
        </div>

        <?php if (!empty($error)): ?>
            <div class="msg-box msg-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="msg-box msg-success">
                <?php echo htmlspecialchars($success); ?>
                <a href="login.php">Login di sini</a>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="input-box">
                <input type="text" class="input-field" name="nama" placeholder="Nama Lengkap" required
                       value="<?php echo isset($nama) ? htmlspecialchars($nama) : ''; ?>">
            </div>

            <div class="input-box">
                <input type="email" class="input-field" name="email" placeholder="Email" required
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>

            <div class="input-box">
                <input type="password" class="input-field" name="password" placeholder="Password (min. 6 karakter)" required minlength="6">
            </div>

            <div class="input-box">
                <input type="password" class="input-field" name="konfirmasi_password" placeholder="Konfirmasi Password" required minlength="6">
            </div>

            <div class="input-box">
                <input type="text" class="input-field" name="no_telepon" placeholder="No. Telepon"
                       value="<?php echo isset($no_telepon) ? htmlspecialchars($no_telepon) : ''; ?>">
            </div>

            <div class="input-box">
                <textarea class="input-field" name="alamat" placeholder="Alamat"><?php echo isset($alamat) ? htmlspecialchars($alamat) : ''; ?></textarea>
            </div>

            <div class="input-submit">
                <button type="submit" class="submit-btn"></button>
                <label>Daftar</label>
            </div>
        </form>

        <p class="login-footer-link">Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</main>

</body>
</html>
<?php
include 'includes/footer.php';
?>