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
if (in_array($statusFilter, ['todo', 'in_progress', 'done'], true)) {
    $where[]  = 't.status = ?';
    $params[] = $statusFilter;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$tasks = $db->prepare(
    "SELECT t.*, u.display_name, u.username
     FROM pm_tasks t
     LEFT JOIN users u ON u.id = t.assignee_user_id
     $whereSql
     ORDER BY (t.status = 'done'), t.due_date IS NULL, t.due_date, t.created_at DESC"
);
$tasks->execute($params);
$tasks = $tasks->fetchAll();

$pageTitle  = 'Tasks';
$activePage = 'tasks';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Tasks</h1>
    <p>Work items for the team, tracked to a status and (optionally) an owner and due date.</p>
  </div>
  <?php if (is_admin()): ?>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/tasks/create.php" class="btn btn--primary">+ Add task</a>
  </div>
  <?php endif; ?>
</div>

<div class="filter-bar">
  <form method="GET" style="display:contents;">
    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status" onchange="this.form.submit()">
        <option value="">All</option>
        <option value="todo" <?= $statusFilter === 'todo' ? 'selected' : '' ?>>To do</option>
        <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In progress</option>
        <option value="done" <?= $statusFilter === 'done' ? 'selected' : '' ?>>Done</option>
      </select>
    </div>
  </form>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Title</th>
        <th>Assignee</th>
        <th>Status</th>
        <th>Due</th>
        <?php if (is_admin()): ?><th class="col-actions">Actions</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (!$tasks): ?>
      <tr><td colspan="5" class="empty-note">No tasks yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($tasks as $t): ?>
      <tr>
        <td><?= e($t['title']) ?></td>
        <td><?= e($t['display_name'] ?: $t['username'] ?: '—') ?></td>
        <td><span class="pill pill--<?= e($t['status']) ?>"><?= e(str_replace('_', ' ', $t['status'])) ?></span></td>
        <td><?= format_date($t['due_date']) ?></td>
        <?php if (is_admin()): ?>
        <td class="col-actions"><a href="<?= APP_URL ?>/tasks/edit.php?id=<?= (int)$t['id'] ?>" class="icon-btn" title="Edit" aria-label="Edit"><?= icon_edit() ?></a></td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
