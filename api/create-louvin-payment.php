<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/louvin.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  louvin_fail(405, 'Method tidak diizinkan.');
}

start_session();
$user = current_user();
if (!$user) {
  louvin_fail(401, 'Silakan login terlebih dahulu.');
}

$input = louvin_read_json_body();
$draft = louvin_read_checkout_draft($input);
if ($draft['payment_method'] !== 'qris') {
  louvin_fail(422, 'Metode pembayaran tidak valid.');
}

$conn = db();
louvin_ensure_payment_columns($conn);

$ids = array_keys($draft['items']);
$idList = implode(',', array_map('intval', $ids));

$menuSql = "
  SELECT
    m.id_menu,
    m.nama_menu,
    m.harga,
    m.sisa_stock,
    k.id_kantin,
    k.nama_kantin,
    p.nama_penjual,
    p.no_telepon
  FROM menu m
  INNER JOIN kantin k ON k.id_kantin = m.id_kantin
  INNER JOIN penjual p ON p.id_penjual = k.id_penjual
  WHERE m.id_menu IN ($idList)
";

$result = $conn->query($menuSql);
$menus = [];
while ($row = $result->fetch_assoc()) {
  $menus[(int)$row['id_menu']] = $row;
}

if (count($menus) !== count($draft['items'])) {
  louvin_fail(422, 'Ada menu yang tidak ditemukan.');
}

$kantinId = null;
$kantinName = '';
$orderItems = [];
$netTotal = 0;

foreach ($draft['items'] as $id => $qty) {
  $menu = $menus[(int)$id];
  $currentKantinId = (int)$menu['id_kantin'];
  if ($kantinId === null) {
    $kantinId = $currentKantinId;
    $kantinName = (string)$menu['nama_kantin'];
  } elseif ($kantinId !== $currentKantinId) {
    louvin_fail(422, 'Satu checkout hanya boleh berisi item dari satu kantin.');
  }

  $stock = (int)$menu['sisa_stock'];
  if ($qty > $stock) {
    louvin_fail(422, 'Stok ' . str_replace('_', ' ', (string)$menu['nama_menu']) . ' tidak mencukupi.');
  }

  $price = (int)$menu['harga'];
  $subtotal = $price * $qty;
  $netTotal += $subtotal;
  $orderItems[] = [
    'id' => (int)$id,
    'name' => mb_convert_case(str_replace('_', ' ', (string)$menu['nama_menu']), MB_CASE_TITLE, 'UTF-8'),
    'price' => $price,
    'qty' => (int)$qty,
    'subtotal' => $subtotal,
  ];
}

if ($netTotal < 1500) {
  louvin_fail(422, 'Minimal pembayaran QRIS Louvin adalah Rp 1.500.');
}

try {
  $orderCode = louvin_generate_order_code($conn);
  $buyerName = trim((string)($user['nama_lengkap'] ?? $user['username'] ?? 'Customer'));
  $louvin = louvin_request('POST', '/create-transaction', [
    'amount' => $netTotal,
    'payment_type' => 'qris',
    'customer_name' => $buyerName !== '' ? $buyerName : 'Customer',
    'description' => 'E-Canteen ' . $orderCode,
    'reference' => $orderCode,
  ]);

  if (!($louvin['success'] ?? false)) {
    throw new RuntimeException('Louvin gagal membuat transaksi.');
  }

  $transaction = is_array($louvin['transaction'] ?? null) ? $louvin['transaction'] : [];
  $payment = is_array($louvin['payment'] ?? null) ? $louvin['payment'] : [];
  $transactionId = (string)($transaction['id'] ?? '');
  $louvinOrderId = (string)($payment['order_id'] ?? $transaction['reference'] ?? $orderCode);
  $qrString = (string)($payment['qr_string'] ?? $payment['payment_number'] ?? '');

  if ($transactionId === '' || $qrString === '') {
    throw new RuntimeException('Response QRIS Louvin belum lengkap.');
  }

  $totalPayment = (int)($payment['total_payment'] ?? $transaction['amount'] ?? $netTotal);
  $fee = (int)($transaction['fee'] ?? max(0, $totalPayment - $netTotal));
  $netAmount = (int)($transaction['net_amount'] ?? $netTotal);
  $expiredAt = (string)($payment['expired_at'] ?? '');
  $rawResponse = json_encode($louvin, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  $rawResponse = is_string($rawResponse) ? $rawResponse : '{}';

  $conn->begin_transaction();

  $orderStmt = $conn->prepare(
    "INSERT INTO order_pesanan
      (kode_pesanan, id_menu, id_user, jumlah, tanggal_pesanan, status_pesanan, waktu_pengambilan, catatan)
     VALUES (?, ?, ?, ?, CURDATE(), 'diproses', ?, ?)"
  );
  $stockStmt = $conn->prepare(
    'UPDATE menu SET sisa_stock = sisa_stock - ? WHERE id_menu = ? AND sisa_stock >= ?'
  );

  $firstOrderId = 0;
  $userId = (int)$user['id_user'];
  foreach ($orderItems as $item) {
    $menuId = (int)$item['id'];
    $qty = (int)$item['qty'];
    $pickupTime = $draft['pickup_time'] !== '' ? $draft['pickup_time'] : '-';
    $note = $draft['note'];

    $stockStmt->bind_param('iii', $qty, $menuId, $qty);
    $stockStmt->execute();
    if ($stockStmt->affected_rows !== 1) {
      throw new RuntimeException('Stok menu berubah. Silakan cek keranjang lagi.');
    }

    $orderStmt->bind_param('siiiss', $orderCode, $menuId, $userId, $qty, $pickupTime, $note);
    $orderStmt->execute();
    if ($firstOrderId === 0) {
      $firstOrderId = (int)$conn->insert_id;
    }
  }
  $stockStmt->close();
  $orderStmt->close();

  $paymentStmt = $conn->prepare(
    "INSERT INTO payment
      (kode_pesanan, id_order_pesanan, total_pembayaran, metode_pembayaran, status_pembayaran, bukti_pembayaran,
       wa_status, buyer_wa_status, louvin_transaction_id, louvin_order_id, louvin_status, louvin_fee,
       louvin_net_amount, louvin_payment_type, louvin_expired_at, louvin_raw_response)
     VALUES (?, ?, ?, 'qris', 'menunggu_konfirmasi', '', 'pending', 'pending', ?, ?, 'pending', ?, ?, 'qris', ?, ?)"
  );
  $paymentStmt->bind_param(
    'siissiiss',
    $orderCode,
    $firstOrderId,
    $totalPayment,
    $transactionId,
    $louvinOrderId,
    $fee,
    $netAmount,
    $expiredAt,
    $rawResponse
  );
  $paymentStmt->execute();
  $paymentId = (int)$conn->insert_id;
  $paymentStmt->close();

  $conn->commit();

  louvin_json_response(201, [
    'ok' => true,
    'order_code' => $orderCode,
    'payment_id' => $paymentId,
    'transaction_id' => $transactionId,
    'louvin_order_id' => $louvinOrderId,
    'status' => 'pending',
    'total' => $totalPayment,
    'net_amount' => $netAmount,
    'fee' => $fee,
    'expired_at' => $expiredAt,
    'payment' => [
      'payment_type' => 'qris',
      'qr_string' => $qrString,
      'payment_number' => (string)($payment['payment_number'] ?? $qrString),
      'total_payment' => $totalPayment,
    ],
    'items' => $orderItems,
    'kantin_name' => mb_convert_case(str_replace('_', ' ', $kantinName), MB_CASE_TITLE, 'UTF-8'),
  ]);
} catch (Throwable $e) {
  if ($conn->errno === 0) {
    try {
      $conn->rollback();
    } catch (Throwable $rollbackError) {
      // No active transaction.
    }
  }

  louvin_fail(500, $e->getMessage());
}
