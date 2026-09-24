<?php
// login.php
// Halaman login user (dan admin, diarahkan sesuai role)

session_start();
require_once 'config/koneksi.php';

$error = '';

// Kalau sudah login, jangan biarkan buka halaman login lagi
if (isset($_SESSION['id_user'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = mysqli_prepare($koneksi, "SELECT id_user, nama, email, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            // Login berhasil — simpan data penting ke session
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            // Arahkan sesuai role
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Email atau password salah.';
        }

        mysqli_stmt_close($stmt);
    }
}

include 'includes/header.php';
include 'includes/navbar.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hotel Booking</title>
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
    </style>
</head>
<body>

<main>
    <div class="login-box">
        <div class="login-header">
            <header>Login</header>
        </div>

        <?php if (!empty($error)): ?>
            <div class="msg-box msg-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['registrasi']) && $_GET['registrasi'] === 'sukses'): ?>
            <div class="msg-box msg-success">Registrasi berhasil! Silakan login.</div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="input-box">
                <input type="email" class="input-field" name="email" placeholder="Email" required>
            </div>

            <div class="input-box">
                <input type="password" class="input-field" name="password" placeholder="Password" required>
            </div>

            <div class="input-submit">
                <button type="submit" class="submit-btn"></button>
                <label>Masuk</label>
            </div>
        </form>

        <p class="login-footer-link">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </div>
</main>

</body>
</html>
<?php
include 'includes/footer.php'
?>