<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$db = db();

$moduleId = (int)($_GET['module_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid form token, please try again.');
        redirect(APP_URL . '/learning/checkpoint.php?module_id=' . $moduleId);
    }
    if (isset($_POST['answer_question'])) {
        $questionId = (int)$_POST['answer_question'];
        $optionId   = (int)($_POST['option_id'] ?? 0);
        record_quiz_attempt($db, get_current_user_id(), $questionId, $optionId);
        redirect(APP_URL . '/learning/checkpoint.php?module_id=' . $moduleId . '#quiz-q-' . $questionId);
    } elseif (isset($_POST['add_question'])) {
        require_admin();
        quiz_question_save($db, null, null, $moduleId, $_POST);
        redirect(APP_URL . '/learning/checkpoint.php?module_id=' . $moduleId);
    } elseif (isset($_POST['update_question'])) {
        require_admin();
        quiz_question_save($db, (int)$_POST['update_question'], null, $moduleId, $_POST);
        redirect(APP_URL . '/learning/checkpoint.php?module_id=' . $moduleId);
    } elseif (isset($_POST['delete_question'])) {
        require_admin();
        $db->prepare('DELETE FROM pm_learning_quiz_questions WHERE id = ?')->execute([(int)$_POST['delete_question']]);
        flash('success', 'Question deleted.');
        redirect(APP_URL . '/learning/checkpoint.php?module_id=' . $moduleId);
    }
}

$stmt = $db->prepare(
    'SELECT m.id, m.title AS module_title, c.id AS course_id, c.title AS course_title
     FROM pm_learning_modules m JOIN pm_learning_courses c ON c.id = m.course_id
     WHERE m.id = ?'
);
$stmt->execute([$moduleId]);
$module = $stmt->fetch();
if (!$module) {
    flash('error', 'Module not found.');
    redirect(APP_URL . '/learning/index.php');
}

$glossary  = get_learning_glossary($db);
$questions = get_quiz_questions_for_module($db, $moduleId);
$questionIds = array_map(fn($q) => (int)$q['id'], $questions);
$attempts  = get_user_quiz_attempts($db, get_current_user_id(), $questionIds);
$correctOptions = get_correct_options($db, $questionIds);

$pageTitle  = 'Checkpoint: ' . $module['module_title'];
$activePage = 'learning';
require __DIR__ . '/../includes/layout/header.php';
?>

<p class="back-nav" style="margin-top:0;">
  <a class="back-link" href="<?= APP_URL ?>/learning/course.php?id=<?= $module['course_id'] ?>"><?= icon_arrow_left() ?> <?= e($module['course_title']) ?></a>
</p>

<div class="page-header">
  <div>
    <p class="li-meta" style="margin:0 0 .2rem;"><?= e($module['module_title']) ?></p>
    <h1>Checkpoint</h1>
    <p>A scenario question covering everything this module just taught, not just what's in one lesson.</p>
  </div>
</div>

<div class="card quiz-card">
  <?php if (!$questions): ?>
  <p class="empty-note">No checkpoint question yet<?= is_admin() ? ' — add one below.' : '.' ?></p>
  <?php endif; ?>

  <?php foreach ($questions as $q):
      $qid     = (int)$q['id'];
      $attempt = $attempts[$qid] ?? null;
      $retry   = isset($_GET['retry']) && (int)$_GET['retry'] === $qid;
      $showForm = !$attempt || $retry;
  ?>
  <div class="quiz-question" id="quiz-q-<?= $qid ?>">
    <div class="quiz-prompt"><?= render_lesson_content($q['prompt'], $glossary) ?></div>

    <?php if ($showForm): ?>
    <form method="POST" action="#quiz-q-<?= $qid ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="answer_question" value="<?= $qid ?>">
      <?php foreach ($q['options'] as $opt): ?>
      <label class="quiz-option-label">
        <input type="radio" name="option_id" value="<?= $opt['id'] ?>" required>
        <span><?= render_lesson_inline((string)$opt['option_text']) ?></span>
      </label>
      <?php endforeach; ?>
      <button type="submit" class="btn btn--primary btn--sm" style="margin-top:.5rem;">Check answer</button>
    </form>
    <?php else: ?>
    <p class="quiz-feedback <?= $attempt['is_correct'] ? 'quiz-feedback--correct' : 'quiz-feedback--incorrect' ?>">
      <strong><?= $attempt['is_correct'] ? 'Correct.' : 'Not quite.' ?></strong>
      You picked "<?= e($attempt['option_text']) ?>".
      <?php if ($attempt['explanation']): ?><?= e($attempt['explanation']) ?><?php endif; ?>
      <?php if (!$attempt['is_correct'] && isset($correctOptions[$qid])): ?>
        The right answer was "<?= e($correctOptions[$qid]['option_text']) ?>".
        <?php if ($correctOptions[$qid]['explanation']): ?><?= e($correctOptions[$qid]['explanation']) ?><?php endif; ?>
      <?php endif; ?>
    </p>
    <a class="back-link" href="<?= APP_URL ?>/learning/checkpoint.php?module_id=<?= $moduleId ?>&retry=<?= $qid ?>#quiz-q-<?= $qid ?>">Try again</a>
    <?php endif; ?>

    <?php if (is_admin()): ?>
    <div class="quiz-admin-actions">
      <button type="button" class="icon-btn" data-show-target="quiz-edit-<?= $qid ?>" title="Edit question" aria-label="Edit question"><?= icon_edit() ?></button>
      <form method="POST" action="" onsubmit="return confirm('Delete this question?');" style="display:inline;">
        <?= csrf_field() ?>
        <input type="hidden" name="delete_question" value="<?= $qid ?>">
        <button type="submit" class="btn btn--outline btn--sm">Delete</button>
      </form>
    </div>
    <form method="POST" action="" id="quiz-edit-<?= $qid ?>" class="editable-form form-grid" hidden style="margin-top:.75rem;">
      <?= csrf_field() ?>
      <input type="hidden" name="update_question" value="<?= $qid ?>">
      <?php quiz_question_form_fields($q); ?>
      <div class="form-actions form-full" style="margin-top:0; padding-top:0; border-top:none;">
        <button type="submit" class="btn btn--primary btn--sm">Save question</button>
        <button type="button" class="btn btn--outline btn--sm" data-hide-self>Cancel</button>
      </div>
    </form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

  <?php if (is_admin() && !$questions): ?>
  <button type="button" class="btn btn--outline btn--sm" data-show-target="quiz-add-form" style="margin-top:.5rem;">+ Add checkpoint question</button>
  <form method="POST" action="" id="quiz-add-form" class="editable-form form-grid" hidden style="margin-top:.75rem;">
    <?= csrf_field() ?>
    <input type="hidden" name="add_question" value="1">
    <?php quiz_question_form_fields(null); ?>
    <div class="form-actions form-full" style="margin-top:0; padding-top:0; border-top:none;">
      <button type="submit" class="btn btn--primary btn--sm">Add question</button>
      <button type="button" class="btn btn--outline btn--sm" data-hide-self>Cancel</button>
    </div>
  </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
