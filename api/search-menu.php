<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/db.php';

header('Content-Type: application/json; charset=utf-8');

function search_menu_display_name(string $value): string {
  $value = str_replace('_', ' ', trim($value));
  $value = preg_replace('/\s+/', ' ', $value) ?? $value;
  return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
}

function search_menu_image(string $gambar, string $menuName): string {
  $gambar = trim($gambar);
  if ($gambar !== '' && $gambar !== 'gambar.jpg' && $gambar !== 'gambar.png') {
    return $gambar;
  }

  $normalized = strtolower(str_replace([' ', '-', '/', '+'], '_', trim($menuName)));
  $normalized = preg_replace('/_+/', '_', $normalized) ?? $normalized;

  $fallbackMap = [
    'nasi_geprek' => 'assets/img/kantin-1/menu-ayam.png',
    'ayam_geprek_nasi_putih' => 'assets/img/kantin-1/menu-ayam.png',
    'cireng' => 'assets/img/kantin-1/menu-cireng.png',
    'es_teh' => 'assets/img/kantin-1/menu-esteh.png',
    'es_teh_manis' => 'assets/img/kantin-1/menu-esteh.png',
    'gorengan' => 'assets/img/kantin-1/menu-mendoan.png',
    'mendoan' => 'assets/img/kantin-1/menu-mendoan.png',
    'teajus' => 'assets/img/kantin-1/menu-goodday.png',
    'kopi_goodday' => 'assets/img/kantin-1/menu-goodday.png',
    'soto' => 'assets/img/kantin-1/menu-soto.png',
    'soto_ayam' => 'assets/img/kantin-1/menu-soto.png',
    'risolmayo_panas' => 'assets/img/kantin-1/menu-risol.png',
    'risoles_mayo_panas' => 'assets/img/kantin-1/menu-risol.png',
  ];

  return $fallbackMap[$normalized] ?? 'assets/img/kantin-1/menu-ayam.png';
}

function search_menu_target_url(int $kantinId): string {
  return $kantinId === 1 ? 'kantin-1' : 'kantin';
}

function search_menu_payload(array $row): array {
  $menuName = (string)$row['nama_menu'];
  $kantinId = (int)$row['id_kantin'];

  return [
    'id_menu' => (int)$row['id_menu'],
    'nama_menu' => search_menu_display_name($menuName),
    'nama_kantin' => search_menu_display_name((string)$row['nama_kantin']),
    'harga' => (int)$row['harga'],
    'sisa_stock' => (int)$row['sisa_stock'],
    'gambar_url' => search_menu_image((string)$row['gambar'], $menuName),
    'target_url' => search_menu_target_url($kantinId),
  ];
}

try {
  $query = trim((string)($_GET['q'] ?? ''));
  $mode = trim((string)($_GET['mode'] ?? ''));

  $conn = db();

  if ($query === '' || $mode === 'recommend') {
    $stmt = $conn->prepare(
      'SELECT ranked.*
       FROM (
         SELECT
           m.id_menu,
           m.id_kantin,
           m.nama_menu,
           m.harga,
           m.sisa_stock,
           m.gambar,
           k.nama_kantin,
           COALESCE(SUM(op.jumlah), 0) AS total_terjual
         FROM menu m
         JOIN kantin k ON k.id_kantin = m.id_kantin
         LEFT JOIN order_pesanan op ON op.id_menu = m.id_menu
         WHERE m.sisa_stock > 0
         GROUP BY m.id_menu, m.id_kantin, m.nama_menu, m.harga, m.sisa_stock, m.gambar, k.nama_kantin
         ORDER BY total_terjual DESC, m.id_menu ASC
         LIMIT 12
       ) ranked
       ORDER BY RAND()
       LIMIT 6'
    );
  } else {
    $search = $query . '%';
    $normalizedSearch = str_replace(' ', '_', $query) . '%';
    $stmt = $conn->prepare(
      'SELECT
         m.id_menu,
         m.id_kantin,
         m.nama_menu,
         m.harga,
         m.sisa_stock,
         m.gambar,
         k.nama_kantin
       FROM menu m
       JOIN kantin k ON k.id_kantin = m.id_kantin
       WHERE m.sisa_stock > 0
         AND (m.nama_menu LIKE ? OR m.nama_menu LIKE ? OR REPLACE(m.nama_menu, "_", " ") LIKE ?)
       ORDER BY m.nama_menu ASC, k.nama_kantin ASC
       LIMIT 8'
    );
    $stmt->bind_param('sss', $search, $normalizedSearch, $search);
  }

  $stmt->execute();
  $result = $stmt->get_result();

  $menus = [];
  while ($row = $result->fetch_assoc()) {
    $menus[] = search_menu_payload($row);
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
