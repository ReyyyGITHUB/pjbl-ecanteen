<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_login('checkout');
$user = current_user();
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Nunito+Sans:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/styles.css" />
    <link rel="stylesheet" href="assets/css/checkout.css" />
    <title>Checkout - E-Canteen</title>
  </head>
  <body class="checkout-body">
    <header class="checkout-nav">
      <a class="checkout-brand" href="index.html" aria-label="E-Canteen">
        <img src="assets/img/figma/logo-mark.png" alt="" />
        <span>E-Canteen</span>
      </a>
      <nav class="checkout-links" aria-label="Navigasi checkout">
        <a href="index.html">Beranda</a>
        <a href="kantin">Pilihan Kantin</a>
      </nav>
      <a class="checkout-user" href="logout" title="Logout">
        <?= htmlspecialchars((string)($user['username'] ?? 'user')) ?>
      </a>
    </header>

    <main class="checkout-page">
      <a class="checkout-back" href="kantin-1" aria-label="Kembali ke halaman kantin">
        <span aria-hidden="true">&#8249;</span>
        <strong>Konfirmasi Pesanan</strong>
      </a>

      <div class="checkout-layout">
        <section class="checkout-cart-card" aria-labelledby="checkout-cart-title">
          <h1 id="checkout-cart-title">Keranjang Jajanmu</h1>
          <div class="checkout-cart-list" data-checkout-cart-list></div>
          <div class="checkout-empty" data-checkout-empty hidden>
            <strong>Keranjangmu masih kosong.</strong>
            <p>Tambahkan menu dari halaman kantin sebelum checkout.</p>
            <a href="kantin-1">Kembali ke Kantin</a>
          </div>
          <div class="checkout-cart-total" data-checkout-cart-total-row>
            <span>Total Pembayaran</span>
            <strong data-checkout-total>Rp 0</strong>
          </div>
        </section>

        <section class="checkout-info-card" aria-labelledby="checkout-info-title">
          <h2 id="checkout-info-title">Informasi Pemesan</h2>

          <form class="checkout-form" data-checkout-form>
            <fieldset class="checkout-fieldset">
              <legend>Pilih Waktu Pengambilan</legend>
              <div class="checkout-pickup-options" role="radiogroup" aria-label="Pilih waktu pengambilan">
                <button type="button" class="checkout-pickup-option is-active" data-pickup-option data-pickup-value="Istirahat 1 (09:00 - 09:15)" aria-pressed="true">
                  Istirahat 1<br />
                  (09:00 - 09:15)
                </button>
                <button type="button" class="checkout-pickup-option" data-pickup-option data-pickup-value="Istirahat 2 (12:00 - 12:30)" aria-pressed="false">
                  Istirahat 2<br />
                  (12:00 - 12:30)
                </button>
              </div>
            </fieldset>

            <label class="checkout-note-field">
              <span>Pesan Buat Ibu Kantin (Opsional)</span>
              <textarea name="note" data-checkout-note placeholder="Cth: Sambalnya dipisah, jangan pakai bawang."></textarea>
            </label>

            <section class="checkout-payment" aria-labelledby="checkout-payment-title">
              <h3 id="checkout-payment-title">Pembayaran</h3>
              <label class="checkout-payment-option is-selected">
                <input type="radio" name="payment_method" value="tunai" checked />
                <span>Tunai - Bayar Langsung di Kantin</span>
              </label>
              <button type="button" class="checkout-cash-button" disabled>Bayar Tunai</button>
            </section>

            <button type="submit" class="checkout-submit" data-checkout-submit disabled>Pesan Sekarang, Rp 0</button>
          </form>
        </section>
      </div>
    </main>

    <script src="assets/js/checkout.js" defer></script>
  </body>
</html>
