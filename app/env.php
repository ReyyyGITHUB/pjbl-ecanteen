<?php
declare(strict_types=1);

function load_env_file(?string $path = null): void {
  static $loaded = false;
  if ($loaded) return;
  $loaded = true;

  $path = $path ?: dirname(__DIR__) . '/.env';
  if (!is_file($path) || !is_readable($path)) return;

  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if (!is_array($lines)) return;

  foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
      continue;
    }

    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    if ($key === '') continue;

    if (
      (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
      (str_starts_with($value, "'") && str_ends_with($value, "'"))
    ) {
      $value = substr($value, 1, -1);
    }

    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
    if (getenv($key) === false) {
      putenv($key . '=' . $value);
    }
  }
}

function env_value(string $key, ?string $default = null): ?string {
  load_env_file();

  $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
  if ($value === false || $value === null || $value === '') {
    return $default;
  }

  return (string)$value;
}
