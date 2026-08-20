<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$db = db();

$statusFilter = $_GET['status'] ?? 'open';
$where  = [];
$params = [];
if (in_array($statusFilter, ['open', 'decided'], true)) {
    $where[]  = 'd.status = ?';
    $params[] = $statusFilter;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$decisions = $db->prepare(
    "SELECT d.*, u.display_name, u.username
     FROM pm_decisions d
     LEFT JOIN users u ON u.id = d.decision_owner_user_id
     $whereSql
     ORDER BY (d.status = 'decided'), d.needed_by_date IS NULL, d.needed_by_date"
);
$decisions->execute($params);
$decisions = $decisions->fetchAll();

$pageTitle  = 'Decisions';
$activePage = 'decisions';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Decisions required</h1>
    <p>Things the project needs a call on — who owns it, by when, and what was decided.</p>
  </div>
  <?php if (is_admin()): ?>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/decisions/create.php" class="btn btn--primary">+ Add decision</a>
  </div>
  <?php endif; ?>
</div>

<div class="filter-bar">
  <form method="GET" style="display:contents;">
    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status" onchange="this.form.submit()">
        <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open</option>
        <option value="decided" <?= $statusFilter === 'decided' ? 'selected' : '' ?>>Decided</option>
        <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All</option>
      </select>
    </div>
  </form>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Title</th>
        <th>Owner</th>
        <th>Needed by</th>
        <th>Status</th>
        <?php if (is_admin()): ?><th class="col-actions">Actions</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (!$decisions): ?>
      <tr><td colspan="5" class="empty-note">Nothing recorded yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($decisions as $d): ?>
      <tr class="table-row--clickable" data-href="<?= APP_URL ?>/decisions/view.php?id=<?= (int)$d['id'] ?>">
        <td><a href="<?= APP_URL ?>/decisions/view.php?id=<?= (int)$d['id'] ?>" class="table-entity-link"><?= e($d['title']) ?></a></td>
        <td><?= e($d['display_name'] ?: $d['username'] ?: '—') ?></td>
        <td><?= format_date($d['needed_by_date']) ?></td>
        <td><span class="pill pill--<?= e($d['status']) ?>"><?= e($d['status']) ?></span></td>
        <?php if (is_admin()): ?>
        <td class="col-actions"><a href="<?= APP_URL ?>/decisions/edit.php?id=<?= (int)$d['id'] ?>" class="icon-btn" title="Edit" aria-label="Edit"><?= icon_edit() ?></a></td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
