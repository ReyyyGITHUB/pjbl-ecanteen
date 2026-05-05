<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

header('Content-Type: application/json; charset=utf-8');

function respond_json(int $status, array $payload): void {
  http_response_code($status);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

function fail_json(int $status, string $message, array $extra = []): void {
  respond_json($status, array_merge(['ok' => false, 'message' => $message], $extra));
}

function current_api_url(string $fileName): string {
  $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  $scheme = $isHttps ? 'https' : 'http';
  $host = (string)($_SERVER['HTTP_HOST'] ?? '127.0.0.1');
  $basePath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''))), '/');

  return $scheme . '://' . $host . $basePath . '/api/' . ltrim($fileName, '/');
}

function normalize_whatsapp_number(string $phone): string {
  $digits = preg_replace('/\D+/', '', $phone) ?? '';
  if ($digits === '') return '';
  if (str_starts_with($digits, '0')) return '62' . substr($digits, 1);
  if (str_starts_with($digits, '8')) return '62' . $digits;
  return $digits;
}

function format_rupiah(int $amount): string {
  return 'Rp ' . number_format($amount, 0, ',', '.');
}

function receipt_visible_length(string $value): int {
  return mb_strlen(str_replace('*', '', $value), 'UTF-8');
}

function center_receipt_line(string $value, int $width): string {
  $padding = max(0, $width - receipt_visible_length($value));
  return str_repeat(' ', intdiv($padding, 2)) . $value;
}

function make_receipt_rule(array $lines, string $char): string {
  $width = 28;
  foreach ($lines as $line) {
    $width = max($width, receipt_visible_length((string)$line));
  }

  return str_repeat($char, min(42, $width));
}

function generate_order_code(mysqli $conn): string {
  $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM order_pesanan WHERE kode_pesanan = ?');

  for ($attempt = 0; $attempt < 30; $attempt++) {
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
  throw new RuntimeException('Kode pesanan penuh. Coba lagi beberapa saat.');
}

function build_receipt_message(array $order): string {
  $itemLines = [];
  foreach ($order['items'] as $item) {
    $itemLines[] = $item['name'] . ' x' . $item['qty'];
  }

  $bodyLines = [
    '*Kode*  : *' . $order['order_code'] . '*',
    '*Nama*  : ' . $order['buyer_name'],
    '*Kelas* : ' . $order['buyer_class'],
    '*Ambil* : ' . $order['pickup_time'],
    ...$itemLines,
    '*Total* : *' . format_rupiah((int)$order['total']) . '*',
  ];

  if ($order['note'] !== '') {
    $bodyLines[] = 'Catatan: ' . $order['note'];
  }

  $bodyLines[] = 'Bukti pembayaran terlampir.';

  $line = make_receipt_rule($bodyLines, '=');
  $dash = str_repeat('-', strlen($line));

  return implode("\n", [
    $line,
    center_receipt_line('*PESANAN BARU SNAPAN*', strlen($line)),
    $line,
    '*Kode*  : *' . $order['order_code'] . '*',
    '*Nama*  : ' . $order['buyer_name'],
    '*Kelas* : ' . $order['buyer_class'],
    '*Ambil* : ' . $order['pickup_time'],
    $dash,
    ...$itemLines,
    $dash,
    '*Total* : *' . format_rupiah((int)$order['total']) . '*',
    $order['note'] !== '' ? 'Catatan: ' . $order['note'] : 'Catatan: -',
    $line,
    'Bukti pembayaran terlampir.',
  ]);
}

function build_buyer_confirmation_message(array $order): string {
  $itemParts = [];
  foreach ($order['items'] as $item) {
    $itemParts[] = $item['qty'] . '× ' . $item['name'];
  }

  return implode("\n", [
    '🍱 *Pesanan masuk!*',
    '',
    'Halo *' . $order['buyer_name'] . '* (@' . $order['buyer_username'] . '),',
    '_Pesanan kamu sudah langsung diteruskan ke penjualnya nih_ ✅',
    '',
    '🧾 *Pesanan:* ' . implode(' + ', $itemParts),
    '',
    '🕐 *Ambil:* _' . $order['pickup_time'] . '_',
    '🔖 *Kode:* *' . $order['order_code'] . '*',
    '💰 *Total:* *' . format_rupiah((int)$order['total']) . '*',
    '',
    '_Tunjukkan kode ini saat mengambil pesanan ya._',
    '*Selamat makan!* 🥳',
  ]);
}

function call_whatsapp_bot(array $payload): array {
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

  $context = stream_context_create([
    'http' => [
      'method' => 'POST',
      'header' => implode("\r\n", [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json),
        'X-WA-Bot-Token: ' . WA_BOT_TOKEN,
      ]),
      'content' => $json,
      'timeout' => WA_BOT_TIMEOUT_SECONDS,
      'ignore_errors' => true,
    ],
  ]);

  $body = @file_get_contents(WA_BOT_ENDPOINT, false, $context);
  $decoded = is_string($body) ? json_decode($body, true) : null;
  if (is_array($decoded) && ($decoded['ok'] ?? false)) {
    return ['ok' => true, 'response' => $decoded];
  }

  $message = is_array($decoded) ? (string)($decoded['message'] ?? $decoded['error'] ?? '') : '';
  return ['ok' => false, 'error' => $message !== '' ? $message : 'Bot WhatsApp tidak merespons.'];
}

function get_uploaded_proof(): array {
  if (!isset($_FILES['proof']) || !is_array($_FILES['proof'])) {
    fail_json(422, 'Pilih gambar bukti pembayaran terlebih dahulu.');
  }

  $file = $_FILES['proof'];
  $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
  if ($error !== UPLOAD_ERR_OK) {
    fail_json(422, 'Upload bukti pembayaran gagal.');
  }

  $size = (int)($file['size'] ?? 0);
  if ($size < 1 || $size > PAYMENT_PROOF_MAX_BYTES) {
    fail_json(422, 'Ukuran bukti pembayaran maksimal 5 MB.');
  }

  $tmpName = (string)($file['tmp_name'] ?? '');
  if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    fail_json(422, 'File bukti pembayaran tidak valid.');
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = (string)$finfo->file($tmpName);
  $extensions = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
  ];

  if (!isset($extensions[$mime])) {
    fail_json(422, 'File harus berupa gambar JPG, PNG, WEBP, atau GIF.');
  }

  return [
    'tmp_name' => $tmpName,
    'original_name' => basename((string)($file['name'] ?? 'bukti-pembayaran')),
    'mime' => $mime,
    'size' => $size,
    'extension' => $extensions[$mime],
  ];
}

function read_checkout_draft(): array {
  $rawDraft = (string)($_POST['draft'] ?? '');
  $draft = json_decode($rawDraft, true);
  if (!is_array($draft)) {
    fail_json(422, 'Data checkout tidak valid.');
  }

  $items = $draft['items'] ?? null;
  if (!is_array($items) || count($items) < 1) {
    fail_json(422, 'Keranjang pesanan masih kosong.');
  }

  $requested = [];
  foreach ($items as $item) {
    $id = (int)($item['id'] ?? 0);
    $qty = (int)($item['qty'] ?? 0);
    if ($id < 1 || $qty < 1) {
      fail_json(422, 'Item pesanan tidak valid.');
    }
    $requested[$id] = ($requested[$id] ?? 0) + min($qty, 99);
  }

  return [
    'items' => $requested,
    'pickup_time' => mb_substr(trim((string)($draft['pickupTime'] ?? '')), 0, 80, 'UTF-8'),
    'note' => mb_substr(trim((string)($draft['note'] ?? '')), 0, 500, 'UTF-8'),
    'payment_method' => (string)($draft['paymentMethod'] ?? 'qris'),
  ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  fail_json(405, 'Method tidak diizinkan.');
}

start_session();
$user = current_user();
if (!$user) {
  fail_json(401, 'Silakan login terlebih dahulu.');
}

$proof = get_uploaded_proof();
$draft = read_checkout_draft();
if ($draft['payment_method'] !== 'qris') {
  fail_json(422, 'Metode pembayaran tidak valid.');
}

$conn = db();
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
  fail_json(422, 'Ada menu yang tidak ditemukan.');
}

$kantinId = null;
$sellerPhone = '';
$sellerName = '';
$kantinName = '';
$orderItems = [];
$total = 0;

foreach ($draft['items'] as $id => $qty) {
  $menu = $menus[(int)$id];
  $currentKantinId = (int)$menu['id_kantin'];
  if ($kantinId === null) {
    $kantinId = $currentKantinId;
    $sellerPhone = (string)$menu['no_telepon'];
    $sellerName = (string)$menu['nama_penjual'];
    $kantinName = (string)$menu['nama_kantin'];
  } elseif ($kantinId !== $currentKantinId) {
    fail_json(422, 'Satu checkout hanya boleh berisi item dari satu kantin.');
  }

  $stock = (int)$menu['sisa_stock'];
  if ($qty > $stock) {
    fail_json(422, 'Stok ' . str_replace('_', ' ', (string)$menu['nama_menu']) . ' tidak mencukupi.');
  }

  $price = (int)$menu['harga'];
  $subtotal = $price * $qty;
  $total += $subtotal;
  $orderItems[] = [
    'id' => (int)$id,
    'name' => mb_convert_case(str_replace('_', ' ', (string)$menu['nama_menu']), MB_CASE_TITLE, 'UTF-8'),
    'price' => $price,
    'qty' => $qty,
    'subtotal' => $subtotal,
  ];
}

$normalizedSellerPhone = normalize_whatsapp_number($sellerPhone);
if ($normalizedSellerPhone === '') {
  fail_json(422, 'Nomor WhatsApp penjual belum valid.');
}
$normalizedBuyerPhone = normalize_whatsapp_number((string)$user['no_telepon']);

$orderCode = generate_order_code($conn);
$year = date('Y');
$month = date('m');
$relativeDir = PAYMENT_PROOF_PUBLIC_PATH . '/' . $year . '/' . $month;
$absoluteDir = PAYMENT_PROOF_DIR . '/' . $year . '/' . $month;

if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
  fail_json(500, 'Folder upload bukti pembayaran gagal dibuat.');
}

$fileName = $orderCode . '.' . $proof['extension'];
$relativeProofPath = $relativeDir . '/' . $fileName;
$absoluteProofPath = $absoluteDir . '/' . $fileName;

if (!move_uploaded_file($proof['tmp_name'], $absoluteProofPath)) {
  fail_json(500, 'Bukti pembayaran gagal disimpan.');
}

$firstOrderId = 0;
$paymentId = 0;

try {
  $conn->begin_transaction();

  $orderStmt = $conn->prepare(
    "INSERT INTO order_pesanan
      (kode_pesanan, id_menu, id_user, jumlah, tanggal_pesanan, status_pesanan, waktu_pengambilan, catatan)
     VALUES (?, ?, ?, ?, CURDATE(), 'diproses', ?, ?)"
  );
  $stockStmt = $conn->prepare(
    'UPDATE menu SET sisa_stock = sisa_stock - ? WHERE id_menu = ? AND sisa_stock >= ?'
  );

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
      (kode_pesanan, id_order_pesanan, total_pembayaran, metode_pembayaran, status_pembayaran, bukti_pembayaran, bukti_original_name, bukti_mime_type, bukti_file_size, wa_status)
     VALUES (?, ?, ?, 'qris', 'menunggu_konfirmasi', ?, ?, ?, ?, 'pending')"
  );

  $paymentStmt->bind_param(
    'siisssi',
    $orderCode,
    $firstOrderId,
    $total,
    $relativeProofPath,
    $proof['original_name'],
    $proof['mime'],
    $proof['size']
  );
  $paymentStmt->execute();
  $paymentId = (int)$conn->insert_id;
  $paymentStmt->close();

  $conn->commit();
} catch (Throwable $e) {
  $conn->rollback();
  if (is_file($absoluteProofPath)) {
    @unlink($absoluteProofPath);
  }
  fail_json(500, 'Order gagal disimpan: ' . $e->getMessage());
}

$orderForMessage = [
  'order_code' => $orderCode,
  'buyer_name' => (string)$user['nama_lengkap'],
  'buyer_username' => (string)$user['username'],
  'buyer_class' => (string)$user['kelas_jurusan'],
  'buyer_phone' => (string)$user['no_telepon'],
  'kantin_name' => mb_convert_case(str_replace('_', ' ', $kantinName), MB_CASE_TITLE, 'UTF-8'),
  'seller_name' => $sellerName,
  'seller_phone' => $normalizedSellerPhone,
  'pickup_time' => $draft['pickup_time'] !== '' ? $draft['pickup_time'] : '-',
  'note' => $draft['note'],
  'items' => $orderItems,
  'total' => $total,
];

$message = build_receipt_message($orderForMessage);
$botResult = call_whatsapp_bot([
  'recipient_phone' => $normalizedSellerPhone,
  'order_code' => $orderCode,
  'message' => $message,
  'proof_absolute_path' => str_replace('\\', '/', $absoluteProofPath),
  'status_poll' => true,
  'ready_endpoint' => current_api_url('mark-order-ready.php'),
]);

$buyerMessage = build_buyer_confirmation_message($orderForMessage);
$buyerBotResult = $normalizedBuyerPhone !== ''
  ? call_whatsapp_bot([
      'recipient_phone' => $normalizedBuyerPhone,
      'order_code' => $orderCode,
      'message' => $buyerMessage,
    ])
  : ['ok' => false, 'error' => 'Nomor WhatsApp pembeli belum valid.'];

$waStatus = $botResult['ok'] ? 'sent' : 'failed';
$waError = $botResult['ok'] ? null : mb_substr((string)($botResult['error'] ?? 'Bot WhatsApp gagal.'), 0, 1000, 'UTF-8');
$buyerWaStatus = $buyerBotResult['ok'] ? 'sent' : 'failed';
$buyerWaError = $buyerBotResult['ok'] ? null : mb_substr((string)($buyerBotResult['error'] ?? 'Bot WhatsApp pembeli gagal.'), 0, 1000, 'UTF-8');

if ($waStatus === 'sent') {
  $updateStmt = $conn->prepare("UPDATE payment SET wa_status = 'sent', wa_error = NULL, wa_sent_at = NOW(), buyer_wa_status = ?, buyer_wa_error = ?, buyer_wa_sent_at = " . ($buyerWaStatus === 'sent' ? 'NOW()' : 'NULL') . " WHERE id_payment = ?");
  $updateStmt->bind_param('ssi', $buyerWaStatus, $buyerWaError, $paymentId);
} else {
  $updateStmt = $conn->prepare("UPDATE payment SET wa_status = 'failed', wa_error = ?, wa_sent_at = NULL, buyer_wa_status = ?, buyer_wa_error = ?, buyer_wa_sent_at = " . ($buyerWaStatus === 'sent' ? 'NOW()' : 'NULL') . " WHERE id_payment = ?");
  $updateStmt->bind_param('sssi', $waError, $buyerWaStatus, $buyerWaError, $paymentId);
}
$updateStmt->execute();
$updateStmt->close();

$manualUrl = 'https://wa.me/' . $normalizedSellerPhone . '?text=' . rawurlencode($message);

respond_json(200, [
  'ok' => true,
  'order_code' => $orderCode,
  'payment_id' => $paymentId,
  'total' => $total,
  'wa_status' => $waStatus,
  'wa_error' => $waError,
  'buyer_wa_status' => $buyerWaStatus,
  'buyer_wa_error' => $buyerWaError,
  'manual_whatsapp_url' => $manualUrl,
  'proof_path' => $relativeProofPath,
]);
