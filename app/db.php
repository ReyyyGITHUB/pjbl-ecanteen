<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): mysqli {
  static $conn = null;
  if ($conn instanceof mysqli) return $conn;

  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
  } catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die('DB connection failed: ' . htmlspecialchars($e->getMessage()));
  }
  return $conn;
}

function table_exists(string $tableName): bool {
  $conn = db();
  $stmt = $conn->prepare(
    'SELECT 1
     FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = ?
       AND TABLE_NAME = ?
     LIMIT 1'
  );
  $databaseName = DB_NAME;
  $stmt->bind_param('ss', $databaseName, $tableName);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_row();
  $stmt->close();

  return $row !== null;
}
