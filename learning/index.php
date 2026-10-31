<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$db = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        $errors[] = 'Invalid form token, please try again.';
        goto render;
    }

    if (isset($_POST['add_course'])) {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($title === '') {
            $errors[] = 'Course title is required.';
        } else {
            $db->prepare('INSERT INTO pm_learning_courses (title, description, created_by) VALUES (?, ?, ?)')
               ->execute([$title, $description ?: null, get_current_user_id()]);
            flash('success', "Course '{$title}' created.");
            redirect(APP_URL . '/learning/course.php?id=' . $db->lastInsertId());
        }
    } elseif (isset($_POST['delete_course'])) {
        $db->prepare('DELETE FROM pm_learning_courses WHERE id = ?')->execute([(int)$_POST['delete_course']]);
        flash('success', 'Course (and its modules and lessons) deleted.');
        redirect(APP_URL . '/learning/index.php');
    }
}

render:
$courses = get_learning_courses($db, get_current_user_id());

$pageTitle  = 'Shared Learning';
$activePage = 'learning';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Shared Learning</h1>
    <p>Courses the team can work through at their own pace — a shared library, not a fixed curriculum.</p>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/learning/glossary.php" class="btn btn--outline">Glossary</a>
  </div>
</div>

<?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>

<?php if ($courses): ?>
<div class="course-list">
  <?php foreach ($courses as $c):
      $total = (int)$c['lessonCount']; $done = (int)$c['completedCount'];
      $pct = $total > 0 ? (int)round($done / $total * 100) : 0;
      $cta = $done === 0 ? 'Start course' : ($done >= $total ? 'Review course' : 'Continue');
      $href = APP_URL . '/learning/course.php?id=' . $c['id'];
  ?>
  <article class="course-card">
    <div class="course-card-main">
      <h2 class="course-card-title"><a href="<?= $href ?>"><?= e($c['title']) ?></a></h2>
      <?php if ($c['description']): ?><p class="course-card-desc"><?= e($c['description']) ?></p><?php endif; ?>
      <ul class="course-card-stats">
        <li><strong><?= $c['moduleCount'] ?></strong> <?= $c['moduleCount'] === 1 ? 'module' : 'modules' ?></li>
        <li><strong><?= $total ?></strong> <?= $total === 1 ? 'lesson' : 'lessons' ?></li>
      </ul>
    </div>
    <div class="course-card-side">
      <?php if ($total > 0): ?>
      <p class="course-card-progress-label"><strong><?= $done ?></strong> of <?= $total ?> lessons complete</p>
      <div class="lesson-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $pct ?>"><div class="lesson-progress-bar-fill" style="width:<?= $pct ?>%;"></div></div>
      <?php else: ?>
      <p class="course-card-progress-label empty-note">No lessons yet</p>
      <?php endif; ?>
      <div class="course-card-actions">
        <a class="btn btn--primary" href="<?= $href ?>"><?= $cta ?></a>
        <?php if (is_admin()): ?>
        <form method="POST" action="" onsubmit="return confirm('Delete &quot;<?= e($c['title']) ?>&quot; and everything in it?');">
          <?= csrf_field() ?>
          <input type="hidden" name="delete_course" value="<?= $c['id'] ?>">
          <button type="submit" class="btn btn--outline btn--sm">Delete</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </article>
  <?php endforeach; ?>
</div>
<?php else: ?>
<p class="empty-note">No courses yet<?= is_admin() ? ' — add the first one below.' : '.' ?></p>
<?php endif; ?>

<?php if (is_admin()): ?>
<div class="card" style="margin-top:1rem;">
  <h2>Add a course</h2>
  <form method="POST" action="" class="form-grid">
    <?= csrf_field() ?>
    <div class="field form-full">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" required>
    </div>
    <div class="field form-full">
      <label for="description">Description</label>
      <textarea id="description" name="description" rows="2" placeholder="A sentence or two on what this course covers and who it's for."></textarea>
    </div>
    <div class="form-actions form-full" style="margin-top:0; padding-top:0; border-top:none;">
      <button type="submit" name="add_course" value="1" class="btn btn--primary">+ Add course</button>
    </div>
  </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
