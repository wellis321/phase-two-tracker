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
 * @return array<int, array{id:int, name:string, tags: array<int, array{id:int, name:string}>}>
 */
function get_tag_categories(PDO $db): array
{
    $categories = $db->query(
        'SELECT id, name FROM pm_tag_categories ORDER BY sort_order, name'
    )->fetchAll();
    $tags = $db->query(
        'SELECT id, category_id, name FROM pm_tags ORDER BY name'
    )->fetchAll();

    $byCategory = [];
    foreach ($tags as $t) {
        $byCategory[(int)$t['category_id']][] = ['id' => (int)$t['id'], 'name' => $t['name']];
    }
    return array_map(fn($c) => [
        'id'   => (int)$c['id'],
        'name' => $c['name'],
        'tags' => $byCategory[(int)$c['id']] ?? [],
    ], $categories);
}

/** @return int[] */
function get_tag_ids_for(PDO $db, string $taggableType, int $taggableId): array
{
    $stmt = $db->prepare('SELECT tag_id FROM pm_taggables WHERE taggable_type = ? AND taggable_id = ?');
    $stmt->execute([$taggableType, $taggableId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * @return array<int, array{id:int, name:string, category:string}>
 */
function get_tags_for(PDO $db, string $taggableType, int $taggableId): array
{
    $stmt = $db->prepare(
        'SELECT t.id, t.name, c.name AS category
         FROM pm_taggables x
         JOIN pm_tags t ON t.id = x.tag_id
         JOIN pm_tag_categories c ON c.id = t.category_id
         WHERE x.taggable_type = ? AND x.taggable_id = ?
         ORDER BY c.sort_order, t.name'
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
        $out .= '<span class="tag-pill" title="' . e($t['category']) . '">' . e($t['name']) . '</span>';
    }
    return $out . '</span>';
}

/**
 * Renders the grouped tag checkboxes used on create/edit forms and quick-add.
 * @param array<int, array{id:int, name:string, tags: array}> $categories
 * @param int[] $selectedTagIds
 */
function render_tag_checkboxes(array $categories, array $selectedTagIds, string $namePrefix = 'tag_ids'): string
{
    if (!$categories) return '<p class="empty-note">No tags set up yet.</p>';
    $out = '<div class="tag-picker">';
    foreach ($categories as $c) {
        if (!$c['tags']) continue;
        $out .= '<div class="tag-picker-group"><span class="tag-picker-label">' . e($c['name']) . '</span><div class="tag-picker-options">';
        foreach ($c['tags'] as $t) {
            $checked = in_array($t['id'], $selectedTagIds, true) ? ' checked' : '';
            $out .= '<label class="tag-picker-option"><input type="checkbox" name="' . e($namePrefix) . '[]" value="' . (int)$t['id'] . '"' . $checked . '> ' . e($t['name']) . '</label>';
        }
        $out .= '</div></div>';
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
