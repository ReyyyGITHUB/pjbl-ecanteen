<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

start_session();
if (current_user()) {
  redirect_after_auth('kantin');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim((string)($_POST['username'] ?? ''));
  $password = (string)($_POST['password'] ?? '');

  if ($username === '' || $password === '') {
    $error = 'Username dan password wajib diisi.';
  } else {
    $row = find_user_by_username($username);
    if (!$row || (string)$row['password'] !== $password) {
      $error = 'Username atau password salah.';
    } else {
      login_user($row);
      redirect_after_auth('kantin');
    }
  }
}
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
    <link rel="stylesheet" href="assets/css/auth.css" />
    <title>Login - E-Canteen</title>
  </head>
  <body>
    <main class="auth-page auth-page-plain">
      <section class="auth-plain" aria-label="Login">
        <header class="auth-plain-head">
          <a class="auth-plain-logo" href="index.html" aria-label="Kembali ke Beranda">
            <img class="auth-plain-logo-mark" src="assets/img/figma/logo-mark.png" alt="" />
            <span class="auth-plain-logo-name">E-Canteen</span>
          </a>
          <h1 class="auth-plain-title">Login dulu ya.</h1>
          <p class="auth-plain-subtitle">Biar bisa pesan tanpa ribet.</p>
        </header>

        <form class="auth-form auth-form-plain" method="post" action="login">
          <div class="field">
            <div class="field-control field-control-plain">
              <span class="field-icon field-icon-plain" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
                  <path d="M12 12.25a4.25 4.25 0 1 0-4.25-4.25A4.25 4.25 0 0 0 12 12.25Z" stroke="currentColor" stroke-width="1.7"/>
                  <path d="M4.5 20.25c1.7-4 13.3-4 15 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
              </span>
              <input class="field-input field-input-plain field-input-plain-icon" name="username" type="text" autocomplete="username" required placeholder="@username" />
            </div>
          </div>

          <div class="field">
            <div class="field-control field-control-plain">
              <span class="field-icon field-icon-plain" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
                  <path d="M7.75 10.25V8.5a4.25 4.25 0 0 1 8.5 0v1.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                  <path d="M7 10.25h10A2.75 2.75 0 0 1 19.75 13v4A2.75 2.75 0 0 1 17 19.75H7A2.75 2.75 0 0 1 4.25 17v-4A2.75 2.75 0 0 1 7 10.25Z" stroke="currentColor" stroke-width="1.7"/>
                </svg>
              </span>
              <input id="login-password" class="field-input field-input-plain field-input-plain-icon" name="password" type="password" autocomplete="current-password" required placeholder="Password tanpa spasi" />
              <button class="field-action" type="button" data-toggle-password aria-controls="login-password" aria-pressed="false" aria-label="Tampilkan password">
                <svg class="field-action-eye" viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
                  <path d="M2.75 12s3.3-7 9.25-7 9.25 7 9.25 7-3.3 7-9.25 7-9.25-7-9.25-7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                  <path d="M12 15.25A3.25 3.25 0 1 0 12 8.75a3.25 3.25 0 0 0 0 6.5Z" stroke="currentColor" stroke-width="1.7"/>
                </svg>
              </button>
            </div>
          </div>

          <p class="auth-error" role="alert" aria-live="polite"><?= htmlspecialchars($error) ?></p>

          <div class="auth-plain-actions auth-plain-actions-cta">
            <a class="auth-secondary auth-plain-back" href="index.html">Back</a>
            <button class="auth-btn auth-plain-submit" type="submit">Login</button>
          </div>

          <div class="auth-plain-links auth-plain-links-center">
            <span>Belum punya akun?</span>
            <a class="auth-link auth-link-plain" href="register">Buat akun</a>
          </div>
        </form>
      </section>
    </main>
    <script src="assets/js/auth-ui.js" defer></script>
  </body>
</html>
