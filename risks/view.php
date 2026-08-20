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
    'SELECT r.*, o.display_name AS owner_name, o.username AS owner_username,
            c.display_name AS creator_name, c.username AS creator_username
     FROM pm_risks_issues r
     LEFT JOIN users o ON o.id = r.owner_user_id
     LEFT JOIN users c ON c.id = r.created_by
     WHERE r.id = ?'
);
$stmt->execute([$id]);
$risk = $stmt->fetch();
if (!$risk) {
    flash('error', 'Not found.');
    redirect(APP_URL . '/risks/index.php');
}
$discussionState = get_discussion_state($db, 'risk', $id, get_current_user_id());

$pageTitle  = $risk['title'];
$activePage = 'risks';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><?= e($risk['title']) ?></h1>
    <p><?= rag_badge($risk['severity']) ?> <?= e(ucfirst($risk['type'])) ?> · <span class="pill pill--<?= e($risk['status']) ?>"><?= e($risk['status']) ?></span></p>
  </div>
  <div class="page-header-actions">
    <?= render_flag_button('risk', $id, $discussionState) ?>
    <?php if (is_admin()): ?>
    <a href="<?= APP_URL ?>/risks/edit.php?id=<?= (int)$risk['id'] ?>" class="btn btn--outline">Edit</a>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <?php if ($risk['description']): ?>
  <span class="dl-label">Description</span>
  <p class="dl-value"><?= e($risk['description']) ?></p>
  <?php endif; ?>

  <div class="detail-grid">
    <div>
      <span class="dl-label">Owner</span>
      <p class="dl-value"><?= e($risk['owner_name'] ?: $risk['owner_username'] ?: 'Unassigned') ?></p>
    </div>
    <div>
      <span class="dl-label">Raised</span>
      <p class="dl-value"><?= format_date($risk['raised_date']) ?></p>
    </div>
    <div>
      <span class="dl-label">Created by</span>
      <p class="dl-value"><?= e($risk['creator_name'] ?: $risk['creator_username'] ?: '—') ?></p>
    </div>
    <div>
      <span class="dl-label">Created</span>
      <p class="dl-value"><?= e(date('j M Y', strtotime($risk['created_at']))) ?></p>
    </div>
  </div>
</div>

<p class="back-nav"><a class="back-link" href="<?= APP_URL ?>/risks/index.php"><?= icon_arrow_left() ?> Back to risks &amp; issues</a></p>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
