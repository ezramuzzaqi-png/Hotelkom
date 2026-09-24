<?php
require __DIR__ . '/config/koneksi.php';
// alias untuk kompatibilitas file lama yang pakai $koneksi
if (!isset($koneksi) && isset($conn)) $koneksi = $conn;
if (!isset($conn) && isset($koneksi)) $conn = $koneksi;

// Handle hapus via GET ?hapus=id
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Hapus hotel (rooms & bookings ikut cascade)
    $stmtDel = $conn->prepare("DELETE FROM hotels WHERE id_hotel=?");
    $stmtDel->bind_param("i", $id);
    $stmtDel->execute();
    $stmtDel->close();
    header('Location: hotels.php?status=hapus');
    exit;
}

$result = $koneksi->query("SELECT * FROM hotels ORDER BY id_hotel DESC");
$total  = $result ? $result->num_rows : 0;

// Pesan sukses (opsional): redirect dari simpan/edit/delete dengan ?status=tambah|edit|hapus
$pesan = [
    'tambah' => 'Hotel baru berhasil ditambahkan.',
    'edit'   => 'Data hotel berhasil diperbarui.',
    'hapus'  => 'Hotel berhasil dihapus.',
];
$status = $_GET['status'] ?? '';

// Escape output supaya aman dari XSS
function e($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Hotel | Kolika</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar_admin.php'; ?>

    <main>
        <div class="page-header">
            <div>
                <h1>Data Hotel</h1>
                <p>Kelola semua hotel yang tersedia di sini.</p>
            </div>
            <a href="tambah-hotels.php" class="btn">+ Tambah hotel</a>
        </div>

        <?php if (isset($pesan[$status])): ?>
            <div class="alert alert-success"><?= e($pesan[$status]) ?></div>
        <?php endif; ?>

        <div class="container">
            <div class="table-toolbar">
                <input type="search" id="cari" class="search-input" placeholder="Cari nama, kota, atau alamat...">
                <span class="count"><?= $total ?> hotel</span>
            </div>

            <div class="table-wrapper">
                <table id="tabel-hotel">
                    <thead>
                        <tr>
                            <th class="no">No</th>
                            <th>Nama hotel</th>
                            <th>Kota</th>
                            <th>Alamat</th>
                            <th>Deskripsi</th>
                            <th class="aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($total === 0): ?>
                        <tr>
                            <td colspan="6" class="empty">Belum ada data hotel. Klik "Tambah hotel" untuk menambahkan.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; while ($h = mysqli_fetch_assoc($result)): ?>
                        <tr data-cari="<?= e(mb_strtolower($h['nama_hotel'] . ' ' . $h['kota'] . ' ' . $h['alamat'])) ?>">
                            <td class="no"><?= $no++ ?></td>
                            <td class="nama"><?= e($h['nama_hotel']) ?></td>
                            <td><span class="badge badge-info"><?= e($h['kota']) ?></span></td>
                            <td class="alamat"><?= e($h['alamat']) ?></td>
                            <td class="deskripsi"><span class="clamp"><?= e($h['deskripsi']) ?></span></td>
                            <td class="aksi">
                                <div class="actions">
                                    <a href="edit-hotels.php?id=<?= (int) $h['id_hotel'] ?>" class="btn btn-sm btn-edit">Edit</a>
                                    <a href="hotels.php?hapus=<?= (int) $h['id_hotel'] ?>" class="btn btn-sm btn-delete"
                                       onclick="return confirm('Hapus hotel ini? Semua kamar dan booking yang terkait juga akan terhapus.')">Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        // Pencarian sederhana di sisi browser
        const cari  = document.getElementById('cari');
        const baris = document.querySelectorAll('#tabel-hotel tbody tr[data-cari]');

        cari.addEventListener('input', () => {
            const kata = cari.value.toLowerCase().trim();
            baris.forEach(tr => {
                tr.hidden = !tr.dataset.cari.includes(kata);
            });
        });
    </script>
</body>
</html>