<!-- MENGHUBUNGKAN PHP DENGAN DATABASE -->
<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "hotel";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Tidak terhubung ke database : " . mysqli_connect_error());
}

$conn =  $koneksi;
?>