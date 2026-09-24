<?php
/**
 * navbar_admin.php
 *
 * Cara pakai di setiap halaman admin:
 *
 *   <body>
 *     <?php include 'navbar_admin.php'; ?>
 *     <main> ... isi halaman ... </main>
 *   </body>
 *
 * Catatan:
 * - Jangan taruh <body> di file ini. CSS kamu memakai "body { display: grid }",
 *   jadi <nav id="sidebar"> dan <main> harus jadi anak langsung <body>.
 * - Menu yang sedang dibuka ditandai otomatis (class "active") dari nama file
 *   halaman, jadi tidak perlu diatur manual di tiap halaman.
 */

$halaman_aktif = basename($_SERVER['PHP_SELF']);

/* ---------- Ikon (Material Symbols, viewBox sama dengan ikon kamu) ---------- */
$ikon = [
    'dashboard' => '<path d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z"/>',
    'hotel'     => '<path d="M80-200v-240q0-27 11-49t29-39v-112q0-50 35-85t85-35h160q23 0 43 8.5t37 23.5q17-15 37-23.5t43-8.5h160q50 0 85 35t35 85v112q18 17 29 39t11 49v240h-80v-80H160v80H80Zm440-360h240v-40q0-17-11.5-28.5T720-640H560q-17 0-28.5 11.5T520-600v40Zm-320 0h240v-40q0-17-11.5-28.5T400-640H240q-17 0-28.5 11.5T200-600v40Zm-40 200h640v-80q0-17-11.5-28.5T760-480H200q-17 0-28.5 11.5T160-440v80Zm640 0H160h640Z"/>',
    'booking'   => '<path d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-80h80v80h320v-80h80v80h40q33 0 56.5 23.5T840-720v560q0 33-23.5 56.5T760-80H200Zm0-80h560v-400H200v400Zm0-480h560v-80H200v80Zm0 0v-80 80Z"/>',
    'payment'   => '<path d="M880-720v480q0 33-23.5 56.5T800-160H160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720Zm-720 80h640v-80H160v80Zm0 160v240h640v-240H160Zm0 240v-480 480Z"/>',
    'user'      => '<path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Zm80-80h480v-32q0-11-5.5-20T700-306q-54-27-109-40.5T480-360q-56 0-111 13.5T260-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Zm0 400Z"/>',
    'logout'    => '<path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/>',
    'chevron'   => '<path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z"/>',
    'toggle'    => '<path d="M440-240 200-480l240-240 56 56-183 184 183 184-56 56Zm264 0L464-480l240-240 56 56-183 184 183 184-56 56Z"/>',
];

/* ---------- Struktur menu ----------
 * 'pages' = daftar file yang membuat menu ini tampil aktif
 * (termasuk halaman tambah/edit, supaya menu tetap menyala saat mengedit).
 */
$menu = [
    [
        'label' => 'Dashboard',
        'icon'  => 'dashboard',
        'href'  => 'dashboard.php',
        'pages' => ['dashboard.php'],
    ],
    [
        'label'    => 'Hotels & Rooms',
        'icon'     => 'hotel',
        'children' => [
            [
                'label' => 'Data hotels',
                'href'  => 'hotels.php',
                'pages' => ['hotels.php', 'tambah-hotels.php', 'edit-hotels.php'],
            ],
            [
                'label' => 'Data rooms',
                'href'  => 'rooms.php',
                'pages' => ['rooms.php', 'tambah-rooms.php', 'edit-rooms.php'],
            ],
        ],
    ],
    [
        'label' => 'Bookings',
        'icon'  => 'booking',
        'href'  => 'bookings.php',
        'pages' => ['bookings.php', 'detail-booking.php'],
    ],
    [
        'label' => 'Payments',
        'icon'  => 'payment',
        'href'  => 'payments.php',
        'pages' => ['payments.php'],
    ],
    [
        'label' => 'Users',
        'icon'  => 'user',
        'href'  => 'users.php',
        'pages' => ['users.php'],
    ],
];

/* ---------- Fungsi bantu ---------- */
if (!function_exists('svg_ikon')) {
    function svg_ikon(string $path): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px">' . $path . '</svg>';
    }
}

if (!function_exists('nav_aktif')) {
    function nav_aktif(array $pages, string $sekarang): bool
    {
        return in_array($sekarang, $pages, true);
    }
}
?>
<nav id="sidebar">
  <ul>
    <li>
      <span class="logo">HotelKom</span>
      <button onclick="toggleSidebar()" id="toggle-btn" aria-label="Buka atau tutup sidebar">
        <?= svg_ikon($ikon['toggle']) ?>
      </button>
    </li>

    <?php foreach ($menu as $item): ?>

      <?php if (isset($item['children'])): ?>
        <?php
          // Dropdown terbuka otomatis jika salah satu halaman anaknya sedang dibuka
          $buka = false;
          foreach ($item['children'] as $anak) {
              if (nav_aktif($anak['pages'], $halaman_aktif)) {
                  $buka = true;
                  break;
              }
          }
        ?>
        <li>
          <button onclick="toggleSubMenu(this)" class="dropdown-btn<?= $buka ? ' rotate' : '' ?>">
            <?= svg_ikon($ikon[$item['icon']]) ?>
            <span><?= htmlspecialchars($item['label']) ?></span>
            <?= svg_ikon($ikon['chevron']) ?>
          </button>
          <ul class="sub-menu<?= $buka ? ' show' : '' ?>">
            <div>
              <?php foreach ($item['children'] as $anak): ?>
                <li<?= nav_aktif($anak['pages'], $halaman_aktif) ? ' class="active"' : '' ?>>
                  <a href="<?= htmlspecialchars($anak['href']) ?>"><?= htmlspecialchars($anak['label']) ?></a>
                </li>
              <?php endforeach; ?>
            </div>
          </ul>
        </li>

      <?php else: ?>
        <li<?= nav_aktif($item['pages'], $halaman_aktif) ? ' class="active"' : '' ?>>
          <a href="<?= htmlspecialchars($item['href']) ?>">
            <?= svg_ikon($ikon[$item['icon']]) ?>
            <span><?= htmlspecialchars($item['label']) ?></span>
          </a>
        </li>
      <?php endif; ?>

    <?php endforeach; ?>

    <!-- Logout: dipisah dan ditempel di dasar sidebar (margin-top: auto di CSS) -->
    <li class="logout">
      <a href="logout.php" onclick="return confirm('Yakin ingin keluar?')">
        <?= svg_ikon($ikon['logout']) ?>
        <span>Logout</span>
      </a>
    </li>
  </ul>
</nav>
<script src="assets/js/main.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="assets/js/custom-select.js"></script>