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
  start_session();
  $_SESSION[SESSION_KEY] = [
    'id_user' => (int)$userRow['id_user'],
    'username' => (string)$userRow['username'],
    'nama_lengkap' => (string)$userRow['nama_lengkap'],
    'kelas_jurusan' => (string)$userRow['kelas_jurusan'],
    'no_telepon' => (string)$userRow['no_telepon'],
  ];
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
  $stmt = $conn->prepare('SELECT * FROM `user` WHERE username = ? LIMIT 1');
  $stmt->bind_param('s', $username);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row ?: null;
}
