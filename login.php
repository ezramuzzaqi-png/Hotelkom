<?php
// login.php
// Halaman login user (dan admin, diarahkan sesuai role)

session_start();
require_once 'config/koneksi.php';

$error = '';
$emailValue = '';

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
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $emailValue = $email;

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
<link rel="stylesheet" href="assets/css/login.css">

<main class="login-main">
    <div class="login-box">
        <div class="login-header">
            <header>Login</header>
            <p class="login-sub">Masuk untuk booking hotel favoritmu</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="msg-box msg-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['registrasi']) && $_GET['registrasi'] === 'sukses'): ?>
            <div class="msg-box msg-success">Registrasi berhasil! Silakan login.</div>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="on">
            <div class="input-box">
                <input type="email" class="input-field" name="email" placeholder="Email"
                       required autocomplete="email"
                       value="<?php echo htmlspecialchars($emailValue); ?>">
            </div>

            <div class="input-box">
                <input type="password" class="input-field" name="password" placeholder="Password"
                       required autocomplete="current-password">
            </div>

            <div class="input-submit">
                <button type="submit" id="loginSubmit" class="submit-btn" aria-label="Masuk"></button>
                <label for="loginSubmit">Masuk</label>
            </div>
        </form>

        <p class="login-footer-link">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </div>
</main>

<?php
include 'includes/footer.php';
?>
