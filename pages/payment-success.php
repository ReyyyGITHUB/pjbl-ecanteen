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
$shouldPromptRating = false;

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
    $shouldPromptRating = $ratingContext['rating'] <= 0;
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
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/page-loader.css" />
    <script src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/js/page-loader.js" defer></script>
    <title>Pesanan Diterima - E-Canteen</title>
  </head>
  <body class="payment-qris-body">
    <main class="payment-qris-page">
      <section class="payment-qris-success is-loading" data-payment-success data-ui-state="loading" aria-labelledby="payment-success-title">
        <div class="payment-success-skeleton" data-success-skeleton aria-hidden="true">
          <div class="payment-success-skeleton-hero">
            <span class="payment-success-skeleton-icon payment-success-skeleton-shimmer"></span>
            <div class="payment-success-skeleton-copy">
              <span class="payment-success-skeleton-line is-title payment-success-skeleton-shimmer"></span>
              <span class="payment-success-skeleton-line is-copy payment-success-skeleton-shimmer"></span>
            </div>
          </div>

          <div class="payment-success-skeleton-card">
            <div class="payment-success-skeleton-head">
              <div class="payment-success-skeleton-head-copy">
                <span class="payment-success-skeleton-line is-label payment-success-skeleton-shimmer"></span>
                <span class="payment-success-skeleton-line is-heading payment-success-skeleton-shimmer"></span>
              </div>
              <span class="payment-success-skeleton-pill payment-success-skeleton-shimmer"></span>
            </div>

            <div class="payment-success-skeleton-code">
              <span class="payment-success-skeleton-line is-code-label payment-success-skeleton-shimmer"></span>
              <span class="payment-success-skeleton-line is-code-value payment-success-skeleton-shimmer"></span>
            </div>

            <div class="payment-success-receipt-divider" aria-hidden="true"></div>

            <div class="payment-success-skeleton-items">
              <div class="payment-success-skeleton-item">
                <div class="payment-success-skeleton-item-main">
                  <span class="payment-success-skeleton-line is-item-title payment-success-skeleton-shimmer"></span>
                  <span class="payment-success-skeleton-line is-item-meta payment-success-skeleton-shimmer"></span>
                </div>
                <span class="payment-success-skeleton-line is-item-total payment-success-skeleton-shimmer"></span>
              </div>
              <div class="payment-success-skeleton-item">
                <div class="payment-success-skeleton-item-main">
                  <span class="payment-success-skeleton-line is-item-title payment-success-skeleton-shimmer"></span>
                  <span class="payment-success-skeleton-line is-item-meta payment-success-skeleton-shimmer"></span>
                </div>
                <span class="payment-success-skeleton-line is-item-total payment-success-skeleton-shimmer"></span>
              </div>
              <div class="payment-success-skeleton-item">
                <div class="payment-success-skeleton-item-main">
                  <span class="payment-success-skeleton-line is-item-title payment-success-skeleton-shimmer"></span>
                  <span class="payment-success-skeleton-line is-item-meta payment-success-skeleton-shimmer"></span>
                </div>
                <span class="payment-success-skeleton-line is-item-total payment-success-skeleton-shimmer"></span>
              </div>
            </div>

            <div class="payment-success-receipt-divider" aria-hidden="true"></div>

            <div class="payment-success-skeleton-footer">
              <div class="payment-success-skeleton-footer-block">
                <span class="payment-success-skeleton-line is-footer-label payment-success-skeleton-shimmer"></span>
                <span class="payment-success-skeleton-line is-footer-value payment-success-skeleton-shimmer"></span>
              </div>
              <div class="payment-success-skeleton-footer-block">
                <span class="payment-success-skeleton-line is-footer-label payment-success-skeleton-shimmer"></span>
                <span class="payment-success-skeleton-line is-footer-value payment-success-skeleton-shimmer"></span>
              </div>
              <div class="payment-success-skeleton-footer-block">
                <span class="payment-success-skeleton-line is-footer-label payment-success-skeleton-shimmer"></span>
                <span class="payment-success-skeleton-line is-footer-value payment-success-skeleton-shimmer"></span>
              </div>
            </div>

            <span class="payment-success-skeleton-line is-note payment-success-skeleton-shimmer"></span>
          </div>

          <div class="payment-success-skeleton-actions">
            <span class="payment-success-skeleton-action payment-success-skeleton-shimmer"></span>
            <span class="payment-success-skeleton-action payment-success-skeleton-shimmer"></span>
          </div>
        </div>

        <div class="payment-success-live" data-success-live aria-hidden="true">
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
            <div class="payment-success-wa" data-success-wa hidden>
              <strong>Pesanan belum masuk ke penjual</strong>
              <p>Pesananmu sudah tersimpan di sistem, tapi notifikasi otomatis gagal terkirim. Agar pesanan diproses, kamu wajib klik WhatsApp manual di bawah ini dan kirim pesannya ke penjual.</p>
              <button type="button" data-manual-whatsapp>Kirim Pesanan via WhatsApp</button>
              <span class="payment-success-wa-note">Jangan tinggalkan halaman ini sebelum pesan WhatsApp berhasil terkirim.</span>
            </div>
            <div class="payment-success-actions" data-success-actions>
              <a class="payment-success-secondary" href="<?= htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8') ?>" data-detail-transaction>Lihat detail transaksi</a>
              <a class="payment-success-primary" href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>" data-home-link>Kembali ke beranda</a>
            </div>
          </div>
        </div>
      </section>
    </main>
    <?php if ($shouldPromptRating && $ratingContext): ?>
      <div class="payment-success-rating-overlay" data-rating-overlay hidden>
        <div class="payment-success-rating-dialog" role="dialog" aria-modal="true" aria-labelledby="payment-success-rating-title">
          <div
            class="payment-success-status payment-success-rating"
            data-rating-card
            data-rating-api="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/api/submit-kantin-rating.php"
            data-rating-order-code="<?= htmlspecialchars($kode, ENT_QUOTES, 'UTF-8') ?>"
            data-current-rating="<?= htmlspecialchars((string)$ratingContext['rating'], ENT_QUOTES, 'UTF-8') ?>"
            tabindex="-1"
          >
            <div class="payment-success-status-head">
              <div>
                <span>Rating toko</span>
                <h2 id="payment-success-rating-title">Nilai <?= htmlspecialchars((string)$ratingContext['kantin_name'], ENT_QUOTES, 'UTF-8') ?></h2>
              </div>
            </div>
            <div class="payment-success-rating-stars" role="radiogroup" aria-label="Pilih rating toko">
              <?php for ($star = 1; $star <= 5; $star++): ?>
                <button
                  type="button"
                  class="payment-success-rating-star"
                  data-rating-star
                  data-rating-value="<?= $star ?>"
                  aria-label="Beri rating <?= $star ?> bintang"
                  aria-pressed="false"
                >★</button>
              <?php endfor; ?>
            </div>
            <div class="payment-success-rating-footer">
              <p class="payment-success-rating-status" data-rating-status aria-live="polite">Pilih rating bintang dulu sebelum lanjut.</p>
              <button type="button" class="payment-success-rating-submit" data-rating-submit disabled>Pilih bintang dulu</button>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <script>
      (() => {
        const PAYMENT_KEY = "ecanteenPaymentQris";
        const pageBody = document.body;
        const successSection = document.querySelector("[data-payment-success]");
        const successSkeleton = document.querySelector("[data-success-skeleton]");
        const successLive = document.querySelector("[data-success-live]");
        const successWa = document.querySelector("[data-success-wa]");
        const successActions = document.querySelector("[data-success-actions]");
        const manualWhatsapp = document.querySelector("[data-manual-whatsapp]");
        const detailLink = document.querySelector("[data-detail-transaction]");
        const homeLink = document.querySelector("[data-home-link]");
        const successDrawer = document.querySelector("[data-success-drawer]");
        const successTime = document.querySelector("[data-success-time]");
        const successTotal = document.querySelector("[data-success-total]");
        const successMethod = document.querySelector("[data-success-method]");
        const successStatusPill = document.querySelector("[data-success-status-pill]");
        const successCodeInline = document.querySelector("[data-success-code-inline]");
        const successItems = document.querySelector("[data-success-items]");
        const ratingOverlay = document.querySelector("[data-rating-overlay]");
        const ratingCard = document.querySelector("[data-rating-card]");
        const ratingStatus = document.querySelector("[data-rating-status]");
        const ratingSubmit = document.querySelector("[data-rating-submit]");
        const ratingStars = Array.from(document.querySelectorAll("[data-rating-star]"));
        const successBasePath = window.location.pathname.replace(/\/payment-success\/?$/, "");
        let selectedRating = Number(ratingCard?.dataset.currentRating || 0);
        let lastSavedRating = selectedRating;
        let previewRating = 0;

        const formatRupiah = (value) => `Rp ${new Intl.NumberFormat("id-ID").format(Number(value) || 0)}`;

        const setUiState = (state) => {
          if (!successSection) return;

          successSection.dataset.uiState = state;
          successSection.classList.remove("is-loading", "is-ready", "is-fallback", "is-expanded");
          successSection.classList.add(`is-${state}`);

          if (state === "loading") {
            successLive?.setAttribute("aria-hidden", "true");
            if (successDrawer) {
              successDrawer.setAttribute("aria-hidden", "true");
              successDrawer.setAttribute("inert", "");
            }
            return;
          }

          successLive?.setAttribute("aria-hidden", "false");
          if (successDrawer) {
            successDrawer.removeAttribute("inert");
            successDrawer.setAttribute("aria-hidden", "false");
          }

          window.requestAnimationFrame(() => {
            successSection.classList.add("is-expanded");
          });
        };

        const readStoredPayment = () => {
          const raw = sessionStorage.getItem(PAYMENT_KEY);
          if (!raw) return null;

          try {
            const parsed = JSON.parse(raw);
            return parsed && typeof parsed === "object" ? parsed : null;
          } catch (error) {
            return null;
          }
        };

        const hasHydrationData = (payment) =>
          !!payment &&
          (
            (typeof payment.order_code === "string" && payment.order_code.trim() !== "") ||
            Array.isArray(payment.items) ||
            Number.isFinite(Number(payment.total)) ||
            typeof payment.confirmedAt === "string" ||
            typeof payment.manual_whatsapp_url === "string"
          );

        const syncNavigationLinks = (orderCode = "") => {
          if (detailLink) {
            detailLink.href = orderCode
              ? `${successBasePath}/detail-transaction/${encodeURIComponent(orderCode)}`
              : `${successBasePath}/detail-transaction`;
          }

          if (homeLink) {
            homeLink.href = `${successBasePath}/`;
          }
        };

        const resetWaState = () => {
          if (successWa) {
            successWa.hidden = true;
            successWa.classList.remove("is-blocking");
          }

          if (manualWhatsapp) {
            manualWhatsapp.dataset.manualWhatsappUrl = "";
          }

          if (successActions) {
            successActions.hidden = false;
            successActions.removeAttribute("aria-hidden");
          }
        };

        const syncRatingStars = () => {
          for (const star of ratingStars) {
            const value = Number(star.dataset.ratingValue || 0);
            const isPreviewMode = previewRating > 0;
            const isActive = !isPreviewMode && value <= selectedRating;
            const isPreview = isPreviewMode && value < previewRating;
            const isPreviewCurrent = isPreviewMode && value === previewRating;
            star.classList.toggle("is-active", isActive);
            star.classList.toggle("is-preview", isPreview);
            star.classList.toggle("is-preview-current", isPreviewCurrent);
            star.setAttribute("aria-pressed", String(isActive || isPreviewCurrent));
          }
        };

        const buildRatingPayload = () =>
          JSON.stringify({
            order_code: ratingCard?.dataset.ratingOrderCode || "",
            rating: selectedRating,
          });

        const persistSavedRating = (avgRating = "") => {
          lastSavedRating = selectedRating;
          if (!ratingCard || !ratingStatus) return;
          ratingCard.dataset.currentRating = String(selectedRating);
          ratingStatus.textContent = avgRating !== ""
            ? `Rating tersimpan: ${selectedRating} bintang. Rata-rata toko sekarang ${avgRating}/5.`
            : `Rating tersimpan: ${selectedRating} bintang.`;
        };

        const syncRatingSubmitState = (isSaving = false) => {
          if (!ratingSubmit) return;
          const hasSelection = selectedRating >= 1 && selectedRating <= 5;
          const hasSavedRating = lastSavedRating >= 1 && lastSavedRating <= 5;
          ratingSubmit.disabled = isSaving || !hasSelection;
          ratingSubmit.textContent = isSaving
            ? "Menyimpan..."
            : !hasSelection
              ? "Pilih bintang dulu"
              : hasSavedRating
                ? "Update Rating"
                : "Simpan Rating";
        };

        const openRatingOverlay = () => {
          if (!ratingOverlay || !ratingCard) return;
          ratingOverlay.hidden = false;
          pageBody.classList.add("is-rating-overlay-open");
          window.requestAnimationFrame(() => {
            ratingCard.focus();
          });
        };

        const closeRatingOverlay = () => {
          if (!ratingOverlay) return;
          ratingOverlay.hidden = true;
          pageBody.classList.remove("is-rating-overlay-open");
        };

        const saveRating = async () => {
          if (!ratingCard || selectedRating < 1 || selectedRating > 5) return false;

          const apiUrl = ratingCard.dataset.ratingApi || "";
          if (!apiUrl) return false;

          const response = await fetch(apiUrl, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              Accept: "application/json",
            },
            body: buildRatingPayload(),
          });

          const result = await response.json().catch(() => ({}));
          if (!response.ok || !result.ok) {
            throw new Error(result.message || "Rating toko gagal disimpan.");
          }

          persistSavedRating(String(result.avg_rating ?? ""));
          return true;
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

        const applySuccessPayload = (payment) => {
          const orderCode = String(payment.order_code || (successCodeInline ? successCodeInline.textContent.trim() : ""));
          syncNavigationLinks(orderCode);

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
            } else {
              successTime.textContent = "-";
            }
          } else if (successTime) {
            successTime.textContent = "-";
          }

          if (successTotal && Number.isFinite(Number(payment.total))) {
            successTotal.textContent = formatRupiah(Number(payment.total));
          } else if (successTotal) {
            successTotal.textContent = "Rp 0";
          }

          if (successMethod) {
            successMethod.textContent = "QRIS";
          }

          if (successStatusPill) {
            successStatusPill.textContent = payment.wa_status === "failed" ? "Perlu WA Manual" : "Menunggu konfirmasi";
          }

          if (successWa && manualWhatsapp && payment.wa_status === "failed" && payment.manual_whatsapp_url) {
            successWa.hidden = false;
            successWa.classList.add("is-blocking");
            manualWhatsapp.dataset.manualWhatsappUrl = payment.manual_whatsapp_url;
            if (successActions) {
              successActions.hidden = true;
              successActions.setAttribute("aria-hidden", "true");
            }
          } else {
            resetWaState();
          }

          renderReceiptItems(payment.items);
        };

        const applyFallbackPayload = () => {
          const orderCode = successCodeInline ? successCodeInline.textContent.trim() : "";
          syncNavigationLinks(orderCode);

          if (successTime) {
            successTime.textContent = "-";
          }

          if (successTotal) {
            successTotal.textContent = "Rp 0";
          }

          if (successMethod) {
            successMethod.textContent = "QRIS";
          }

          if (successStatusPill) {
            successStatusPill.textContent = "Menunggu konfirmasi";
          }

          resetWaState();
          renderReceiptItems([]);
        };

        setUiState("loading");
        successSkeleton?.removeAttribute("hidden");

        const payment = readStoredPayment();
        if (hasHydrationData(payment)) {
          applySuccessPayload(payment);
          setUiState("ready");
        } else {
          applyFallbackPayload();
          setUiState("fallback");
        }

        if (ratingOverlay && ratingCard) {
          window.setTimeout(() => {
            openRatingOverlay();
          }, 280);
        }

        if (manualWhatsapp) {
          manualWhatsapp.addEventListener("click", () => {
            const manualUrl = manualWhatsapp.dataset.manualWhatsappUrl || "";
            if (!manualUrl) return;
            window.open(manualUrl, "_blank", "noopener");
          });
        }

        if (ratingCard && ratingSubmit && ratingStatus && ratingStars.length) {
          syncRatingStars();
          syncRatingSubmitState();

          for (const star of ratingStars) {
            star.addEventListener("mouseenter", () => {
              previewRating = Number(star.dataset.ratingValue || 0);
              syncRatingStars();
            });

            star.addEventListener("focus", () => {
              previewRating = Number(star.dataset.ratingValue || 0);
              syncRatingStars();
            });

            star.addEventListener("click", () => {
              selectedRating = Number(star.dataset.ratingValue || 0);
              previewRating = 0;
              syncRatingStars();
              ratingStatus.textContent = `Rating dipilih: ${selectedRating} bintang.`;
              syncRatingSubmitState();
            });
          }

          ratingCard.addEventListener("mouseleave", () => {
            previewRating = 0;
            syncRatingStars();
          });

          for (const star of ratingStars) {
            star.addEventListener("blur", () => {
              window.setTimeout(() => {
                const focused = document.activeElement;
                const isStillInside = focused instanceof HTMLElement && focused.closest("[data-rating-card]");
                if (!isStillInside) {
                  previewRating = 0;
                  syncRatingStars();
                }
              }, 0);
            });
          }

          ratingSubmit.addEventListener("click", async () => {
            if (selectedRating < 1 || selectedRating > 5) {
              ratingStatus.textContent = "Pilih rating bintang dulu sebelum menyimpan.";
              syncRatingSubmitState();
              return;
            }

            syncRatingSubmitState(true);

            try {
              await saveRating();
              window.setTimeout(() => {
                closeRatingOverlay();
              }, 160);
            } catch (error) {
              ratingStatus.textContent = error.message || "Rating toko gagal disimpan.";
            } finally {
              syncRatingSubmitState();
            }
          });
        }
      })();
    </script>
  </body>
</html>
