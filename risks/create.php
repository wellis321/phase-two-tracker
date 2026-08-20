<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();
$db = db();

$f = [
    'type' => 'risk', 'title' => '', 'description' => '', 'severity' => 'amber',
    'status' => 'open', 'owner_user_id' => '', 'raised_date' => date('Y-m-d'),
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid form token, please try again.';
        goto render;
    }
    $f['type']          = $_POST['type'] ?? 'risk';
    $f['title']          = trim($_POST['title'] ?? '');
    $f['description']    = trim($_POST['description'] ?? '');
    $f['severity']       = $_POST['severity'] ?? 'amber';
    $f['status']         = $_POST['status'] ?? 'open';
    $f['owner_user_id']  = $_POST['owner_user_id'] ?? '';
    $f['raised_date']    = $_POST['raised_date'] ?? date('Y-m-d');

    if ($f['title'] === '') $errors[] = 'Title is required.';
    if (!in_array($f['type'], ['risk', 'issue'], true)) $errors[] = 'Invalid type.';
    if (!in_array($f['severity'], ['red', 'amber', 'green'], true)) $errors[] = 'Invalid severity.';
    if (!in_array($f['status'], ['open', 'mitigated', 'closed'], true)) $errors[] = 'Invalid status.';

    if (!$errors) {
        $db->prepare(
            'INSERT INTO pm_risks_issues (type, title, description, severity, status, owner_user_id, raised_date, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $f['type'], $f['title'], $f['description'] ?: null, $f['severity'], $f['status'],
            $f['owner_user_id'] ?: null, $f['raised_date'], get_current_user_id(),
        ]);
        flash('success', "'{$f['title']}' added.");
        redirect(APP_URL . '/risks/index.php');
    }
}

render:
$users = $db->query("SELECT id, username, display_name FROM users WHERE is_active = 1 ORDER BY COALESCE(display_name, username)")->fetchAll();

$pageTitle  = 'Add Risk / Issue';
$activePage = 'risks';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header"><div><h1>Add risk / issue</h1></div></div>

<?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>

<div class="card">
  <form method="POST" action="">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="field">
        <label for="type">Type</label>
        <select id="type" name="type">
          <option value="risk" <?= $f['type'] === 'risk' ? 'selected' : '' ?>>Risk</option>
          <option value="issue" <?= $f['type'] === 'issue' ? 'selected' : '' ?>>Issue</option>
        </select>
      </div>
      <div class="field">
        <label for="severity">Severity</label>
        <select id="severity" name="severity">
          <option value="red" <?= $f['severity'] === 'red' ? 'selected' : '' ?>>Red</option>
          <option value="amber" <?= $f['severity'] === 'amber' ? 'selected' : '' ?>>Amber</option>
          <option value="green" <?= $f['severity'] === 'green' ? 'selected' : '' ?>>Green</option>
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
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="open" <?= $f['status'] === 'open' ? 'selected' : '' ?>>Open</option>
          <option value="mitigated" <?= $f['status'] === 'mitigated' ? 'selected' : '' ?>>Mitigated</option>
          <option value="closed" <?= $f['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
        </select>
      </div>
      <div class="field">
        <label for="raised_date">Raised</label>
        <input type="date" id="raised_date" name="raised_date" value="<?= e($f['raised_date']) ?>" required>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn--primary">Save</button>
      <a href="<?= APP_URL ?>/risks/index.php" class="btn btn--outline">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
