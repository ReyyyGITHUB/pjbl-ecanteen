<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/louvin.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
  louvin_fail(405, 'Method tidak diizinkan.');
}

start_session();
$user = current_user();
if (!$user) {
  louvin_fail(401, 'Silakan login terlebih dahulu.');
}

$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? louvin_read_json_body() : $_GET;
$orderCode = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', (string)($input['order_code'] ?? '')) ?? '');
$transactionId = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($input['transaction_id'] ?? '')) ?? '';

$conn = db();
louvin_ensure_payment_columns($conn);

$payment = louvin_find_payment($conn, $orderCode, $transactionId);
if (!$payment) {
  louvin_fail(404, 'Transaksi tidak ditemukan.');
}

$stmt = $conn->prepare(
  'SELECT COUNT(*) AS total
   FROM order_pesanan
   WHERE kode_pesanan = ? AND id_user = ?'
);
$userId = (int)$user['id_user'];
$paymentOrderCode = (string)$payment['kode_pesanan'];
$stmt->bind_param('si', $paymentOrderCode, $userId);
$stmt->execute();
$owned = (int)(($stmt->get_result()->fetch_assoc()['total'] ?? 0));
$stmt->close();

if ($owned < 1) {
  louvin_fail(403, 'Kamu tidak punya akses ke transaksi ini.');
}

$status = (string)($payment['louvin_status'] ?: 'pending');
$louvinData = [];

try {
  if (($payment['status_pembayaran'] ?? '') !== 'pembayaran_dikonfirmasi' && (string)$payment['louvin_transaction_id'] !== '') {
    $louvin = louvin_request('GET', '/check-status', [], ['id' => (string)$payment['louvin_transaction_id']]);
    $louvinData = is_array($louvin['transaction'] ?? null) ? $louvin['transaction'] : [];
    $status = (string)($louvinData['status'] ?? $status);
    $sideEffects = louvin_apply_transaction_status($conn, $payment, $status, $louvin);
    $payment = louvin_find_payment($conn, (string)$payment['kode_pesanan']) ?: $payment;
  } else {
    $sideEffects = [
      'wa_status' => (string)($payment['wa_status'] ?? 'pending'),
      'wa_error' => (string)($payment['wa_error'] ?? ''),
      'buyer_wa_status' => (string)($payment['buyer_wa_status'] ?? 'pending'),
      'buyer_wa_error' => (string)($payment['buyer_wa_error'] ?? ''),
      'manual_whatsapp_url' => '',
    ];
  }

  $items = louvin_order_items_for_payload($conn, (string)$payment['kode_pesanan']);
  louvin_json_response(200, [
    'ok' => true,
    'order_code' => (string)$payment['kode_pesanan'],
    'transaction_id' => (string)$payment['louvin_transaction_id'],
    'louvin_order_id' => (string)$payment['louvin_order_id'],
    'status' => (string)($payment['louvin_status'] ?: $status),
    'payment_status' => (string)$payment['status_pembayaran'],
    'total' => (int)$payment['total_pembayaran'],
    'net_amount' => (int)($payment['louvin_net_amount'] ?? 0),
    'fee' => (int)($payment['louvin_fee'] ?? 0),
    'items' => $items,
    'confirmed' => (string)$payment['status_pembayaran'] === 'pembayaran_dikonfirmasi',
    'confirmedAt' => date(DATE_ATOM),
    ...$sideEffects,
    'louvin' => $louvinData,
  ]);
} catch (Throwable $e) {
  louvin_fail(500, $e->getMessage(), [
    'order_code' => (string)$payment['kode_pesanan'],
    'status' => $status,
  ]);
}
