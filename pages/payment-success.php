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
        <div class="payment-success-drawer" data-success-drawer aria-hidden="true" inert>
          <div class="payment-success-status payment-success-receipt">
            <div class="payment-success-status-head">
              <div>
                <span>Struk pembelian</span>
                <h2>Pesanan diterima</h2>
              </div>
              <strong data-success-status-pill>Menunggu konfirmasi</strong>
            </div>

            <div class="payment-success-receipt-code">
              <span>Kode pesanan</span>
              <strong data-success-code-inline><?= htmlspecialchars($kode, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>

            <div class="payment-success-receipt-divider" aria-hidden="true"></div>

            <div class="payment-success-item-list" data-success-items>
              <div class="payment-success-item is-placeholder">
                <div class="payment-success-item-main">
                  <h3>Detail pesanan menunggu</h3>
                  <p>Item akan muncul setelah pembayaran berhasil diproses.</p>
                </div>
                <strong>-</strong>
              </div>
            </div>

            <div class="payment-success-receipt-divider" aria-hidden="true"></div>

            <div class="payment-success-receipt-footer">
              <div>
                <span>Waktu</span>
                <strong data-success-time>-</strong>
              </div>
              <div>
                <span>Metode</span>
                <strong data-success-method>QRIS</strong>
              </div>
              <div>
                <span>Total</span>
                <strong data-success-total>Rp 0</strong>
              </div>
            </div>

            <p class="payment-success-receipt-note">Tunjukkan kode pesanan saat mengambil makanan di kantin. Detail pesanan lengkap bisa dibuka dari tombol di bawah.</p>
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
        const successWa = document.querySelector("[data-success-wa]");
        const manualWhatsapp = document.querySelector("[data-manual-whatsapp]");
        const detailLink = document.querySelector("[data-detail-transaction]");
        const homeLink = document.querySelector("[data-home-link]");
        const successSection = document.querySelector("[data-payment-success]");
        const successDrawer = document.querySelector("[data-success-drawer]");
        const successTime = document.querySelector("[data-success-time]");
        const successTotal = document.querySelector("[data-success-total]");
        const successMethod = document.querySelector("[data-success-method]");
        const successStatusPill = document.querySelector("[data-success-status-pill]");
        const successCodeInline = document.querySelector("[data-success-code-inline]");
        const successItems = document.querySelector("[data-success-items]");

        const formatRupiah = (value) => `Rp ${new Intl.NumberFormat("id-ID").format(Number(value) || 0)}`;

        const renderReceiptItems = (items) => {
          if (!successItems) return;

          const normalizedItems = Array.isArray(items) ? items : [];
          if (!normalizedItems.length) {
            successItems.replaceChildren();
            const placeholder = document.createElement("div");
            placeholder.className = "payment-success-item is-placeholder";

            const main = document.createElement("div");
            main.className = "payment-success-item-main";

            const title = document.createElement("h3");
            title.textContent = "Detail pesanan tidak tersedia";

            const note = document.createElement("p");
            note.textContent = "Struk tetap valid, tetapi daftar item belum tersimpan di sesi browser.";

            const total = document.createElement("strong");
            total.textContent = "-";

            main.append(title, note);
            placeholder.append(main, total);
            successItems.appendChild(placeholder);
            return;
          }

          successItems.replaceChildren(
            ...normalizedItems.map((item) => {
              const row = document.createElement("div");
              row.className = "payment-success-item";

              const main = document.createElement("div");
              main.className = "payment-success-item-main";

              const title = document.createElement("h3");
              title.textContent = String(item?.name || "Menu");

              const qty = Number(item?.qty || 0);
              const price = Number(item?.price || 0);
              const subtotal = qty * price;

              const meta = document.createElement("p");
              meta.textContent = `${qty} x ${formatRupiah(price)}`;

              const total = document.createElement("strong");
              total.textContent = formatRupiah(subtotal);

              main.append(title, meta);
              row.append(main, total);
              return row;
            })
          );
        };

        try {
          const payment = JSON.parse(sessionStorage.getItem(PAYMENT_KEY) || "{}");
          const orderCode = payment.order_code || (successCodeInline ? successCodeInline.textContent.trim() : "");

          if (detailLink && orderCode) {
            detailLink.href = `${window.location.pathname.replace(/\/payment-success\/?$/, "")}/detail-transaction/${encodeURIComponent(orderCode)}`;
          }

          if (homeLink) {
            homeLink.href = `${window.location.pathname.replace(/\/payment-success\/?$/, "")}/index.html`;
          }

          if (successCodeInline && orderCode) {
            successCodeInline.textContent = orderCode;
          }

          if (successTime && payment.confirmedAt) {
            const confirmed = new Date(payment.confirmedAt);
            if (!Number.isNaN(confirmed.getTime())) {
              successTime.textContent = new Intl.DateTimeFormat("id-ID", {
                day: "2-digit",
                month: "2-digit",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
              }).format(confirmed);
            }
          }

          if (successTotal && Number.isFinite(Number(payment.total))) {
            successTotal.textContent = formatRupiah(Number(payment.total));
          }

          if (successMethod) {
            successMethod.textContent = "QRIS";
          }

          if (successStatusPill) {
            successStatusPill.textContent = payment.wa_status === "failed" ? "Belum terkirim" : "Menunggu konfirmasi";
          }

          if (successWa && manualWhatsapp && payment.wa_status === "failed" && payment.manual_whatsapp_url) {
            successWa.hidden = false;
            manualWhatsapp.href = payment.manual_whatsapp_url;
          }

          renderReceiptItems(payment.items);
        } catch (error) {
          // The route still works without sessionStorage metadata.
          renderReceiptItems([]);
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
