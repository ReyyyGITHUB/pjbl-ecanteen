<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

require_login('kantin');
$user = current_user();
$isSeller = (($user['role'] ?? '') === 'seller');
$dashboardHref = $isSeller ? seller_dashboard_route($user) : '';

$kantinCards = [
  ['name' => 'Kantin PJBL', 'image' => 'assets/img/kantin/kantin-make.png', 'active' => true, 'target' => 'kantin-1'],
  ['name' => 'Kantin Belum Terdaftar', 'image' => 'assets/img/icons/not-active.svg', 'active' => false],
  ['name' => 'Kantin Belum Terdaftar', 'image' => 'assets/img/icons/not-active.svg', 'active' => false],
  ['name' => 'Kantin Belum Terdaftar', 'image' => 'assets/img/icons/not-active.svg', 'active' => false],
];

function format_menu_name(string $value): string {
  $value = str_replace('_', ' ', trim($value));
  $value = preg_replace('/\s+/', ' ', $value) ?? $value;
  return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
}

function format_price_label(int $price): string {
  return 'Rp. ' . number_format($price, 0, ',', '.');
}

function resolve_kantin_menu_image(string $gambar, string $menuName): string {
  $gambar = trim($gambar);
  if ($gambar !== '' && $gambar !== 'gambar.jpg') {
    return $gambar;
  }

  $normalized = strtolower(str_replace([' ', '-', '/', '+'], '_', trim($menuName)));
  $normalized = preg_replace('/_+/', '_', $normalized) ?? $normalized;

  $fallbackMap = [
    'nasi_geprek' => 'assets/img/kantin/menu-ayam.png',
    'ayam_geprek_nasi_putih' => 'assets/img/kantin/menu-ayam.png',
    'cireng' => 'assets/img/kantin/menu-2.png',
    'es_teh' => 'assets/img/kantin/hero-drink.png',
    'es_teh_manis' => 'assets/img/kantin/hero-drink.png',
    'gorengan' => 'assets/img/kantin/hero-mendoan.png',
    'mendoan' => 'assets/img/kantin/hero-mendoan.png',
    'teajus' => 'assets/img/kantin/hero-side.png',
    'kopi_goodday' => 'assets/img/kantin/hero-side.png',
    'soto' => 'assets/img/kantin/hero-soto.png',
    'soto_ayam' => 'assets/img/kantin/hero-soto.png',
    'risolmayo_panas' => 'assets/img/kantin/menu-4.png',
    'risoles_mayo_panas' => 'assets/img/kantin/menu-4.png',
  ];

  return $fallbackMap[$normalized] ?? 'assets/img/kantin/menu-ayam.png';
}

$menus = [];
$conn = db();
$stmtMenu = $conn->prepare(
  'SELECT nama_menu, harga, sisa_stock, gambar
   FROM menu
   WHERE id_kantin = ?
   ORDER BY id_menu ASC
   LIMIT 6'
);
$kantinId = 1;
$stmtMenu->bind_param('i', $kantinId);
$stmtMenu->execute();
$menuResult = $stmtMenu->get_result();

while ($row = $menuResult->fetch_assoc()) {
  $menus[] = [
    'name' => format_menu_name((string)$row['nama_menu']),
    'price' => format_price_label((int)$row['harga']),
    'image' => resolve_kantin_menu_image((string)$row['gambar'], (string)$row['nama_menu']),
    'stock' => (int)$row['sisa_stock'],
  ];
}

$stmtMenu->close();

$benefits = [
  ['title' => 'Tanpa Ribet, SatSet Makanan Jadi', 'image' => 'menu-3.png', 'class' => 'benefit-pink'],
  ['title' => 'Memudahkan Penjual Transaksi', 'image' => 'menu-2.png', 'class' => 'benefit-violet'],
  ['title' => 'Terhindar Dari Antrian Panjanggg...', 'image' => 'menu-ayam.png', 'class' => 'benefit-purple'],
  ['title' => 'Support Bayar Via QRIS', 'image' => 'menu-4.png', 'class' => 'benefit-yellow'],
];
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/styles.css" />
    <link rel="stylesheet" href="assets/css/kantin.css" />
    <link rel="stylesheet" href="assets/css/page-loader.css" />
    <script src="assets/js/page-loader.js" defer></script>
    <title>Kantin - E-Canteen</title>
  </head>
  <body class="kantin-body">
    <header class="kantin-nav">
      <a class="kantin-brand" href="./" aria-label="E-Canteen">
        <img src="assets/img/figma/logo-mark.png" alt="" />
        <span>E-Canteen</span>
      </a>
      <nav class="kantin-links" aria-label="Navigasi kantin">
        <a href="./">Beranda</a>
        <a href="#pilihan-kantin">Pilihan Kantin</a>
        <a href="riwayat">Riwayat Pembelian</a>
      </nav>
      <div class="kantin-user-wrap" data-kantin-user-menu>
        <button
          class="kantin-user"
          type="button"
          aria-haspopup="menu"
          aria-expanded="false"
          aria-controls="kantin-user-menu"
          data-kantin-user-toggle
        >
          <span class="kantin-user-name"><?= htmlspecialchars((string)($user['username'] ?? 'user')) ?></span>
          <svg class="kantin-user-caret" viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true">
            <path d="M6.75 9.75 12 15l5.25-5.25" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
        <div class="kantin-user-menu" id="kantin-user-menu" role="menu" hidden>
          <div class="kantin-user-menu-head">
            <span class="kantin-user-menu-label">Akun</span>
            <strong class="kantin-user-menu-name"><?= htmlspecialchars((string)($user['username'] ?? 'user')) ?></strong>
          </div>
          <?php if ($isSeller): ?>
            <a class="kantin-user-menu-item" role="menuitem" href="<?= htmlspecialchars($dashboardHref) ?>">Dashboard</a>
          <?php endif; ?>
          <a class="kantin-user-menu-item" role="menuitem" href="logout">Logout</a>
        </div>
      </div>
    </header>

    <main class="kantin-page">
      <section class="kantin-hero" aria-labelledby="kantin-hero-title">
        <img class="hero-food hero-food-drink" src="assets/img/kantin/hero-drink.png" alt="" />
        <img class="hero-food hero-food-side" src="assets/img/kantin/hero-side.png" alt="" />
        <img class="hero-food hero-food-mendoan" src="assets/img/kantin/hero-mendoan.png" alt="" />
        <img class="hero-food hero-food-banner" src="assets/img/kantin/hero-banner.png" alt="" />
        <img class="hero-food hero-food-soto" src="assets/img/kantin/hero-soto.png" alt="" />
        <div class="kantin-hero-copy">
          <img class="kantin-hero-icon" src="assets/img/kantin/icon-food.svg" alt="" />
          <h1 id="kantin-hero-title">Laper? Pesan di sini aja!</h1>
          <p>Pilih kantin, cari menu favoritmu, lalu pesan tanpa perlu antre panjang di jam istirahat.</p>
        </div>
      </section>

      <section class="kantin-search-panel" aria-label="Cari makanan">
        <p>Pesan apa hari ini?</p>
        <form class="kantin-search" action="kantin" method="get" data-skip-page-loader>
          <label class="kantin-search-field">
            <img src="assets/img/kantin/icon-food-small.svg" alt="" />
            <input
              type="search"
              name="q"
              placeholder="Cari menu, minuman, atau jajanan..."
              autocomplete="off"
              data-kantin-search-input
              aria-autocomplete="list"
              aria-controls="kantin-search-results"
              aria-expanded="false"
            />
          </label>
          <button type="submit">
            <img src="assets/img/kantin/icon-cart.svg" alt="" />
            <span>Pesan Sekarang</span>
          </button>
        </form>
        <div
          class="kantin-search-results"
          id="kantin-search-results"
          data-kantin-search-results
          role="listbox"
          aria-label="Hasil pencarian menu"
          hidden
        ></div>
        <div
          class="kantin-search-notice"
          data-kantin-search-notice
          role="status"
          aria-live="polite"
          hidden
        >
          <span data-kantin-search-notice-text></span>
          <button type="button" class="kantin-search-notice-close" data-kantin-search-notice-close aria-label="Tutup notifikasi">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </section>

      <section class="kantin-section" id="pilihan-kantin">
        <div class="section-heading">
          <h2>Cari Kantin Favoritmu? Cek di Sini</h2>
        </div>
        <div class="kantin-card-grid">
          <?php foreach ($kantinCards as $index => $card): ?>
            <button
              class="kantin-choice <?= $card['active'] ? 'is-ready' : 'is-empty' ?>"
              type="button"
              data-kantin-choice
              <?php if (!empty($card['target'])): ?>data-kantin-target="<?= htmlspecialchars((string)$card['target']) ?>"<?php endif; ?>
            >
              <div class="kantin-choice-img">
                <img src="<?= htmlspecialchars($card['image']) ?>" alt="" />
              </div>
              <h3><?= htmlspecialchars($card['name']) ?></h3>
            </button>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="kantin-section">
        <div class="section-heading">
          <h2>Rekomendasi menu untukmu</h2>
          <p>Menu favorit yang sering dipesan dan cocok buat jam istirahat.</p>
        </div>
        <div class="menu-grid">
          <?php foreach ($menus as $menu): ?>
            <a class="menu-card" href="#pilihan-kantin" aria-label="Pilih <?= htmlspecialchars($menu['name']) ?>">
              <div class="menu-img">
                <img src="<?= htmlspecialchars($menu['image']) ?>" alt="<?= htmlspecialchars($menu['name']) ?>" />
              </div>
              <h3><?= htmlspecialchars($menu['name']) ?></h3>
              <p class="menu-stock">Stok: <?= htmlspecialchars((string)$menu['stock']) ?></p>
              <div class="menu-footer">
                <span><?= htmlspecialchars($menu['price']) ?></span>
                <img src="assets/img/kantin/icon-plus.svg" alt="" />
              </div>
            </a>
          <?php endforeach; ?>
        </div>
        <a class="more-menu" href="#pilihan-kantin">
          <span>Lihat Menu Selengkapnya</span>
          <img src="assets/img/icons/weui_arrow-outlined.svg" alt="" />
        </a>
      </section>

      <section class="kantin-section">
        <div class="section-heading">
          <h2>Alasan Kalian Harus Pake E-Canteen!!</h2>
          <p>Pesan lebih awal, bayar lebih mudah, dan waktu istirahat jadi lebih efisien.</p>
        </div>
        <div class="benefit-grid">
          <?php foreach ($benefits as $benefit): ?>
            <article class="benefit-card <?= htmlspecialchars($benefit['class']) ?>">
              <img src="assets/img/kantin/<?= htmlspecialchars($benefit['image']) ?>" alt="" />
              <h3><?= htmlspecialchars($benefit['title']) ?></h3>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="kantin-section kantin-map-section">
        <div class="section-heading">
          <h2>Lokasi Kantin Yang Wajib Kamu Tau</h2>
          <p>Denah sederhana untuk membantu kamu menemukan posisi kantin dengan cepat.</p>
        </div>
        <div class="map-layout">
          <div class="canteen-map" aria-label="Denah kantin">
            <div class="map-left">
              <?php for ($i = 0; $i < 5; $i++): ?>
                <span class="map-room"></span><span class="map-dot"></span>
              <?php endfor; ?>
            </div>
            <div class="map-kantin-active">Kantin Mak'e</div>
            <?php for ($i = 1; $i <= 3; $i++): ?>
              <div class="map-kantin-empty map-empty-<?= $i ?>">
                <img src="assets/img/kantin/icon-error.svg" alt="" />
              </div>
            <?php endfor; ?>
            <div class="map-exit">
              <img src="assets/img/kantin/icon-map-arrow-up.svg" alt="" />
              <span>Masuk / Keluar</span>
              <img src="assets/img/kantin/icon-map-arrow-down.svg" alt="" />
            </div>
            <div class="map-bottom">
              <?php for ($i = 0; $i < 5; $i++): ?><span></span><?php endfor; ?>
            </div>
          </div>
          <aside class="kantin-list">
            <h3>Daftar Nama - Nama Kantin</h3>
            <?php foreach ($kantinCards as $card): ?>
              <a href="<?= htmlspecialchars((string)($card['target'] ?? '#pilihan-kantin')) ?>" class="kantin-list-item">
                <span>
                  <img src="<?= htmlspecialchars($card['image']) ?>" alt="" />
                  <?= htmlspecialchars($card['name']) ?>
                </span>
                <img class="list-arrow" src="assets/img/kantin/icon-arrow.svg" alt="" />
              </a>
            <?php endforeach; ?>
          </aside>
        </div>
      </section>
    </main>

    <footer class="kantin-footer">
      <div>
        <h2>Mau Pesan Makanan Tanpa Antri?</h2>
        <a href="#pilihan-kantin"><img src="assets/img/kantin/icon-cart.svg" alt="" /> Klik di Sini!</a>
      </div>
      <p>Copyright &copy; 2025 Kelompok 1 XPPLG2 SMKN 8 Semarang. All Rights Reserved.</p>
      <nav aria-label="Footer link">
        <a href="./">Beranda</a>
        <a href="#pilihan-kantin">Pilih Kantin</a>
        <a href="logout">Logout</a>
      </nav>
    </footer>
    <script src="assets/js/kantin.js" defer></script>
  </body>
</html>
