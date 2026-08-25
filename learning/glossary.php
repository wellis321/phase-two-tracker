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

    if (isset($_POST['add_term'])) {
        $term       = trim($_POST['term'] ?? '');
        $definition = trim($_POST['definition'] ?? '');
        if ($term === '' || $definition === '') {
            $errors[] = 'Both a term and a definition are required.';
        } else {
            try {
                $db->prepare('INSERT INTO pm_learning_glossary (term, definition) VALUES (?, ?)')->execute([$term, $definition]);
                flash('success', "\"{$term}\" added to the glossary.");
                redirect(APP_URL . '/learning/glossary.php');
            } catch (PDOException $e) {
                $errors[] = str_contains($e->getMessage(), 'uniq_term')
                    ? "\"{$term}\" is already in the glossary."
                    : 'Could not save that term.';
            }
        }
    } elseif (isset($_POST['rename_term'])) {
        $id         = (int)$_POST['rename_term'];
        $term       = trim($_POST['term'] ?? '');
        $definition = trim($_POST['definition'] ?? '');
        if ($term === '' || $definition === '') {
            $errors[] = 'Both a term and a definition are required.';
        } else {
            try {
                $db->prepare('UPDATE pm_learning_glossary SET term = ?, definition = ? WHERE id = ?')->execute([$term, $definition, $id]);
                flash('success', 'Glossary entry updated.');
                redirect(APP_URL . '/learning/glossary.php');
            } catch (PDOException $e) {
                $errors[] = str_contains($e->getMessage(), 'uniq_term')
                    ? "\"{$term}\" is already in the glossary."
                    : 'Could not save that term.';
            }
        }
    } elseif (isset($_POST['delete_term'])) {
        $db->prepare('DELETE FROM pm_learning_glossary WHERE id = ?')->execute([(int)$_POST['delete_term']]);
        flash('success', 'Glossary entry deleted.');
        redirect(APP_URL . '/learning/glossary.php');
    }
}

render:
$terms = $db->query('SELECT * FROM pm_learning_glossary ORDER BY term')->fetchAll();

$pageTitle  = 'Glossary';
$activePage = 'learning';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Glossary</h1>
    <p>Terms used across Shared Learning courses. The first mention of any of these in a lesson is also hoverable (or tappable) right where it appears.</p>
  </div>
</div>

<?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>

<?php if ($terms): ?>
<div class="card">
  <dl class="glossary-list">
    <?php foreach ($terms as $t): ?>
    <div class="glossary-entry editable-wrap">
      <div class="editable-view glossary-entry-view">
        <div>
          <dt><?= e($t['term']) ?></dt>
          <dd><?= e($t['definition']) ?></dd>
        </div>
        <?php if (is_admin()): ?>
        <div class="glossary-entry-actions">
          <button type="button" class="icon-btn" data-edit-toggle title="Edit" aria-label="Edit <?= e($t['term']) ?>"><?= icon_edit() ?></button>
          <form method="POST" action="" onsubmit="return confirm('Delete &quot;<?= e($t['term']) ?>&quot; from the glossary?');">
            <?= csrf_field() ?>
            <input type="hidden" name="delete_term" value="<?= $t['id'] ?>">
            <button type="submit" class="btn btn--outline btn--sm">Delete</button>
          </form>
        </div>
        <?php endif; ?>
      </div>
      <?php if (is_admin()): ?>
      <form method="POST" action="" class="editable-form form-grid" hidden>
        <?= csrf_field() ?>
        <input type="hidden" name="rename_term" value="<?= $t['id'] ?>">
        <div class="field form-full">
          <label>Term</label>
          <input type="text" name="term" value="<?= e($t['term']) ?>" required>
        </div>
        <div class="field form-full">
          <label>Definition</label>
          <textarea name="definition" rows="2" required><?= e($t['definition']) ?></textarea>
        </div>
        <div class="form-actions" style="margin-top:0; padding-top:0; border-top:none;">
          <button type="submit" class="btn btn--primary btn--sm">Save</button>
          <button type="button" class="btn btn--outline btn--sm" data-edit-cancel>Cancel</button>
        </div>
      </form>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </dl>
</div>
<?php else: ?>
<p class="empty-note">No glossary terms yet<?= is_admin() ? ' — add the first one below.' : '.' ?></p>
<?php endif; ?>

<?php if (is_admin()): ?>
<div class="card" style="margin-top:1rem;">
  <h2>Add a term</h2>
  <form method="POST" action="" class="form-grid">
    <?= csrf_field() ?>
    <div class="field form-full">
      <label for="term">Term</label>
      <input type="text" id="term" name="term" required>
    </div>
    <div class="field form-full">
      <label for="definition">Definition</label>
      <textarea id="definition" name="definition" rows="2" required></textarea>
    </div>
    <div class="form-actions form-full" style="margin-top:0; padding-top:0; border-top:none;">
      <button type="submit" name="add_term" value="1" class="btn btn--primary">+ Add term</button>
    </div>
  </form>
</div>
<?php endif; ?>

<p class="back-nav"><a class="back-link" href="<?= APP_URL ?>/learning/index.php"><?= icon_arrow_left() ?> Back to Shared Learning</a></p>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
