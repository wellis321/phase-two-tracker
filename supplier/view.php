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
    'SELECT s.*, o.display_name AS owner_name, o.username AS owner_username,
            c.display_name AS creator_name, c.username AS creator_username
     FROM pm_supplier_activities s
     LEFT JOIN users o ON o.id = s.owner_user_id
     LEFT JOIN users c ON c.id = s.created_by
     WHERE s.id = ?'
);
$stmt->execute([$id]);
$activity = $stmt->fetch();
if (!$activity) {
    flash('error', 'Not found.');
    redirect(APP_URL . '/supplier/index.php');
}

$pageTitle  = $activity['title'];
$activePage = 'supplier';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><?= e($activity['title']) ?></h1>
    <p><?= e($activity['supplier']) ?> · <span class="pill pill--<?= e($activity['status']) ?>"><?= e(str_replace('_', ' ', $activity['status'])) ?></span></p>
  </div>
  <?php if (is_admin()): ?>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/supplier/edit.php?id=<?= (int)$activity['id'] ?>" class="btn btn--outline">Edit</a>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <?php if ($activity['description']): ?>
  <span class="dl-label">Description</span>
  <p class="dl-value"><?= e($activity['description']) ?></p>
  <?php endif; ?>

  <div class="detail-grid">
    <div>
      <span class="dl-label">Owner</span>
      <p class="dl-value"><?= e($activity['owner_name'] ?: $activity['owner_username'] ?: 'Unassigned') ?></p>
    </div>
    <div>
      <span class="dl-label">Due date</span>
      <p class="dl-value"><?= format_date($activity['due_date']) ?></p>
    </div>
    <div>
      <span class="dl-label">Created by</span>
      <p class="dl-value"><?= e($activity['creator_name'] ?: $activity['creator_username'] ?: '—') ?></p>
    </div>
    <div>
      <span class="dl-label">Created</span>
      <p class="dl-value"><?= e(date('j M Y', strtotime($activity['created_at']))) ?></p>
    </div>
  </div>
</div>

<p><a href="<?= APP_URL ?>/supplier/index.php">&larr; Back to supplier activity</a></p>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
