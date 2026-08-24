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
</div>

<?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>

<?php if ($courses): ?>
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Course</th>
        <th>Modules</th>
        <th>Your progress</th>
        <?php if (is_admin()): ?><th class="col-actions">Actions</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($courses as $c): ?>
      <tr class="table-row--clickable" data-href="<?= APP_URL ?>/learning/course.php?id=<?= $c['id'] ?>">
        <td>
          <a href="<?= APP_URL ?>/learning/course.php?id=<?= $c['id'] ?>" class="table-entity-link"><?= e($c['title']) ?></a>
          <?php if ($c['description']): ?><div class="li-meta" style="margin-top:.2rem;"><?= e($c['description']) ?></div><?php endif; ?>
        </td>
        <td><?= $c['moduleCount'] ?> <?= $c['moduleCount'] === 1 ? 'module' : 'modules' ?>, <?= $c['lessonCount'] ?> <?= $c['lessonCount'] === 1 ? 'lesson' : 'lessons' ?></td>
        <td>
          <?php if ($c['lessonCount'] > 0): ?>
          <?= $c['completedCount'] ?> / <?= $c['lessonCount'] ?> complete
          <?php else: ?>
          <span class="empty-note">No lessons yet</span>
          <?php endif; ?>
        </td>
        <?php if (is_admin()): ?>
        <td class="col-actions">
          <form method="POST" action="" onsubmit="return confirm('Delete &quot;<?= e($c['title']) ?>&quot; and everything in it?');">
            <?= csrf_field() ?>
            <input type="hidden" name="delete_course" value="<?= $c['id'] ?>">
            <button type="submit" class="btn btn--outline btn--sm">Delete</button>
          </form>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
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
