<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

require_login('kantin');
$user = current_user();
if (($user['role'] ?? '') !== 'seller') {
  header('Location: kantin');
  exit;
}

$kantinSlug = trim((string)($_GET['kantin'] ?? 'dashboard'));
$kantinTitle = $kantinSlug !== '' ? str_replace(['-', '_'], ' ', $kantinSlug) : 'dashboard';
$kantinTitle = mb_convert_case($kantinTitle, MB_CASE_TITLE, 'UTF-8');
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
    <title>Dashboard <?= htmlspecialchars($kantinTitle) ?> - E-Canteen</title>
  </head>
  <body>
    <main class="auth-page auth-page-plain">
      <section class="auth-plain" aria-label="Dashboard Seller">
        <header class="auth-plain-head">
          <h1 class="auth-plain-title">Dashboard <?= htmlspecialchars($kantinTitle) ?></h1>
          <p class="auth-plain-subtitle">Halaman penjual sedang disiapkan.</p>
        </header>
      </section>
    </main>
  </body>
</html>
