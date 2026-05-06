<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/db.php';

header('Content-Type: application/json; charset=utf-8');

function respond_json(int $status, array $payload): void {
  http_response_code($status);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

function fail_json(int $status, string $message): void {
  respond_json($status, ['ok' => false, 'message' => $message]);
}

function normalize_whatsapp_number(string $phone): string {
  $digits = preg_replace('/\D+/', '', $phone) ?? '';
  if ($digits === '') return '';
  if (str_starts_with($digits, '0')) return '62' . substr($digits, 1);
  if (str_starts_with($digits, '8')) return '62' . $digits;
  return $digits;
}

function format_kantin_name(string $name): string {
  return mb_convert_case(str_replace('_', ' ', $name), MB_CASE_TITLE, 'UTF-8');
}

function build_ready_message(array $order): string {
  return implode("\n", [
    '✅ *Pesanan kamu sudah ready!*',
    '',
    'Halo *' . $order['buyer_name'] . '* (@' . $order['buyer_username'] . '),',
    'Makananmu sudah siap diambil nih.',
    '',
    '🔖 *' . $order['order_code'] . '*',
    '📍 ' . $order['kantin_name'],
    '🕐 ' . $order['pickup_time'],
    '',
    '_Tunjukkan kode ini ke penjual ya._',
  ]);
}

function build_rejected_message(array $order): string {
  return implode("\n", [
    '❌ *Pesanan tidak dapat diproses*',
    '',
    'Halo *' . $order['buyer_name'] . '* (@' . $order['buyer_username'] . '),',
    'Pesanan kamu dengan kode *' . $order['order_code'] . '* tidak dapat diproses oleh penjual.',
    '',
    '📍 ' . $order['kantin_name'],
    '💰 Pembayaran: perlu dikonfirmasi ulang',
    '',
    'Silakan konfirmasi langsung ke penjual di kantin dengan menunjukkan kode pesanan ini.',
  ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  fail_json(405, 'Method tidak diizinkan.');
}

$token = $_SERVER['HTTP_X_WA_BOT_TOKEN'] ?? '';
if (!hash_equals(WA_BOT_TOKEN, (string)$token)) {
  fail_json(401, 'Token bot tidak valid.');
}

$raw = file_get_contents('php://input');
$payload = json_decode(is_string($raw) ? $raw : '', true);
if (!is_array($payload)) {
  fail_json(422, 'Payload tidak valid.');
}

$orderCode = strtoupper(trim((string)($payload['order_code'] ?? '')));
$sellerPhone = normalize_whatsapp_number((string)($payload['seller_phone'] ?? ''));
$action = strtolower(trim((string)($payload['action'] ?? 'ready')));

if (!preg_match('/^SNAPAN-\d{3}$/', $orderCode)) {
  fail_json(422, 'Kode pesanan tidak valid.');
}

if ($sellerPhone === '') {
  fail_json(422, 'Nomor penjual tidak valid.');
}

if (!in_array($action, ['ready', 'reject'], true)) {
  fail_json(422, 'Aksi status pesanan tidak valid.');
}

$nextOrderStatus = $action === 'ready' ? 'siap_diambil' : 'ditolak';
$nextPaymentStatus = $action === 'ready' ? 'pembayaran_dikonfirmasi' : 'pembayaran_ditolak';
$actionDoneText = $action === 'ready' ? 'ditandai siap diambil' : 'ditolak';

$conn = db();
$stmt = $conn->prepare(
  "SELECT
     op.kode_pesanan,
     op.status_pesanan,
     op.waktu_pengambilan,
     u.nama_lengkap,
     u.username,
     u.no_telepon AS buyer_phone,
     k.nama_kantin,
     p.no_telepon AS seller_phone
   FROM order_pesanan op
   INNER JOIN menu m ON m.id_menu = op.id_menu
   INNER JOIN kantin k ON k.id_kantin = m.id_kantin
   INNER JOIN penjual p ON p.id_penjual = k.id_penjual
   INNER JOIN user u ON u.id_user = op.id_user
   WHERE op.kode_pesanan = ?
   ORDER BY op.id_order_pesanan ASC"
);
$stmt->bind_param('s', $orderCode);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
  $rows[] = $row;
}
$stmt->close();

if (!$rows) {
  fail_json(404, 'Pesanan tidak ditemukan.');
}

$first = $rows[0];
if (normalize_whatsapp_number((string)$first['seller_phone']) !== $sellerPhone) {
  fail_json(403, 'Nomor penjual tidak sesuai dengan pesanan.');
}

$alreadyProcessed = true;
$hasOtherFinalStatus = false;
foreach ($rows as $row) {
  $status = (string)$row['status_pesanan'];
  if ($status !== $nextOrderStatus) {
    $alreadyProcessed = false;
  }

  if ($status !== 'diproses' && $status !== $nextOrderStatus) {
    $hasOtherFinalStatus = true;
  }
}

if ($alreadyProcessed) {
  respond_json(200, [
    'ok' => true,
    'already_processed' => true,
    'order_code' => $orderCode,
    'seller_message' => 'Pesanan ' . $orderCode . ' sudah pernah ' . $actionDoneText . '.',
  ]);
}

if ($hasOtherFinalStatus) {
  fail_json(409, 'Pesanan ' . $orderCode . ' sudah punya status final lain.');
}

$conn->begin_transaction();
try {
  $updateOrder = $conn->prepare('UPDATE order_pesanan SET status_pesanan = ? WHERE kode_pesanan = ?');
  $updateOrder->bind_param('ss', $nextOrderStatus, $orderCode);
  $updateOrder->execute();
  $updateOrder->close();

  $updatePayment = $conn->prepare('UPDATE payment SET status_pembayaran = ? WHERE kode_pesanan = ?');
  $updatePayment->bind_param('ss', $nextPaymentStatus, $orderCode);
  $updatePayment->execute();
  $updatePayment->close();

  $conn->commit();
} catch (Throwable $e) {
  $conn->rollback();
  fail_json(500, 'Status pesanan gagal diperbarui.');
}

$buyerPhone = normalize_whatsapp_number((string)$first['buyer_phone']);
$readyOrder = [
  'order_code' => $orderCode,
  'buyer_name' => (string)$first['nama_lengkap'],
  'buyer_username' => (string)$first['username'],
  'buyer_phone' => $buyerPhone,
  'kantin_name' => format_kantin_name((string)$first['nama_kantin']),
  'pickup_time' => (string)($first['waktu_pengambilan'] ?: '-'),
];

respond_json(200, [
  'ok' => true,
  'already_processed' => false,
  'action' => $action,
  'order_code' => $orderCode,
  'buyer_phone' => $buyerPhone,
  'buyer_message' => $action === 'ready' ? build_ready_message($readyOrder) : build_rejected_message($readyOrder),
  'seller_message' => 'Status ' . $orderCode . ' sudah ' . $actionDoneText . '.',
]);
