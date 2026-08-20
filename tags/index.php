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

    if (isset($_POST['add_tag'])) {
        $name     = trim($_POST['tag_name'] ?? '');
        $parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
        if ($name === '') {
            $errors[] = 'Tag name is required.';
        } elseif (tag_name_taken($db, $parentId, $name)) {
            $errors[] = "A tag called '{$name}' already exists there.";
        } else {
            $db->prepare('INSERT INTO pm_tags (name, parent_id) VALUES (?, ?)')->execute([$name, $parentId]);
            flash('success', "Tag '{$name}' added.");
            redirect(APP_URL . '/tags/index.php');
        }
    } elseif (isset($_POST['rename_tag'])) {
        $id   = (int)$_POST['rename_tag'];
        $name = trim($_POST['tag_name'] ?? '');
        $row  = $db->prepare('SELECT parent_id FROM pm_tags WHERE id = ?');
        $row->execute([$id]);
        $parentId = $row->fetchColumn();
        $parentId = $parentId !== false && $parentId !== null ? (int)$parentId : null;
        if ($name === '') {
            $errors[] = 'Tag name is required.';
        } elseif (tag_name_taken($db, $parentId, $name, $id)) {
            $errors[] = "A tag called '{$name}' already exists there.";
        } else {
            $db->prepare('UPDATE pm_tags SET name = ? WHERE id = ?')->execute([$name, $id]);
            flash('success', "Tag renamed to '{$name}'.");
            redirect(APP_URL . '/tags/index.php');
        }
    } elseif (isset($_POST['delete_tag'])) {
        $db->prepare('DELETE FROM pm_tags WHERE id = ?')->execute([(int)$_POST['delete_tag']]);
        flash('success', 'Tag (and any children) deleted.');
        redirect(APP_URL . '/tags/index.php');
    } elseif (isset($_POST['add_field'])) {
        $tagId = (int)($_POST['tag_id'] ?? 0);
        $name  = trim($_POST['field_name'] ?? '');
        $value = trim($_POST['field_value'] ?? '');
        if ($name === '' || $tagId <= 0) {
            $errors[] = 'Field name is required.';
        } else {
            $db->prepare('INSERT INTO pm_tag_fields (tag_id, field_name, field_value) VALUES (?, ?, ?)')
               ->execute([$tagId, $name, $value ?: null]);
            flash('success', "Field '{$name}' added.");
            redirect(APP_URL . '/tags/index.php');
        }
    } elseif (isset($_POST['rename_field'])) {
        $id    = (int)$_POST['rename_field'];
        $name  = trim($_POST['field_name'] ?? '');
        $value = trim($_POST['field_value'] ?? '');
        if ($name === '') {
            $errors[] = 'Field name is required.';
        } else {
            $db->prepare('UPDATE pm_tag_fields SET field_name = ?, field_value = ? WHERE id = ?')
               ->execute([$name, $value ?: null, $id]);
            flash('success', 'Field updated.');
            redirect(APP_URL . '/tags/index.php');
        }
    } elseif (isset($_POST['delete_field'])) {
        $db->prepare('DELETE FROM pm_tag_fields WHERE id = ?')->execute([(int)$_POST['delete_field']]);
        flash('success', 'Field deleted.');
        redirect(APP_URL . '/tags/index.php');
    }
}

render:
$tagTree  = get_tag_tree($db);
$flatTags = flatten_tag_tree($tagTree);

$pageTitle  = 'Tags';
$activePage = 'tags';
require __DIR__ . '/../includes/layout/header.php';

/**
 * Recursively renders one tag and its children/fields.
 */
function render_tag_node(array $node, int $depth): void
{
    $id     = $node['id'];
    $indent = $depth * 1.5;
    ?>
    <div class="tag-node" style="margin-left:<?= $indent ?>rem;">
      <div class="tag-node-row">
        <div class="editable-wrap tag-node-name-wrap">
          <span class="editable-view tag-node-name-view">
            <strong><?= e($node['name']) ?></strong>
            <button type="button" class="icon-btn" data-edit-toggle title="Rename" aria-label="Rename <?= e($node['name']) ?>"><?= icon_edit() ?></button>
          </span>
          <form method="POST" action="" class="editable-form tag-inline-form" hidden>
            <?= csrf_field() ?>
            <input type="hidden" name="rename_tag" value="<?= $id ?>">
            <input type="text" name="tag_name" value="<?= e($node['name']) ?>" required>
            <button type="submit" class="btn btn--primary btn--sm">Save</button>
            <button type="button" class="btn btn--outline btn--sm" data-edit-cancel>Cancel</button>
          </form>
        </div>
        <div class="tag-node-actions">
          <button type="button" class="btn btn--outline btn--sm" data-show-target="tag-child-form-<?= $id ?>">+ Child</button>
          <button type="button" class="btn btn--outline btn--sm" data-show-target="tag-field-form-<?= $id ?>">+ Field</button>
          <form method="POST" action="" onsubmit="return confirm('Delete &quot;<?= e($node['name']) ?>&quot;, its fields, and any child tags?');">
            <?= csrf_field() ?>
            <input type="hidden" name="delete_tag" value="<?= $id ?>">
            <button type="submit" class="btn btn--outline btn--sm">Delete</button>
          </form>
        </div>
      </div>

      <?php if ($node['fields']): ?>
      <div class="tag-fields">
        <?php foreach ($node['fields'] as $f): ?>
        <span class="editable-wrap">
          <span class="pill editable-view field-pill-view">
            <?= e($f['name']) ?>: <?= e($f['value'] ?? '') ?>
            <button type="button" data-edit-toggle title="Edit field" aria-label="Edit field <?= e($f['name']) ?>">&#9998;</button>
            <form method="POST" action="" onsubmit="return confirm('Delete the &quot;<?= e($f['name']) ?>&quot; field?');" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_field" value="<?= $f['id'] ?>">
              <button type="submit" aria-label="Delete field <?= e($f['name']) ?>">&times;</button>
            </form>
          </span>
          <form method="POST" action="" class="editable-form field-pill-edit-form" hidden>
            <?= csrf_field() ?>
            <input type="hidden" name="rename_field" value="<?= $f['id'] ?>">
            <input type="text" name="field_name" value="<?= e($f['name']) ?>" placeholder="Field" required>
            <input type="text" name="field_value" value="<?= e($f['value'] ?? '') ?>" placeholder="Value">
            <button type="submit" aria-label="Save">&#10003;</button>
            <button type="button" data-edit-cancel aria-label="Cancel">&times;</button>
          </form>
        </span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="" id="tag-field-form-<?= $id ?>" class="editable-form tag-inline-form" hidden>
        <?= csrf_field() ?>
        <input type="hidden" name="tag_id" value="<?= $id ?>">
        <input type="text" name="field_name" placeholder="Field name, e.g. Address" required>
        <input type="text" name="field_value" placeholder="Value">
        <button type="submit" name="add_field" value="1" class="btn btn--primary btn--sm">Add field</button>
        <button type="button" class="btn btn--outline btn--sm" data-hide-self>Cancel</button>
      </form>

      <form method="POST" action="" id="tag-child-form-<?= $id ?>" class="editable-form tag-inline-form" hidden>
        <?= csrf_field() ?>
        <input type="hidden" name="parent_id" value="<?= $id ?>">
        <input type="text" name="tag_name" placeholder="New child tag name" required>
        <button type="submit" name="add_tag" value="1" class="btn btn--primary btn--sm">Add child</button>
        <button type="button" class="btn btn--outline btn--sm" data-hide-self>Cancel</button>
      </form>

      <?php foreach ($node['children'] as $child): render_tag_node($child, $depth + 1); endforeach; ?>
    </div>
    <?php
}
?>

<div class="page-header">
  <div>
    <h1>Tags</h1>
    <p>Organise tasks (and, over time, other items) however makes sense to you. Any tag can have child tags, and any tag can carry its own custom fields.</p>
  </div>
</div>

<?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>

<div class="card">
  <?php if ($tagTree): ?>
  <?php foreach ($tagTree as $node): render_tag_node($node, 0); endforeach; ?>
  <?php else: ?>
  <p class="empty-note">No tags yet — add your first one below.</p>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Add a tag</h2>
  <form method="POST" action="" class="tag-add-form">
    <?= csrf_field() ?>
    <input type="text" name="tag_name" placeholder="New tag name" required>
    <select name="parent_id">
      <option value="0">No parent (top-level)</option>
      <?php foreach ($flatTags as $t): ?>
      <option value="<?= $t['id'] ?>"><?= str_repeat('&nbsp;&nbsp;', $t['depth']) . e($t['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" name="add_tag" value="1" class="btn btn--primary btn--sm">+ Add tag</button>
  </form>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
