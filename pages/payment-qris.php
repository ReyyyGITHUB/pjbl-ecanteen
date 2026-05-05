<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_login('payment-qris');
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Inter:wght@600;700&family=Nunito+Sans:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/styles.css" />
    <link rel="stylesheet" href="assets/css/payment-qris.css" />
    <title>Pembayaran QRIS - E-Canteen</title>
  </head>
  <body class="payment-qris-body">
    <main class="payment-qris-page" data-payment-root>
      <section class="payment-qris-card" aria-labelledby="payment-qris-title" data-payment-screen>
        <div class="payment-qris-header">
          <p class="payment-qris-kicker">Pembayaran QRIS</p>
          <h1 id="payment-qris-title">Scan QR</h1>
          <p>Siap-siap ya, pesananmu akan segera diproses Ibu Kantin.</p>
        </div>

        <div class="payment-qris-code" aria-label="Kode QRIS untuk pembayaran">
          <img src="assets/img/checkout/qris-static.png" alt="QRIS Kantin Mak'e" width="250" height="352" />
        </div>

        <form class="payment-qris-form" data-payment-form novalidate>
          <div class="payment-qris-upload-group">
            <label class="payment-qris-label" for="payment-proof">Masukkan bukti pembayaran di sini</label>
            <label class="payment-qris-dropzone" for="payment-proof" data-proof-dropzone tabindex="0" role="button" aria-describedby="payment-proof-error">
              <input id="payment-proof" type="file" accept="image/*" data-proof-input />
              <span class="payment-qris-upload-button">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 3v11m0 0 4-4m-4 4-4-4" />
                  <path d="M5 15v3a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3v-3" />
                </svg>
                <span data-proof-label>Click or drop image</span>
              </span>
            </label>
            <p class="payment-qris-error" id="payment-proof-error" data-proof-error aria-live="polite" hidden>Pilih gambar bukti pembayaran terlebih dahulu.</p>
          </div>

          <button class="payment-qris-submit" type="submit">Sudah Membayar</button>
        </form>
      </section>

      <section class="payment-qris-success" aria-labelledby="payment-success-title" data-success-screen hidden>
        <div class="payment-success-icon" aria-hidden="true">
          <span></span>
        </div>
        <div class="payment-success-copy">
          <h1 id="payment-success-title">Pesanan Diterima!</h1>
          <p>Siap-siap ya, pesananmu akan segera diproses Ibu Kantin.</p>
        </div>
        <div class="payment-success-actions">
          <button class="payment-success-secondary" type="button" data-download-proof>Simpan Bukti</button>
          <a class="payment-success-primary" href="kantin-1">Mau Jajan Lagi?</a>
        </div>
      </section>
    </main>

    <script src="assets/js/payment-qris.js" defer></script>
  </body>
</html>
