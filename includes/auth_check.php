<?php
// includes/auth_check.php
// Cek apakah user sudah login. Jika belum, redirect ke login.php
// Cara pakai: include file ini di baris paling atas halaman yang butuh login
// Contoh: booking.php, my_bookings.php, profile.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
?>