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
$tagFilter    = (int)($_GET['tag'] ?? 0);
$where  = [];
$params = [];
if (in_array($statusFilter, ['todo', 'in_progress', 'done'], true)) {
    $where[]  = 't.status = ?';
    $params[] = $statusFilter;
}
if ($tagFilter > 0) {
    $where[]  = "t.id IN (SELECT taggable_id FROM pm_taggables WHERE taggable_type = 'task' AND tag_id = ?)";
    $params[] = $tagFilter;
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

$tagTree      = get_tag_tree($db);
$flatTags     = flatten_tag_tree($tagTree);
$taskTagsById = [];
if ($tasks) {
    $ids = implode(',', array_map(fn($t) => (int)$t['id'], $tasks));
    $rows = $db->query(
        "SELECT x.taggable_id, tg.id AS tag_id, tg.name, p.name AS parent_name
         FROM pm_taggables x
         JOIN pm_tags tg ON tg.id = x.tag_id
         LEFT JOIN pm_tags p ON p.id = tg.parent_id
         WHERE x.taggable_type = 'task' AND x.taggable_id IN ($ids)
         ORDER BY tg.name"
    )->fetchAll();
    foreach ($rows as $r) {
        $taskTagsById[(int)$r['taggable_id']][] = ['id' => (int)$r['tag_id'], 'name' => $r['name'], 'parent_name' => $r['parent_name']];
    }
}

$blockedTaskIds = [];
if ($tasks) {
    $ids = implode(',', array_map(fn($t) => (int)$t['id'], $tasks));
    $blockedTaskIds = $db->query(
        "SELECT DISTINCT d.task_id
         FROM pm_task_dependencies d
         JOIN pm_tasks dep ON dep.id = d.depends_on_id
         WHERE d.task_id IN ($ids) AND dep.status != 'done'"
    )->fetchAll(PDO::FETCH_COLUMN);
    $blockedTaskIds = array_map('intval', $blockedTaskIds);
}

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
    <a href="<?= APP_URL ?>/tags/index.php" class="btn btn--outline">Manage tags</a>
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
    <?php if ($flatTags): ?>
    <div class="field">
      <label for="tag">Tag</label>
      <select id="tag" name="tag" onchange="this.form.submit()">
        <option value="">All</option>
        <?php foreach ($flatTags as $t): ?>
        <option value="<?= $t['id'] ?>" <?= $tagFilter === $t['id'] ? 'selected' : '' ?>><?= str_repeat('&nbsp;&nbsp;', $t['depth']) . e($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
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
      <tr class="table-row--clickable" data-href="<?= APP_URL ?>/tasks/view.php?id=<?= (int)$t['id'] ?>">
        <td>
          <a href="<?= APP_URL ?>/tasks/view.php?id=<?= (int)$t['id'] ?>" class="table-entity-link"><?= e($t['title']) ?></a>
          <?php if (!empty($taskTagsById[(int)$t['id']])): ?>
          <div style="margin-top:.3rem;"><?= render_tag_pills($taskTagsById[(int)$t['id']]) ?></div>
          <?php endif; ?>
        </td>
        <td><?= e($t['display_name'] ?: $t['username'] ?: '—') ?></td>
        <td>
          <span class="pill pill--<?= e($t['status']) ?>"><?= e(str_replace('_', ' ', $t['status'])) ?></span>
          <?php if ($t['status'] !== 'done' && in_array((int)$t['id'], $blockedTaskIds, true)): ?>
          <span class="pill pill--blocked">Blocked</span>
          <?php endif; ?>
        </td>
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
