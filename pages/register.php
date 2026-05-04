<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';

start_session();
if (current_user()) {
  redirect_after_auth('kantin');
}

$step = (int)($_GET['step'] ?? ($_POST['step'] ?? 1));
$step = ($step === 2) ? 2 : 1;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($step === 1) {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['password_confirm'] ?? '');

    if (strlen($username) < 3) {
      $error = 'Username minimal 3 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
      $error = 'Username hanya boleh huruf, angka, dan underscore (tanpa spasi/simbol).';
    } elseif (strlen($password) < 6) {
      $error = 'Password minimal 6 karakter.';
    } elseif (preg_match('/\s/', $password)) {
      $error = 'Password tidak boleh mengandung spasi.';
    } elseif ($password !== $confirm) {
      $error = 'Konfirmasi password tidak sama.';
    } elseif (find_user_by_username($username)) {
      $error = 'Username sudah dipakai.';
    } else {
      $_SESSION['register_step1'] = [
        'username' => $username,
        'password' => $password, // per request: no hashing
      ];
      header('Location: register?step=2');
      exit;
    }
  } else {
    $step1 = $_SESSION['register_step1'] ?? null;
    if (!$step1) {
      header('Location: register');
      exit;
    }

    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $kelas = trim((string)($_POST['kelas'] ?? ''));
    $jurusan = trim((string)($_POST['jurusan'] ?? ''));
    $noKelas = trim((string)($_POST['no_kelas'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));

    if ($fullName === '') {
      $error = 'Nama lengkap wajib diisi.';
    } elseif ($kelas === '' || $jurusan === '' || $noKelas === '') {
      $error = 'Kelas & jurusan wajib lengkap.';
    } elseif (strlen(preg_replace('/\D+/', '', $phone)) <= 12) {
      $error = 'Nomor telepon minimal 13 digit.';
    } else {
      $kelasJurusan = strtolower($kelas . '_' . $jurusan . '_' . $noKelas);

      $conn = db();
      try {
        $stmt = $conn->prepare('INSERT INTO `user` (nama_lengkap, kelas_jurusan, no_telepon, username, password) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $fullName, $kelasJurusan, $phone, $step1['username'], $step1['password']);
        $stmt->execute();
        $stmt->close();

        unset($_SESSION['register_step1']);
        $row = find_user_by_username($step1['username']);
        if ($row) login_user($row);
        redirect_after_auth('kantin');
      } catch (mysqli_sql_exception $e) {
        $error = 'Gagal membuat akun: ' . $e->getMessage();
      }
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
    <title>Register - E-Canteen</title>
  </head>
  <body>
    <main class="auth-page">
      <section class="auth-card" aria-label="Buat akun">
        <header class="auth-head">
          <div class="auth-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" aria-hidden="true">
              <path d="M12 12.25a4.25 4.25 0 1 0-4.25-4.25A4.25 4.25 0 0 0 12 12.25Z" stroke="currentColor" stroke-width="1.7"/>
              <path d="M4.5 20.25c1.7-4 13.3-4 15 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
          </div>
          <h1 class="auth-title">Buat Akun Baru</h1>
        </header>

        <div class="stepper">
          <p class="stepper-text">Langkah <?= $step ?> dari 2</p>
          <div class="stepper-dots" aria-hidden="true">
            <span class="stepper-dot <?= $step === 1 ? 'stepper-dot-active' : '' ?>"></span>
            <span class="stepper-dot <?= $step === 2 ? 'stepper-dot-active' : '' ?>"></span>
          </div>
        </div>

        <?php if ($step === 1): ?>
          <form class="auth-form" method="post" action="register">
            <input type="hidden" name="step" value="1" />
            <label class="field">
              <span class="field-label">Username</span>
              <div class="field-control">
                <span class="field-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                    <path d="M12 12.25a4.25 4.25 0 1 0-4.25-4.25A4.25 4.25 0 0 0 12 12.25Z" stroke="currentColor" stroke-width="1.7"/>
                    <path d="M4.5 20.25c1.7-4 13.3-4 15 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                  </svg>
                </span>
                <input class="field-input" name="username" type="text" autocomplete="username" required placeholder="Contoh: @rayhan01" />
              </div>
            </label>

            <label class="field">
              <span class="field-label">Password</span>
              <div class="field-control">
                <span class="field-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                    <path d="M7.75 10.25V8.5a4.25 4.25 0 0 1 8.5 0v1.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    <path d="M7 10.25h10A2.75 2.75 0 0 1 19.75 13v4A2.75 2.75 0 0 1 17 19.75H7A2.75 2.75 0 0 1 4.25 17v-4A2.75 2.75 0 0 1 7 10.25Z" stroke="currentColor" stroke-width="1.7"/>
                  </svg>
                </span>
                <input id="reg-password" class="field-input" name="password" type="password" autocomplete="new-password" required placeholder="Minimal 6 karakter, tanpa spasi" />
                <button class="field-action" type="button" data-toggle-password aria-controls="reg-password" aria-pressed="false" aria-label="Tampilkan password">
                  <svg class="field-action-eye" viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
                    <path d="M2.75 12s3.3-7 9.25-7 9.25 7 9.25 7-3.3 7-9.25 7-9.25-7-9.25-7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                    <path d="M12 15.25A3.25 3.25 0 1 0 12 8.75a3.25 3.25 0 0 0 0 6.5Z" stroke="currentColor" stroke-width="1.7"/>
                  </svg>
                </button>
              </div>
            </label>

            <label class="field">
              <span class="field-label">Konfirmasi Password</span>
              <div class="field-control">
                <span class="field-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                    <path d="M7.75 10.25V8.5a4.25 4.25 0 0 1 8.5 0v1.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    <path d="M7 10.25h10A2.75 2.75 0 0 1 19.75 13v4A2.75 2.75 0 0 1 17 19.75H7A2.75 2.75 0 0 1 4.25 17v-4A2.75 2.75 0 0 1 7 10.25Z" stroke="currentColor" stroke-width="1.7"/>
                    <path d="M9.6 14.1 11 15.5l3.4-3.4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
                <input id="reg-confirm" class="field-input" name="password_confirm" type="password" autocomplete="new-password" required placeholder="Ketik ulang password yang sama" />
                <button class="field-action" type="button" data-toggle-password aria-controls="reg-confirm" aria-pressed="false" aria-label="Tampilkan password">
                  <svg class="field-action-eye" viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
                    <path d="M2.75 12s3.3-7 9.25-7 9.25 7 9.25 7-3.3 7-9.25 7-9.25-7-9.25-7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                    <path d="M12 15.25A3.25 3.25 0 1 0 12 8.75a3.25 3.25 0 0 0 0 6.5Z" stroke="currentColor" stroke-width="1.7"/>
                  </svg>
                </button>
              </div>
            </label>

            <p class="auth-error" role="alert" aria-live="polite"><?= htmlspecialchars($error) ?></p>

            <button class="auth-btn" type="submit">Lanjut</button>
          </form>
        <?php else: ?>
          <form class="auth-form" method="post" action="register?step=2">
            <input type="hidden" name="step" value="2" />

            <h2 class="auth-section-title">Lengkapi Profil Kamu!</h2>
            <p class="auth-subtitle">Langkah 2 dari 2</p>

            <label class="field">
              <span class="field-label">Nama Lengkap</span>
              <div class="field-control">
                <span class="field-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                    <path d="M12 12.25a4.25 4.25 0 1 0-4.25-4.25A4.25 4.25 0 0 0 12 12.25Z" stroke="currentColor" stroke-width="1.7"/>
                    <path d="M4.5 20.25c1.7-4 13.3-4 15 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                  </svg>
                </span>
                <input class="field-input" name="full_name" type="text" autocomplete="name" required placeholder="Contoh: Rayhan Pratama" />
              </div>
            </label>

            <div class="field">
              <span class="field-label">Kelas & Jurusan Kamu</span>
              <div class="triple">
                <select class="field-input" name="kelas" required>
                  <option value="" selected disabled>Kelas</option>
                  <option value="x">X</option>
                  <option value="xi">XI</option>
                  <option value="xii">XII</option>
                </select>
                <select class="field-input" name="jurusan" required>
                  <option value="" selected disabled>Jurusan</option>
                  <option value="pplg">PPLG</option>
                  <option value="tkj">TJKT</option>
                  <option value="dkv">DKV</option>
                </select>
                <select class="field-input" name="no_kelas" required>
                  <option value="" selected disabled>No.</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                </select>
              </div>
            </div>

            <label class="field">
              <span class="field-label">Nomor Telepon</span>
              <div class="field-control">
                <span class="field-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                    <path d="M8.2 6.75 6.9 5.45a2 2 0 0 0-2.83 0l-.7.7a2.1 2.1 0 0 0-.5 2.2c1.3 3.8 4.2 8.3 8.4 12.4s8.6 7.1 12.4 8.4a2.1 2.1 0 0 0 2.2-.5l.7-.7a2 2 0 0 0 0-2.83l-1.3-1.3a2 2 0 0 0-2.1-.47l-2.2.73a2 2 0 0 1-2.08-.5l-2.8-2.8a2 2 0 0 1-.5-2.08l.73-2.2A2 2 0 0 0 15.9 14l-1.3-1.3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
                <input class="field-input" name="phone" type="tel" autocomplete="tel" inputmode="tel" required placeholder="Contoh: 0812345678901" />
              </div>
            </label>

            <p class="auth-error" role="alert" aria-live="polite"><?= htmlspecialchars($error) ?></p>

            <div class="auth-row">
              <a class="auth-secondary" href="register">Kembali</a>
              <button class="auth-btn" type="submit">Buat Akun</button>
            </div>
          </form>
        <?php endif; ?>

        <a class="auth-home" href="index.html" style="margin-top: 12px;">Kembali ke Beranda</a>
      </section>
    </main>
    <script src="assets/js/auth-ui.js" defer></script>
  </body>
</html>
