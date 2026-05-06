<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function start_session(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
}

function current_user(): ?array {
  start_session();
  return $_SESSION[SESSION_KEY] ?? null;
}

function require_login(string $redirectTo = 'kantin'): void {
  start_session();
  if (!current_user()) {
    $_SESSION['redirect_to'] = $redirectTo;
    header('Location: login');
    exit;
  }
}

function login_user(array $userRow): void {
  login_account($userRow, 'user');
}

function login_seller(array $sellerRow): void {
  login_account($sellerRow, 'seller');
}

function login_account(array $row, string $type): void {
  start_session();
  $_SESSION[SESSION_KEY] = [
    'account_type' => $type,
    'id_user' => (int)($row['id_user'] ?? $row['id_penjual'] ?? 0),
    'id_penjual' => (int)($row['id_penjual'] ?? 0),
    'username' => (string)$row['username'],
    'nama_lengkap' => (string)($row['nama_lengkap'] ?? $row['nama_penjual'] ?? ''),
    'kelas_jurusan' => (string)($row['kelas_jurusan'] ?? 'penjual'),
    'no_telepon' => (string)$row['no_telepon'],
    'role' => (string)($row['role'] ?? $type),
  ];
}

function normalize_kantin_slug(string $value): string {
  $value = trim(mb_strtolower($value, 'UTF-8'));
  $value = str_replace([' ', '/'], '-', $value);
  $value = preg_replace('/[^a-z0-9_-]+/u', '-', $value) ?? $value;
  $value = preg_replace('/-+/', '-', $value) ?? $value;
  return trim($value, '-');
}

function seller_dashboard_route(array $sellerRow): string {
  $sellerId = (int)($sellerRow['id_penjual'] ?? $sellerRow['id_user'] ?? 0);
  if ($sellerId <= 0) {
    return 'dashboard';
  }

  $conn = db();
  $stmt = $conn->prepare(
    'SELECT nama_kantin
     FROM kantin
     WHERE id_penjual = ?
     ORDER BY id_kantin ASC
     LIMIT 1'
  );
  $stmt->bind_param('i', $sellerId);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  $slug = normalize_kantin_slug((string)($row['nama_kantin'] ?? 'dashboard'));
  return $slug !== '' ? 'dashboard/' . $slug : 'dashboard';
}

function logout_user(): void {
  start_session();
  unset($_SESSION[SESSION_KEY]);
}

function redirect_after_auth(string $fallback = 'kantin'): void {
  start_session();
  $to = $_SESSION['redirect_to'] ?? $fallback;
  unset($_SESSION['redirect_to']);
  header('Location: ' . $to);
  exit;
}

function find_user_by_username(string $username): ?array {
  $conn = db();
  $stmt = $conn->prepare('SELECT id_user, nama_lengkap, kelas_jurusan, no_telepon, username, password, role FROM `user` WHERE username = ? LIMIT 1');
  $stmt->bind_param('s', $username);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ?: null;
}

function find_seller_by_username(string $username): ?array {
  $conn = db();
  $stmt = $conn->prepare('SELECT id_penjual, nama_penjual, no_telepon, username, password, role FROM `penjual` WHERE username = ? LIMIT 1');
  $stmt->bind_param('s', $username);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ?: null;
}

function find_account_by_username(string $username): ?array {
  return find_user_by_username($username) ?: find_seller_by_username($username);
}
