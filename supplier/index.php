<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$db = db();

$statusFilter = $_GET['status'] ?? '';
$where  = [];
$params = [];
if (in_array($statusFilter, ['planned', 'in_progress', 'complete', 'blocked'], true)) {
    $where[]  = 's.status = ?';
    $params[] = $statusFilter;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$activities = $db->prepare(
    "SELECT s.*, u.display_name, u.username
     FROM pm_supplier_activities s
     LEFT JOIN users u ON u.id = s.owner_user_id
     $whereSql
     ORDER BY (s.status = 'complete'), s.due_date IS NULL, s.due_date"
);
$activities->execute($params);
$activities = $activities->fetchAll();

$pageTitle  = 'Supplier Activity';
$activePage = 'supplier';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Supplier activity</h1>
    <p>Work items with the ROCC (or other) supplier — workshops, deliverables, sign-offs.</p>
  </div>
  <?php if (is_admin()): ?>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/supplier/create.php" class="btn btn--primary">+ Add activity</a>
  </div>
  <?php endif; ?>
</div>

<div class="filter-bar">
  <form method="GET" style="display:contents;">
    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status" onchange="this.form.submit()">
        <option value="">All</option>
        <option value="planned" <?= $statusFilter === 'planned' ? 'selected' : '' ?>>Planned</option>
        <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In progress</option>
        <option value="complete" <?= $statusFilter === 'complete' ? 'selected' : '' ?>>Complete</option>
        <option value="blocked" <?= $statusFilter === 'blocked' ? 'selected' : '' ?>>Blocked</option>
      </select>
    </div>
  </form>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Supplier</th>
        <th>Title</th>
        <th>Owner</th>
        <th>Status</th>
        <th>Due</th>
        <?php if (is_admin()): ?><th class="col-actions">Actions</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (!$activities): ?>
      <tr><td colspan="6" class="empty-note">Nothing recorded yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($activities as $s): ?>
      <tr>
        <td><?= e($s['supplier']) ?></td>
        <td><?= e($s['title']) ?></td>
        <td><?= e($s['display_name'] ?: $s['username'] ?: '—') ?></td>
        <td><span class="pill pill--<?= e($s['status']) ?>"><?= e(str_replace('_', ' ', $s['status'])) ?></span></td>
        <td><?= format_date($s['due_date']) ?></td>
        <?php if (is_admin()): ?>
        <td class="col-actions"><a href="<?= APP_URL ?>/supplier/edit.php?id=<?= (int)$s['id'] ?>">Edit</a></td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
