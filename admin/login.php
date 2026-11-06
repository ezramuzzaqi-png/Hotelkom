<?php
// admin/login.php
// Halaman login khusus admin (disamakan dengan login.php public)

session_start();
require_once '../config/koneksi.php';

$error = '';
$emailValue = '';

// Kalau admin sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['id_user']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $emailValue = $email;

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        // Ambil user berdasarkan email, gunakan prepared statement supaya aman dari SQL Injection
        $stmt = mysqli_prepare($koneksi, "SELECT id_user, nama, email, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            // Cek apakah user ini memang role admin
            if ($user['role'] === 'admin') {
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['nama']    = $user['nama'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = $user['role'];

                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Akun ini tidak memiliki akses sebagai admin.';
            }
        } else {
            $error = 'Email atau password salah.';
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Hotel Booking</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

<main class="login-main">
    <div class="login-box">
        <div class="login-header">
            <header>Login Admin</header>
            <p class="login-sub">Khusus untuk pengelola website Hotel Booking</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="msg-box msg-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="on">
            <div class="input-box">
                <input type="email" class="input-field" name="email" placeholder="admin@hotelbooking.com"
                       required autocomplete="email"
                       value="<?php echo htmlspecialchars($emailValue); ?>">
            </div>

            <div class="input-box">
                <input type="password" class="input-field" name="password" placeholder="Masukkan password"
                       required autocomplete="current-password">
            </div>

            <div class="input-submit">
                <button type="submit" id="loginSubmit" class="submit-btn" aria-label="Masuk"></button>
                <label for="loginSubmit">Masuk</label>
            </div>
        </form>

        <p class="login-footer-link"><a href="../index.php">&larr; Kembali ke Website</a></p>
    </div>
</main>

</body>
</html>
