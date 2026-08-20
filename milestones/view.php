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
    'SELECT m.*, c.display_name AS creator_name, c.username AS creator_username
     FROM pm_milestones m
     LEFT JOIN users c ON c.id = m.created_by
     WHERE m.id = ?'
);
$stmt->execute([$id]);
$milestone = $stmt->fetch();
if (!$milestone) {
    flash('error', 'Not found.');
    redirect(APP_URL . '/milestones/index.php');
}

$pageTitle  = $milestone['title'];
$activePage = 'milestones';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><?= e($milestone['title']) ?></h1>
    <p><span class="pill pill--<?= e($milestone['status']) ?>"><?= e(str_replace('_', ' ', $milestone['status'])) ?></span></p>
  </div>
  <?php if (is_admin()): ?>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/milestones/edit.php?id=<?= (int)$milestone['id'] ?>" class="btn btn--outline">Edit</a>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <?php if ($milestone['notes']): ?>
  <span class="dl-label">Notes</span>
  <p class="dl-value"><?= e($milestone['notes']) ?></p>
  <?php endif; ?>

  <div class="detail-grid">
    <div>
      <span class="dl-label">Phase</span>
      <p class="dl-value"><?= e($milestone['phase'] ?: '—') ?></p>
    </div>
    <div>
      <span class="dl-label">Target date</span>
      <p class="dl-value"><?= format_date($milestone['target_date']) ?></p>
    </div>
    <div>
      <span class="dl-label">Created by</span>
      <p class="dl-value"><?= e($milestone['creator_name'] ?: $milestone['creator_username'] ?: '—') ?></p>
    </div>
    <div>
      <span class="dl-label">Created</span>
      <p class="dl-value"><?= e(date('j M Y', strtotime($milestone['created_at']))) ?></p>
    </div>
  </div>
</div>

<p><a href="<?= APP_URL ?>/milestones/index.php">&larr; Back to milestones</a></p>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
