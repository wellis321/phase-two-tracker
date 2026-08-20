<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();
$db = db();

$id   = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM pm_supplier_activities WHERE id = ?');
$stmt->execute([$id]);
$activity = $stmt->fetch();
if (!$activity) {
    flash('error', 'Not found.');
    redirect(APP_URL . '/supplier/index.php');
}

$f = [
    'supplier' => $activity['supplier'], 'title' => $activity['title'], 'description' => $activity['description'],
    'status' => $activity['status'], 'due_date' => $activity['due_date'], 'owner_user_id' => $activity['owner_user_id'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid form token, please try again.';
        goto render;
    }

    if (isset($_POST['delete'])) {
        $db->prepare('DELETE FROM pm_supplier_activities WHERE id = ?')->execute([$id]);
        flash('success', 'Deleted.');
        redirect(APP_URL . '/supplier/index.php');
    }

    $f['supplier']      = trim($_POST['supplier'] ?? '') ?: 'ROCC';
    $f['title']         = trim($_POST['title'] ?? '');
    $f['description']   = trim($_POST['description'] ?? '');
    $f['status']        = $_POST['status'] ?? 'planned';
    $f['due_date']      = $_POST['due_date'] ?? '';
    $f['owner_user_id'] = $_POST['owner_user_id'] ?? '';

    if ($f['title'] === '') $errors[] = 'Title is required.';
    if (!in_array($f['status'], ['planned', 'in_progress', 'complete', 'blocked'], true)) $errors[] = 'Invalid status.';

    if (!$errors) {
        $db->prepare(
            'UPDATE pm_supplier_activities SET supplier=?, title=?, description=?, status=?, due_date=?, owner_user_id=? WHERE id=?'
        )->execute([
            $f['supplier'], $f['title'], $f['description'] ?: null, $f['status'],
            $f['due_date'] ?: null, $f['owner_user_id'] ?: null, $id,
        ]);
        flash('success', 'Updated.');
        redirect(APP_URL . '/supplier/index.php');
    }
}

render:
$users = $db->query("SELECT id, username, display_name FROM users WHERE is_active = 1 ORDER BY COALESCE(display_name, username)")->fetchAll();

$pageTitle  = 'Edit Supplier Activity';
$activePage = 'supplier';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header"><div><h1>Edit supplier activity</h1></div></div>

<?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>

<div class="card">
  <form method="POST" action="">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="field">
        <label for="supplier">Supplier</label>
        <input type="text" id="supplier" name="supplier" value="<?= e($f['supplier']) ?>">
      </div>
      <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="planned" <?= $f['status'] === 'planned' ? 'selected' : '' ?>>Planned</option>
          <option value="in_progress" <?= $f['status'] === 'in_progress' ? 'selected' : '' ?>>In progress</option>
          <option value="complete" <?= $f['status'] === 'complete' ? 'selected' : '' ?>>Complete</option>
          <option value="blocked" <?= $f['status'] === 'blocked' ? 'selected' : '' ?>>Blocked</option>
        </select>
      </div>
      <div class="field form-full">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= e($f['title']) ?>" required>
      </div>
      <div class="field form-full">
        <label for="description">Description</label>
        <textarea id="description" name="description"><?= e($f['description']) ?></textarea>
      </div>
      <div class="field">
        <label for="owner_user_id">Owner</label>
        <select id="owner_user_id" name="owner_user_id">
          <option value="">Unassigned</option>
          <?php foreach ($users as $u): ?>
          <option value="<?= (int)$u['id'] ?>" <?= (string)$u['id'] === (string)$f['owner_user_id'] ? 'selected' : '' ?>><?= e($u['display_name'] ?: $u['username']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="due_date">Due date</label>
        <input type="date" id="due_date" name="due_date" value="<?= e($f['due_date']) ?>">
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn--primary">Save</button>
      <a href="<?= APP_URL ?>/supplier/index.php" class="btn btn--outline">Cancel</a>
      <button type="submit" name="delete" value="1" class="btn btn--danger" style="margin-left:auto;" onclick="return confirm('Delete this?');">Delete</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
