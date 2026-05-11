<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/louvin.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  louvin_json_response(200, ['received' => true, 'ignored' => 'method']);
}

$payload = louvin_read_json_body();
$event = (string)($payload['event'] ?? '');
$data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

$transactionId = (string)($data['transaction_id'] ?? '');
$louvinOrderId = (string)($data['order_id'] ?? '');
$status = (string)($data['status'] ?? '');

try {
  $conn = db();
  louvin_ensure_payment_columns($conn);
  $payment = louvin_find_payment($conn, '', $transactionId, $louvinOrderId);

  if (!$payment) {
    louvin_json_response(200, [
      'received' => true,
      'matched' => false,
      'event' => $event,
    ]);
  }

  if ($status === '' && str_starts_with($event, 'payment.')) {
    $status = substr($event, strlen('payment.'));
  }

  $sideEffects = louvin_apply_transaction_status($conn, $payment, $status !== '' ? $status : 'pending', $payload);

  louvin_json_response(200, [
    'received' => true,
    'matched' => true,
    'order_code' => (string)$payment['kode_pesanan'],
    'status' => $status,
    ...$sideEffects,
  ]);
} catch (Throwable $e) {
  louvin_json_response(200, [
    'received' => true,
    'error' => $e->getMessage(),
  ]);
}
