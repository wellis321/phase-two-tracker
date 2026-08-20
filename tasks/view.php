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
    'SELECT t.*, a.display_name AS assignee_name, a.username AS assignee_username,
            c.display_name AS creator_name, c.username AS creator_username
     FROM pm_tasks t
     LEFT JOIN users a ON a.id = t.assignee_user_id
     LEFT JOIN users c ON c.id = t.created_by
     WHERE t.id = ?'
);
$stmt->execute([$id]);
$task = $stmt->fetch();
if (!$task) {
    flash('error', 'Task not found.');
    redirect(APP_URL . '/tasks/index.php');
}

$pageTitle  = $task['title'];
$activePage = 'tasks';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><?= e($task['title']) ?></h1>
    <p><span class="pill pill--<?= e($task['status']) ?>"><?= e(str_replace('_', ' ', $task['status'])) ?></span></p>
  </div>
  <?php if (is_admin()): ?>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/tasks/edit.php?id=<?= (int)$task['id'] ?>" class="btn btn--outline">Edit</a>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <?php if ($task['description']): ?>
  <span class="dl-label">Description</span>
  <p class="dl-value"><?= e($task['description']) ?></p>
  <?php endif; ?>

  <div class="detail-grid">
    <div>
      <span class="dl-label">Assignee</span>
      <p class="dl-value"><?= e($task['assignee_name'] ?: $task['assignee_username'] ?: 'Unassigned') ?></p>
    </div>
    <div>
      <span class="dl-label">Due date</span>
      <p class="dl-value"><?= format_date($task['due_date']) ?></p>
    </div>
    <div>
      <span class="dl-label">Created by</span>
      <p class="dl-value"><?= e($task['creator_name'] ?: $task['creator_username'] ?: '—') ?></p>
    </div>
    <div>
      <span class="dl-label">Created</span>
      <p class="dl-value"><?= e(date('j M Y', strtotime($task['created_at']))) ?></p>
    </div>
  </div>
</div>

<p class="back-nav"><a class="back-link" href="<?= APP_URL ?>/tasks/index.php"><?= icon_arrow_left() ?> Back to tasks</a></p>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
