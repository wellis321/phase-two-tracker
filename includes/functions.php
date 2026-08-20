<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function asset_url(string $path): string
{
    $file = dirname(__DIR__) . $path;
    $v    = is_file($file) ? filemtime($file) : time();
    return APP_URL . $path . '?v=' . $v;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $posted = $_POST['csrf_token'] ?? '';
    return is_string($posted) && $posted !== '' && hash_equals(csrf_token(), $posted);
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function render_flash(): string
{
    if (empty($_SESSION['flash'])) return '';
    $out = '';
    foreach ($_SESSION['flash'] as $f) {
        $out .= '<div class="flash flash-' . e($f['type']) . '">' . e($f['msg']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/**
 * @return array{total:int, pages:int, page:int, offset:int, per_page:int}
 */
function paginate(int $total, int $page, int $perPage): array
{
    $pages  = max(1, (int)ceil($total / max(1, $perPage)));
    $page   = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;
    return ['total' => $total, 'pages' => $pages, 'page' => $page, 'offset' => $offset, 'per_page' => $perPage];
}

function rag_badge(?string $level): string
{
    $level = strtolower((string)$level);
    $labels = ['red' => 'Red', 'amber' => 'Amber', 'green' => 'Green'];
    $label  = $labels[$level] ?? e((string)$level);
    return '<span class="rag rag-' . e($level) . '">' . $label . '</span>';
}

function health_indicator(?string $level, ?string $context = null): string
{
    $level  = strtolower((string)$level);
    $labels = ['red' => 'Red', 'amber' => 'Amber', 'green' => 'Green'];
    $label  = $labels[$level] ?? ucfirst($level);
    return '<div class="health-indicator health-indicator--' . e($level) . '">'
        . '<span class="health-indicator__label">Overall status</span>'
        . '<span class="health-indicator__value">' . e($label) . '</span>'
        . ($context !== null && $context !== '' ? '<span class="health-indicator__context">' . e($context) . '</span>' : '')
        . '</div>';
}

function icon_edit(): string
{
    return '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M13.4 3.6a1.7 1.7 0 0 1 2.4 2.4L7 14.8l-3.2.8.8-3.2 8.8-8.8z"/></svg>';
}

function icon_arrow_left(): string
{
    return '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12.5 4.5 6 10l6.5 5.5M6 10h9"/></svg>';
}

function format_date(?string $date): string
{
    if (!$date) return '—';
    $ts = strtotime($date);
    return $ts ? date('j M Y', $ts) : e($date);
}

/**
 * Nested tree of every tag: id, name, fields (free-form key/value pairs),
 * and children (same shape). Top-level tags have no parent.
 * @return array<int, array{id:int, name:string, fields: array, children: array}>
 */
function get_tag_tree(PDO $db): array
{
    $rows = $db->query('SELECT id, parent_id, name FROM pm_tags ORDER BY name')->fetchAll();
    $fieldRows = $db->query('SELECT id, tag_id, field_name, field_value FROM pm_tag_fields ORDER BY id')->fetchAll();

    $fieldsByTag = [];
    foreach ($fieldRows as $f) {
        $fieldsByTag[(int)$f['tag_id']][] = ['id' => (int)$f['id'], 'name' => $f['field_name'], 'value' => $f['field_value']];
    }

    $childrenOf = [];
    foreach ($rows as $r) {
        $key = $r['parent_id'] !== null ? (int)$r['parent_id'] : 0;
        $childrenOf[$key][] = $r;
    }

    $build = function (int $parentKey) use (&$build, $childrenOf, $fieldsByTag): array {
        $out = [];
        foreach ($childrenOf[$parentKey] ?? [] as $r) {
            $id = (int)$r['id'];
            $out[] = [
                'id'       => $id,
                'name'     => $r['name'],
                'fields'   => $fieldsByTag[$id] ?? [],
                'children' => $build($id),
            ];
        }
        return $out;
    };

    return $build(0);
}

/**
 * Flattens get_tag_tree() into depth-first order with a depth for indentation.
 * @return array<int, array{id:int, name:string, depth:int}>
 */
function flatten_tag_tree(array $tree, int $depth = 0): array
{
    $out = [];
    foreach ($tree as $node) {
        $out[] = ['id' => $node['id'], 'name' => $node['name'], 'depth' => $depth];
        $out = array_merge($out, flatten_tag_tree($node['children'], $depth + 1));
    }
    return $out;
}

function tag_name_taken(PDO $db, ?int $parentId, string $name, int $excludeId = 0): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) FROM pm_tags WHERE name = ? AND parent_id <=> ? AND id != ?');
    $stmt->execute([$name, $parentId, $excludeId]);
    return (int)$stmt->fetchColumn() > 0;
}

/** @return int[] */
function get_tag_ids_for(PDO $db, string $taggableType, int $taggableId): array
{
    $stmt = $db->prepare('SELECT tag_id FROM pm_taggables WHERE taggable_type = ? AND taggable_id = ?');
    $stmt->execute([$taggableType, $taggableId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * @return array<int, array{id:int, name:string, parent_name: ?string}>
 */
function get_tags_for(PDO $db, string $taggableType, int $taggableId): array
{
    $stmt = $db->prepare(
        'SELECT t.id, t.name, p.name AS parent_name
         FROM pm_taggables x
         JOIN pm_tags t ON t.id = x.tag_id
         LEFT JOIN pm_tags p ON p.id = t.parent_id
         WHERE x.taggable_type = ? AND x.taggable_id = ?
         ORDER BY t.name'
    );
    $stmt->execute([$taggableType, $taggableId]);
    return $stmt->fetchAll();
}

/** @param int[] $tagIds */
function save_tags_for(PDO $db, string $taggableType, int $taggableId, array $tagIds): void
{
    $db->prepare('DELETE FROM pm_taggables WHERE taggable_type = ? AND taggable_id = ?')
       ->execute([$taggableType, $taggableId]);
    if (!$tagIds) return;
    $stmt = $db->prepare('INSERT IGNORE INTO pm_taggables (tag_id, taggable_type, taggable_id) VALUES (?, ?, ?)');
    foreach (array_unique(array_map('intval', $tagIds)) as $tagId) {
        $stmt->execute([$tagId, $taggableType, $taggableId]);
    }
}

function render_tag_pills(array $tags): string
{
    if (!$tags) return '';
    $out = '<span class="tag-pills">';
    foreach ($tags as $t) {
        $title = $t['parent_name'] ?? null;
        $out .= '<span class="tag-pill"' . ($title ? ' title="' . e($title) . '"' : '') . '>' . e($t['name']) . '</span>';
    }
    return $out . '</span>';
}

/**
 * Renders every tag as an indented checkbox list (depth-first), for
 * create/edit forms and quick-add's tag picker.
 * @param array $tree get_tag_tree() output
 * @param int[] $selectedTagIds
 */
function render_tag_checkboxes(array $tree, array $selectedTagIds, string $namePrefix = 'tag_ids'): string
{
    $flat = flatten_tag_tree($tree);
    if (!$flat) return '<p class="empty-note">No tags set up yet.</p>';
    $out = '<div class="tag-picker">';
    foreach ($flat as $t) {
        $checked = in_array($t['id'], $selectedTagIds, true) ? ' checked' : '';
        $out .= '<label class="tag-picker-option" style="padding-left:' . ($t['depth'] * 1.15) . 'rem;">'
              . '<input type="checkbox" name="' . e($namePrefix) . '[]" value="' . (int)$t['id'] . '"' . $checked . '> ' . e($t['name']) . '</label>';
    }
    return $out . '</div>';
}

function days_until(?string $date): ?int
{
    if (!$date) return null;
    $ts = strtotime($date);
    if (!$ts) return null;
    return (int)floor((strtotime(date('Y-m-d', $ts)) - strtotime(date('Y-m-d'))) / 86400);
}
