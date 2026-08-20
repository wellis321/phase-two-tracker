<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();
$db = db();

$f = ['title' => '', 'phase' => '', 'target_date' => '', 'status' => 'upcoming', 'notes' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid form token, please try again.';
        goto render;
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
            'INSERT INTO pm_milestones (title, phase, target_date, status, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $f['title'], $f['phase'] ?: null, $f['target_date'] ?: null, $f['status'],
            $f['notes'] ?: null, get_current_user_id(),
        ]);
        flash('success', "'{$f['title']}' added.");
        redirect(APP_URL . '/milestones/index.php');
    }
}

render:
$pageTitle  = 'Add Milestone';
$activePage = 'milestones';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header"><div><h1>Add milestone</h1></div></div>

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
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
