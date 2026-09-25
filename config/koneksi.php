<?php
$koneksi = mysqli_connect(getenv('MYSQLHOST'), getenv('MYSQLUSER'), getenv('MYSQLPASSWORD'), getenv('MYSQLDATABASE'), getenv('MYSQLPORT'));

if (!$koneksi) {
    die("Tidak terhubung ke database : " . mysqli_connect_error());
}

$conn =  $koneksi;
?>