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

$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    if (!csrf_verify()) {
        $errors[] = 'Invalid form token, please try again.';
        goto render;
    }

    if (isset($_POST['rename_course'])) {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($title === '') {
            $errors[] = 'Course title is required.';
        } else {
            $db->prepare('UPDATE pm_learning_courses SET title = ?, description = ? WHERE id = ?')
               ->execute([$title, $description ?: null, $id]);
            flash('success', 'Course updated.');
            redirect(APP_URL . '/learning/course.php?id=' . $id);
        }
    } elseif (isset($_POST['add_module'])) {
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            $errors[] = 'Module title is required.';
        } else {
            $next = (int)$db->query("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM pm_learning_modules WHERE course_id = $id")->fetchColumn();
            $db->prepare('INSERT INTO pm_learning_modules (course_id, title, sort_order) VALUES (?, ?, ?)')->execute([$id, $title, $next]);
            flash('success', "Module '{$title}' added.");
            redirect(APP_URL . '/learning/course.php?id=' . $id);
        }
    } elseif (isset($_POST['rename_module'])) {
        $moduleId    = (int)$_POST['rename_module'];
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($title === '') {
            $errors[] = 'Module title is required.';
        } else {
            $db->prepare('UPDATE pm_learning_modules SET title = ?, description = ? WHERE id = ?')
               ->execute([$title, $description ?: null, $moduleId]);
            flash('success', 'Module updated.');
            redirect(APP_URL . '/learning/course.php?id=' . $id);
        }
    } elseif (isset($_POST['delete_module'])) {
        $db->prepare('DELETE FROM pm_learning_modules WHERE id = ?')->execute([(int)$_POST['delete_module']]);
        flash('success', 'Module (and its lessons) deleted.');
        redirect(APP_URL . '/learning/course.php?id=' . $id);
    } elseif (isset($_POST['add_lesson'])) {
        $moduleId = (int)($_POST['module_id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $summary  = trim($_POST['summary'] ?? '');
        if ($title === '') {
            $errors[] = 'Lesson title is required.';
        } else {
            $next = (int)$db->query("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM pm_learning_lessons WHERE module_id = $moduleId")->fetchColumn();
            $db->prepare('INSERT INTO pm_learning_lessons (module_id, title, summary, sort_order) VALUES (?, ?, ?, ?)')
               ->execute([$moduleId, $title, $summary ?: null, $next]);
            $lessonId = (int)$db->lastInsertId();
            flash('success', "Lesson '{$title}' added — write its content below.");
            redirect(APP_URL . '/learning/lesson-edit.php?id=' . $lessonId);
        }
    } elseif (isset($_POST['delete_lesson'])) {
        $db->prepare('DELETE FROM pm_learning_lessons WHERE id = ?')->execute([(int)$_POST['delete_lesson']]);
        flash('success', 'Lesson deleted.');
        redirect(APP_URL . '/learning/course.php?id=' . $id);
    }
}

render:
$course   = get_course_with_modules($db, $id, get_current_user_id());
$timeline = get_course_timeline($db, $id);
if (!$course) {
    flash('error', 'Course not found.');
    redirect(APP_URL . '/learning/index.php');
}

$checkpointModuleIds = $db->prepare(
    'SELECT DISTINCT q.module_id FROM pm_learning_quiz_questions q
     JOIN pm_learning_modules m ON m.id = q.module_id
     WHERE m.course_id = ?'
);
$checkpointModuleIds->execute([$id]);
$checkpointModuleIds = array_map('intval', $checkpointModuleIds->fetchAll(PDO::FETCH_COLUMN));

$pageTitle  = $course['title'];
$activePage = 'learning';
require __DIR__ . '/../includes/layout/header.php';
?>

<?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>

<?php if (is_admin()): ?>
<div class="editable-wrap" style="margin-bottom:1rem;">
  <div class="editable-view page-header">
    <div>
      <h1><?= e($course['title']) ?></h1>
      <?php if ($course['description']): ?><p><?= e($course['description']) ?></p><?php endif; ?>
    </div>
    <div class="page-header-actions">
      <a href="<?= APP_URL ?>/learning/progress.php?id=<?= $course['id'] ?>" class="btn btn--outline">View progress</a>
      <button type="button" class="btn btn--outline" data-edit-toggle>Edit course</button>
    </div>
  </div>
  <form method="POST" action="" class="editable-form form-grid" hidden>
    <?= csrf_field() ?>
    <input type="hidden" name="rename_course" value="1">
    <div class="field form-full">
      <label>Title</label>
      <input type="text" name="title" value="<?= e($course['title']) ?>" required>
    </div>
    <div class="field form-full">
      <label>Description</label>
      <textarea name="description" rows="2"><?= e((string)$course['description']) ?></textarea>
    </div>
    <div class="form-actions form-full" style="margin-top:0; padding-top:0; border-top:none;">
      <button type="submit" class="btn btn--primary btn--sm">Save</button>
      <button type="button" class="btn btn--outline btn--sm" data-edit-cancel>Cancel</button>
    </div>
  </form>
</div>
<?php else: ?>
<div class="page-header">
  <div>
    <h1><?= e($course['title']) ?></h1>
    <?php if ($course['description']): ?><p><?= e($course['description']) ?></p><?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php if ($timeline): ?>
<div class="card lineage-card">
  <h2 style="margin-top:0;">The lineage, at a glance</h2>
  <p class="li-meta" style="margin-top:-.3rem;">Every dated figure or work this course covers, in order. Click one to jump to its lesson.</p>
  <?= render_course_timeline($timeline) ?>
</div>
<?php endif; ?>

<?php if (!$course['modules']): ?>
<p class="empty-note">No modules yet<?= is_admin() ? ' — add the first one below.' : '.' ?></p>
<?php endif; ?>

<?php foreach ($course['modules'] as $module): ?>
<div class="card learning-module" style="margin-bottom:1rem;">
  <?php if (is_admin()): ?>
  <div class="editable-wrap">
    <div class="editable-view learning-module-head">
      <h2><?= e($module['title']) ?></h2>
      <div class="learning-module-actions">
        <button type="button" class="icon-btn" data-edit-toggle title="Edit module" aria-label="Edit module <?= e($module['title']) ?>"><?= icon_edit() ?></button>
        <form method="POST" action="" onsubmit="return confirm('Delete &quot;<?= e($module['title']) ?>&quot; and its lessons?');" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="delete_module" value="<?= $module['id'] ?>">
          <button type="submit" class="btn btn--outline btn--sm">Delete</button>
        </form>
      </div>
    </div>
    <?php if ($module['description']): ?><p class="learning-module-desc"><?= e($module['description']) ?></p><?php endif; ?>
    <form method="POST" action="" class="editable-form form-grid" hidden>
      <?= csrf_field() ?>
      <input type="hidden" name="rename_module" value="<?= $module['id'] ?>">
      <div class="field form-full">
        <label>Title</label>
        <input type="text" name="title" value="<?= e($module['title']) ?>" required>
      </div>
      <div class="field form-full">
        <label>Description</label>
        <textarea name="description" rows="2"><?= e((string)$module['description']) ?></textarea>
      </div>
      <div class="form-actions form-full" style="margin-top:0; padding-top:0; border-top:none;">
        <button type="submit" class="btn btn--primary btn--sm">Save</button>
        <button type="button" class="btn btn--outline btn--sm" data-edit-cancel>Cancel</button>
      </div>
    </form>
  </div>
  <?php else: ?>
  <h2><?= e($module['title']) ?></h2>
  <?php if ($module['description']): ?><p class="learning-module-desc"><?= e($module['description']) ?></p><?php endif; ?>
  <?php endif; ?>

  <?php if ($module['lessons']): ?>
  <ul class="learning-lesson-list">
    <?php foreach ($module['lessons'] as $lesson): ?>
    <li class="learning-lesson-row">
      <a href="<?= APP_URL ?>/learning/lesson.php?id=<?= $lesson['id'] ?>" class="learning-lesson-link">
        <span class="learning-lesson-check<?= $lesson['completed'] ? ' learning-lesson-check--done' : '' ?>" aria-hidden="true"><?= $lesson['completed'] ? '&#10003;' : '' ?></span>
        <span>
          <?= e($lesson['title']) ?>
          <?php if ($lesson['summary']): ?><span class="li-meta learning-lesson-summary"><?= e($lesson['summary']) ?></span><?php endif; ?>
        </span>
      </a>
      <?php if (is_admin()): ?>
      <div class="learning-lesson-actions">
        <a href="<?= APP_URL ?>/learning/lesson-edit.php?id=<?= $lesson['id'] ?>" class="icon-btn" title="Edit lesson" aria-label="Edit lesson <?= e($lesson['title']) ?>"><?= icon_edit() ?></a>
        <form method="POST" action="" onsubmit="return confirm('Delete &quot;<?= e($lesson['title']) ?>&quot;?');">
          <?= csrf_field() ?>
          <input type="hidden" name="delete_lesson" value="<?= $lesson['id'] ?>">
          <button type="submit" class="btn btn--outline btn--sm">Delete</button>
        </form>
      </div>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php else: ?>
  <p class="empty-note">No lessons in this module yet.</p>
  <?php endif; ?>

  <?php if (in_array($module['id'], $checkpointModuleIds, true) || is_admin()): ?>
  <p style="margin-top:.75rem;">
    <a class="back-link" href="<?= APP_URL ?>/learning/checkpoint.php?module_id=<?= $module['id'] ?>">
      <?= in_array($module['id'], $checkpointModuleIds, true) ? 'Checkpoint: test your understanding of this module' : 'Checkpoint (add a scenario question)' ?>
    </a>
  </p>
  <?php endif; ?>

  <?php if (is_admin()): ?>
  <button type="button" class="btn btn--outline btn--sm" data-show-target="lesson-form-<?= $module['id'] ?>" style="margin-top:.75rem;">+ Add lesson</button>
  <form method="POST" action="" id="lesson-form-<?= $module['id'] ?>" class="editable-form form-grid" hidden style="margin-top:.75rem;">
    <?= csrf_field() ?>
    <input type="hidden" name="module_id" value="<?= $module['id'] ?>">
    <div class="field form-full">
      <label>Lesson title</label>
      <input type="text" name="title" required>
    </div>
    <div class="field form-full">
      <label>Summary <span style="color:var(--muted); font-weight:400; text-transform:none;">(shown in the lesson list — optional)</span></label>
      <input type="text" name="summary">
    </div>
    <div class="form-actions form-full" style="margin-top:0; padding-top:0; border-top:none;">
      <button type="submit" name="add_lesson" value="1" class="btn btn--primary btn--sm">Add lesson &amp; write content</button>
      <button type="button" class="btn btn--outline btn--sm" data-hide-self>Cancel</button>
    </div>
  </form>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<?php if (is_admin()): ?>
<div class="card">
  <h2>Add a module</h2>
  <form method="POST" action="" style="display:flex; gap:.6rem; align-items:flex-end; flex-wrap:wrap;">
    <?= csrf_field() ?>
    <div class="field" style="flex:1 1 260px;">
      <label for="module-title">Title</label>
      <input type="text" id="module-title" name="title" required>
    </div>
    <button type="submit" name="add_module" value="1" class="btn btn--primary btn--sm">+ Add module</button>
  </form>
</div>
<?php endif; ?>

<p class="back-nav">
  <a class="back-link" href="<?= APP_URL ?>/learning/index.php"><?= icon_arrow_left() ?> Back to Shared Learning</a>
  <a class="back-link" href="<?= APP_URL ?>/learning/glossary.php" style="margin-left:1.5rem;">Glossary</a>
</p>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
