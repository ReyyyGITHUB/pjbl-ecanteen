<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';

$currentUser = current_user();
$isSeller = (($currentUser['role'] ?? '') === 'seller');
$dashboardHref = ($currentUser && $isSeller) ? seller_dashboard_route($currentUser) : '';
$accountName = htmlspecialchars((string)($currentUser['username'] ?? ''), ENT_QUOTES, 'UTF-8');

function landing_default_testimonials(): array {
  return [
    [
      'nama' => 'Bu Rina',
      'peran_label' => 'Guru SMKN 8 Semarang',
      'isi_testimoni' => 'Istirahat terasa lebih efisien. Saya bisa pesan makanan lebih cepat tanpa antre, dan waktunya bisa dipakai buat istirahat beneran.',
      'avatar_path' => 'assets/img/figma/testi-avatar-1.png',
      'rating' => 5,
    ],
    [
      'nama' => 'Naila Putri',
      'peran_label' => 'Siswi PPLG SMKN 8 Semarang',
      'isi_testimoni' => 'Pesan dulu dari kelas bikin jam istirahat lebih santai. Tinggal ambil, terus bisa langsung makan tanpa buru-buru.',
      'avatar_path' => 'assets/img/figma/testi-avatar-2.png',
      'rating' => 5,
    ],
    [
      'nama' => 'Bu Suharni',
      'peran_label' => 'Penjual Kantin Mak\'e',
      'isi_testimoni' => 'E-Canteen bantu saya ngatur antrean lebih rapi. Pesanan yang masuk juga lebih jelas, jadi lebih cepat diproses.',
      'avatar_path' => 'assets/img/figma/testi-avatar-3.png',
      'rating' => 5,
    ],
  ];
}

$testimonials = [];
if (table_exists('testimoni')) {
  $conn = db();
  $testimonialQuery = $conn->query(
    "SELECT nama, peran_label, isi_testimoni, avatar_path, rating
     FROM testimoni
     WHERE is_active = 1
     ORDER BY urutan ASC, id_testimoni ASC"
  );

  while ($testimonialQuery && ($row = $testimonialQuery->fetch_assoc())) {
    $testimonials[] = [
      'nama' => (string)$row['nama'],
      'peran_label' => (string)$row['peran_label'],
      'isi_testimoni' => (string)$row['isi_testimoni'],
      'avatar_path' => (string)$row['avatar_path'],
      'rating' => max(1, min(5, (int)$row['rating'])),
    ];
  }
}

if (!$testimonials) {
  $testimonials = landing_default_testimonials();
}
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@700&family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/styles.css" />
    <link rel="stylesheet" href="assets/css/navbar.css" />
    <link rel="stylesheet" href="assets/css/hero.css" />
    <link rel="stylesheet" href="assets/css/about.css" />
    <link rel="stylesheet" href="assets/css/howto.css" />
    <link rel="stylesheet" href="assets/css/testimonials.css" />
    <link rel="stylesheet" href="assets/css/footer.css" />
    <link rel="stylesheet" href="assets/css/page-loader.css" />
    <script src="assets/js/page-loader.js" defer></script>
    <title>E-Canteen</title>
  </head>
  <body>
    <header class="navbar">
      <div class="navbar-inner">
        <a class="brand" href="#beranda" aria-label="E-Canteen">
          <img class="brand-mark" src="assets/img/figma/logo-mark.png" alt="" />
          <span class="brand-name">E-Canteen</span>
        </a>

        <div class="nav-panel" id="nav-panel" aria-hidden="true">
          <nav class="nav-links" aria-label="Primary" id="nav-links">
            <a class="nav-link" href="#beranda">Beranda</a>
            <a class="nav-link" href="#tentang">Tentang</a>
            <a class="nav-link" href="#cara-pakai">Cara Pakai</a>
          </nav>

          <div class="nav-account-wrap">
            <?php if ($currentUser): ?>
              <button
                class="nav-account"
                type="button"
                aria-haspopup="menu"
                aria-expanded="false"
                aria-controls="nav-account-menu"
                data-nav-account-toggle
              >
                <svg class="nav-account-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M12 12.25a4.25 4.25 0 1 0 0-8.5 4.25 4.25 0 0 0 0 8.5Z" stroke="currentColor" stroke-width="1.8" />
                  <path d="M4.75 20.25a7.25 7.25 0 0 1 14.5 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
                <span><?= $accountName ?></span>
                <svg class="nav-account-caret" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>

              <div class="nav-account-menu" id="nav-account-menu" role="menu" hidden>
                <a class="nav-account-menu-item" role="menuitem" href="kantin">Kantin</a>
                <?php if ($isSeller): ?>
                  <a class="nav-account-menu-item" role="menuitem" href="<?= htmlspecialchars($dashboardHref, ENT_QUOTES, 'UTF-8') ?>">Dashboard</a>
                <?php endif; ?>
                <a class="nav-account-menu-item" role="menuitem" href="logout">Logout</a>
              </div>
            <?php else: ?>
              <a class="nav-account" href="register">
                <svg class="nav-account-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M12 12.25a4.25 4.25 0 1 0 0-8.5 4.25 4.25 0 0 0 0 8.5Z" stroke="currentColor" stroke-width="1.8" />
                  <path d="M4.75 20.25a7.25 7.25 0 0 1 14.5 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
                <span>Daftar</span>
              </a>
            <?php endif; ?>
          </div>
        </div>

        <button class="hamburger" type="button" aria-label="Menu" aria-controls="nav-panel" aria-expanded="false">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
        </button>
      </div>
      <div class="nav-backdrop" id="nav-backdrop" aria-hidden="true"></div>
    </header>

    <main>
      <section class="hero" id="beranda">
        <div class="hero-top">
          <div class="hero-title">
            <div class="hero-badge">
              ⚡ Pesan Makanan Kantin Lebih Cepat Tanpa Antre ⚡
            </div>
            <h1 class="hero-heading">
              Pesan lebih awal, nikmati makan
              <span class="hero-accent">tanpa antri.</span>
            </h1>
          </div>

          <div class="hero-actions">
            <form class="hero-search" aria-label="Cari Makanan Anda" action="kantin" method="get" data-hero-search-form>
              <svg class="hero-search-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6.75 10.5a3.75 3.75 0 1 0 7.5 0 3.75 3.75 0 0 0-7.5 0Z" stroke="currentColor" stroke-width="1.7" />
                <path d="M2.75 10.5c0 4.28 3.47 7.75 7.75 7.75s7.75-3.47 7.75-7.75S14.78 2.75 10.5 2.75 2.75 6.22 2.75 10.5Z" stroke="currentColor" stroke-width="1.7" />
                <path d="M15.8 15.8 21 21" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
              </svg>
              <input
                class="hero-search-input"
                type="text"
                placeholder="Cari Makanan Anda"
                name="q"
                autocomplete="off"
                data-hero-search-input
                aria-autocomplete="list"
                aria-controls="hero-search-results"
                aria-expanded="false"
              />
              <div
                class="hero-search-results"
                id="hero-search-results"
                data-hero-search-results
                role="listbox"
                aria-label="Rekomendasi menu"
                hidden
              ></div>
            </form>

            <a class="hero-cta" href="kantin">
              <img class="hero-cta-icon" src="assets/img/figma/icon-cart.svg" alt="" />
              <span class="hero-cta-text">Pesan Sekarang</span>
            </a>
          </div>
        </div>

        <div class="hero-image">
          <img src="assets/img/figma/hero-image.png" alt="" width="934" height="480" />
        </div>
      </section>

      <section class="about" id="tentang">
        <div class="about-inner">
          <div class="about-image">
            <img src="assets/img/figma/about-ellipse.png" alt="" loading="lazy" width="344" height="344" />
          </div>

          <div class="about-text">
            <h2 class="about-title">
              Apa itu <span class="about-accent">E - Canteen?</span>
            </h2>
            <p class="about-desc">
              E-Canteen adalah sistem kantin digital yang memudahkan warga sekolah memesan makanan tanpa antre dan membantu kantin mengelola menu serta transaksi secara efisien.
            </p>
          </div>
        </div>
      </section>

      <section class="howto" id="cara-pakai">
        <div class="howto-inner">
          <div class="howto-head">
            <div class="howto-kicker">
              <span class="howto-bar" aria-hidden="true"></span>
              <span class="howto-label">Cara Pesan</span>
            </div>
            <h2 class="howto-title">
              Pesan <span class="howto-accent">Makanan Lebih Cepat</span> dalam 3 Langkah!
            </h2>
          </div>

          <div class="howto-steps">
            <img class="howto-step" src="assets/img/figma/howto-1.png" alt="Langkah 1" loading="lazy" width="374" height="573" />
            <img class="howto-step" src="assets/img/figma/howto-2.png" alt="Langkah 2" loading="lazy" width="374" height="573" />
            <img class="howto-step" src="assets/img/figma/howto-3.png" alt="Langkah 3" loading="lazy" width="374" height="573" />
          </div>
        </div>
      </section>

      <?php if ($testimonials): ?>
      <section class="testimonials" id="testimoni">
        <div class="testimonials-inner">
          <div class="testimonials-head">
            <h2 class="testimonials-title">
              Apa <span class="testimonials-accent">Kata Mereka?</span>
            </h2>
            <p class="testimonials-subtitle">
              Dengarkan pengalaman para pengguna yang sudah merasakan kemudahan bertransaksi di E-Canteen SMKN 8 Semarang.
            </p>
            <div class="testimonials-pill">Testimoni Pengguna E-Canteen</div>
          </div>

          <div class="testimonials-carousel" aria-label="Carousel testimoni">
            <div class="testimonials-viewport">
              <div class="testimonials-track">
                <?php foreach ($testimonials as $testimonial): ?>
                  <article class="testi-card">
                    <div class="testi-top">
                      <img class="testi-quote" src="assets/img/figma/testi-quote.svg" alt="" />
                      <div class="testi-stars" aria-hidden="true">
                        <?php for ($i = 0; $i < (int)$testimonial['rating']; $i++): ?>
                          <img src="assets/img/figma/testi-star.svg" alt="" />
                        <?php endfor; ?>
                      </div>
                    </div>
                    <p class="testi-text"><?= htmlspecialchars((string)$testimonial['isi_testimoni']) ?></p>
                    <div class="testi-footer">
                      <img class="testi-avatar" src="<?= htmlspecialchars((string)$testimonial['avatar_path']) ?>" alt="<?= htmlspecialchars((string)$testimonial['nama']) ?>" loading="lazy" width="48" height="48" />
                      <div class="testi-user">
                        <p class="testi-name"><?= htmlspecialchars((string)$testimonial['nama']) ?></p>
                        <p class="testi-role"><?= htmlspecialchars((string)$testimonial['peran_label']) ?></p>
                      </div>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="testimonials-controls" aria-label="Kontrol carousel">
              <button class="testimonials-arrow" type="button" data-dir="-1" aria-label="Sebelumnya">
                <img src="assets/img/figma/testi-arrow-left.svg" alt="" />
              </button>
              <div class="testimonials-dots" aria-label="Posisi carousel">
                <?php foreach ($testimonials as $index => $_testimonial): ?>
                  <button class="testimonials-dot" type="button" aria-label="Slide <?= $index + 1 ?>"></button>
                <?php endforeach; ?>
              </div>
              <button class="testimonials-arrow" type="button" data-dir="1" aria-label="Berikutnya">
                <img src="assets/img/figma/testi-arrow-right.svg" alt="" />
              </button>
            </div>
          </div>
        </div>
      </section>
      <?php endif; ?>

      <footer class="footer" id="footer">
        <div class="footer-inner">
          <div class="footer-left">
            <div class="footer-cta">
              <h2 class="footer-title">Mau Pesan Makanan Tanpa Antri?</h2>
              <a class="footer-button" href="kantin" aria-label="Mulai pesan">
                <img class="footer-button-icon" src="assets/img/figma/footer-cart.svg" alt="" />
                <span class="footer-button-text">Klik Di Sini!</span>
              </a>
            </div>
            <p class="footer-copy">
              Copyright © 2025 Kelompok 1 XPPLG2 SMKN 8 Semarang. All Rights Reserved.
            </p>
          </div>

          <div class="footer-right">
            <div class="footer-col">
              <p class="footer-heading">Link to</p>
              <a href="#beranda">Beranda</a>
              <a href="#tentang">Tentang</a>
              <a href="#cara-pakai">Cara Pakai</a>
              <a href="#testimoni">Testimoni</a>
              <a href="kantin">Pilih Kantin</a>
              <a href="kantin-1">Halaman Kantin</a>
            </div>

            <div class="footer-col">
              <p class="footer-heading">Informasi Kontak</p>
              <span>Email sekolah tersedia saat demo langsung.</span>
              <span>Instagram kantin akan ditambahkan di fase berikutnya.</span>
              <span>WhatsApp penjual dipakai di alur pemesanan internal.</span>
              <span>Nomor telepon ditampilkan saat operasional aktif.</span>
            </div>
          </div>
        </div>
      </footer>
    </main>

    <script src="assets/js/navbar.js"></script>
    <script src="assets/js/hero-search.js"></script>
    <script src="assets/js/testimonials.js"></script>
  </body>
</html>
