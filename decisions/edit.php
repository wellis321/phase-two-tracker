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
$stmt = $db->prepare('SELECT * FROM pm_decisions WHERE id = ?');
$stmt->execute([$id]);
$decision = $stmt->fetch();
if (!$decision) {
    flash('error', 'Not found.');
    redirect(APP_URL . '/decisions/index.php');
}

$f = [
    'title' => $decision['title'], 'description' => $decision['description'],
    'needed_by_date' => $decision['needed_by_date'], 'decision_owner_user_id' => $decision['decision_owner_user_id'],
    'status' => $decision['status'], 'outcome' => $decision['outcome'], 'decided_date' => $decision['decided_date'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid form token, please try again.';
        goto render;
    }

    if (isset($_POST['delete'])) {
        $db->prepare('DELETE FROM pm_decisions WHERE id = ?')->execute([$id]);
        flash('success', 'Deleted.');
        redirect(APP_URL . '/decisions/index.php');
    }

    $f['title']                  = trim($_POST['title'] ?? '');
    $f['description']            = trim($_POST['description'] ?? '');
    $f['needed_by_date']         = $_POST['needed_by_date'] ?? '';
    $f['decision_owner_user_id'] = $_POST['decision_owner_user_id'] ?? '';
    $f['status']                 = $_POST['status'] ?? 'open';
    $f['outcome']                = trim($_POST['outcome'] ?? '');
    $f['decided_date']           = $_POST['decided_date'] ?? '';

    if ($f['title'] === '') $errors[] = 'Title is required.';
    if (!in_array($f['status'], ['open', 'decided'], true)) $errors[] = 'Invalid status.';

    if (!$errors) {
        $db->prepare(
            'UPDATE pm_decisions SET title=?, description=?, needed_by_date=?, decision_owner_user_id=?, status=?, outcome=?, decided_date=? WHERE id=?'
        )->execute([
            $f['title'], $f['description'] ?: null, $f['needed_by_date'] ?: null,
            $f['decision_owner_user_id'] ?: null, $f['status'], $f['outcome'] ?: null,
            $f['decided_date'] ?: null, $id,
        ]);
        flash('success', 'Updated.');
        redirect(APP_URL . '/decisions/index.php');
    }
}

render:
$users = $db->query("SELECT id, username, display_name FROM users WHERE is_active = 1 ORDER BY COALESCE(display_name, username)")->fetchAll();

$pageTitle  = 'Edit Decision';
$activePage = 'decisions';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header"><div><h1>Edit decision</h1></div></div>

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
        <label for="decision_owner_user_id">Owner</label>
        <select id="decision_owner_user_id" name="decision_owner_user_id">
          <option value="">Unassigned</option>
          <?php foreach ($users as $u): ?>
          <option value="<?= (int)$u['id'] ?>" <?= (string)$u['id'] === (string)$f['decision_owner_user_id'] ? 'selected' : '' ?>><?= e($u['display_name'] ?: $u['username']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="needed_by_date">Needed by</label>
        <input type="date" id="needed_by_date" name="needed_by_date" value="<?= e($f['needed_by_date']) ?>">
      </div>
      <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status" onchange="document.getElementById('decided-fields').style.display = this.value==='decided' ? '' : 'none';">
          <option value="open" <?= $f['status'] === 'open' ? 'selected' : '' ?>>Open</option>
          <option value="decided" <?= $f['status'] === 'decided' ? 'selected' : '' ?>>Decided</option>
        </select>
      </div>
      <div id="decided-fields" class="form-full form-grid" style="display:<?= $f['status'] === 'decided' ? '' : 'none' ?>; margin:0;">
        <div class="field">
          <label for="decided_date">Decided on</label>
          <input type="date" id="decided_date" name="decided_date" value="<?= e($f['decided_date']) ?>">
        </div>
        <div class="field">
          <label for="outcome">Outcome</label>
          <textarea id="outcome" name="outcome"><?= e($f['outcome']) ?></textarea>
        </div>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn--primary">Save</button>
      <a href="<?= APP_URL ?>/decisions/index.php" class="btn btn--outline">Cancel</a>
      <button type="submit" name="delete" value="1" class="btn btn--danger" style="margin-left:auto;" onclick="return confirm('Delete this?');">Delete</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
