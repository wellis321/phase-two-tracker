<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/includes/db.php';

http_response_code(404);

$pageTitle = 'Page not found';
require __DIR__ . '/includes/layout/header.php';
?>

<div class="card" style="max-width:520px; margin:3rem auto; text-align:center;">
  <h1 style="margin-top:0;">Page not found</h1>
  <p style="color:var(--muted);">That page doesn't exist, or may have been moved.</p>
  <p style="margin-top:1.5rem;">
    <a class="btn btn--primary" href="<?= APP_URL ?>/<?= is_logged_in() ? 'index.php' : 'login.php' ?>">
      <?= is_logged_in() ? 'Back to dashboard' : 'Go to sign in' ?>
    </a>
  </p>
</div>

<?php require __DIR__ . '/includes/layout/footer.php'; ?>
