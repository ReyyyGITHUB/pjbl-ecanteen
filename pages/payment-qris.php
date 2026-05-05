<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_login('payment-qris');

$basePath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Inter:wght@600;700&family=Nunito+Sans:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/styles.css" />
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/payment-qris.css" />
    <title>Pembayaran QRIS - E-Canteen</title>
  </head>
  <body class="payment-qris-body">
    <main class="payment-qris-page" data-payment-root>
      <section class="payment-qris-flow" aria-labelledby="payment-qris-title" data-payment-screen>
        <div class="payment-qris-brandbar">
          <a class="payment-qris-cancel" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/checkout" aria-label="Batalkan pembayaran dan kembali ke checkout" data-cancel-payment>
            <span aria-hidden="true">&#8249;</span>
            <strong>Batalkan</strong>
          </a>
          <a class="payment-qris-brand" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/kantin-1" aria-label="Kembali ke E-Canteen">
            <img src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/img/figma/logo-mark.png" alt="" width="30" height="45" />
            <span>E-Canteen</span>
          </a>
        </div>

        <div class="payment-qris-card payment-qris-card-main">
          <div class="payment-qris-topbar">
            <div class="payment-qris-identity">
              <span class="payment-qris-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <path d="M5 5h5v5H5V5Zm9 0h5v5h-5V5ZM5 14h5v5H5v-5Zm9 0h2v2h-2v-2Zm4 0h1v2h-1v-2Zm-4 4h5v1h-5v-1Z" />
                </svg>
              </span>
              <div>
                <h1 id="payment-qris-title">Pembayaran</h1>
                <p>QRIS</p>
              </div>
            </div>

            <div class="payment-qris-amount">
              <strong data-payment-total>Rp 0</strong>
              <span>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 7v5l3 2" />
                  <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span data-payment-countdown>59:42</span>
              </span>
            </div>
          </div>

          <div class="payment-qris-visual">
            <div class="payment-qris-code" aria-label="Kode QRIS untuk pembayaran" data-qris-code data-static-qris-src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/img/checkout/qris-static.png">
              <div class="payment-qris-generated" data-qris-generated></div>
              <img class="payment-qris-static-fallback" src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/img/checkout/qris-static.png" alt="QRIS Kantin Mak'e" width="250" height="352" data-qris-fallback />
            </div>
            <p class="payment-qris-status" data-qris-status aria-live="polite">Menyiapkan QRIS dinamis sesuai nominal pesanan...</p>
            <p>Scan dengan E-Wallet atau M-Banking</p>
          </div>
        </div>

        <section class="payment-qris-card payment-qris-guide" aria-labelledby="payment-guide-title">
          <div class="payment-qris-guide-head">
            <span class="payment-guide-icon" aria-hidden="true">i</span>
            <div>
              <h2 id="payment-guide-title">Cara Pembayaran</h2>
              <p>Scan QR code dengan aplikasi Anda</p>
            </div>
          </div>

          <details class="payment-guide-item" open>
            <summary>
              <span class="payment-guide-item-icon is-wallet" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <path d="M7 6h10a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" />
                  <path d="M16 12h3" />
                </svg>
              </span>
              <strong>E-Wallet</strong>
            </summary>
            <ol>
              <li>Buka aplikasi DANA, GoPay, OVO, ShopeePay, atau e-wallet lain.</li>
              <li>Pilih menu Scan QRIS, arahkan kamera ke QR di atas, lalu cek nominal pembayaran.</li>
              <li>Selesaikan pembayaran dan simpan screenshot bukti transaksi.</li>
            </ol>
          </details>

          <details class="payment-guide-item">
            <summary>
              <span class="payment-guide-item-icon is-bank" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <path d="M4 10h16L12 5 4 10Z" />
                  <path d="M6 10v7m4-7v7m4-7v7m4-7v7M5 19h14" />
                </svg>
              </span>
              <strong>Mobile Banking</strong>
            </summary>
            <ol>
              <li>Buka aplikasi mobile banking yang mendukung QRIS.</li>
              <li>Pilih Bayar atau Scan QRIS, lalu scan kode pada halaman ini.</li>
              <li>Pastikan nama merchant dan nominal benar sebelum konfirmasi.</li>
            </ol>
          </details>
        </section>

        <div class="payment-qris-card payment-qris-proof-card">
          <div class="payment-qris-proof-head">
            <h2>Upload Bukti Pembayaran</h2>
            <p>Gunakan screenshot transaksi agar Ibu Kantin bisa memverifikasi pembayaranmu.</p>
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
        </div>
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
          <a class="payment-success-primary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/kantin-1">Mau Jajan Lagi?</a>
        </div>
      </section>
    </main>

    <script src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/js/vendor/jsQR.min.js" defer></script>
    <script src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/js/vendor/qrcode.min.js" defer></script>
    <script src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/js/payment-qris.js" defer></script>
  </body>
</html>
