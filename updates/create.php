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
    'period_label' => 'Week of ' . date('j M Y'),
    'overall_status' => 'green',
    'current_focus' => '', 'progress_narrative' => '', 'achievements' => '',
    'key_decisions' => '', 'risks_raised' => '', 'lessons_learned' => '', 'looking_ahead_notes' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid form token, please try again.';
        goto render;
    }
    foreach (array_keys($f) as $key) {
        $f[$key] = trim($_POST[$key] ?? '');
    }

    if ($f['period_label'] === '') $errors[] = 'Period label is required.';
    if (!in_array($f['overall_status'], ['red', 'amber', 'green'], true)) $errors[] = 'Invalid status.';

    if (!$errors) {
        $db->prepare(
            'INSERT INTO pm_weekly_snapshots
             (period_label, overall_status, current_focus, progress_narrative, achievements, key_decisions, risks_raised, lessons_learned, looking_ahead_notes, created_by_user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $f['period_label'], $f['overall_status'], $f['current_focus'] ?: null, $f['progress_narrative'] ?: null,
            $f['achievements'] ?: null, $f['key_decisions'] ?: null, $f['risks_raised'] ?: null,
            $f['lessons_learned'] ?: null, $f['looking_ahead_notes'] ?: null, get_current_user_id(),
        ]);
        flash('success', 'Update published.');
        redirect(APP_URL . '/updates/index.php');
    }
}

render:
$openTasks     = (int)$db->query("SELECT COUNT(*) FROM pm_tasks WHERE status != 'done'")->fetchColumn();
$openRisks     = (int)$db->query("SELECT COUNT(*) FROM pm_risks_issues WHERE status != 'closed'")->fetchColumn();
$openDecisions = (int)$db->query("SELECT COUNT(*) FROM pm_decisions WHERE status = 'open'")->fetchColumn();

$pageTitle  = 'Publish Weekly Update';
$activePage = 'updates';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header"><div><h1>Publish weekly update</h1></div></div>

<?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>

<div class="dash-grid">
  <div class="card">
    <form method="POST" action="">
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="field">
          <label for="period_label">Period</label>
          <input type="text" id="period_label" name="period_label" value="<?= e($f['period_label']) ?>" required>
        </div>
        <div class="field">
          <label for="overall_status">Overall status</label>
          <select id="overall_status" name="overall_status">
            <option value="green" <?= $f['overall_status'] === 'green' ? 'selected' : '' ?>>Green</option>
            <option value="amber" <?= $f['overall_status'] === 'amber' ? 'selected' : '' ?>>Amber</option>
            <option value="red" <?= $f['overall_status'] === 'red' ? 'selected' : '' ?>>Red</option>
          </select>
        </div>
        <div class="field form-full">
          <label for="current_focus">Current focus — next couple of weeks</label>
          <textarea id="current_focus" name="current_focus"><?= e($f['current_focus']) ?></textarea>
        </div>
        <div class="field form-full">
          <label for="progress_narrative">Progress this week</label>
          <textarea id="progress_narrative" name="progress_narrative"><?= e($f['progress_narrative']) ?></textarea>
        </div>
        <div class="field form-full">
          <label for="achievements">Achievements</label>
          <textarea id="achievements" name="achievements"><?= e($f['achievements']) ?></textarea>
        </div>
        <div class="field form-full">
          <label for="key_decisions">Key decisions</label>
          <textarea id="key_decisions" name="key_decisions"><?= e($f['key_decisions']) ?></textarea>
        </div>
        <div class="field form-full">
          <label for="risks_raised">Risks raised</label>
          <textarea id="risks_raised" name="risks_raised"><?= e($f['risks_raised']) ?></textarea>
        </div>
        <div class="field form-full">
          <label for="lessons_learned">Lessons learned</label>
          <textarea id="lessons_learned" name="lessons_learned"><?= e($f['lessons_learned']) ?></textarea>
        </div>
        <div class="field form-full">
          <label for="looking_ahead_notes">Looking ahead — next 60–90 days</label>
          <textarea id="looking_ahead_notes" name="looking_ahead_notes"><?= e($f['looking_ahead_notes']) ?></textarea>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Publish</button>
        <a href="<?= APP_URL ?>/updates/index.php" class="btn btn--outline">Cancel</a>
      </div>
    </form>
  </div>

  <div class="card">
    <h2>For reference</h2>
    <p class="hint" style="margin:0 0 .75rem;">Live counts, in case they're useful while writing this up.</p>
    <ul class="list-simple">
      <li><span class="li-title">Open tasks</span><span class="li-meta"><?= $openTasks ?></span></li>
      <li><span class="li-title">Open risks &amp; issues</span><span class="li-meta"><?= $openRisks ?></span></li>
      <li><span class="li-title">Decisions required</span><span class="li-meta"><?= $openDecisions ?></span></li>
    </ul>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
