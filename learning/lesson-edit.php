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
$stmt = $db->prepare(
    'SELECT l.*, m.course_id
     FROM pm_learning_lessons l
     JOIN pm_learning_modules m ON m.id = l.module_id
     WHERE l.id = ?'
);
$stmt->execute([$id]);
$lesson = $stmt->fetch();
if (!$lesson) {
    flash('error', 'Lesson not found.');
    redirect(APP_URL . '/learning/index.php');
}

$errors = [];
$f = ['title' => $lesson['title'], 'summary' => $lesson['summary'] ?? '', 'content' => $lesson['content'] ?? ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid form token, please try again.';
    } else {
        $f['title']   = trim($_POST['title'] ?? '');
        $f['summary'] = trim($_POST['summary'] ?? '');
        $f['content'] = $_POST['content'] ?? '';

        if ($f['title'] === '') $errors[] = 'Title is required.';

        if (!$errors) {
            $db->prepare('UPDATE pm_learning_lessons SET title = ?, summary = ?, content = ? WHERE id = ?')
               ->execute([$f['title'], $f['summary'] ?: null, $f['content'], $id]);
            flash('success', 'Lesson saved.');
            redirect(APP_URL . '/learning/course.php?id=' . (int)$lesson['course_id']);
        }
    }
}

$pageTitle  = 'Edit: ' . $lesson['title'];
$activePage = 'learning';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Edit lesson</h1>
    <p>Blank lines start a new paragraph. Start a line with <code>## </code> for a heading, <code>- </code> for a bullet list, or <code>&gt; </code> for a quote.</p>
  </div>
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
        <label for="summary">Summary <span style="color:var(--muted); font-weight:400; text-transform:none;">(shown in the lesson list — optional)</span></label>
        <input type="text" id="summary" name="summary" value="<?= e($f['summary']) ?>">
      </div>
      <div class="field form-full">
        <label for="content">Content</label>
        <textarea id="content" name="content" rows="24" style="font-family: 'IBM Plex Sans', sans-serif;"><?= e($f['content']) ?></textarea>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn--primary">Save lesson</button>
      <a href="<?= APP_URL ?>/learning/course.php?id=<?= (int)$lesson['course_id'] ?>" class="btn btn--outline">Cancel</a>
      <a href="<?= APP_URL ?>/learning/lesson.php?id=<?= $id ?>" class="btn btn--outline" style="margin-left:auto;">Preview</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
