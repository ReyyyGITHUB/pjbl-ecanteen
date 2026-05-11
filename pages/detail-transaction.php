<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_login('detail-transaction');

$basePath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
$current = current_user() ?? [];
$rawCode = (string)($_GET['kode'] ?? '');
$orderCode = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $rawCode) ?? '');

function detail_display_name(string $value): string {
  $value = trim($value);
  if ($value === '') {
    return '-';
  }

  $value = str_replace(['_', '-'], ' ', $value);
  $value = preg_replace('/\s+/', ' ', $value) ?? $value;
  return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
}

function detail_rupiah(int $amount): string {
  return 'Rp ' . number_format($amount, 0, ',', '.');
}

function detail_status_label(string $orderStatus, string $paymentStatus): array {
  if ($orderStatus === 'ditolak' || $paymentStatus === 'pembayaran_ditolak') {
    return ['Ditolak', 'is-rejected'];
  }

  if ($orderStatus === 'siap_diambil' || $paymentStatus === 'pembayaran_dikonfirmasi') {
    return ['Siap diambil', 'is-ready'];
  }

  return ['Diproses', 'is-processing'];
}

$transaction = null;
$items = [];
$proofUrl = '';
$backHref = $basePath . '/riwayat';
$homeHref = $basePath . '/';

if ($orderCode !== '' && (int)($current['id_user'] ?? 0) > 0) {
  $conn = db();
  $stmt = $conn->prepare(
    "SELECT
      op.kode_pesanan,
      op.id_order_pesanan,
      op.id_user,
      op.jumlah,
      op.status_pesanan,
      op.waktu_pengambilan,
      op.catatan,
      op.created_at,
      m.id_menu,
      m.nama_menu,
      m.harga,
      m.gambar,
      k.id_kantin,
      k.nama_kantin,
      p.nama_penjual,
      p.no_telepon,
      pay.id_payment,
      pay.total_pembayaran,
      pay.metode_pembayaran,
      pay.status_pembayaran,
      pay.bukti_pembayaran,
      pay.wa_status,
      pay.buyer_wa_status,
      pay.wa_sent_at,
      pay.buyer_wa_sent_at
     FROM order_pesanan op
     INNER JOIN menu m ON m.id_menu = op.id_menu
     INNER JOIN kantin k ON k.id_kantin = m.id_kantin
     INNER JOIN penjual p ON p.id_penjual = k.id_penjual
     LEFT JOIN payment pay ON pay.kode_pesanan = op.kode_pesanan
     WHERE op.kode_pesanan = ? AND op.id_user = ?
     ORDER BY op.id_order_pesanan ASC"
  );
  $userId = (int)($current['id_user'] ?? 0);
  $stmt->bind_param('si', $orderCode, $userId);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result ? $result->fetch_assoc() : null) {
    if (!is_array($row)) {
      break;
    }

    if ($transaction === null) {
      $transaction = $row;
      $proofPath = trim((string)($row['bukti_pembayaran'] ?? ''));
      if ($proofPath !== '' && $proofPath !== '-') {
        $proofUrl = $basePath . '/' . ltrim($proofPath, '/');
      }
    }

    $items[] = [
      'name' => detail_display_name((string)$row['nama_menu']),
      'qty' => (int)$row['jumlah'],
      'price' => (int)$row['harga'],
      'subtotal' => (int)$row['harga'] * (int)$row['jumlah'],
    ];
  }

  $stmt->close();
}

$isFound = is_array($transaction);
$orderStatus = (string)($transaction['status_pesanan'] ?? '');
$paymentStatus = (string)($transaction['status_pembayaran'] ?? '');
[$statusLabel, $statusClass] = detail_status_label($orderStatus, $paymentStatus);
$orderDate = $isFound ? date('d/m/Y H:i', strtotime((string)($transaction['created_at'] ?? 'now'))) : '';
$pickupTime = $isFound ? trim((string)($transaction['waktu_pengambilan'] ?? '')) : '';
$note = $isFound ? trim((string)($transaction['catatan'] ?? '')) : '';
$buyerName = $isFound ? detail_display_name((string)($current['nama_lengkap'] ?? '')) : '';
$buyerClass = $isFound ? detail_display_name((string)($current['kelas_jurusan'] ?? '')) : '';
$sellerName = $isFound ? detail_display_name((string)($transaction['nama_penjual'] ?? '')) : '';
$kantinName = $isFound ? detail_display_name((string)($transaction['nama_kantin'] ?? '')) : '';
$totalPayment = (int)($transaction['total_pembayaran'] ?? 0);
$paymentMethod = $isFound ? strtoupper((string)($transaction['metode_pembayaran'] ?? '')) : '';
$waStatus = (string)($transaction['wa_status'] ?? '');
$buyerWaStatus = (string)($transaction['buyer_wa_status'] ?? '');
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
    <title>Detail Transaksi - E-Canteen</title>
  </head>
  <body class="payment-qris-body">
    <main class="payment-qris-page payment-detail-page">
      <?php if ($isFound): ?>
        <section class="payment-detail-shell" aria-labelledby="payment-detail-title">
          <header class="payment-detail-hero">
            <a class="payment-detail-back" href="<?= htmlspecialchars($backHref, ENT_QUOTES, 'UTF-8') ?>">
              <span aria-hidden="true">←</span>
              <strong>Kembali ke Riwayat</strong>
            </a>
            <div class="payment-detail-hero-copy">
              <span class="payment-detail-eyebrow">Detail transaksi</span>
              <h1 id="payment-detail-title"><?= htmlspecialchars($orderCode, ENT_QUOTES, 'UTF-8') ?></h1>
              <p>Ringkasan transaksi yang tersimpan di server, lengkap dengan status pesanan dan pembayaran terbarunya.</p>
            </div>
            <div class="payment-detail-code-card">
              <span>Kode pesanan</span>
              <strong><?= htmlspecialchars($orderCode, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
          </header>

          <div class="payment-detail-grid">
            <section class="payment-detail-card payment-detail-main">
              <div class="payment-detail-section-head">
                <div>
                  <span class="payment-detail-label">Status transaksi</span>
                  <h2>Pesanan kamu</h2>
                </div>
                <span class="payment-detail-badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
              </div>

              <dl class="payment-detail-meta-grid">
                <div>
                  <dt>Nama</dt>
                  <dd><?= htmlspecialchars($buyerName, ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div>
                  <dt>Kelas</dt>
                  <dd><?= htmlspecialchars($buyerClass, ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div>
                  <dt>Kantin</dt>
                  <dd><?= htmlspecialchars($kantinName, ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div>
                  <dt>Penjual</dt>
                  <dd><?= htmlspecialchars($sellerName, ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div>
                  <dt>Tanggal</dt>
                  <dd><?= htmlspecialchars($orderDate, ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div>
                  <dt>Ambil</dt>
                  <dd><?= htmlspecialchars($pickupTime !== '' ? $pickupTime : '-', ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div>
                  <dt>Pembayaran</dt>
                  <dd><?= htmlspecialchars($paymentMethod !== '' ? $paymentMethod : '-', ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div>
                  <dt>Status bayar</dt>
                  <dd><?= htmlspecialchars(detail_display_name($paymentStatus), ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
              </dl>

              <div class="payment-detail-items">
                <div class="payment-detail-section-head payment-detail-section-head-tight">
                  <div>
                    <span class="payment-detail-label">Item dibeli</span>
                    <h2>Rincian pesanan</h2>
                  </div>
                </div>

                <div class="payment-detail-item-list">
                  <?php foreach ($items as $item): ?>
                    <article class="payment-detail-item">
                      <div class="payment-detail-item-main">
                        <h3><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars((string)$item['qty'], ENT_QUOTES, 'UTF-8') ?> x <?= htmlspecialchars(detail_rupiah($item['price']), ENT_QUOTES, 'UTF-8') ?></p>
                      </div>
                      <strong><?= htmlspecialchars(detail_rupiah($item['subtotal']), ENT_QUOTES, 'UTF-8') ?></strong>
                    </article>
                  <?php endforeach; ?>
                </div>
              </div>

              <?php if ($note !== ''): ?>
                <div class="payment-detail-note">
                  <span>Catatan</span>
                  <p><?= htmlspecialchars($note, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
              <?php endif; ?>
            </section>

            <aside class="payment-detail-card payment-detail-side">
              <div class="payment-detail-proof">
                <span>Bukti pembayaran</span>
                <?php if ($proofUrl !== ''): ?>
                  <img src="<?= htmlspecialchars($proofUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Bukti pembayaran <?= htmlspecialchars($orderCode, ENT_QUOTES, 'UTF-8') ?>" />
                <?php else: ?>
                  <div class="payment-detail-proof-empty">
                    <strong>Belum ada bukti tersimpan.</strong>
                    <p>Informasi file pembayaran tidak tersedia untuk transaksi ini.</p>
                  </div>
                <?php endif; ?>
              </div>

              <div class="payment-detail-summary">
                <div>
                  <span>Total pembayaran</span>
                  <strong><?= htmlspecialchars(detail_rupiah($totalPayment), ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div>
                  <span>Status WhatsApp penjual</span>
                  <strong><?= htmlspecialchars(detail_display_name($waStatus), ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div>
                  <span>Status WhatsApp pembeli</span>
                  <strong><?= htmlspecialchars(detail_display_name($buyerWaStatus), ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
              </div>

              <a class="payment-detail-home" href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>">Kembali ke beranda</a>
            </aside>
          </div>
        </section>
      <?php else: ?>
        <section class="payment-detail-empty">
          <span>Detail transaksi tidak ditemukan</span>
          <h1>Periksa kode pesanan.</h1>
          <p>Pastikan kode transaksi benar atau buka halaman ini langsung dari riwayat pembelian supaya datanya sesuai.</p>
          <a href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>">Kembali ke beranda</a>
        </section>
      <?php endif; ?>
    </main>
  </body>
</html>
