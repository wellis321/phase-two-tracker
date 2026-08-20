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
$stmt = $db->prepare('SELECT * FROM pm_milestones WHERE id = ?');
$stmt->execute([$id]);
$milestone = $stmt->fetch();
if (!$milestone) {
    flash('error', 'Not found.');
    redirect(APP_URL . '/milestones/index.php');
}

$f = [
    'title' => $milestone['title'], 'phase' => $milestone['phase'],
    'target_date' => $milestone['target_date'], 'status' => $milestone['status'], 'notes' => $milestone['notes'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid form token, please try again.';
        goto render;
    }

    if (isset($_POST['delete'])) {
        $db->prepare('DELETE FROM pm_milestones WHERE id = ?')->execute([$id]);
        flash('success', 'Deleted.');
        redirect(APP_URL . '/milestones/index.php');
    }

    $f['title']       = trim($_POST['title'] ?? '');
    $f['phase']       = trim($_POST['phase'] ?? '');
    $f['target_date'] = $_POST['target_date'] ?? '';
    $f['status']      = $_POST['status'] ?? 'upcoming';
    $f['notes']       = trim($_POST['notes'] ?? '');

    if ($f['title'] === '') $errors[] = 'Title is required.';
    if (!in_array($f['status'], ['upcoming', 'at_risk', 'complete'], true)) $errors[] = 'Invalid status.';

    if (!$errors) {
        $db->prepare(
            'UPDATE pm_milestones SET title=?, phase=?, target_date=?, status=?, notes=? WHERE id=?'
        )->execute([
            $f['title'], $f['phase'] ?: null, $f['target_date'] ?: null, $f['status'], $f['notes'] ?: null, $id,
        ]);
        flash('success', 'Updated.');
        redirect(APP_URL . '/milestones/index.php');
    }
}

render:
$pageTitle  = 'Edit Milestone';
$activePage = 'milestones';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header"><div><h1>Edit milestone</h1></div></div>

<?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>

<div class="card">
  <form method="POST" action="">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="field form-full">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= e($f['title']) ?>" required>
      </div>
      <div class="field">
        <label for="phase">Phase</label>
        <input type="text" id="phase" name="phase" value="<?= e($f['phase']) ?>" placeholder="e.g. Solution Design">
      </div>
      <div class="field">
        <label for="target_date">Target date</label>
        <input type="date" id="target_date" name="target_date" value="<?= e($f['target_date']) ?>">
      </div>
      <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="upcoming" <?= $f['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
          <option value="at_risk" <?= $f['status'] === 'at_risk' ? 'selected' : '' ?>>At risk</option>
          <option value="complete" <?= $f['status'] === 'complete' ? 'selected' : '' ?>>Complete</option>
        </select>
      </div>
      <div class="field form-full">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes"><?= e($f['notes']) ?></textarea>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn--primary">Save</button>
      <a href="<?= APP_URL ?>/milestones/index.php" class="btn btn--outline">Cancel</a>
      <button type="submit" name="delete" value="1" class="btn btn--danger" style="margin-left:auto;" onclick="return confirm('Delete this?');">Delete</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
