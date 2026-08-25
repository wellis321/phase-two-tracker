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
$stmt = $db->prepare('SELECT * FROM pm_learning_courses WHERE id = ?');
$stmt->execute([$id]);
$course = $stmt->fetch();
if (!$course) {
    flash('error', 'Course not found.');
    redirect(APP_URL . '/learning/index.php');
}

$progress       = get_course_progress_by_user($db, $id);
$quizStats      = get_quiz_stats_by_user($db, $id);
$totalQuestions = $db->prepare(
    'SELECT COUNT(*) FROM pm_learning_quiz_questions q
     LEFT JOIN pm_learning_lessons l ON l.id = q.lesson_id
     LEFT JOIN pm_learning_modules ml ON ml.id = COALESCE(q.module_id, l.module_id)
     WHERE ml.course_id = ?'
);
$totalQuestions->execute([$id]);
$totalQuestions = (int)$totalQuestions->fetchColumn();

$pageTitle  = 'Progress: ' . $course['title'];
$activePage = 'learning';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Progress</h1>
    <p><?= e($course['title']) ?></p>
  </div>
</div>

<?php if ($progress): ?>
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Person</th>
        <th>Progress</th>
        <th>Furthest module reached</th>
        <th>Understanding checks</th>
        <th>Last activity</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($progress as $p): ?>
      <tr>
        <td><?= e($p['name']) ?></td>
        <td>
          <?php if ($p['completedCount'] > 0): ?>
          <?= $p['completedCount'] ?> / <?= $p['totalLessons'] ?>
          <div class="lesson-progress-bar" style="margin-top:.3rem; max-width:160px;"><div class="lesson-progress-bar-fill" style="width:<?= round($p['completedCount'] / $p['totalLessons'] * 100) ?>%;"></div></div>
          <?php else: ?>
          <span class="empty-note">Not started</span>
          <?php endif; ?>
        </td>
        <td><?= $p['furthestModule'] ? e($p['furthestModule']) : '—' ?></td>
        <td>
          <?php if ($totalQuestions === 0): ?>
          &mdash;
          <?php else: $qs = $quizStats[$p['userId']] ?? null; ?>
          <?= $qs ? $qs['correct'] . ' / ' . $qs['total'] . ' complete' : '<span class="empty-note">Not started</span>' ?>
          <?php endif; ?>
        </td>
        <td><?= $p['lastActivity'] ? format_date($p['lastActivity']) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<p class="empty-note">This course doesn't have any lessons yet, so there's nothing to track progress against.</p>
<?php endif; ?>

<p class="back-nav"><a class="back-link" href="<?= APP_URL ?>/learning/course.php?id=<?= $id ?>"><?= icon_arrow_left() ?> Back to <?= e($course['title']) ?></a></p>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
