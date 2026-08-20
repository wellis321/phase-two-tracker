<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();
$db = db();

$f = ['title' => '', 'description' => '', 'assignee_user_id' => '', 'status' => 'todo', 'due_date' => ''];
$errors = [];
$selectedTagIds = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid form token, please try again.';
        goto render;
    }
    $f['title']            = trim($_POST['title'] ?? '');
    $f['description']      = trim($_POST['description'] ?? '');
    $f['assignee_user_id'] = $_POST['assignee_user_id'] ?? '';
    $f['status']           = $_POST['status'] ?? 'todo';
    $f['due_date']         = $_POST['due_date'] ?? '';
    $selectedTagIds        = array_map('intval', $_POST['tag_ids'] ?? []);

    if ($f['title'] === '') $errors[] = 'Title is required.';
    if (!in_array($f['status'], ['todo', 'in_progress', 'done'], true)) $errors[] = 'Invalid status.';

    if (!$errors) {
        $db->prepare(
            'INSERT INTO pm_tasks (title, description, assignee_user_id, status, due_date, created_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $f['title'],
            $f['description'] ?: null,
            $f['assignee_user_id'] ?: null,
            $f['status'],
            $f['due_date'] ?: null,
            get_current_user_id(),
        ]);
        save_tags_for($db, 'task', (int)$db->lastInsertId(), $selectedTagIds);
        flash('success', "Task '{$f['title']}' created.");
        redirect(APP_URL . '/tasks/index.php');
    }
}

render:
$users   = $db->query("SELECT id, username, display_name FROM users WHERE is_active = 1 ORDER BY COALESCE(display_name, username)")->fetchAll();
$tagTree = get_tag_tree($db);

$pageTitle  = 'Add Task';
$activePage = 'tasks';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div><h1>Add task</h1></div>
</div>

<?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>

<div class="card">
  <form method="POST" action="">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="field form-full">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= e($f['title']) ?>" required>
      </div>
      <div class="field form-full">
        <label for="description">Description</label>
        <textarea id="description" name="description"><?= e($f['description']) ?></textarea>
      </div>
      <div class="field">
        <label for="assignee_user_id">Assignee</label>
        <select id="assignee_user_id" name="assignee_user_id">
          <option value="">Unassigned</option>
          <?php foreach ($users as $u): ?>
          <option value="<?= (int)$u['id'] ?>" <?= (string)$u['id'] === (string)$f['assignee_user_id'] ? 'selected' : '' ?>><?= e($u['display_name'] ?: $u['username']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="todo" <?= $f['status'] === 'todo' ? 'selected' : '' ?>>To do</option>
          <option value="in_progress" <?= $f['status'] === 'in_progress' ? 'selected' : '' ?>>In progress</option>
          <option value="done" <?= $f['status'] === 'done' ? 'selected' : '' ?>>Done</option>
        </select>
      </div>
      <div class="field">
        <label for="due_date">Due date</label>
        <input type="date" id="due_date" name="due_date" value="<?= e($f['due_date']) ?>">
      </div>
      <div class="field form-full">
        <label>Tags</label>
        <?= render_tag_checkboxes($tagTree, $selectedTagIds) ?>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn--primary">Save</button>
      <a href="<?= APP_URL ?>/tasks/index.php" class="btn btn--outline">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
