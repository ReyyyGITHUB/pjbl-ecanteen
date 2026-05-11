<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

require_login('payment-success');

$basePath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
$kode = isset($_GET['kode']) ? preg_replace('/[^A-Za-z0-9_-]/', '', (string)$_GET['kode']) : '';
$detailHref = $kode !== '' ? $basePath . '/detail-transaction/' . rawurlencode($kode) : $basePath . '/detail-transaction';
$homeHref = $basePath . '/';
$ratingContext = null;

if ($kode !== '' && table_exists('rating_kantin')) {
  $conn = db();
  $stmt = $conn->prepare(
    'SELECT
       k.nama_kantin,
       MAX(rk.rating) AS rating
     FROM order_pesanan op
     INNER JOIN menu m ON m.id_menu = op.id_menu
     INNER JOIN kantin k ON k.id_kantin = m.id_kantin
     LEFT JOIN rating_kantin rk ON rk.kode_pesanan = op.kode_pesanan
     WHERE op.kode_pesanan = ? AND op.id_user = ?
     GROUP BY k.nama_kantin
     LIMIT 1'
  );
  $current = current_user();
  $userId = (int)($current['id_user'] ?? 0);
  $stmt->bind_param('si', $kode, $userId);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc() ?: null;
  $stmt->close();

  if ($row) {
    $ratingContext = [
      'kantin_name' => (string)$row['nama_kantin'],
      'rating' => (int)($row['rating'] ?? 0),
    ];
  }
}
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
          <p>Pembayaranmu sudah tersimpan. Penjual akan memproses pesanan ini sebelum siap diambil.</p>
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
                  <h3>Ringkasan pesanan sedang disiapkan</h3>
                  <p>Kalau detail item belum muncul di sini, buka halaman detail transaksi untuk melihat status terbaru.</p>
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

            <p class="payment-success-receipt-note">Simpan kode pesanan ini. Kamu bisa memantau status terbaru dan melihat rincian lengkap dari tombol detail transaksi di bawah.</p>
          </div>
          <?php if ($ratingContext): ?>
            <div
              class="payment-success-status payment-success-rating"
              data-rating-card
              data-rating-api="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/api/submit-kantin-rating.php"
              data-rating-order-code="<?= htmlspecialchars($kode, ENT_QUOTES, 'UTF-8') ?>"
              data-current-rating="<?= htmlspecialchars((string)$ratingContext['rating'], ENT_QUOTES, 'UTF-8') ?>"
            >
              <div class="payment-success-status-head">
                <div>
                  <span>Rating toko</span>
                  <h2>Nilai <?= htmlspecialchars((string)$ratingContext['kantin_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                </div>
              </div>
              <p class="payment-success-rating-copy">Kasih rating setelah pembayaran supaya nilai toko di halaman kantin ikut ter-update.</p>
              <div class="payment-success-rating-stars" role="radiogroup" aria-label="Pilih rating toko">
                <?php for ($star = 1; $star <= 5; $star++): ?>
                  <button
                    type="button"
                    class="payment-success-rating-star<?= $ratingContext['rating'] >= $star ? ' is-active' : '' ?>"
                    data-rating-star
                    data-rating-value="<?= $star ?>"
                    aria-label="Beri rating <?= $star ?> bintang"
                    aria-pressed="<?= $ratingContext['rating'] >= $star ? 'true' : 'false' ?>"
                  >★</button>
                <?php endfor; ?>
              </div>
              <div class="payment-success-rating-footer">
                <p class="payment-success-rating-status" data-rating-status aria-live="polite">
                  <?= $ratingContext['rating'] > 0 ? 'Rating saat ini: ' . $ratingContext['rating'] . ' bintang.' : 'Belum ada rating dari pesanan ini.' ?>
                </p>
                <button type="button" class="payment-success-rating-submit" data-rating-submit>
                  <?= $ratingContext['rating'] > 0 ? 'Update Rating' : 'Simpan Rating' ?>
                </button>
              </div>
            </div>
          <?php endif; ?>
          <div class="payment-success-wa" data-success-wa hidden>
            <strong>Notifikasi otomatis belum terkirim.</strong>
            <p>Pesanan sudah tersimpan. Gunakan WhatsApp manual agar penjual tetap menerima detail pesanan.</p>
            <button type="button" data-manual-whatsapp>Kirim WhatsApp Manual</button>
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
        const ratingCard = document.querySelector("[data-rating-card]");
        const ratingStatus = document.querySelector("[data-rating-status]");
        const ratingSubmit = document.querySelector("[data-rating-submit]");
        const ratingStars = Array.from(document.querySelectorAll("[data-rating-star]"));
        let selectedRating = Number(ratingCard?.dataset.currentRating || 0);

        const formatRupiah = (value) => `Rp ${new Intl.NumberFormat("id-ID").format(Number(value) || 0)}`;

        const syncRatingStars = () => {
          for (const star of ratingStars) {
            const value = Number(star.dataset.ratingValue || 0);
            const isActive = value <= selectedRating;
            star.classList.toggle("is-active", isActive);
            star.setAttribute("aria-pressed", String(isActive));
          }
        };

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
            title.textContent = "Ringkasan pesanan belum tersedia";

            const note = document.createElement("p");
            note.textContent = "Struk tetap valid. Buka halaman detail transaksi untuk melihat status dan rincian terbaru dari server.";

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
            homeLink.href = `${window.location.pathname.replace(/\/payment-success\/?$/, "")}/`;
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
            manualWhatsapp.dataset.manualWhatsappUrl = payment.manual_whatsapp_url;
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

        if (manualWhatsapp) {
          manualWhatsapp.addEventListener("click", () => {
            const manualUrl = manualWhatsapp.dataset.manualWhatsappUrl || "";
            if (!manualUrl) return;
            window.open(manualUrl, "_blank", "noopener");
          });
        }

        if (ratingCard && ratingSubmit && ratingStatus && ratingStars.length) {
          syncRatingStars();

          for (const star of ratingStars) {
            star.addEventListener("click", () => {
              selectedRating = Number(star.dataset.ratingValue || 0);
              syncRatingStars();
              ratingStatus.textContent = `Rating dipilih: ${selectedRating} bintang.`;
            });
          }

          ratingSubmit.addEventListener("click", async () => {
            if (selectedRating < 1 || selectedRating > 5) {
              ratingStatus.textContent = "Pilih rating bintang dulu sebelum menyimpan.";
              return;
            }

            ratingSubmit.disabled = true;
            ratingSubmit.textContent = "Menyimpan...";

            try {
              const response = await fetch(ratingCard.dataset.ratingApi || "", {
                method: "POST",
                headers: {
                  "Content-Type": "application/json",
                  Accept: "application/json",
                },
                body: JSON.stringify({
                  order_code: ratingCard.dataset.ratingOrderCode || "",
                  rating: selectedRating,
                }),
              });

              const result = await response.json().catch(() => ({}));
              if (!response.ok || !result.ok) {
                throw new Error(result.message || "Rating toko gagal disimpan.");
              }

              ratingCard.dataset.currentRating = String(selectedRating);
              ratingStatus.textContent = `Rating tersimpan: ${selectedRating} bintang. Rata-rata toko sekarang ${result.avg_rating}/5.`;
              ratingSubmit.textContent = "Update Rating";
            } catch (error) {
              ratingStatus.textContent = error.message || "Rating toko gagal disimpan.";
              ratingSubmit.textContent = "Simpan Rating";
            } finally {
              ratingSubmit.disabled = false;
            }
          });
        }
      })();
    </script>
  </body>
</html>
