<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

require_login('riwayat');

$user = current_user();
if (($user['role'] ?? '') === 'seller') {
  header('Location: ' . seller_dashboard_route($user));
  exit;
}

$basePath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''))), '/');

function history_display_name(string $value): string {
  $value = trim($value);
  if ($value === '') {
    return '-';
  }

  $value = str_replace(['_', '-'], ' ', $value);
  $value = preg_replace('/\s+/', ' ', $value) ?? $value;
  return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
}

function history_rupiah(int $amount): string {
  return 'Rp ' . number_format($amount, 0, ',', '.');
}

function history_order_status(string $orderStatus): array {
  return match ($orderStatus) {
    'siap_diambil' => ['Siap Diambil', 'is-ready'],
    'ditolak' => ['Ditolak', 'is-rejected'],
    default => ['Diproses', 'is-processing'],
  };
}

function history_payment_status(string $paymentStatus): array {
  return match ($paymentStatus) {
    'pembayaran_dikonfirmasi' => ['Pembayaran Dikonfirmasi', 'is-ready'],
    'pembayaran_ditolak' => ['Pembayaran Ditolak', 'is-rejected'],
    default => ['Menunggu Konfirmasi', 'is-processing'],
  };
}

$historyRows = [];
$conn = db();
$stmt = $conn->prepare(
  'SELECT
     op.kode_pesanan,
     MAX(op.created_at) AS created_at,
     MAX(op.status_pesanan) AS status_pesanan,
     MAX(COALESCE(pay.status_pembayaran, "menunggu_konfirmasi")) AS status_pembayaran,
     MAX(COALESCE(pay.metode_pembayaran, "-")) AS metode_pembayaran,
     MAX(k.nama_kantin) AS nama_kantin,
     COALESCE(MAX(pay.total_pembayaran), SUM(op.jumlah * m.harga)) AS total_pembayaran,
     SUM(op.jumlah) AS total_items,
     GROUP_CONCAT(
       CONCAT(REPLACE(m.nama_menu, "_", " "), " x", op.jumlah)
       ORDER BY op.id_order_pesanan ASC
       SEPARATOR " • "
     ) AS item_ringkas
   FROM order_pesanan op
   INNER JOIN menu m ON m.id_menu = op.id_menu
   INNER JOIN kantin k ON k.id_kantin = m.id_kantin
   LEFT JOIN payment pay ON pay.kode_pesanan = op.kode_pesanan
   WHERE op.id_user = ?
   GROUP BY op.kode_pesanan
   ORDER BY MAX(op.created_at) DESC, op.kode_pesanan DESC'
);
$userId = (int)($user['id_user'] ?? 0);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result ? $result->fetch_assoc() : null) {
  if (!is_array($row)) {
    break;
  }

  $historyRows[] = [
    'kode' => (string)$row['kode_pesanan'],
    'created_at' => (string)$row['created_at'],
    'order_status' => (string)$row['status_pesanan'],
    'payment_status' => (string)$row['status_pembayaran'],
    'payment_method' => strtoupper((string)$row['metode_pembayaran']),
    'kantin' => history_display_name((string)$row['nama_kantin']),
    'total_payment' => (int)$row['total_pembayaran'],
    'total_items' => (int)$row['total_items'],
    'items_preview' => history_display_name((string)$row['item_ringkas']),
  ];
}
$stmt->close();

$summary = [
  'total' => count($historyRows),
  'processing' => 0,
  'ready' => 0,
  'rejected' => 0,
];

foreach ($historyRows as $historyRow) {
  if ($historyRow['order_status'] === 'siap_diambil') {
    $summary['ready']++;
  } elseif ($historyRow['order_status'] === 'ditolak') {
    $summary['rejected']++;
  } else {
    $summary['processing']++;
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
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Nunito+Sans:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/styles.css" />
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/riwayat.css" />
    <title>Riwayat Pembelian - E-Canteen</title>
  </head>
  <body class="history-body">
    <main class="history-page">
      <section class="history-shell">
        <header class="history-topbar">
          <a class="history-back" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/kantin">
            <span aria-hidden="true">←</span>
            <strong>Kembali ke Kantin</strong>
          </a>
          <a class="history-brand" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/">
            <img src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/img/figma/logo-mark.png" alt="" width="30" height="45" />
            <span>E-Canteen</span>
          </a>
        </header>

        <section class="history-hero">
          <div class="history-hero-copy">
            <span class="history-eyebrow">Riwayat pembelian</span>
            <h1>Semua transaksi jajanmu tersimpan rapi.</h1>
            <p>Lihat status pesanan, total pembayaran, dan buka detail transaksi kapan saja tanpa perlu cari ulang kode pesanan.</p>
          </div>

          <div class="history-summary-grid" aria-label="Ringkasan riwayat">
            <article class="history-summary-card">
              <span>Total transaksi</span>
              <strong><?= htmlspecialchars((string)$summary['total']) ?></strong>
            </article>
            <article class="history-summary-card">
              <span>Diproses</span>
              <strong><?= htmlspecialchars((string)$summary['processing']) ?></strong>
            </article>
            <article class="history-summary-card">
              <span>Siap diambil</span>
              <strong><?= htmlspecialchars((string)$summary['ready']) ?></strong>
            </article>
            <article class="history-summary-card">
              <span>Ditolak</span>
              <strong><?= htmlspecialchars((string)$summary['rejected']) ?></strong>
            </article>
          </div>
        </section>

        <?php if ($historyRows): ?>
          <section class="history-list" aria-label="Daftar riwayat pembelian">
            <?php foreach ($historyRows as $row): ?>
              <?php [$orderLabel, $orderClass] = history_order_status($row['order_status']); ?>
              <?php [$paymentLabel, $paymentClass] = history_payment_status($row['payment_status']); ?>
              <article class="history-card">
                <div class="history-card-head">
                  <div>
                    <span class="history-card-eyebrow">Kode pesanan</span>
                    <h2><?= htmlspecialchars($row['kode']) ?></h2>
                  </div>
                  <div class="history-badges">
                    <span class="history-badge <?= htmlspecialchars($orderClass) ?>"><?= htmlspecialchars($orderLabel) ?></span>
                    <span class="history-badge <?= htmlspecialchars($paymentClass) ?> is-payment"><?= htmlspecialchars($paymentLabel) ?></span>
                  </div>
                </div>

                <dl class="history-meta-grid">
                  <div>
                    <dt>Tanggal</dt>
                    <dd><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['created_at']))) ?></dd>
                  </div>
                  <div>
                    <dt>Kantin</dt>
                    <dd><?= htmlspecialchars($row['kantin']) ?></dd>
                  </div>
                  <div>
                    <dt>Total item</dt>
                    <dd><?= htmlspecialchars((string)$row['total_items']) ?> item</dd>
                  </div>
                  <div>
                    <dt>Metode bayar</dt>
                    <dd><?= htmlspecialchars($row['payment_method'] !== '' ? $row['payment_method'] : '-') ?></dd>
                  </div>
                </dl>

                <div class="history-card-footer">
                  <div class="history-items-preview">
                    <span>Ringkasan item</span>
                    <p><?= htmlspecialchars($row['items_preview']) ?></p>
                  </div>
                  <div class="history-total-box">
                    <span>Total pembayaran</span>
                    <strong><?= htmlspecialchars(history_rupiah($row['total_payment'])) ?></strong>
                  </div>
                  <a class="history-detail-link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/detail-transaction/<?= rawurlencode($row['kode']) ?>">Lihat Detail Transaksi</a>
                </div>
              </article>
            <?php endforeach; ?>
          </section>
        <?php else: ?>
          <section class="history-empty">
            <span>Belum ada transaksi</span>
            <h2>Riwayat pembelianmu masih kosong.</h2>
            <p>Setelah kamu checkout dari halaman kantin, transaksi akan muncul di sini lengkap dengan status pesanan dan detail pembayarannya.</p>
            <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/kantin">Mulai Pesan dari Kantin</a>
          </section>
        <?php endif; ?>
      </section>
    </main>
  </body>
</html>
