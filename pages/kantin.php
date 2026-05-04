<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_login('kantin');
$user = current_user();
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@700&family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/styles.css" />
    <title>Kantin - E-Canteen</title>
  </head>
  <body>
    <main style="padding: 32px 16px; max-width: 960px; margin: 0 auto;">
      <h1 style="font-family: var(--font-display); font-size: 32px; line-height: 1.1; margin: 0 0 12px;">
        Kamu sudah masuk ke halaman Kantin
      </h1>
      <p style="margin: 0; color: #4a4a4a; line-height: 1.6;">
        Halo, <strong><?= htmlspecialchars((string)$user['nama_lengkap']) ?></strong> (<?= htmlspecialchars((string)$user['username']) ?>)
      </p>
      <p style="margin: 16px 0 0;">
        <a href="index.html" style="color: var(--primary); font-weight: 600;">Kembali ke Beranda</a>
        <span style="margin: 0 10px; color: #ccc;">|</span>
        <a href="logout" style="color: #c62828; font-weight: 600;">Logout</a>
      </p>
    </main>
  </body>
</html>
