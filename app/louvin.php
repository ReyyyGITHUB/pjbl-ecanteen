<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/env.php';

function louvin_base_url(): string {
  return rtrim(env_value('LOUVIN_BASE_URL', 'https://api.louvin.dev') ?? 'https://api.louvin.dev', '/');
}

function louvin_api_key(): string {
  return trim((string)env_value('LOUVIN_API_KEY', ''));
}

function louvin_json_response(int $status, array $payload): void {
  http_response_code($status);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

function louvin_fail(int $status, string $message, array $extra = []): void {
  louvin_json_response($status, array_merge(['ok' => false, 'message' => $message], $extra));
}

function louvin_request(string $method, string $path, array $payload = [], array $query = []): array {
  $apiKey = louvin_api_key();
  if ($apiKey === '') {
    throw new RuntimeException('LOUVIN_API_KEY belum diisi di .env.');
  }

  $url = louvin_base_url() . '/' . ltrim($path, '/');
  if ($query) {
    $url .= '?' . http_build_query($query);
  }

  $json = $payload ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
  if ($payload && !is_string($json)) {
    throw new RuntimeException('Payload Louvin gagal dibuat.');
  }

  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_CUSTOMREQUEST => strtoupper($method),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'x-api-key: ' . $apiKey,
      ],
    ]);

    if ($json !== '') {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    }

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
      throw new RuntimeException($error !== '' ? $error : 'Louvin tidak merespons.');
    }
  } else {
    $context = stream_context_create([
      'http' => [
        'method' => strtoupper($method),
        'header' => implode("\r\n", [
          'Content-Type: application/json',
          'Accept: application/json',
          'x-api-key: ' . $apiKey,
        ]),
        'content' => $json,
        'timeout' => 20,
        'ignore_errors' => true,
      ],
    ]);

    $body = @file_get_contents($url, false, $context);
    $statusLine = is_array($http_response_header ?? null) ? ($http_response_header[0] ?? '') : '';
    preg_match('/\s(\d{3})\s/', $statusLine, $matches);
    $status = isset($matches[1]) ? (int)$matches[1] : 0;
    if (!is_string($body)) {
      throw new RuntimeException('Louvin tidak merespons.');
    }
  }

  $decoded = json_decode((string)$body, true);
  if (!is_array($decoded)) {
    throw new RuntimeException('Response Louvin tidak valid.');
  }

  if ($status < 200 || $status >= 300 || isset($decoded['error'])) {
    $message = (string)($decoded['error'] ?? $decoded['message'] ?? 'Request Louvin gagal.');
    throw new RuntimeException($message);
  }

  return $decoded;
}

function louvin_column_exists(mysqli $conn, string $table, string $column): bool {
  $stmt = $conn->prepare(
    'SELECT 1
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = ?
       AND TABLE_NAME = ?
       AND COLUMN_NAME = ?
     LIMIT 1'
  );
  $database = DB_NAME;
  $stmt->bind_param('sss', $database, $table, $column);
  $stmt->execute();
  $exists = $stmt->get_result()->fetch_row() !== null;
  $stmt->close();
  return $exists;
}

function louvin_ensure_payment_columns(mysqli $conn): void {
  $columns = [
    'louvin_transaction_id' => 'ALTER TABLE payment ADD COLUMN louvin_transaction_id VARCHAR(100) NULL AFTER buyer_wa_sent_at',
    'louvin_order_id' => 'ALTER TABLE payment ADD COLUMN louvin_order_id VARCHAR(100) NULL AFTER louvin_transaction_id',
    'louvin_status' => "ALTER TABLE payment ADD COLUMN louvin_status VARCHAR(30) NULL AFTER louvin_order_id",
    'louvin_fee' => 'ALTER TABLE payment ADD COLUMN louvin_fee INT NOT NULL DEFAULT 0 AFTER louvin_status',
    'louvin_net_amount' => 'ALTER TABLE payment ADD COLUMN louvin_net_amount INT NOT NULL DEFAULT 0 AFTER louvin_fee',
    'louvin_payment_type' => 'ALTER TABLE payment ADD COLUMN louvin_payment_type VARCHAR(40) NULL AFTER louvin_net_amount',
    'louvin_expired_at' => 'ALTER TABLE payment ADD COLUMN louvin_expired_at VARCHAR(40) NULL AFTER louvin_payment_type',
    'louvin_raw_response' => 'ALTER TABLE payment ADD COLUMN louvin_raw_response MEDIUMTEXT NULL AFTER louvin_expired_at',
  ];

  foreach ($columns as $column => $sql) {
    if (!louvin_column_exists($conn, 'payment', $column)) {
      $conn->query($sql);
    }
  }
}

function louvin_normalize_phone(string $phone): string {
  $digits = preg_replace('/\D+/', '', $phone) ?? '';
  if ($digits === '') return '';
  if (str_starts_with($digits, '0')) return '62' . substr($digits, 1);
  if (str_starts_with($digits, '8')) return '62' . $digits;
  return $digits;
}

function louvin_format_rupiah(int $amount): string {
  return 'Rp ' . number_format($amount, 0, ',', '.');
}

function louvin_generate_order_code(mysqli $conn): string {
  $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM order_pesanan WHERE kode_pesanan = ?');

  for ($attempt = 0; $attempt < 50; $attempt++) {
    $code = 'SNAPAN-' . str_pad((string)random_int(0, 999), 3, '0', STR_PAD_LEFT);
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ((int)($row['total'] ?? 0) === 0) {
      $stmt->close();
      return $code;
    }
  }

  $stmt->close();
  throw new RuntimeException('Kode pesanan gagal dibuat. Coba lagi.');
}

function louvin_read_json_body(): array {
  $raw = file_get_contents('php://input');
  $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
  return is_array($decoded) ? $decoded : [];
}

function louvin_read_checkout_draft(array $input): array {
  $items = $input['items'] ?? null;
  if (!is_array($items) || count($items) < 1) {
    louvin_fail(422, 'Keranjang pesanan masih kosong.');
  }

  $requested = [];
  foreach ($items as $item) {
    $id = (int)($item['id'] ?? 0);
    $qty = (int)($item['qty'] ?? 0);
    if ($id < 1 || $qty < 1) {
      louvin_fail(422, 'Item pesanan tidak valid.');
    }
    $requested[$id] = ($requested[$id] ?? 0) + min($qty, 99);
  }

  return [
    'items' => $requested,
    'pickup_time' => mb_substr(trim((string)($input['pickupTime'] ?? '')), 0, 80, 'UTF-8'),
    'note' => mb_substr(trim((string)($input['note'] ?? '')), 0, 500, 'UTF-8'),
    'payment_method' => (string)($input['paymentMethod'] ?? 'qris'),
  ];
}

function louvin_current_api_url(string $fileName): string {
  $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  $scheme = $isHttps ? 'https' : 'http';
  $host = (string)($_SERVER['HTTP_HOST'] ?? '127.0.0.1');
  $basePath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
  return $scheme . '://' . $host . $basePath . '/api/' . ltrim($fileName, '/');
}

function louvin_call_whatsapp_bot(array $payload): array {
  $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  if (!is_string($json)) {
    return ['ok' => false, 'error' => 'Payload WhatsApp gagal dibuat.'];
  }

  if (function_exists('curl_init')) {
    $ch = curl_init(WA_BOT_ENDPOINT);
    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $json,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json),
        'X-WA-Bot-Token: ' . WA_BOT_TOKEN,
      ],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => WA_BOT_TIMEOUT_SECONDS,
      CURLOPT_TIMEOUT => WA_BOT_TIMEOUT_SECONDS,
    ]);

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
      return ['ok' => false, 'error' => $error !== '' ? $error : 'Bot WhatsApp tidak merespons.'];
    }

    $decoded = json_decode((string)$body, true);
    if ($status >= 200 && $status < 300 && is_array($decoded) && ($decoded['ok'] ?? false)) {
      return ['ok' => true, 'response' => $decoded];
    }

    $message = is_array($decoded) ? (string)($decoded['message'] ?? $decoded['error'] ?? '') : '';
    return ['ok' => false, 'error' => $message !== '' ? $message : 'Bot WhatsApp gagal mengirim pesan.'];
  }

  return ['ok' => false, 'error' => 'cURL PHP belum aktif untuk mengirim WhatsApp.'];
}

function louvin_build_receipt_message(array $order): string {
  $itemLines = [];
  foreach ($order['items'] as $item) {
    $itemLines[] = $item['name'] . ' x' . $item['qty'];
  }

  return implode("\n", [
    '==============================',
    '      *PESANAN BARU SNAPAN*',
    '==============================',
    '*Kode*  : *' . $order['order_code'] . '*',
    '*Nama*  : ' . $order['buyer_name'],
    '*Kelas* : ' . $order['buyer_class'],
    '*Ambil* : ' . $order['pickup_time'],
    '------------------------------',
    ...$itemLines,
    '------------------------------',
    '*Total* : *' . louvin_format_rupiah((int)$order['total']) . '*',
    'Catatan: ' . ($order['note'] !== '' ? $order['note'] : '-'),
    '==============================',
    'Pembayaran Louvin sudah dikonfirmasi.',
  ]);
}

function louvin_build_buyer_message(array $order): string {
  $itemParts = [];
  foreach ($order['items'] as $item) {
    $itemParts[] = $item['qty'] . 'x ' . $item['name'];
  }

  return implode("\n", [
    '*Pembayaran berhasil!*',
    '',
    'Halo *' . $order['buyer_name'] . '* (@' . $order['buyer_username'] . '),',
    'Pesanan kamu sudah dibayar dan diteruskan ke penjual.',
    '',
    '*Pesanan:* ' . implode(' + ', $itemParts),
    '*Ambil:* ' . $order['pickup_time'],
    '*Kode:* *' . $order['order_code'] . '*',
    '*Total:* *' . louvin_format_rupiah((int)$order['total']) . '*',
    '',
    'Tunjukkan kode ini saat mengambil pesanan.',
  ]);
}

function louvin_send_order_notifications(mysqli $conn, string $orderCode, int $paymentId): array {
  $stmt = $conn->prepare(
    'SELECT
       op.kode_pesanan,
       op.jumlah,
       op.waktu_pengambilan,
       op.catatan,
       u.nama_lengkap,
       u.username,
       u.kelas_jurusan,
       u.no_telepon AS buyer_phone,
       m.nama_menu,
       m.harga,
       k.nama_kantin,
       p.nama_penjual,
       p.no_telepon AS seller_phone,
       pay.total_pembayaran
     FROM order_pesanan op
     INNER JOIN `user` u ON u.id_user = op.id_user
     INNER JOIN menu m ON m.id_menu = op.id_menu
     INNER JOIN kantin k ON k.id_kantin = m.id_kantin
     INNER JOIN penjual p ON p.id_penjual = k.id_penjual
     INNER JOIN payment pay ON pay.kode_pesanan = op.kode_pesanan
     WHERE op.kode_pesanan = ?
     ORDER BY op.id_order_pesanan ASC'
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
    return ['wa_status' => 'failed', 'wa_error' => 'Order tidak ditemukan.', 'buyer_wa_status' => 'failed', 'buyer_wa_error' => 'Order tidak ditemukan.'];
  }

  $first = $rows[0];
  $items = [];
  foreach ($rows as $row) {
    $items[] = [
      'name' => mb_convert_case(str_replace('_', ' ', (string)$row['nama_menu']), MB_CASE_TITLE, 'UTF-8'),
      'price' => (int)$row['harga'],
      'qty' => (int)$row['jumlah'],
    ];
  }

  $sellerPhone = louvin_normalize_phone((string)$first['seller_phone']);
  $buyerPhone = louvin_normalize_phone((string)$first['buyer_phone']);
  $order = [
    'order_code' => $orderCode,
    'buyer_name' => (string)$first['nama_lengkap'],
    'buyer_username' => (string)$first['username'],
    'buyer_class' => (string)$first['kelas_jurusan'],
    'pickup_time' => (string)($first['waktu_pengambilan'] ?: '-'),
    'note' => (string)($first['catatan'] ?? ''),
    'items' => $items,
    'total' => (int)$first['total_pembayaran'],
  ];

  $sellerMessage = louvin_build_receipt_message($order);
  $sellerResult = $sellerPhone !== ''
    ? louvin_call_whatsapp_bot([
        'recipient_phone' => $sellerPhone,
        'order_code' => $orderCode,
        'message' => $sellerMessage,
        'status_poll' => true,
        'ready_endpoint' => louvin_current_api_url('mark-order-ready.php'),
      ])
    : ['ok' => false, 'error' => 'Nomor WhatsApp penjual belum valid.'];

  $buyerMessage = louvin_build_buyer_message($order);
  $buyerResult = $buyerPhone !== ''
    ? louvin_call_whatsapp_bot([
        'recipient_phone' => $buyerPhone,
        'order_code' => $orderCode,
        'message' => $buyerMessage,
      ])
    : ['ok' => false, 'error' => 'Nomor WhatsApp pembeli belum valid.'];

  $waStatus = $sellerResult['ok'] ? 'sent' : 'failed';
  $waError = $sellerResult['ok'] ? null : mb_substr((string)($sellerResult['error'] ?? 'Bot WhatsApp gagal.'), 0, 1000, 'UTF-8');
  $buyerWaStatus = $buyerResult['ok'] ? 'sent' : 'failed';
  $buyerWaError = $buyerResult['ok'] ? null : mb_substr((string)($buyerResult['error'] ?? 'Bot WhatsApp pembeli gagal.'), 0, 1000, 'UTF-8');

  if ($waStatus === 'sent') {
    $update = $conn->prepare("UPDATE payment SET wa_status = 'sent', wa_error = NULL, wa_sent_at = NOW(), buyer_wa_status = ?, buyer_wa_error = ?, buyer_wa_sent_at = " . ($buyerWaStatus === 'sent' ? 'NOW()' : 'NULL') . ' WHERE id_payment = ?');
    $update->bind_param('ssi', $buyerWaStatus, $buyerWaError, $paymentId);
  } else {
    $update = $conn->prepare("UPDATE payment SET wa_status = 'failed', wa_error = ?, wa_sent_at = NULL, buyer_wa_status = ?, buyer_wa_error = ?, buyer_wa_sent_at = " . ($buyerWaStatus === 'sent' ? 'NOW()' : 'NULL') . ' WHERE id_payment = ?');
    $update->bind_param('sssi', $waError, $buyerWaStatus, $buyerWaError, $paymentId);
  }
  $update->execute();
  $update->close();

  return [
    'wa_status' => $waStatus,
    'wa_error' => $waError,
    'buyer_wa_status' => $buyerWaStatus,
    'buyer_wa_error' => $buyerWaError,
    'manual_whatsapp_url' => $sellerPhone !== '' ? 'https://wa.me/' . $sellerPhone . '?text=' . rawurlencode($sellerMessage) : '',
  ];
}

function louvin_apply_transaction_status(mysqli $conn, array $payment, string $status, array $rawData = []): array {
  $status = strtolower(trim($status));
  $paymentId = (int)$payment['id_payment'];
  $orderCode = (string)$payment['kode_pesanan'];
  $currentPaymentStatus = (string)$payment['status_pembayaran'];
  $rawJson = json_encode($rawData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  $rawJson = is_string($rawJson) ? $rawJson : '{}';

  if ($status === 'settled') {
    if ($currentPaymentStatus !== 'pembayaran_dikonfirmasi') {
      $stmt = $conn->prepare("UPDATE payment SET status_pembayaran = 'pembayaran_dikonfirmasi', louvin_status = 'settled', louvin_raw_response = ? WHERE id_payment = ?");
      $stmt->bind_param('si', $rawJson, $paymentId);
      $stmt->execute();
      $stmt->close();
    }

    $fresh = $conn->prepare('SELECT wa_status FROM payment WHERE id_payment = ? LIMIT 1');
    $fresh->bind_param('i', $paymentId);
    $fresh->execute();
    $freshRow = $fresh->get_result()->fetch_assoc() ?: [];
    $fresh->close();

    if (($freshRow['wa_status'] ?? '') !== 'sent') {
      return louvin_send_order_notifications($conn, $orderCode, $paymentId);
    }

    return ['wa_status' => 'sent', 'wa_error' => null, 'buyer_wa_status' => (string)($payment['buyer_wa_status'] ?? 'sent'), 'buyer_wa_error' => null, 'manual_whatsapp_url' => ''];
  }

  if ($status === 'failed') {
    $stmt = $conn->prepare("UPDATE payment SET status_pembayaran = 'pembayaran_ditolak', louvin_status = 'failed', louvin_raw_response = ? WHERE id_payment = ?");
    $stmt->bind_param('si', $rawJson, $paymentId);
    $stmt->execute();
    $stmt->close();
    return ['wa_status' => (string)($payment['wa_status'] ?? 'pending'), 'wa_error' => null, 'buyer_wa_status' => (string)($payment['buyer_wa_status'] ?? 'pending'), 'buyer_wa_error' => null, 'manual_whatsapp_url' => ''];
  }

  $stmt = $conn->prepare("UPDATE payment SET louvin_status = 'pending', louvin_raw_response = ? WHERE id_payment = ?");
  $stmt->bind_param('si', $rawJson, $paymentId);
  $stmt->execute();
  $stmt->close();
  return ['wa_status' => (string)($payment['wa_status'] ?? 'pending'), 'wa_error' => null, 'buyer_wa_status' => (string)($payment['buyer_wa_status'] ?? 'pending'), 'buyer_wa_error' => null, 'manual_whatsapp_url' => ''];
}

function louvin_find_payment(mysqli $conn, string $orderCode = '', string $transactionId = '', string $louvinOrderId = ''): ?array {
  if ($transactionId !== '') {
    $stmt = $conn->prepare('SELECT * FROM payment WHERE louvin_transaction_id = ? LIMIT 1');
    $stmt->bind_param('s', $transactionId);
  } elseif ($louvinOrderId !== '') {
    $stmt = $conn->prepare('SELECT * FROM payment WHERE louvin_order_id = ? OR kode_pesanan = ? LIMIT 1');
    $stmt->bind_param('ss', $louvinOrderId, $louvinOrderId);
  } elseif ($orderCode !== '') {
    $stmt = $conn->prepare('SELECT * FROM payment WHERE kode_pesanan = ? LIMIT 1');
    $stmt->bind_param('s', $orderCode);
  } else {
    return null;
  }

  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  return is_array($row) ? $row : null;
}

function louvin_order_items_for_payload(mysqli $conn, string $orderCode): array {
  $stmt = $conn->prepare(
    'SELECT m.nama_menu, m.harga, op.jumlah
     FROM order_pesanan op
     INNER JOIN menu m ON m.id_menu = op.id_menu
     WHERE op.kode_pesanan = ?
     ORDER BY op.id_order_pesanan ASC'
  );
  $stmt->bind_param('s', $orderCode);
  $stmt->execute();
  $result = $stmt->get_result();
  $items = [];
  while ($row = $result->fetch_assoc()) {
    $items[] = [
      'name' => mb_convert_case(str_replace('_', ' ', (string)$row['nama_menu']), MB_CASE_TITLE, 'UTF-8'),
      'price' => (int)$row['harga'],
      'qty' => (int)$row['jumlah'],
    ];
  }
  $stmt->close();
  return $items;
}
