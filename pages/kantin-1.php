<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

require_login('kantin-1');
$user = current_user();

$kantinId = 1;

function format_menu_name(string $value): string {
  $value = str_replace('_', ' ', trim($value));
  $value = preg_replace('/\s+/', ' ', $value) ?? $value;
  return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
}

function resolve_menu_image(string $gambar, string $menuName): string {
  $gambar = trim($gambar);
  if ($gambar !== '' && $gambar !== 'gambar.jpg') {
    return $gambar;
  }

  $normalized = strtolower(str_replace([' ', '-', '/'], '_', trim($menuName)));
  $fallbackMap = [
    'nasi_geprek' => 'assets/img/kantin-1/menu-ayam.png',
    'ayam_geprek_+_nasi_putih' => 'assets/img/kantin-1/menu-ayam.png',
    'cireng' => 'assets/img/kantin-1/menu-cireng.png',
    'es_teh' => 'assets/img/kantin-1/menu-esteh.png',
    'es_teh_manis' => 'assets/img/kantin-1/menu-esteh.png',
    'gorengan' => 'assets/img/kantin-1/menu-mendoan.png',
    'mendoan' => 'assets/img/kantin-1/menu-mendoan.png',
    'teajus' => 'assets/img/kantin-1/menu-goodday.png',
    'kopi_goodday' => 'assets/img/kantin-1/menu-goodday.png',
    'soto' => 'assets/img/kantin-1/menu-soto.png',
    'soto_ayam' => 'assets/img/kantin-1/menu-soto.png',
    'risolmayo_panas' => 'assets/img/kantin-1/menu-risol.png',
  ];

  return $fallbackMap[$normalized] ?? 'assets/img/kantin-1/menu-ayam.png';
}

function format_price_label(int $price): string {
  return 'Rp ' . number_format($price, 0, ',', '.');
}

$conn = db();

$stmtKantin = $conn->prepare(
  'SELECT id_kantin, nama_kantin
   FROM kantin
   WHERE id_kantin = ?
   LIMIT 1'
);
$stmtKantin->bind_param('i', $kantinId);
$stmtKantin->execute();
$kantinResult = $stmtKantin->get_result();
$kantin = $kantinResult->fetch_assoc() ?: null;
$stmtKantin->close();

if (!$kantin) {
  http_response_code(404);
  die('Kantin tidak ditemukan.');
}

$stmtMenu = $conn->prepare(
  'SELECT id_menu, id_kantin, nama_menu, kategori, harga, sisa_stock, gambar
   FROM menu
   WHERE id_kantin = ?
   ORDER BY kategori ASC, nama_menu ASC'
);
$stmtMenu->bind_param('i', $kantinId);
$stmtMenu->execute();
$menuResult = $stmtMenu->get_result();

$allMenus = [];
$foodMenus = [];
$drinkMenus = [];

while ($row = $menuResult->fetch_assoc()) {
  $menu = [
    'id' => (int)$row['id_menu'],
    'name' => format_menu_name((string)$row['nama_menu']),
    'price' => (int)$row['harga'],
    'price_label' => format_price_label((int)$row['harga']),
    'image' => resolve_menu_image((string)$row['gambar'], (string)$row['nama_menu']),
    'featured' => ((string)$row['kategori']) === 'makanan',
    'category' => (string)$row['kategori'],
    'stock' => (int)$row['sisa_stock'],
    'disabled' => (int)$row['sisa_stock'] < 1,
  ];

  $allMenus[] = $menu;

  if ($menu['category'] === 'minuman') {
    $drinkMenus[] = $menu;
  } else {
    $foodMenus[] = $menu;
  }
}
$stmtMenu->close();

$popularMenus = array_slice($allMenus, 0, 6);

$sections = [
  [
    'title' => 'Menu Paling Laris 🔥',
    'items' => $popularMenus,
  ],
  [
    'title' => 'Menu Makanan',
    'items' => $foodMenus,
  ],
  [
    'title' => 'Menu Minuman',
    'items' => $drinkMenus,
  ],
];

$stats = [
  ['icon' => 'icon-star.svg', 'value' => '4.6', 'label' => 'Rating Kantin', 'tone' => 'neutral'],
  ['icon' => 'icon-time-blue.svg', 'value' => '1 Menit', 'label' => 'Kecepatan Respon', 'tone' => 'blue'],
  ['icon' => 'icon-thumb-up-outline.svg', 'value' => '20+ Ratings', 'label' => 'Rasanya Enak!', 'tone' => 'neutral'],
  ['icon' => 'icon-smile.svg', 'value' => '14+ Ratings', 'label' => 'Penjual Ramah :)', 'tone' => 'orange'],
];
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Nunito+Sans:wght@400;600;700&family=Poppins:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/styles.css" />
    <link rel="stylesheet" href="assets/css/kantin-1.css" />
    <title>Kantin PJBL - E-Canteen</title>
  </head>
  <body class="kantin-detail-body">
    <header class="kantin-detail-nav">
      <a class="kantin-detail-brand" href="index.html" aria-label="E-Canteen">
        <img src="assets/img/figma/logo-mark.png" alt="" />
        <span>E-Canteen</span>
      </a>
      <nav class="kantin-detail-links" aria-label="Navigasi kantin">
        <a href="index.html">Beranda</a>
        <a href="kantin">Pilihan Kantin</a>
        <a href="#riwayat">Riwayat Pembelian</a>
      </nav>
      <button class="kantin-detail-menu-toggle" type="button" aria-label="Buka menu" aria-controls="kantin-mobile-nav" aria-expanded="false" data-mobile-menu-toggle>
        <span></span>
        <span></span>
        <span></span>
      </button>
      <a class="kantin-detail-user" href="logout" title="Logout">
        <?= htmlspecialchars((string)($user['username'] ?? 'user')) ?>
      </a>
    </header>
    <nav class="kantin-mobile-nav" id="kantin-mobile-nav" aria-label="Navigasi seluler" hidden>
      <a href="index.html">Beranda</a>
      <a href="kantin">Pilihan Kantin</a>
      <a href="#riwayat">Riwayat Pembelian</a>
      <a href="logout">Logout</a>
    </nav>

    <main class="kantin-detail-page">
      <div class="kantin-detail-layout">
        <section class="kantin-main-content">
          <div class="kantin-breadcrumbs" aria-label="Breadcrumb">
            <a href="index.html">Beranda</a>
            <span>/</span>
            <a href="kantin">Kantin</a>
            <span>/</span>
            <span>Mak'e</span>
          </div>

          <section class="kantin-profile-card">
            <img class="kantin-profile-thumb" src="assets/img/kantin-1/kantin-make-thumb.png" alt="Kantin Mak'e" />
            <div class="kantin-profile-copy">
              <div class="kantin-profile-meta">
                <img src="assets/img/kantin-1/icon-time.svg" alt="" />
                <p>Buka pas hari sekolah aja, 07:00 - 15:30 WIB</p>
              </div>
              <h1><?= htmlspecialchars(format_menu_name((string)$kantin['nama_kantin'])) ?> (Spot Paling Pojok)</h1>
              <div class="kantin-recommend-badge">
                <img src="assets/img/kantin-1/icon-thumb-up-fill.svg" alt="" />
                <span>Wajib Coba!</span>
              </div>
            </div>
          </section>

          <section class="kantin-stats-grid" aria-label="Informasi kantin">
            <?php foreach ($stats as $stat): ?>
              <article class="kantin-stat-card">
                <div class="kantin-stat-value">
                  <span class="kantin-stat-icon <?= $stat['tone'] !== 'neutral' ? 'is-' . htmlspecialchars($stat['tone']) : '' ?>">
                    <img src="assets/img/kantin-1/<?= htmlspecialchars($stat['icon']) ?>" alt="" />
                  </span>
                  <strong><?= htmlspecialchars($stat['value']) ?></strong>
                </div>
                <p><?= htmlspecialchars($stat['label']) ?></p>
              </article>
            <?php endforeach; ?>
          </section>

          <?php foreach ($sections as $sectionIndex => $section): ?>
            <section class="kantin-menu-section <?= $sectionIndex === 0 ? 'is-featured-section' : 'is-list-section' ?>">
              <h2><?= htmlspecialchars($section['title']) ?></h2>
              <div class="kantin-menu-grid">
                <?php if (!$section['items']): ?>
                  <p class="kantin-menu-empty">Belum ada menu untuk kategori ini.</p>
                <?php endif; ?>
                <?php foreach ($section['items'] as $itemIndex => $item): ?>
                  <article
                    class="kantin-menu-card <?= $sectionIndex === 0 ? 'is-featured-card' : 'is-list-card' ?> <?= $item['disabled'] ? 'is-disabled-card' : '' ?>"
                    data-menu-card
                    data-menu-id="<?= htmlspecialchars((string)$item['id']) ?>"
                    data-menu-name="<?= htmlspecialchars($item['name']) ?>"
                    data-menu-price="<?= htmlspecialchars((string)$item['price']) ?>"
                    data-menu-image="<?= htmlspecialchars($item['image']) ?>"
                    data-menu-stock="<?= htmlspecialchars((string)$item['stock']) ?>"
                  >
                    <img class="kantin-menu-image" src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" />
                    <div class="kantin-menu-copy">
                      <h3><?= htmlspecialchars($item['name']) ?></h3>
                      <div class="kantin-menu-bottom">
                        <span><?= htmlspecialchars($item['price_label']) ?></span>
                        <button
                          type="button"
                          class="kantin-add-button"
                          data-add-to-cart
                          data-menu-id="<?= htmlspecialchars((string)$item['id']) ?>"
                          data-menu-name="<?= htmlspecialchars($item['name']) ?>"
                          data-menu-price="<?= htmlspecialchars((string)$item['price']) ?>"
                          data-menu-image="<?= htmlspecialchars($item['image']) ?>"
                          data-menu-stock="<?= htmlspecialchars((string)$item['stock']) ?>"
                          <?= $item['disabled'] ? 'disabled aria-disabled="true"' : '' ?>
                          aria-label="Tambah <?= htmlspecialchars($item['name']) ?>"
                        >
                          <img src="assets/img/kantin-1/<?= $item['disabled'] ? 'icon-plus-gray.svg' : ($item['featured'] ? 'icon-plus-orange.svg' : 'icon-plus-gray.svg') ?>" alt="" />
                        </button>
                      </div>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endforeach; ?>
        </section>

        <aside class="kantin-order-panel" id="riwayat">
          <div class="kantin-order-box">
            <h2>Pesanan Anda</h2>
            <div class="kantin-order-content" data-order-content></div>

            <div class="kantin-order-footer">
              <div class="kantin-order-total">
                <span>Total Pesanan:</span>
                <strong data-order-total>Rp 0</strong>
              </div>
              <button type="button" class="kantin-order-submit" data-order-submit disabled>Pesan Sekarang!</button>
            </div>
          </div>
        </aside>
      </div>
    </main>

    <button type="button" class="kantin-mobile-cartbar" data-mobile-cartbar hidden aria-label="Lihat ringkasan pesanan">
      <span class="kantin-mobile-cartbar-copy">
        <span class="kantin-mobile-cartbar-items" data-mobile-cartbar-items>0 Items</span>
        <span class="kantin-mobile-cartbar-kantin"><?= htmlspecialchars(format_menu_name((string)$kantin['nama_kantin'])) ?> (Spot Paling Pojok)</span>
      </span>
      <span class="kantin-mobile-cartbar-totalwrap">
        <strong class="kantin-mobile-cartbar-total" data-mobile-cartbar-total>0</strong>
        <img src="assets/img/kantin/icon-cart.svg" alt="" />
      </span>
    </button>

    <footer class="kantin-detail-footer">
      <div class="kantin-detail-footer-main">
        <div class="kantin-detail-footer-cta">
          <h2>Mau Pesan Makanan Tanpa Antri?</h2>
          <a href="#riwayat">
            <img src="assets/img/kantin-1/icon-footer-cart.svg" alt="" />
            <span>Click Disni!</span>
          </a>
        </div>
        <p>Copyright © 2025 Kelompok 1 XPPLG2 SMKN 8 Semarang. All Rights Reserved.</p>
      </div>

      <div class="kantin-detail-footer-links">
        <div>
          <h3>Link to</h3>
          <a href="index.html">Beranda</a>
          <a href="index.html#tentang">Tentang</a>
          <a href="index.html#cara-pakai">Cara Pakai</a>
          <a href="index.html#testimoni">Testimoni</a>
          <a href="kantin">Pilih Kantin</a>
          <a href="kantin-1">Halaman Kantin</a>
        </div>
        <div>
          <h3>Informasi Kontak</h3>
          <a href="#">Email</a>
          <a href="#">Instagram</a>
          <a href="#">Whatsapp</a>
          <a href="#">Nomor Telepon</a>
        </div>
      </div>
    </footer>

    <script src="assets/js/kantin-1.js" defer></script>
  </body>
</html>
