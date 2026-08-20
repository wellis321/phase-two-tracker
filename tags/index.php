<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();
$db = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid form token, please try again.';
        goto render;
    }

    if (isset($_POST['add_category'])) {
        $name = trim($_POST['category_name'] ?? '');
        if ($name === '') {
            $errors[] = 'Category name is required.';
        } else {
            $nextOrder = (int)$db->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM pm_tag_categories')->fetchColumn();
            try {
                $db->prepare('INSERT INTO pm_tag_categories (name, sort_order) VALUES (?, ?)')->execute([$name, $nextOrder]);
                flash('success', "Category '{$name}' added.");
                redirect(APP_URL . '/tags/index.php');
            } catch (PDOException $e) {
                $errors[] = "A category called '{$name}' already exists.";
            }
        }
    } elseif (isset($_POST['add_tag'])) {
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name       = trim($_POST['tag_name'] ?? '');
        if ($name === '' || $categoryId <= 0) {
            $errors[] = 'Tag name is required.';
        } else {
            try {
                $db->prepare('INSERT INTO pm_tags (category_id, name) VALUES (?, ?)')->execute([$categoryId, $name]);
                flash('success', "Tag '{$name}' added.");
                redirect(APP_URL . '/tags/index.php');
            } catch (PDOException $e) {
                $errors[] = "A tag called '{$name}' already exists in that category.";
            }
        }
    } elseif (isset($_POST['rename_category'])) {
        $id   = (int)$_POST['rename_category'];
        $name = trim($_POST['category_name'] ?? '');
        if ($name === '') {
            $errors[] = 'Category name is required.';
        } else {
            try {
                $db->prepare('UPDATE pm_tag_categories SET name = ? WHERE id = ?')->execute([$name, $id]);
                flash('success', "Category renamed to '{$name}'.");
                redirect(APP_URL . '/tags/index.php');
            } catch (PDOException $e) {
                $errors[] = "A category called '{$name}' already exists.";
            }
        }
    } elseif (isset($_POST['rename_tag'])) {
        $id   = (int)$_POST['rename_tag'];
        $name = trim($_POST['tag_name'] ?? '');
        if ($name === '') {
            $errors[] = 'Tag name is required.';
        } else {
            try {
                $db->prepare('UPDATE pm_tags SET name = ? WHERE id = ?')->execute([$name, $id]);
                flash('success', "Tag renamed to '{$name}'.");
                redirect(APP_URL . '/tags/index.php');
            } catch (PDOException $e) {
                $errors[] = "A tag called '{$name}' already exists in that category.";
            }
        }
    } elseif (isset($_POST['delete_tag'])) {
        $db->prepare('DELETE FROM pm_tags WHERE id = ?')->execute([(int)$_POST['delete_tag']]);
        flash('success', 'Tag deleted.');
        redirect(APP_URL . '/tags/index.php');
    } elseif (isset($_POST['delete_category'])) {
        $db->prepare('DELETE FROM pm_tag_categories WHERE id = ?')->execute([(int)$_POST['delete_category']]);
        flash('success', 'Category and its tags deleted.');
        redirect(APP_URL . '/tags/index.php');
    }
}

render:
$categories = get_tag_categories($db);

$pageTitle  = 'Tags';
$activePage = 'tags';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Tags</h1>
    <p>Group tasks (and, over time, other items) by whatever matters to you — systems, stakeholders, sections, anything.</p>
  </div>
</div>

<?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>

<?php foreach ($categories as $c): ?>
<div class="card">
  <div class="card-title-row editable-wrap">
    <div class="editable-view tag-category-view">
      <h2><?= e($c['name']) ?></h2>
      <div class="tag-category-actions">
        <button type="button" class="icon-btn" data-edit-toggle title="Rename category" aria-label="Rename category"><?= icon_edit() ?></button>
        <form method="POST" action="" onsubmit="return confirm('Delete the &quot;<?= e($c['name']) ?>&quot; category and all its tags?');">
          <?= csrf_field() ?>
          <input type="hidden" name="delete_category" value="<?= $c['id'] ?>">
          <button type="submit" class="btn btn--outline btn--sm">Delete category</button>
        </form>
      </div>
    </div>
    <form method="POST" action="" class="editable-form tag-add-form tag-category-form" hidden>
      <?= csrf_field() ?>
      <input type="hidden" name="rename_category" value="<?= $c['id'] ?>">
      <input type="text" name="category_name" value="<?= e($c['name']) ?>" required>
      <button type="submit" class="btn btn--primary btn--sm">Save</button>
      <button type="button" class="btn btn--outline btn--sm" data-edit-cancel>Cancel</button>
    </form>
  </div>

  <?php if ($c['tags']): ?>
  <div class="tag-pills" style="margin-bottom:1rem;">
    <?php foreach ($c['tags'] as $t): ?>
    <span class="editable-wrap">
      <span class="tag-pill tag-pill--removable editable-view">
        <?= e($t['name']) ?>
        <button type="button" data-edit-toggle title="Rename tag" aria-label="Rename tag <?= e($t['name']) ?>">&#9998;</button>
        <form method="POST" action="" onsubmit="return confirm('Delete the &quot;<?= e($t['name']) ?>&quot; tag?');">
          <?= csrf_field() ?>
          <input type="hidden" name="delete_tag" value="<?= $t['id'] ?>">
          <button type="submit" aria-label="Delete tag <?= e($t['name']) ?>">&times;</button>
        </form>
      </span>
      <form method="POST" action="" class="editable-form tag-pill tag-pill--edit-form" hidden>
        <?= csrf_field() ?>
        <input type="hidden" name="rename_tag" value="<?= $t['id'] ?>">
        <input type="text" name="tag_name" value="<?= e($t['name']) ?>" required>
        <button type="submit" aria-label="Save">&#10003;</button>
        <button type="button" data-edit-cancel aria-label="Cancel">&times;</button>
      </form>
    </span>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <p class="empty-note" style="margin-bottom:1rem;">No tags in this category yet.</p>
  <?php endif; ?>

  <form method="POST" action="" class="tag-add-form">
    <?= csrf_field() ?>
    <input type="hidden" name="category_id" value="<?= $c['id'] ?>">
    <input type="text" name="tag_name" placeholder="New tag name, e.g. <?= $c['name'] === 'System' ? 'APEX' : 'Add a tag' ?>" required>
    <button type="submit" name="add_tag" value="1" class="btn btn--outline btn--sm">+ Add tag</button>
  </form>
</div>
<?php endforeach; ?>

<div class="card">
  <h2>Add a category</h2>
  <p class="empty-note" style="margin-bottom:.75rem;">e.g. System, Stakeholder, Section — whatever groupings you need.</p>
  <form method="POST" action="" class="tag-add-form">
    <?= csrf_field() ?>
    <input type="text" name="category_name" placeholder="New category name" required>
    <button type="submit" name="add_category" value="1" class="btn btn--primary btn--sm">+ Add category</button>
  </form>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
