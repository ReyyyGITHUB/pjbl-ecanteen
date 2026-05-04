<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_login('kantin');
$user = current_user();

$kantinCards = [
  ['name' => 'Kantin PJBL', 'image' => 'assets/img/kantin/kantin-make.png', 'active' => true, 'target' => 'kantin-1'],
  ['name' => 'Kantin Belum Terdaftar', 'image' => 'assets/img/icons/not-active.svg', 'active' => false],
  ['name' => 'Kantin Belum Terdaftar', 'image' => 'assets/img/icons/not-active.svg', 'active' => false],
  ['name' => 'Kantin Belum Terdaftar', 'image' => 'assets/img/icons/not-active.svg', 'active' => false],
];

$menus = [
  ['name' => 'Ayam Geprek + Nasi Putih', 'price' => 'Rp. 7.000', 'image' => 'menu-ayam.png'],
  ['name' => 'Cireng Goreng', 'price' => 'Rp. 3.000', 'image' => 'menu-2.png'],
  ['name' => 'Es Teh Manis', 'price' => 'Rp. 2.000', 'image' => 'hero-drink.png'],
  ['name' => 'Risoles Mayo Panas', 'price' => 'Rp. 2.500', 'image' => 'menu-4.png'],
  ['name' => 'Kopi Goodday', 'price' => 'Rp. 2.000', 'image' => 'hero-side.png'],
  ['name' => 'Mendoan / Gorengan', 'price' => 'Rp. 3.000', 'image' => 'hero-mendoan.png'],
  ['name' => 'Soto Ayam Seger', 'price' => 'Rp. 5.000', 'image' => 'hero-soto.png'],
];

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
    <title>Kantin - E-Canteen</title>
  </head>
  <body class="kantin-body">
    <header class="kantin-nav">
      <a class="kantin-brand" href="index.html" aria-label="E-Canteen">
        <img src="assets/img/figma/logo-mark.png" alt="" />
        <span>E-Canteen</span>
      </a>
      <nav class="kantin-links" aria-label="Navigasi kantin">
        <a href="index.html">Beranda</a>
        <a href="#pilihan-kantin">Pilihan Kantin</a>
        <a href="#riwayat">Riwayat Pembelian</a>
      </nav>
      <a class="kantin-user" href="logout" title="Logout">
        <?= htmlspecialchars((string)($user['username'] ?? 'user')) ?>
      </a>
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
          <h1 id="kantin-hero-title">Laper? Pesan disini aja!</h1>
          <p>Pilih kantin, cari menu favoritmu, lalu pesan tanpa perlu antre panjang di jam istirahat.</p>
        </div>
      </section>

      <section class="kantin-search-panel" aria-label="Cari makanan">
        <p>Pesan apa hari ini?</p>
        <form class="kantin-search" action="kantin" method="get">
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
          <h2>Cari Kantin Favoritmu? Cek Disini </h2>
        </div>
        <div class="kantin-card-grid">
          <?php foreach ($kantinCards as $index => $card): ?>
            <button
              class="kantin-choice <?= $card['active'] ? 'is-ready' : 'is-empty' ?> <?= $index === 0 ? 'is-selected' : '' ?>"
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
                <img src="assets/img/kantin/<?= htmlspecialchars($menu['image']) ?>" alt="<?= htmlspecialchars($menu['name']) ?>" />
              </div>
              <h3><?= htmlspecialchars($menu['name']) ?></h3>
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
              <a href="#" class="kantin-list-item">
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
        <a href="#pilihan-kantin"><img src="assets/img/kantin/icon-cart.svg" alt="" /> Klik Disini!</a>
      </div>
      <p>Copyright &copy; 2025 Kelompok 1 XPPLG2 SMKN 8 Semarang. All Rights Reserved.</p>
      <nav aria-label="Footer link">
        <a href="index.html">Beranda</a>
        <a href="#pilihan-kantin">Pilih Kantin</a>
        <a href="logout">Logout</a>
      </nav>
    </footer>
    <script src="assets/js/kantin.js" defer></script>
  </body>
</html>
