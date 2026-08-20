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
    'title'        => 'Programme Board',
    'meeting_date' => date('Y-m-d'),
    'content'      => generate_agenda_draft($db),
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid form token, please try again.';
        goto render;
    }

    $f['title']        = trim($_POST['title'] ?? '');
    $f['meeting_date'] = $_POST['meeting_date'] ?? '';
    $f['content']      = $_POST['content'] ?? '';

    if (isset($_POST['regenerate'])) {
        $f['content'] = generate_agenda_draft($db);
        goto render;
    }

    if ($f['title'] === '') $errors[] = 'Title is required.';
    if (trim($f['content']) === '') $errors[] = 'Agenda content is required.';

    if (!$errors) {
        $db->prepare(
            'INSERT INTO pm_agendas (title, meeting_date, content, created_by_user_id) VALUES (?, ?, ?, ?)'
        )->execute([$f['title'], $f['meeting_date'] ?: null, $f['content'], get_current_user_id()]);
        flash('success', "Agenda '{$f['title']}' published.");
        redirect(APP_URL . '/agenda/index.php');
    }
}

render:
$pageTitle  = 'Generate Agenda';
$activePage = 'agendas';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Generate agenda</h1>
    <p>Drafted from current status, decisions, risks, and milestones. Edit anything below before publishing.</p>
  </div>
</div>

<?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>

<div class="card">
  <form method="POST" action="">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="field">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= e($f['title']) ?>" required>
      </div>
      <div class="field">
        <label for="meeting_date">Meeting date</label>
        <input type="date" id="meeting_date" name="meeting_date" value="<?= e($f['meeting_date']) ?>">
      </div>
      <div class="field form-full">
        <label for="content">Agenda</label>
        <textarea id="content" name="content" class="agenda-textarea" rows="24"><?= e($f['content']) ?></textarea>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn--primary">Publish agenda</button>
      <button type="submit" name="regenerate" value="1" class="btn btn--outline" onclick="return confirm('Replace the agenda text below with a fresh draft from current data? Your edits to it will be lost — title and meeting date are kept.');">Regenerate from current data</button>
      <a href="<?= APP_URL ?>/agenda/index.php" class="btn btn--outline" style="margin-left:auto;">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
