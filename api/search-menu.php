<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $query = trim((string)($_GET['q'] ?? ''));

  if ($query === '') {
    echo json_encode([
      'success' => true,
      'data' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $conn = db();
  $search = $query . '%';

  $stmt = $conn->prepare(
    'SELECT m.id_menu, m.nama_menu, k.nama_kantin
     FROM menu m
     JOIN kantin k ON k.id_kantin = m.id_kantin
     WHERE m.nama_menu LIKE ?
     ORDER BY m.nama_menu ASC, k.nama_kantin ASC
     LIMIT 8'
  );
  $stmt->bind_param('s', $search);
  $stmt->execute();
  $result = $stmt->get_result();

  $menus = [];
  while ($row = $result->fetch_assoc()) {
    $menus[] = [
      'id_menu' => (int)$row['id_menu'],
      'nama_menu' => (string)$row['nama_menu'],
      'nama_kantin' => (string)$row['nama_kantin'],
      'gambar_url' => 'assets/img/kantin/kantin-make.png',
    ];
  }

  $stmt->close();

  echo json_encode([
    'success' => true,
    'data' => $menus,
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'Gagal mengambil data menu.',
  ], JSON_UNESCAPED_UNICODE);
}
