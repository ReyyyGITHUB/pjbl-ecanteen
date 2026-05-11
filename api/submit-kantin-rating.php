<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

header('Content-Type: application/json; charset=utf-8');

function rating_respond_json(int $status, array $payload): void {
  http_response_code($status);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

function rating_fail_json(int $status, string $message): void {
  rating_respond_json($status, ['ok' => false, 'message' => $message]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  rating_fail_json(405, 'Method tidak diizinkan.');
}

start_session();
$user = current_user();
if (!$user || (int)($user['id_user'] ?? 0) < 1) {
  rating_fail_json(401, 'Silakan login terlebih dahulu.');
}

if (!table_exists('rating_kantin')) {
  rating_fail_json(500, 'Tabel rating_kantin belum tersedia.');
}

$raw = file_get_contents('php://input');
$payload = json_decode(is_string($raw) ? $raw : '', true);
if (!is_array($payload)) {
  rating_fail_json(422, 'Payload rating tidak valid.');
}

$orderCode = strtoupper(trim((string)($payload['order_code'] ?? '')));
$rating = (int)($payload['rating'] ?? 0);

if (!preg_match('/^SNAPAN-\d{3}$/', $orderCode)) {
  rating_fail_json(422, 'Kode pesanan tidak valid.');
}

if ($rating < 1 || $rating > 5) {
  rating_fail_json(422, 'Rating harus antara 1 sampai 5.');
}

$conn = db();
$stmt = $conn->prepare(
  'SELECT
     k.id_kantin,
     k.nama_kantin
   FROM order_pesanan op
   INNER JOIN menu m ON m.id_menu = op.id_menu
   INNER JOIN kantin k ON k.id_kantin = m.id_kantin
   WHERE op.kode_pesanan = ? AND op.id_user = ?
   GROUP BY k.id_kantin, k.nama_kantin
   LIMIT 1'
);
$userId = (int)$user['id_user'];
$stmt->bind_param('si', $orderCode, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc() ?: null;
$stmt->close();

if (!$order) {
  rating_fail_json(404, 'Pesanan tidak ditemukan untuk akun ini.');
}

$kantinId = (int)$order['id_kantin'];

$upsert = $conn->prepare(
  'INSERT INTO rating_kantin (id_kantin, id_user, kode_pesanan, rating)
   VALUES (?, ?, ?, ?)
   ON DUPLICATE KEY UPDATE
     rating = VALUES(rating),
     updated_at = NOW()'
);
$upsert->bind_param('iisi', $kantinId, $userId, $orderCode, $rating);
$upsert->execute();
$upsert->close();

$avgStmt = $conn->prepare(
  'SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_rating
   FROM rating_kantin
   WHERE id_kantin = ?'
);
$avgStmt->bind_param('i', $kantinId);
$avgStmt->execute();
$avgRow = $avgStmt->get_result()->fetch_assoc() ?: ['avg_rating' => 0, 'total_rating' => 0];
$avgStmt->close();

rating_respond_json(200, [
  'ok' => true,
  'message' => 'Rating toko berhasil disimpan.',
  'rating' => $rating,
  'avg_rating' => round((float)($avgRow['avg_rating'] ?? 0), 1),
  'total_rating' => (int)($avgRow['total_rating'] ?? 0),
  'kantin_name' => (string)$order['nama_kantin'],
]);
