<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$db = db();

$id   = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare(
    'SELECT a.*, u.display_name, u.username
     FROM pm_agendas a
     LEFT JOIN users u ON u.id = a.created_by_user_id
     WHERE a.id = ?'
);
$stmt->execute([$id]);
$agenda = $stmt->fetch();
if (!$agenda) {
    flash('error', 'Agenda not found.');
    redirect(APP_URL . '/agenda/index.php');
}

$pageTitle  = $agenda['title'];
$activePage = 'agendas';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><?= e($agenda['title']) ?></h1>
    <p class="status-hero-meta">
      Meeting date <?= format_date($agenda['meeting_date']) ?>
      <?php if ($agenda['location']): ?>&middot; <?= e($agenda['location']) ?><?php endif; ?>
      &middot; Published <?= e(date('j M Y, g:ia', strtotime($agenda['created_at']))) ?>
      by <?= e($agenda['display_name'] ?: $agenda['username'] ?: 'unknown') ?>
    </p>
  </div>
</div>

<?php if ($agenda['attendees']): ?>
<div class="card" style="margin-bottom:1rem;">
  <span class="dl-label">Attendees</span>
  <p class="dl-value" style="margin-bottom:0;"><?= e($agenda['attendees']) ?></p>
</div>
<?php endif; ?>

<div class="card">
  <pre class="agenda-content"><?= e($agenda['content']) ?></pre>
</div>

<p class="back-nav"><a class="back-link" href="<?= APP_URL ?>/agenda/index.php"><?= icon_arrow_left() ?> Back to agendas</a></p>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
