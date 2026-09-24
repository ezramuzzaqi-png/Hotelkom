<?php
// admin/login.php
// Halaman login khusus admin

session_start();
require_once '../config/koneksi.php';

$error = '';

// ADMIN
$password_hash = password_hash('adminhotel', PASSWORD_DEFAULT);
echo $password_hash;

// Kalau admin sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['id_user']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-login-wrapper">
    <div class="admin-login-box">
        <h2>Login Admin</h2>
        <p class="subtitle">Khusus untuk pengelola website Hotel Booking</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="admin@hotelbooking.com"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Masukkan password"
                    required
                >
            </div>

            <button type="submit" class="btn-login">Masuk</button>
        </form>

        <div class="back-link">
            <a href="../index.php">&larr; Kembali ke Website</a>
        </div>
    </div>
</div>

</body>
</html>