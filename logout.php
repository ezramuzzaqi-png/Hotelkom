<?php
// logout.php
// Menghapus session dan mengakhiri login

session_start();

// Hapus semua data session
$_SESSION = [];
session_unset();
session_destroy();

// Arahkan kembali ke halaman login
header('Location: login.php');
exit;
?>