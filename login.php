<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect(APP_URL . '/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid form submission. Please try again.';
    } elseif (is_rate_limited(client_ip())) {
        $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } else {
        $user   = trim($_POST['username'] ?? '');
        $pass   = $_POST['password'] ?? '';
        if (attempt_login($user, $pass)) {
            clear_attempts(client_ip());
            $next = $_GET['next'] ?? '';
            if ($next === '' || !str_starts_with($next, APP_URL . '/')) {
                $next = APP_URL . '/index.php';
            }
            redirect($next);
        } else {
            record_failed_attempt(client_ip());
            $error = 'Incorrect username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Sign in — Phase 2 Delivery Tracker</title>
<?php require __DIR__ . '/includes/layout/favicon.php'; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset_url('/assets/css/style.css') ?>">
</head>
<body>
<?php require __DIR__ . '/includes/layout/notice-banner.php'; ?>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <span class="org">Repairs Delivery — Phase 2</span>
      <span class="app">Programme Tracker</span>
    </div>
    <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
    <?= render_flash() ?>
    <form method="POST" action="<?= e($_SERVER['REQUEST_URI']) ?>">
      <?= csrf_field() ?>
      <div class="field" style="margin-bottom:1rem;">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus autocomplete="username">
      </div>
      <div class="field" style="margin-bottom:1.25rem;">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn--primary" style="width:100%;">Sign in</button>
    </form>
    <p class="login-note">Uses your existing SOR System sign-in — no separate account needed.</p>
  </div>
</div>
</body>
</html>
