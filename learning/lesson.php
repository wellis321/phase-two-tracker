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
    } elseif (isset($_POST['answer_question'])) {
        $questionId = (int)$_POST['answer_question'];
        $optionId   = (int)($_POST['option_id'] ?? 0);
        record_quiz_attempt($db, get_current_user_id(), $questionId, $optionId);
        redirect(APP_URL . '/learning/lesson.php?id=' . $id . '#quiz-q-' . $questionId);
    } elseif (isset($_POST['submit_reflection'])) {
        $questionId = (int)$_POST['submit_reflection'];
        $response   = trim($_POST['response_text'] ?? '');
        if ($response !== '') {
            record_quiz_reflection($db, get_current_user_id(), $questionId, $response);
        }
        redirect(APP_URL . '/learning/lesson.php?id=' . $id . '#quiz-q-' . $questionId);
    } elseif (isset($_POST['add_question'])) {
        require_admin();
        quiz_question_save($db, null, $id, null, $_POST);
        redirect(APP_URL . '/learning/lesson.php?id=' . $id);
    } elseif (isset($_POST['update_question'])) {
        require_admin();
        quiz_question_save($db, (int)$_POST['update_question'], $id, null, $_POST);
        redirect(APP_URL . '/learning/lesson.php?id=' . $id);
    } elseif (isset($_POST['delete_question'])) {
        require_admin();
        $db->prepare('DELETE FROM pm_learning_quiz_questions WHERE id = ?')->execute([(int)$_POST['delete_question']]);
        flash('success', 'Question deleted.');
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

$glossary  = get_learning_glossary($db);
$questions = get_quiz_questions_for_lesson($db, $id);
$questionIds = array_map(fn($q) => (int)$q['id'], $questions);
$attempts  = get_user_quiz_attempts($db, get_current_user_id(), $questionIds);
$correctOptions = get_correct_options($db, $questionIds);

$pageTitle  = $lesson['title'];
$activePage = 'learning';
require __DIR__ . '/../includes/layout/header.php';
?>

<p class="back-nav" style="margin-top:0;">
  <a class="back-link" href="<?= APP_URL ?>/learning/course.php?id=<?= $lesson['courseId'] ?>"><?= icon_arrow_left() ?> <?= e($lesson['courseTitle']) ?></a>
  <a class="back-link" href="<?= APP_URL ?>/learning/glossary.php" style="margin-left:1.5rem;">Glossary</a>
</p>

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
  <div class="lesson-content"><?= render_lesson_content($lesson['content'], $glossary) ?></div>
</div>

<?php if ($questions || is_admin()): ?>
<div class="card quiz-card">
  <h2 style="margin-top:0;">Check your understanding</h2>
  <?php if (!$questions): ?>
  <p class="empty-note">No questions yet.</p>
  <?php endif; ?>

  <?php foreach ($questions as $q):
      $qid     = (int)$q['id'];
      $attempt = $attempts[$qid] ?? null;
      $retry   = isset($_GET['retry']) && (int)$_GET['retry'] === $qid;
      $showForm = !$attempt || $retry;
  ?>
  <div class="quiz-question" id="quiz-q-<?= $qid ?>">
    <div class="quiz-prompt"><?= render_lesson_content($q['prompt'], $glossary) ?></div>

    <?php if ($q['format'] === 'reflection'): ?>
    <form method="POST" action="#quiz-q-<?= $qid ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="submit_reflection" value="<?= $qid ?>">
      <div class="field">
        <textarea name="response_text" rows="4" placeholder="Write your own answer first..."><?= e((string)($attempt['response_text'] ?? '')) ?></textarea>
      </div>
      <button type="submit" class="btn btn--primary btn--sm" style="margin-top:.5rem;"><?= $attempt ? 'Update my answer' : 'Submit' ?></button>
    </form>
    <?php if ($attempt): ?>
    <div class="quiz-feedback" style="margin-top:.75rem;">
      <strong>One way to think about it:</strong>
      <?= render_lesson_content((string)$q['model_answer'], $glossary) ?>
    </div>
    <?php endif; ?>

    <?php elseif ($showForm): ?>
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
    <a class="back-link" href="<?= APP_URL ?>/learning/lesson.php?id=<?= $id ?>&retry=<?= $qid ?>#quiz-q-<?= $qid ?>">Try again</a>
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

  <?php if (is_admin()): ?>
  <button type="button" class="btn btn--outline btn--sm" data-show-target="quiz-add-form" style="margin-top:.5rem;">+ Add question</button>
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
<?php endif; ?>

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
