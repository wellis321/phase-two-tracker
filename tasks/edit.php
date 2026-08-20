<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();
$db = db();

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM pm_tasks WHERE id = ?');
$stmt->execute([$id]);
$task = $stmt->fetch();
if (!$task) {
    flash('error', 'Task not found.');
    redirect(APP_URL . '/tasks/index.php');
}

$f = [
    'title'            => $task['title'],
    'description'      => $task['description'],
    'assignee_user_id' => $task['assignee_user_id'],
    'status'           => $task['status'],
    'due_date'         => $task['due_date'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid form token, please try again.';
        goto render;
    }

    if (isset($_POST['delete'])) {
        $db->prepare('DELETE FROM pm_tasks WHERE id = ?')->execute([$id]);
        flash('success', 'Task deleted.');
        redirect(APP_URL . '/tasks/index.php');
    }

    $f['title']            = trim($_POST['title'] ?? '');
    $f['description']      = trim($_POST['description'] ?? '');
    $f['assignee_user_id'] = $_POST['assignee_user_id'] ?? '';
    $f['status']           = $_POST['status'] ?? 'todo';
    $f['due_date']         = $_POST['due_date'] ?? '';

    if ($f['title'] === '') $errors[] = 'Title is required.';
    if (!in_array($f['status'], ['todo', 'in_progress', 'done'], true)) $errors[] = 'Invalid status.';

    if (!$errors) {
        $db->prepare(
            'UPDATE pm_tasks SET title = ?, description = ?, assignee_user_id = ?, status = ?, due_date = ? WHERE id = ?'
        )->execute([
            $f['title'],
            $f['description'] ?: null,
            $f['assignee_user_id'] ?: null,
            $f['status'],
            $f['due_date'] ?: null,
            $id,
        ]);
        flash('success', "Task '{$f['title']}' updated.");
        redirect(APP_URL . '/tasks/index.php');
    }
}

render:
$users = $db->query("SELECT id, username, display_name FROM users WHERE is_active = 1 ORDER BY COALESCE(display_name, username)")->fetchAll();

$pageTitle  = 'Edit Task';
$activePage = 'tasks';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header"><div><h1>Edit task</h1></div></div>

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
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn--primary">Save</button>
      <a href="<?= APP_URL ?>/tasks/index.php" class="btn btn--outline">Cancel</a>
      <button type="submit" name="delete" value="1" class="btn btn--danger" style="margin-left:auto;" onclick="return confirm('Delete this task?');">Delete</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
