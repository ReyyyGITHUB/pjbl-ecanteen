<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_login('payment-success');

$basePath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
$kode = isset($_GET['kode']) ? preg_replace('/[^A-Za-z0-9_-]/', '', (string)$_GET['kode']) : '';
$detailHref = $kode !== '' ? $basePath . '/detail-transaction/' . rawurlencode($kode) : $basePath . '/detail-transaction';
$homeHref = $basePath . '/index.html';
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
    <title>Pesanan Diterima - E-Canteen</title>
  </head>
  <body class="payment-qris-body">
    <main class="payment-qris-page">
      <section class="payment-qris-success" data-payment-success aria-labelledby="payment-success-title">
        <div class="payment-success-icon" aria-hidden="true">
          <span></span>
        </div>
        <div class="payment-success-copy">
          <h1 id="payment-success-title">Pesanan Diterima!</h1>
          <p>Siap-siap ya, pesananmu akan segera diproses Ibu Kantin.</p>
        </div>
        <div class="payment-success-meta" data-success-meta <?= $kode === '' ? 'hidden' : '' ?>>
          <span>Kode Pesanan</span>
          <strong data-success-code><?= htmlspecialchars($kode, ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div class="payment-success-drawer" data-success-drawer aria-hidden="true" inert>
          <div class="payment-success-status">
            <span>Order berhasil dibuat</span>
            <h2>Detail pesanan sudah diteruskan.</h2>
            <p>Cek WhatsApp kamu untuk konfirmasi. Tunjukkan kode pesanan saat mengambil makanan di kantin.</p>
          </div>
          <div class="payment-success-wa" data-success-wa hidden>
            <strong>Notifikasi otomatis belum terkirim.</strong>
            <p>Pesanan sudah tersimpan. Gunakan WhatsApp manual agar penjual tetap menerima detail pesanan.</p>
            <a href="#" target="_blank" rel="noopener" data-manual-whatsapp>Kirim WhatsApp Manual</a>
          </div>
          <div class="payment-success-actions">
            <a class="payment-success-secondary" href="<?= htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8') ?>" data-detail-transaction>Lihat detail transaksi</a>
            <a class="payment-success-primary" href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>" data-home-link>Kembali ke beranda</a>
          </div>
        </div>
      </section>
    </main>

    <script>
      (() => {
        const PAYMENT_KEY = "ecanteenPaymentQris";
        const successMeta = document.querySelector("[data-success-meta]");
        const successCode = document.querySelector("[data-success-code]");
        const successWa = document.querySelector("[data-success-wa]");
        const manualWhatsapp = document.querySelector("[data-manual-whatsapp]");
        const detailLink = document.querySelector("[data-detail-transaction]");
        const homeLink = document.querySelector("[data-home-link]");
        const successSection = document.querySelector("[data-payment-success]");
        const successDrawer = document.querySelector("[data-success-drawer]");

        try {
          const payment = JSON.parse(sessionStorage.getItem(PAYMENT_KEY) || "{}");
          const orderCode = payment.order_code || (successCode ? successCode.textContent.trim() : "");

          if (successCode && orderCode) {
            successCode.textContent = orderCode;
            if (successMeta) successMeta.hidden = false;
          }

          if (detailLink && orderCode) {
            detailLink.href = `${window.location.pathname.replace(/\/payment-success\/?$/, "")}/detail-transaction/${encodeURIComponent(orderCode)}`;
          }

          if (homeLink) {
            homeLink.href = `${window.location.pathname.replace(/\/payment-success\/?$/, "")}/index.html`;
          }

          if (successWa && manualWhatsapp && payment.wa_status === "failed" && payment.manual_whatsapp_url) {
            successWa.hidden = false;
            manualWhatsapp.href = payment.manual_whatsapp_url;
          }
        } catch (error) {
          // The route still works without sessionStorage metadata.
        }

        window.setTimeout(() => {
          if (successSection) {
            successSection.classList.add("is-expanded");
          }

          if (successDrawer) {
            successDrawer.removeAttribute("inert");
            successDrawer.setAttribute("aria-hidden", "false");
          }
        }, 800);
      })();
    </script>
  </body>
</html>
