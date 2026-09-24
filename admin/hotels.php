<?php
include_once("../config/koneksi.php");
 
$result = mysqli_query($koneksi, "SELECT * FROM hotels ORDER BY id_hotel DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar_admin.php'; ?>

    <main>
    <button class="add-user"></button>

    <table width='80%' border=1>

    <tr>
        <th>Nama Hotel</th> <th>Alamat</th> <th>Kota</th> <th>Deskripsi</th> <th>Update</th>
    </tr>
    <?php  
    while($user_data = mysqli_fetch_array($result)) {         
        echo "<tr>";
        echo "<td>".$user_data['nama_hotel']."</td>";
        echo "<td>".$user_data['alamat']."</td>";
        echo "<td>".$user_data['kota']."</td>";
        echo "<td>".$user_data['deskripsi']."</td>";
        echo "<td><a href='edit.php?id=$user_data[id_hotel]'>Edit</a> | <a href='delete.php?id=$user_data[id_hotel]'>Delete</a></td></tr>";        
    }
    ?>
    </table>
</main>
</body>
</html>