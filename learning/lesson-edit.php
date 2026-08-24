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
$f = [
    'title'      => $lesson['title'],
    'summary'    => $lesson['summary'] ?? '',
    'content'    => $lesson['content'] ?? '',
    'year_label' => $lesson['year_label'] ?? '',
    'year_sort'  => $lesson['year_sort'] !== null ? (string)$lesson['year_sort'] : '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid form token, please try again.';
    } else {
        $f['title']      = trim($_POST['title'] ?? '');
        $f['summary']    = trim($_POST['summary'] ?? '');
        $f['content']    = $_POST['content'] ?? '';
        $f['year_label'] = trim($_POST['year_label'] ?? '');
        $f['year_sort']  = trim($_POST['year_sort'] ?? '');
        $yearSort        = $f['year_sort'];

        if ($f['title'] === '') $errors[] = 'Title is required.';
        if ($f['year_label'] !== '' && $yearSort === '') $errors[] = 'Add a sort year (e.g. -380 for 380 BC) so this lesson can be placed on the timeline.';

        if (!$errors) {
            $db->prepare('UPDATE pm_learning_lessons SET title = ?, summary = ?, content = ?, year_label = ?, year_sort = ? WHERE id = ?')
               ->execute([
                   $f['title'], $f['summary'] ?: null, $f['content'],
                   $f['year_label'] ?: null, $yearSort !== '' ? (int)$yearSort : null,
                   $id,
               ]);
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
    <p>Blank lines start a new paragraph. Start a line with <code>## </code> for a heading, <code>- </code> for a bullet list, or <code>&gt; </code> for a quote. <code>[label](https://url)</code> anywhere becomes a link. <code>![caption](https://url)</code> on its own line becomes a small portrait floated beside the text — must be a Wikimedia Commons image URL.</p>
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
      <div class="field">
        <label for="year_label">Timeline year <span style="color:var(--muted); font-weight:400; text-transform:none;">(optional)</span></label>
        <input type="text" id="year_label" name="year_label" value="<?= e($f['year_label']) ?>" placeholder="e.g. 1859 or c. 380 BC">
      </div>
      <div class="field">
        <label for="year_sort">Sort year <span style="color:var(--muted); font-weight:400; text-transform:none;">(number, negative for BC)</span></label>
        <input type="number" id="year_sort" name="year_sort" value="<?= e($f['year_sort']) ?>" placeholder="e.g. -380">
      </div>
      <p class="empty-note form-full" style="margin:-.5rem 0 0;">Setting both places this lesson on the course's lineage timeline, using the first portrait image in its content as the thumbnail. Leave blank to leave it off the timeline.</p>
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
