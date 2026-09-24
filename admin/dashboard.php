<?php
require __DIR__ . '/config/koneksi.php';

/* ---------- Ringkasan (kartu atas) ---------- */
$h = $conn->query("SELECT COUNT(*) AS total, COUNT(DISTINCT kota) AS kota FROM hotels")->fetch_assoc();
$r = $conn->query("SELECT COALESCE(SUM(stok_kamar),0) AS stok, COUNT(*) AS tipe FROM rooms")->fetch_assoc();
$b = $conn->query("SELECT
        SUM(status_booking = 'Pending')   AS pending,
        SUM(status_booking = 'Confirmed') AS confirmed
    FROM bookings")->fetch_assoc();
$p = $conn->query("SELECT COALESCE(SUM(jumlah_bayar),0) AS total, COUNT(*) AS jumlah
    FROM payments WHERE status_pembayaran = 'Success'")->fetch_assoc();

$stats = [
    ['label' => 'Hotels',           'value' => number_format($h['total'], 0, ',', '.'),
     'sub'   => (int)$h['kota'] . ' kota',                'icon' => 'hotel'],
    ['label' => 'Kamar',            'value' => number_format($r['stok'], 0, ',', '.'),
     'sub'   => 'DI tempati',                              'icon' => 'bed'],
    ['label' => 'Booking Pending',  'value' => number_format((int)$b['pending'], 0, ',', '.'),
     'sub'   => (int)$b['confirmed'] . ' dikonfirmasi',    'icon' => 'clock'],
    ['label' => 'Pendapatan',       'value' => rupiah($p['total']),
     'sub'   => (int)$p['jumlah'] . ' pembayaran sukses',  'icon' => 'wallet'],
];

/* ---------- Ikon (stroke, mengikuti warna teks) ---------- */
$icons = [
  'hotel'  => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"/>',
  'bed'    => '<path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4M12 4v6M2 18h20"/>',
  'clock'  => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
  'wallet' => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4M3 5v14a2 2 0 0 0 2 2h16v-5M18 12a2 2 0 0 0 0 4h4v-4Z"/>',
];
function ikon($icons, $nama, $size = 22) {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$icons[$nama].'</svg>';
}

/* ---------- 6 booking terbaru + progres pembayaran ---------- */
$sql = "SELECT b.id_booking, u.nama, h.nama_hotel, h.kota, r.tipe_kamar,
               b.tanggal_checkin, b.tanggal_checkout, b.total_biaya, b.status_booking,
               DATEDIFF(b.tanggal_checkout, b.tanggal_checkin) AS malam,
               COALESCE((SELECT SUM(p.jumlah_bayar) FROM payments p
                         WHERE p.id_booking = b.id_booking
                           AND p.status_pembayaran = 'Success'), 0) AS dibayar
        FROM bookings b
        JOIN users  u ON u.id_user   = b.id_user
        JOIN rooms  r ON r.id_kamar  = b.id_kamar
        JOIN hotels h ON h.id_hotel  = r.id_hotel
        ORDER BY b.tanggal_booking DESC
        LIMIT 6";
$booking_terbaru = $conn->query($sql);

/* Warna avatar dari inisial nama */
$warna_avatar = ['#0e8fd6', '#063d73', '#e0a800', '#2a9d8f', '#7b61ff', '#e76f51'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Kolika</title>
  <link rel="stylesheet" href="assets/css/style.css">

  <!-- Tambahan untuk tampilan dashboard (boleh dipindah ke style.css) -->
  <style>
    main.dash { padding: 0; }

    /* Banner atas */
    .d-hero {
        background: linear-gradient(135deg, var(--base-clr), var(--base-clr-dark));
        color: #fff;
        padding: 2.2em min(30px, 5%) 110px;
    }
    .d-hero-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1em;
    }
    .d-hero h1 { font-weight: 500; font-size: 1.9rem; }
    .d-btn {
        background: #fff;
        color: var(--secondary-text-clr);
        text-decoration: none;
        padding: .7em 1.4em;
        border-radius: .5em;
        box-shadow: 0 2px 6px rgba(0,0,0,.12);
        transition: background-color 150ms ease;
    }
    .d-btn:hover { background: var(--accent-clr); }

    /* Isi halaman naik menimpa banner */
    .d-body {
        padding: 0 min(30px, 5%) 30px;
        margin-top: -80px;
        color: var(--secondary-text-clr);
    }

    /* Kartu ringkasan */
    .d-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 1.2em;
        margin-bottom: 1.6em;
    }
    .d-stat {
        background: #fff;
        border-radius: .8em;
        padding: 1.3em 1.5em;
        box-shadow: 0 2px 10px rgba(0,0,0,.08);
    }
    .d-stat-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1em;
    }
    .d-stat-label { font-size: 1.15rem; }
    .d-stat-icon {
        width: 46px; height: 46px;
        display: grid; place-items: center;
        border-radius: .6em;
        background: rgba(163,177,138,.18);
        color: var(--base-clr);
        flex-shrink: 0;
    }
    .d-stat-value {
        margin-top: .4em;
        font-size: 2.3rem;
        font-weight: 700;
        line-height: 1.25;
    }
    .d-stat-sub { color: #8a94a6; }

    /* Tabel */
    .d-card {
        background: #fff;
        border-radius: .8em;
        box-shadow: 0 2px 10px rgba(0,0,0,.08);
        overflow: hidden;
    }
    .d-card-head {
        padding: 1.1em 1.5em;
        border-bottom: 1px solid #e3e8ef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1em;
    }
    .d-card-head h2 { font-size: 1.2rem; font-weight: 500; }
    .d-card-head a { color: var(--base-clr); text-decoration: none; }
    .d-card-head a:hover { text-decoration: underline; }

    .d-table-wrap { overflow-x: auto; }
    .d-table { width: 100%; border-collapse: collapse; min-width: 760px; }
    .d-table th {
        background: #f4f6f9;
        color: #6b7686;
        font-weight: 400;
        padding: .9em 1.2em;
        border-bottom: 1px solid #e3e8ef;
        text-align: left;
    }
    .d-table td {
        padding: .9em 1.2em;
        border-bottom: 1px solid #eef1f5;
        text-align: left;
        vertical-align: middle;
    }
    .d-table tbody tr:last-child td { border-bottom: none; }
    .d-table tbody tr:hover { background: #f8fafc; }
    .d-table td.empty { text-align: center; color: #8a94a6; padding: 2em; }

    .d-hotel { display: flex; align-items: center; gap: 1em; }
    .d-hotel-icon {
        width: 44px; height: 44px;
        display: grid; place-items: center;
        border: 1px solid #e3e8ef;
        border-radius: .5em;
        color: var(--base-clr);
        flex-shrink: 0;
    }
    .d-muted { display: block; color: #8a94a6; font-size: .85rem; }

    .d-badge {
        display: inline-block;
        padding: .15em .8em;
        border-radius: .4em;
        font-size: .85rem;
        font-weight: 600;
        color: #fff;
    }
    .d-badge.pending   { background: #f5a30a; }
    .d-badge.confirmed { background: #22a45d; }
    .d-badge.cancelled { background: #e5484d; }

    .d-user { display: flex; align-items: center; gap: .7em; }
    .d-avatar {
        width: 32px; height: 32px;
        border-radius: 50%;
        display: grid; place-items: center;
        color: #fff; font-size: .9rem; font-weight: 600;
        flex-shrink: 0;
    }

    .d-progress { display: flex; align-items: center; gap: .8em; }
    .d-progress-num { min-width: 3em; }
    .d-bar {
        flex: 1; min-width: 70px; max-width: 110px;
        height: 6px; border-radius: 999px;
        background: #e9edf3; overflow: hidden;
    }
    .d-bar span { display: block; height: 100%; background: var(--base-clr); border-radius: 999px; }
  </style>
</head>
<body>

  <?php include 'includes/navbar_admin.php'; ?>

  <main class="dash">
    <section class="d-hero">
      <div class="d-hero-top">
        <h1>Dashboard</h1>
        <a href="bookings.php" class="d-btn">Kelola Booking</a>
      </div>
    </section>

    <div class="d-body">

      <!-- Kartu ringkasan -->
      <section class="d-stats">
        <?php foreach ($stats as $s): ?>
          <div class="d-stat">
            <div class="d-stat-top">
              <span class="d-stat-label"><?= htmlspecialchars($s['label']) ?></span>
              <span class="d-stat-icon"><?= ikon($icons, $s['icon']) ?></span>
            </div>
            <div class="d-stat-value"><?= htmlspecialchars($s['value']) ?></div>
            <div class="d-stat-sub"><?= htmlspecialchars($s['sub']) ?></div>
          </div>
        <?php endforeach; ?>
      </section>

      <!-- Booking terbaru -->
      <section class="d-card">
        <div class="d-card-head">
          <h2>Booking Terbaru</h2>
          <a href="bookings.php">Lihat semua</a>
        </div>

        <div class="d-table-wrap">
          <table class="d-table">
            <thead>
              <tr>
                <th>Hotel &amp; Kamar</th>
                <th>Menginap</th>
                <th>Status</th>
                <th>Pelanggan</th>
                <th>Pembayaran</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($booking_terbaru->num_rows > 0): ?>
                <?php while ($row = $booking_terbaru->fetch_assoc()):
                    $persen  = $row['total_biaya'] > 0
                             ? min(100, (int)round($row['dibayar'] / $row['total_biaya'] * 100))
                             : 0;
                    $inisial = strtoupper(mb_substr($row['nama'], 0, 1));
                    $warna   = $warna_avatar[crc32($row['nama']) % count($warna_avatar)];
                ?>
                  <tr>
                    <td>
                      <div class="d-hotel">
                        <span class="d-hotel-icon"><?= ikon($icons, 'hotel', 24) ?></span>
                        <div>
                          <?= htmlspecialchars($row['nama_hotel']) ?>
                          <span class="d-muted"><?= htmlspecialchars($row['tipe_kamar']) ?> &middot; <?= htmlspecialchars($row['kota']) ?></span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <?= (int)$row['malam'] ?> malam
                      <span class="d-muted">
                        <?= date('d M', strtotime($row['tanggal_checkin'])) ?> - <?= date('d M Y', strtotime($row['tanggal_checkout'])) ?>
                      </span>
                    </td>
                    <td>
                      <span class="d-badge <?= strtolower($row['status_booking']) ?>">
                        <?= htmlspecialchars($row['status_booking']) ?>
                      </span>
                    </td>
                    <td>
                      <div class="d-user">
                        <span class="d-avatar" style="background: <?= $warna ?>"><?= htmlspecialchars($inisial) ?></span>
                        <?= htmlspecialchars($row['nama']) ?>
                      </div>
                    </td>
                    <td>
                      <div class="d-progress">
                        <span class="d-progress-num"><?= $persen ?>%</span>
                        <div class="d-bar"><span style="width: <?= $persen ?>%"></span></div>
                      </div>
                      <span class="d-muted"><?= rupiah($row['dibayar']) ?> / <?= rupiah($row['total_biaya']) ?></span>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="5" class="empty">Belum ada booking.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

    </div>
  </main>

  <script src="assets/js/main.js"></script>
</body>
</html>