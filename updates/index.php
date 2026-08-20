<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$db = db();

$snapshots = $db->query(
    "SELECT s.*, u.display_name, u.username
     FROM pm_weekly_snapshots s
     LEFT JOIN users u ON u.id = s.created_by_user_id
     ORDER BY s.created_at DESC"
)->fetchAll();

$pageTitle  = 'Weekly Archive';
$activePage = 'updates';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Weekly archive</h1>
    <p>Every published update — achievements, key decisions, risks raised, and lessons learned, over time.</p>
  </div>
  <?php if (is_admin()): ?>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/updates/create.php" class="btn btn--primary">+ Publish update</a>
  </div>
  <?php endif; ?>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Status</th>
        <th>Period</th>
        <th>Published</th>
        <th>By</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$snapshots): ?>
      <tr><td colspan="4" class="empty-note">No updates published yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($snapshots as $s): ?>
      <tr class="table-row--clickable" data-href="<?= APP_URL ?>/updates/view.php?id=<?= (int)$s['id'] ?>">
        <td><?= rag_badge($s['overall_status']) ?></td>
        <td><a href="<?= APP_URL ?>/updates/view.php?id=<?= (int)$s['id'] ?>" class="table-entity-link"><?= e($s['period_label']) ?></a></td>
        <td><?= e(date('j M Y', strtotime($s['created_at']))) ?></td>
        <td><?= e($s['display_name'] ?: $s['username'] ?: '—') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
