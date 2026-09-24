<?php
// koneksi.php - koneksi ke database "hotel"
$host = "localhost";
$user = "root";
$pass = "";          // sesuaikan dengan password MariaDB kamu
$db   = "hotel";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Koneksi database gagal: " . htmlspecialchars($e->getMessage()));
}

// Helper: format angka ke Rupiah
function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ",", ".");
}