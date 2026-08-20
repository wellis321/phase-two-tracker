<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$db = db();

$milestones = $db->query(
    "SELECT * FROM pm_milestones ORDER BY (status = 'complete'), target_date IS NULL, target_date"
)->fetchAll();

$roadmap = build_milestone_roadmap($milestones);

$pageTitle  = 'Milestones';
$activePage = 'milestones';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Milestones</h1>
    <p>Key dates across Phase 2 — build, integration, pilot, and go-live markers.</p>
  </div>
  <?php if (is_admin()): ?>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/milestones/create.php" class="btn btn--primary">+ Add milestone</a>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/layout/roadmap.php'; ?>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Title</th>
        <th>Phase</th>
        <th>Target date</th>
        <th>Status</th>
        <?php if (is_admin()): ?><th class="col-actions">Actions</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (!$milestones): ?>
      <tr><td colspan="5" class="empty-note">No milestones recorded yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($milestones as $m): ?>
      <tr class="table-row--clickable" data-href="<?= APP_URL ?>/milestones/view.php?id=<?= (int)$m['id'] ?>">
        <td><a href="<?= APP_URL ?>/milestones/view.php?id=<?= (int)$m['id'] ?>" class="table-entity-link"><?= e($m['title']) ?></a></td>
        <td><?= e($m['phase'] ?: '—') ?></td>
        <td><?= format_date($m['target_date']) ?></td>
        <td><span class="pill pill--<?= e($m['status']) ?>"><?= e(str_replace('_', ' ', $m['status'])) ?></span></td>
        <?php if (is_admin()): ?>
        <td class="col-actions"><a href="<?= APP_URL ?>/milestones/edit.php?id=<?= (int)$m['id'] ?>" class="icon-btn" title="Edit" aria-label="Edit"><?= icon_edit() ?></a></td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
