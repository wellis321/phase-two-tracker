<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$db = db();

$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid form token, please try again.');
        redirect(APP_URL . '/learning/lesson.php?id=' . $id);
    }
    if (isset($_POST['toggle_complete'])) {
        toggle_lesson_progress($db, $id, get_current_user_id());
        redirect(APP_URL . '/learning/lesson.php?id=' . $id);
    }
}

$lesson = get_lesson($db, $id, get_current_user_id());
if (!$lesson) {
    flash('error', 'Lesson not found.');
    redirect(APP_URL . '/learning/index.php');
}

$sequence = get_lesson_sequence($db, $lesson['courseId']);
$position = null;
foreach ($sequence as $i => $row) {
    if ((int)$row['id'] === $id) { $position = $i; break; }
}
$prevLesson = $position !== null && $position > 0 ? $sequence[$position - 1] : null;
$nextLesson = $position !== null && $position < count($sequence) - 1 ? $sequence[$position + 1] : null;

$totalInCourse = count($sequence);
$completedInCourse = 0;
if ($totalInCourse > 0) {
    $lessonIds = implode(',', array_map(fn($r) => (int)$r['id'], $sequence));
    $stmt = $db->prepare("SELECT COUNT(*) FROM pm_learning_progress WHERE user_id = ? AND lesson_id IN ($lessonIds)");
    $stmt->execute([get_current_user_id()]);
    $completedInCourse = (int)$stmt->fetchColumn();
}

$pageTitle  = $lesson['title'];
$activePage = 'learning';
require __DIR__ . '/../includes/layout/header.php';
?>

<p class="back-nav" style="margin-top:0;"><a class="back-link" href="<?= APP_URL ?>/learning/course.php?id=<?= $lesson['courseId'] ?>"><?= icon_arrow_left() ?> <?= e($lesson['courseTitle']) ?></a></p>

<div class="page-header">
  <div>
    <p class="li-meta" style="margin:0 0 .2rem;"><?= e($lesson['moduleTitle']) ?></p>
    <h1><?= e($lesson['title']) ?></h1>
    <?php if ($totalInCourse > 0): ?>
    <p class="li-meta" style="margin-top:.3rem;"><?= $completedInCourse ?> / <?= $totalInCourse ?> lessons complete in this course</p>
    <div class="lesson-progress-bar"><div class="lesson-progress-bar-fill" style="width:<?= round($completedInCourse / $totalInCourse * 100) ?>%;"></div></div>
    <?php endif; ?>
  </div>
  <div class="page-header-actions">
    <form method="POST" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="toggle_complete" value="1">
      <button type="submit" class="btn <?= $lesson['completed'] ? 'btn--outline' : 'btn--primary' ?>"><?= $lesson['completed'] ? '&#10003; Completed' : 'Mark complete' ?></button>
    </form>
    <?php if (is_admin()): ?>
    <a href="<?= APP_URL ?>/learning/lesson-edit.php?id=<?= $id ?>" class="btn btn--outline">Edit</a>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="lesson-content"><?= render_lesson_content($lesson['content']) ?></div>
</div>

<div class="lesson-nav">
  <div class="lesson-nav-link">
    <?php if ($prevLesson): ?>
    <a class="back-link" href="<?= APP_URL ?>/learning/lesson.php?id=<?= $prevLesson['id'] ?>"><?= icon_arrow_left() ?> <?= e($prevLesson['title']) ?></a>
    <?php endif; ?>
  </div>
  <div class="lesson-nav-link lesson-nav-link--next">
    <?php if ($nextLesson): ?>
    <a class="back-link" href="<?= APP_URL ?>/learning/lesson.php?id=<?= $nextLesson['id'] ?>"><?= e($nextLesson['title']) ?> &rarr;</a>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
