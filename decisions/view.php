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
    'SELECT d.*, o.display_name AS owner_name, o.username AS owner_username,
            c.display_name AS creator_name, c.username AS creator_username
     FROM pm_decisions d
     LEFT JOIN users o ON o.id = d.decision_owner_user_id
     LEFT JOIN users c ON c.id = d.created_by
     WHERE d.id = ?'
);
$stmt->execute([$id]);
$decision = $stmt->fetch();
if (!$decision) {
    flash('error', 'Not found.');
    redirect(APP_URL . '/decisions/index.php');
}

$pageTitle  = $decision['title'];
$activePage = 'decisions';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><?= e($decision['title']) ?></h1>
    <p><span class="pill pill--<?= e($decision['status']) ?>"><?= e($decision['status']) ?></span></p>
  </div>
  <?php if (is_admin()): ?>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/decisions/edit.php?id=<?= (int)$decision['id'] ?>" class="btn btn--outline">Edit</a>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <?php if ($decision['description']): ?>
  <span class="dl-label">Description</span>
  <p class="dl-value"><?= e($decision['description']) ?></p>
  <?php endif; ?>

  <div class="detail-grid">
    <div>
      <span class="dl-label">Owner</span>
      <p class="dl-value"><?= e($decision['owner_name'] ?: $decision['owner_username'] ?: 'Unassigned') ?></p>
    </div>
    <div>
      <span class="dl-label">Needed by</span>
      <p class="dl-value"><?= format_date($decision['needed_by_date']) ?></p>
    </div>
    <?php if ($decision['status'] === 'decided'): ?>
    <div>
      <span class="dl-label">Decided on</span>
      <p class="dl-value"><?= format_date($decision['decided_date']) ?></p>
    </div>
    <?php endif; ?>
    <div>
      <span class="dl-label">Created by</span>
      <p class="dl-value"><?= e($decision['creator_name'] ?: $decision['creator_username'] ?: '—') ?></p>
    </div>
  </div>

  <?php if ($decision['outcome']): ?>
  <span class="dl-label">Outcome</span>
  <p class="dl-value"><?= e($decision['outcome']) ?></p>
  <?php endif; ?>
</div>

<p class="back-nav"><a class="back-link" href="<?= APP_URL ?>/decisions/index.php"><?= icon_arrow_left() ?> Back to decisions</a></p>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
