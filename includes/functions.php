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

/** Footer badge label for the current deployment (APP_ENV). */
function app_env_label(): string
{
    return match (APP_ENV) {
        'local'       => 'LOCAL',
        'development' => 'DEVELOPMENT',
        'production'  => 'PRODUCTION',
        default       => strtoupper(APP_ENV),
    };
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

/** The site favicon glyph (green rounded square, white checkmark), for letterheads etc. */
function icon_checkmark_badge(): string
{
    return '<svg viewBox="0 0 32 32" role="img" aria-hidden="true"><rect width="32" height="32" rx="7" fill="#006A51"/><path d="M9 16.5l4.5 4.5L23 11" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

function icon_flag(): string
{
    return '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17.5V3"/><path class="flag-icon-fill" d="M5 3.5c1.6-1.1 3.6-1.1 5.2 0 1.5 1 3.3 1 4.8 0v7.6c-1.5 1-3.3 1-4.8 0-1.6-1.1-3.6-1.1-5.2 0z"/></svg>';
}

function format_date(?string $date): string
{
    if (!$date) return '—';
    $ts = strtotime($date);
    return $ts ? date('j M Y', $ts) : e($date);
}

/**
 * Nested tree of every tag: id, name, fields (each a nested tree of its
 * own — a field can have child fields, e.g. Address -> Street/City/
 * Postcode), and children (same shape as the tag itself). Top-level tags
 * have no parent.
 * @return array<int, array{id:int, name:string, fields: array, children: array}>
 */
function get_tag_tree(PDO $db): array
{
    $rows = $db->query('SELECT id, parent_id, name FROM pm_tags ORDER BY name')->fetchAll();
    $fieldRows = $db->query('SELECT id, tag_id, parent_field_id, field_name, field_value FROM pm_tag_fields ORDER BY id')->fetchAll();

    $fieldChildrenOf = [];
    foreach ($fieldRows as $f) {
        $tagId     = (int)$f['tag_id'];
        $parentKey = $f['parent_field_id'] !== null ? (int)$f['parent_field_id'] : 0;
        $fieldChildrenOf[$tagId][$parentKey][] = $f;
    }
    $buildFields = function (int $tagId, int $parentKey) use (&$buildFields, $fieldChildrenOf): array {
        $out = [];
        foreach ($fieldChildrenOf[$tagId][$parentKey] ?? [] as $f) {
            $id = (int)$f['id'];
            $out[] = [
                'id'       => $id,
                'name'     => $f['field_name'],
                'value'    => $f['field_value'],
                'children' => $buildFields($tagId, $id),
            ];
        }
        return $out;
    };

    $childrenOf = [];
    foreach ($rows as $r) {
        $key = $r['parent_id'] !== null ? (int)$r['parent_id'] : 0;
        $childrenOf[$key][] = $r;
    }

    $build = function (int $parentKey) use (&$build, $childrenOf, $buildFields): array {
        $out = [];
        foreach ($childrenOf[$parentKey] ?? [] as $r) {
            $id = (int)$r['id'];
            $out[] = [
                'id'       => $id,
                'name'     => $r['name'],
                'fields'   => $buildFields($id, 0),
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
    if (!$tree) return '<p class="empty-note">No tags set up yet.</p>';
    return '<div class="tag-picker">' . render_tag_checkbox_nodes($tree, $selectedTagIds, $namePrefix) . '</div>';
}

/** Only the top level is shown open; branches with a selected descendant start expanded too. */
function render_tag_checkbox_nodes(array $nodes, array $selectedTagIds, string $namePrefix): string
{
    $out = '';
    foreach ($nodes as $node) {
        $hasChildren = !empty($node['children']);
        $checked     = in_array($node['id'], $selectedTagIds, true) ? ' checked' : '';
        $expanded    = $hasChildren && tag_node_has_selected_descendant($node['children'], $selectedTagIds);

        $out .= '<div class="tag-picker-group">';
        $out .= '<div class="tag-picker-row">';
        if ($hasChildren) {
            $out .= '<button type="button" class="tag-picker-toggle" aria-expanded="' . ($expanded ? 'true' : 'false') . '">'
                  . '<span class="tag-picker-toggle-glyph">' . ($expanded ? '&#9662;' : '&#9656;') . '</span>'
                  . '<span class="sr-only">Toggle ' . e($node['name']) . ' sub-tags</span></button>';
        } else {
            $out .= '<span class="tag-picker-spacer" aria-hidden="true"></span>';
        }
        $out .= '<label class="tag-picker-option"><input type="checkbox" name="' . e($namePrefix) . '[]" value="' . (int)$node['id'] . '"' . $checked . '> ' . e($node['name']) . '</label>';
        $out .= '</div>';
        if ($hasChildren) {
            $out .= '<div class="tag-picker-children"' . ($expanded ? '' : ' hidden') . '>'
                  . render_tag_checkbox_nodes($node['children'], $selectedTagIds, $namePrefix) . '</div>';
        }
        $out .= '</div>';
    }
    return $out;
}

function tag_node_has_selected_descendant(array $nodes, array $selectedTagIds): bool
{
    foreach ($nodes as $node) {
        if (in_array($node['id'], $selectedTagIds, true)) return true;
        if (!empty($node['children']) && tag_node_has_selected_descendant($node['children'], $selectedTagIds)) return true;
    }
    return false;
}

function days_until(?string $date): ?int
{
    if (!$date) return null;
    $ts = strtotime($date);
    if (!$ts) return null;
    return (int)floor((strtotime(date('Y-m-d', $ts)) - strtotime(date('Y-m-d'))) / 86400);
}

/**
 * Tasks this task depends on (must be done before it can proceed).
 * @return array<int, array{id:int, title:string, status:string}>
 */
function get_task_dependencies(PDO $db, int $taskId): array
{
    $stmt = $db->prepare(
        'SELECT t.id, t.title, t.status
         FROM pm_task_dependencies d
         JOIN pm_tasks t ON t.id = d.depends_on_id
         WHERE d.task_id = ?
         ORDER BY t.title'
    );
    $stmt->execute([$taskId]);
    return $stmt->fetchAll();
}

/**
 * Tasks that depend on this task (what it's blocking).
 * @return array<int, array{id:int, title:string, status:string}>
 */
function get_task_dependents(PDO $db, int $taskId): array
{
    $stmt = $db->prepare(
        'SELECT t.id, t.title, t.status
         FROM pm_task_dependencies d
         JOIN pm_tasks t ON t.id = d.task_id
         WHERE d.depends_on_id = ?
         ORDER BY t.title'
    );
    $stmt->execute([$taskId]);
    return $stmt->fetchAll();
}

/** @param int[] $dependsOnIds */
function save_task_dependencies(PDO $db, int $taskId, array $dependsOnIds): void
{
    $db->prepare('DELETE FROM pm_task_dependencies WHERE task_id = ?')->execute([$taskId]);
    $ids = array_unique(array_filter(array_map('intval', $dependsOnIds), fn($id) => $id !== $taskId));
    if (!$ids) return;
    $stmt = $db->prepare('INSERT IGNORE INTO pm_task_dependencies (task_id, depends_on_id) VALUES (?, ?)');
    foreach ($ids as $depId) {
        $stmt->execute([$taskId, $depId]);
    }
}

/** @param array<int, array{status: string}> $dependencies */
function task_is_blocked(array $dependencies): bool
{
    foreach ($dependencies as $d) {
        if ($d['status'] !== 'done') return true;
    }
    return false;
}

/** Human label for a task status value. */
function task_status_label(string $status): string
{
    return ['todo' => 'To do', 'in_progress' => 'In progress', 'done' => 'Done'][$status] ?? $status;
}

/**
 * Renders the pre-selected dependency chips for a task edit/create form.
 * New chips are added client-side via search (see tasks/search.php and
 * assets/js/task-deps.js) rather than pre-loading every task in the
 * system, which doesn't scale once there are thousands of them.
 * @param array<int, array{id:int, title:string, status:string}> $dependencyTasks
 */
function render_task_dependency_chips(array $dependencyTasks): string
{
    $out = '';
    foreach ($dependencyTasks as $t) {
        $out .= '<div class="attendee-chip" data-id="' . (int)$t['id'] . '">'
              . '<span class="attendee-chip-name">' . e($t['title']) . ' <span class="dep-picker-status">(' . e(task_status_label($t['status'])) . ')</span></span>'
              . '<button type="button" class="attendee-chip-remove" aria-label="Remove ' . e($t['title']) . '">&times;</button>'
              . '<input type="hidden" name="depends_on_ids[]" value="' . (int)$t['id'] . '">'
              . '</div>';
    }
    return $out;
}

/** Initials for a person's display name (falls back to username), e.g. "William Ellis" -> "WE". */
function user_initials(?string $displayName, ?string $username): string
{
    $source = trim((string)($displayName ?: $username));
    if ($source === '') return '?';
    $words = preg_split('/\s+/', $source) ?: [$source];
    if (count($words) >= 2) {
        return strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[count($words) - 1], 0, 1));
    }
    return strtoupper(mb_substr($source, 0, 2));
}

/** Record types that can be flagged for discussion, and where to link back to them. */
function discussion_flaggable_types(): array
{
    return [
        'task'      => ['table' => 'pm_tasks',             'url' => 'tasks/view.php'],
        'milestone' => ['table' => 'pm_milestones',         'url' => 'milestones/view.php'],
        'risk'      => ['table' => 'pm_risks_issues',       'url' => 'risks/view.php'],
        'decision'  => ['table' => 'pm_decisions',          'url' => 'decisions/view.php'],
        'supplier'  => ['table' => 'pm_supplier_activities', 'url' => 'supplier/view.php'],
    ];
}

/**
 * Discussion-flag state for one record: whether the current user has
 * flagged it, everyone who has, and the shared note/status if so.
 * @return array{itemId:?int, flaggedByMe:bool, flaggers:array<int,array{id:int,name:string,initials:string}>, note:?string, status:?string}
 */
function get_discussion_state(PDO $db, string $type, int $id, int $currentUserId): array
{
    $stmt = $db->prepare(
        'SELECT di.id, di.note, di.status, f.user_id, u.display_name, u.username
         FROM pm_discussion_items di
         LEFT JOIN pm_discussion_flags f ON f.discussion_item_id = di.id
         LEFT JOIN users u ON u.id = f.user_id
         WHERE di.flaggable_type = ? AND di.flaggable_id = ?
         ORDER BY u.display_name, u.username'
    );
    $stmt->execute([$type, $id]);
    $rows = $stmt->fetchAll();

    if (!$rows) {
        return ['itemId' => null, 'flaggedByMe' => false, 'flaggers' => [], 'note' => null, 'status' => null];
    }

    $flaggers    = [];
    $flaggedByMe = false;
    foreach ($rows as $r) {
        if ($r['user_id'] === null) continue;
        $flaggers[] = [
            'id'       => (int)$r['user_id'],
            'name'     => $r['display_name'] ?: $r['username'],
            'initials' => user_initials($r['display_name'], $r['username']),
        ];
        if ((int)$r['user_id'] === $currentUserId) $flaggedByMe = true;
    }
    return [
        'itemId'      => (int)$rows[0]['id'],
        'flaggedByMe' => $flaggedByMe,
        'flaggers'    => $flaggers,
        'note'        => $rows[0]['note'],
        'status'      => $rows[0]['status'],
    ];
}

/**
 * Batch version of get_discussion_state() for list pages, keyed by
 * flaggable_id, so a page with N rows doesn't run N queries.
 * @param int[] $ids
 * @return array<int, array{itemId:int, flaggedByMe:bool, flaggers:array<int,array{id:int,name:string,initials:string}>, note:?string, status:string}>
 */
function get_discussion_states_bulk(PDO $db, string $type, array $ids, int $currentUserId): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) return [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare(
        "SELECT di.id, di.flaggable_id, di.note, di.status, f.user_id, u.display_name, u.username
         FROM pm_discussion_items di
         LEFT JOIN pm_discussion_flags f ON f.discussion_item_id = di.id
         LEFT JOIN users u ON u.id = f.user_id
         WHERE di.flaggable_type = ? AND di.flaggable_id IN ($placeholders)
         ORDER BY u.display_name, u.username"
    );
    $stmt->execute([$type, ...$ids]);

    $out = [];
    foreach ($stmt->fetchAll() as $r) {
        $fid = (int)$r['flaggable_id'];
        if (!isset($out[$fid])) {
            $out[$fid] = ['itemId' => (int)$r['id'], 'flaggedByMe' => false, 'flaggers' => [], 'note' => $r['note'], 'status' => $r['status']];
        }
        if ($r['user_id'] !== null) {
            $out[$fid]['flaggers'][] = [
                'id'       => (int)$r['user_id'],
                'name'     => $r['display_name'] ?: $r['username'],
                'initials' => user_initials($r['display_name'], $r['username']),
            ];
            if ((int)$r['user_id'] === $currentUserId) $out[$fid]['flaggedByMe'] = true;
        }
    }
    return $out;
}

/**
 * Toggles the current user's flag on a record. Creates the discussion item
 * on first flag; removes it again if unflagging leaves it with no flags,
 * no note, and still open — i.e. nobody's said anything about it yet.
 * @return array{itemId:?int, flaggedByMe:bool, flaggers:array<int,array{id:int,name:string,initials:string}>, note:?string, status:?string}
 */
function toggle_discussion_flag(PDO $db, string $type, int $id, int $userId): array
{
    $stmt = $db->prepare('SELECT id, note, status FROM pm_discussion_items WHERE flaggable_type = ? AND flaggable_id = ?');
    $stmt->execute([$type, $id]);
    $item = $stmt->fetch();

    if (!$item) {
        $db->prepare('INSERT INTO pm_discussion_items (flaggable_type, flaggable_id) VALUES (?, ?)')->execute([$type, $id]);
        $itemId = (int)$db->lastInsertId();
        $db->prepare('INSERT INTO pm_discussion_flags (discussion_item_id, user_id) VALUES (?, ?)')->execute([$itemId, $userId]);
        return get_discussion_state($db, $type, $id, $userId);
    }

    $itemId = (int)$item['id'];
    $already = $db->prepare('SELECT id FROM pm_discussion_flags WHERE discussion_item_id = ? AND user_id = ?');
    $already->execute([$itemId, $userId]);

    if ($already->fetch()) {
        $db->prepare('DELETE FROM pm_discussion_flags WHERE discussion_item_id = ? AND user_id = ?')->execute([$itemId, $userId]);
        $countStmt = $db->prepare('SELECT COUNT(*) FROM pm_discussion_flags WHERE discussion_item_id = ?');
        $countStmt->execute([$itemId]);
        $remaining = (int)$countStmt->fetchColumn();
        if ($remaining === 0 && $item['status'] === 'open' && trim((string)$item['note']) === '') {
            $db->prepare('DELETE FROM pm_discussion_items WHERE id = ?')->execute([$itemId]);
        }
    } else {
        $db->prepare('INSERT INTO pm_discussion_flags (discussion_item_id, user_id) VALUES (?, ?)')->execute([$itemId, $userId]);
    }

    return get_discussion_state($db, $type, $id, $userId);
}

/** The flag toggle button shown on record view pages and list rows. */
function render_flag_button(string $type, int $id, array $state): string
{
    $names = array_map(fn($f) => $f['name'], $state['flaggers']);
    $title = $names ? 'Flagged by ' . implode(', ', $names) : 'Flag for discussion';
    $count = count($state['flaggers']);
    return '<button type="button" class="flag-btn' . (!empty($state['flaggedByMe']) ? ' flag-btn--active' : '') . '"'
         . ' data-flag-type="' . e($type) . '" data-flag-id="' . $id . '" data-endpoint="' . APP_URL . '/discussion/toggle.php"'
         . ' aria-pressed="' . (!empty($state['flaggedByMe']) ? 'true' : 'false') . '" title="' . e($title) . '">'
         . icon_flag()
         . ($count > 0 ? '<span class="flag-btn-count">' . $count . '</span>' : '')
         . '</button>';
}

/** Human label for a discussion-flag type value. */
function discussion_type_label(string $type): string
{
    return ['task' => 'Task', 'milestone' => 'Milestone', 'risk' => 'Risk/Issue', 'decision' => 'Decision', 'supplier' => 'Supplier'][$type] ?? ucfirst($type);
}

/**
 * All discussion items with a given status, joined to their record's title
 * and their flaggers. Used by the Discussion page.
 * @return array<int, array{itemId:int, type:string, flaggableId:int, title:string, url:string, note:?string, status:string, agendaId:?int, updatedAt:string, flaggers:array<int,array{id:int,name:string,initials:string}>}>
 */
function get_discussion_items(PDO $db, string $status): array
{
    $items = [];
    foreach (discussion_flaggable_types() as $type => $conf) {
        $stmt = $db->prepare(
            "SELECT di.id AS item_id, di.note, di.status, di.agenda_id, di.updated_at, r.id AS record_id, r.title
             FROM pm_discussion_items di
             JOIN {$conf['table']} r ON r.id = di.flaggable_id
             WHERE di.flaggable_type = ? AND di.status = ?"
        );
        $stmt->execute([$type, $status]);
        foreach ($stmt->fetchAll() as $row) {
            $items[(int)$row['item_id']] = [
                'itemId'      => (int)$row['item_id'],
                'type'        => $type,
                'flaggableId' => (int)$row['record_id'],
                'title'       => $row['title'],
                'url'         => APP_URL . '/' . $conf['url'] . '?id=' . (int)$row['record_id'],
                'note'        => $row['note'],
                'status'      => $row['status'],
                'agendaId'    => $row['agenda_id'] !== null ? (int)$row['agenda_id'] : null,
                'updatedAt'   => $row['updated_at'],
                'flaggers'    => [],
            ];
        }
    }
    if (!$items) return [];

    $ids = implode(',', array_keys($items));
    $flagRows = $db->query(
        "SELECT f.discussion_item_id, u.id AS user_id, u.display_name, u.username
         FROM pm_discussion_flags f
         JOIN users u ON u.id = f.user_id
         WHERE f.discussion_item_id IN ($ids)
         ORDER BY u.display_name, u.username"
    )->fetchAll();
    foreach ($flagRows as $r) {
        $itemId = (int)$r['discussion_item_id'];
        if (!isset($items[$itemId])) continue;
        $items[$itemId]['flaggers'][] = [
            'id'       => (int)$r['user_id'],
            'name'     => $r['display_name'] ?: $r['username'],
            'initials' => user_initials($r['display_name'], $r['username']),
        ];
    }

    $items = array_values($items);
    usort($items, fn($a, $b) => strcmp($b['updatedAt'], $a['updatedAt']));
    return $items;
}

/** One card in the Discussion list: title/link, flag button, flaggers, editable note, and (admin) the agenda-queue action. */
function render_discussion_item_card(array $item, int $currentUserId, bool $queued): string
{
    $state = [
        'flaggedByMe' => (bool)array_filter($item['flaggers'], fn($f) => $f['id'] === $currentUserId),
        'flaggers'    => $item['flaggers'],
    ];

    $out = '<div class="card discussion-item" data-discussion-row>';
    $out .= '<div class="discussion-item-head">';
    $out .= '<span class="pill">' . e(discussion_type_label($item['type'])) . '</span> ';
    $out .= '<a href="' . e($item['url']) . '" class="table-entity-link discussion-item-title">' . e($item['title']) . '</a> ';
    $out .= render_flag_button($item['type'], $item['flaggableId'], $state);
    $out .= '</div>';

    if ($item['flaggers']) {
        $out .= '<div class="discussion-flaggers">Raised by ';
        foreach ($item['flaggers'] as $f) {
            $out .= '<span class="discussion-initial" title="' . e($f['name']) . '">' . e($f['initials']) . '</span>';
        }
        $out .= '</div>';
    }

    $out .= '<div class="editable-wrap">';
    $out .= '<div class="editable-view">';
    $out .= '<p class="dl-value discussion-note' . ($item['note'] ? '' : ' empty-note') . '">' . ($item['note'] ? e($item['note']) : 'No note yet.') . '</p>';
    $out .= '<button type="button" class="btn btn--outline btn--sm" data-edit-toggle>Edit note</button>';
    $out .= '</div>';
    $out .= '<form method="POST" action="" class="editable-form discussion-note-form" hidden>';
    $out .= csrf_field();
    $out .= '<input type="hidden" name="item_id" value="' . $item['itemId'] . '">';
    $out .= '<textarea name="note" rows="3" placeholder="What\'s this actually about? Add context as the team learns more.">' . e((string)$item['note']) . '</textarea>';
    $out .= '<div class="form-actions" style="margin-top:.5rem; padding-top:0; border-top:none;">';
    $out .= '<button type="submit" name="save_note" value="1" class="btn btn--primary btn--sm">Save note</button>';
    $out .= '<button type="button" class="btn btn--outline btn--sm" data-edit-cancel>Cancel</button>';
    $out .= '</div></form></div>';

    if (is_admin()) {
        $out .= '<form method="POST" action="" class="discussion-item-actions">';
        $out .= csrf_field();
        $out .= '<input type="hidden" name="item_id" value="' . $item['itemId'] . '">';
        $out .= $queued
            ? '<button type="submit" name="return_to_open" value="1" class="btn btn--outline btn--sm">Move back to open discussion</button>'
            : '<button type="submit" name="add_to_agenda" value="1" class="btn btn--outline btn--sm">Add to next agenda</button>';
        $out .= '</form>';
    }

    return $out . '</div>';
}

/**
 * Drafts a plain-text meeting agenda from current status, open decisions,
 * open risks/issues, and upcoming/at-risk milestones. Meant to be edited
 * before publishing, not used verbatim.
 */
function generate_agenda_draft(PDO $db): string
{
    $latestSnapshot = $db->query(
        'SELECT overall_status, period_label FROM pm_weekly_snapshots ORDER BY created_at DESC LIMIT 1'
    )->fetch();

    $redRiskCount       = (int)$db->query("SELECT COUNT(*) FROM pm_risks_issues WHERE status != 'closed' AND severity = 'red'")->fetchColumn();
    $atRiskMilestones   = (int)$db->query("SELECT COUNT(*) FROM pm_milestones WHERE status = 'at_risk'")->fetchColumn();
    $overdueTasks       = (int)$db->query("SELECT COUNT(*) FROM pm_tasks WHERE status != 'done' AND due_date IS NOT NULL AND due_date < CURDATE()")->fetchColumn();
    $openDecisionsCount = (int)$db->query("SELECT COUNT(*) FROM pm_decisions WHERE status = 'open'")->fetchColumn();

    $headline = null;
    if ($redRiskCount > 0) {
        $headline = $redRiskCount . ' red ' . ($redRiskCount === 1 ? 'risk' : 'risks') . ' open';
    } elseif ($atRiskMilestones > 0) {
        $headline = $atRiskMilestones . ' ' . ($atRiskMilestones === 1 ? 'milestone' : 'milestones') . ' at risk';
    } elseif ($overdueTasks > 0) {
        $headline = $overdueTasks . ' ' . ($overdueTasks === 1 ? 'task' : 'tasks') . ' overdue';
    } elseif ($openDecisionsCount > 0) {
        $headline = $openDecisionsCount . ' ' . ($openDecisionsCount === 1 ? 'decision' : 'decisions') . ' awaiting a call';
    }

    $decisions = $db->query(
        "SELECT title, needed_by_date FROM pm_decisions WHERE status = 'open' ORDER BY needed_by_date IS NULL, needed_by_date"
    )->fetchAll();

    $risks = $db->query(
        "SELECT title, type, severity FROM pm_risks_issues WHERE status != 'closed'
         ORDER BY FIELD(severity,'red','amber','green'), raised_date DESC LIMIT 8"
    )->fetchAll();

    $milestones = $db->query(
        "SELECT title, target_date, status FROM pm_milestones
         WHERE status != 'complete'
           AND (status = 'at_risk' OR (target_date IS NOT NULL AND target_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)))
         ORDER BY target_date IS NULL, target_date"
    )->fetchAll();

    $lines = [];
    $lines[] = 'PROGRAMME BOARD — MEETING AGENDA';
    $lines[] = 'Week of ' . date('j M Y');
    $lines[] = '';

    $lines[] = '1. OVERALL STATUS' . ($latestSnapshot ? ': ' . strtoupper((string)$latestSnapshot['overall_status']) : ': not yet published');
    if ($headline) $lines[] = '   ' . $headline;
    if ($latestSnapshot) $lines[] = '   (from weekly update: ' . $latestSnapshot['period_label'] . ')';
    $lines[] = '';

    $lines[] = '2. DECISIONS REQUIRED';
    if ($decisions) {
        foreach ($decisions as $d) {
            $due = $d['needed_by_date'] ? ' (needed by ' . date('j M Y', strtotime($d['needed_by_date'])) . ')' : '';
            $lines[] = '   - ' . $d['title'] . $due;
        }
    } else {
        $lines[] = '   - None currently open.';
    }
    $lines[] = '';

    $lines[] = '3. RISKS & ISSUES';
    if ($risks) {
        $bySeverity = ['red' => [], 'amber' => [], 'green' => []];
        foreach ($risks as $r) {
            $bySeverity[$r['severity']][] = $r;
        }
        foreach (['red', 'amber', 'green'] as $severity) {
            if (!$bySeverity[$severity]) continue;
            $lines[] = '   ' . ucfirst($severity);
            foreach ($bySeverity[$severity] as $r) {
                $lines[] = '   - ' . $r['title'] . ' (' . ucfirst((string)$r['type']) . ')';
            }
        }
    } else {
        $lines[] = '   - None currently open.';
    }
    $lines[] = '';

    $lines[] = '4. MILESTONES';
    if ($milestones) {
        foreach ($milestones as $m) {
            $date = $m['target_date'] ? date('j M Y', strtotime($m['target_date'])) : 'no date set';
            $lines[] = '   - ' . $m['title'] . ' — ' . $date . ' (' . str_replace('_', ' ', (string)$m['status']) . ')';
        }
    } else {
        $lines[] = '   - Nothing upcoming or at risk in the next 90 days.';
    }
    $lines[] = '';

    $flaggableTypes = discussion_flaggable_types();
    $raised = [];
    foreach ($flaggableTypes as $type => $conf) {
        $rows = $db->prepare(
            "SELECT r.title FROM pm_discussion_items di
             JOIN {$conf['table']} r ON r.id = di.flaggable_id
             WHERE di.flaggable_type = ? AND di.status = 'added_to_agenda' AND di.agenda_id IS NULL
             ORDER BY di.updated_at"
        );
        $rows->execute([$type]);
        foreach ($rows->fetchAll(PDO::FETCH_COLUMN) as $title) {
            $raised[] = $title;
        }
    }

    $lines[] = '5. RAISED BY THE TEAM';
    if ($raised) {
        foreach ($raised as $title) {
            $lines[] = '   - ' . $title;
        }
    } else {
        $lines[] = '   - Nothing queued from the discussion list.';
    }
    $lines[] = '';

    $lines[] = '6. ANY OTHER BUSINESS';
    $lines[] = '';
    $lines[] = '';
    $lines[] = '7. NEXT MEETING';
    $lines[] = '';

    return implode("\n", $lines);
}

/**
 * Replaces the attendee list for an agenda.
 * @param array<int, array{user_id: ?int, name: string, status: string}> $rows
 */
function save_agenda_attendees(PDO $db, int $agendaId, array $rows): void
{
    $db->prepare('DELETE FROM pm_agenda_attendees WHERE agenda_id = ?')->execute([$agendaId]);
    if (!$rows) return;
    $stmt = $db->prepare(
        'INSERT INTO pm_agenda_attendees (agenda_id, user_id, name, status) VALUES (?, ?, ?, ?)'
    );
    foreach ($rows as $r) {
        $stmt->execute([$agendaId, $r['user_id'] ?: null, $r['name'], $r['status']]);
    }
}

/**
 * @return array{attending: array<int, array{name:string, user_id:?int}>, apologies: array<int, array{name:string, user_id:?int}>}
 */
function get_agenda_attendees(PDO $db, int $agendaId): array
{
    $stmt = $db->prepare(
        'SELECT user_id, name, status FROM pm_agenda_attendees WHERE agenda_id = ? ORDER BY name'
    );
    $stmt->execute([$agendaId]);
    $out = ['attending' => [], 'apologies' => []];
    foreach ($stmt->fetchAll() as $r) {
        $out[$r['status']][] = ['name' => $r['name'], 'user_id' => $r['user_id'] !== null ? (int)$r['user_id'] : null];
    }
    return $out;
}

/**
 * Builds the roadmap timeline data structure (phase rows, axis ticks,
 * date->% helper) from a milestone list. Returns null when there isn't
 * enough dated data to plot.
 *
 * When $hideCompletePhases is true, any phase where every milestone is
 * already 'complete' is dropped entirely, and the date axis is rebuilt
 * from only what's left — a condensed, forward-looking view for the
 * dashboard, as opposed to the full history shown on the Milestones page.
 */
function build_milestone_roadmap(array $milestones, bool $hideCompletePhases = false): ?array
{
    $dated = array_values(array_filter($milestones, fn($m) => !empty($m['target_date'])));
    if (count($dated) < 2) return null;

    $phaseGroups = [];
    foreach ($dated as $m) {
        $phase = ($m['phase'] !== null && $m['phase'] !== '') ? $m['phase'] : 'Unphased';
        $phaseGroups[$phase][] = $m;
    }

    if ($hideCompletePhases) {
        $phaseGroups = array_filter($phaseGroups, function (array $items): bool {
            foreach ($items as $m) {
                if ($m['status'] !== 'complete') return true;
            }
            return false;
        });
        if (!$phaseGroups) return null;
    }

    $shown = array_merge(...array_values($phaseGroups));
    if (count($shown) < 2) return null;

    $timestamps = array_map(fn($m) => strtotime($m['target_date']), $shown);
    $todayTs    = strtotime(date('Y-m-d'));
    $rangeMinTs = min(min($timestamps), $todayTs);
    $rangeMaxTs = max(max($timestamps), $todayTs);
    $pad        = max((int)round(($rangeMaxTs - $rangeMinTs) * 0.06), 86400 * 14);
    $axisMinTs  = $rangeMinTs - $pad;
    $axisMaxTs  = $rangeMaxTs + $pad;
    $axisSpan   = $axisMaxTs - $axisMinTs;
    $toPct      = fn(int $ts): float => $axisSpan > 0 ? max(0, min(100, ($ts - $axisMinTs) / $axisSpan * 100)) : 50.0;

    uksort($phaseGroups, function ($a, $b) use ($phaseGroups) {
        $aMin = min(array_map(fn($m) => strtotime($m['target_date']), $phaseGroups[$a]));
        $bMin = min(array_map(fn($m) => strtotime($m['target_date']), $phaseGroups[$b]));
        return $aMin <=> $bMin;
    });

    $axisTicks = [];
    $tick = new DateTime('@' . $axisMinTs);
    $tick->setTime(0, 0, 0);
    $qStartMonth = intdiv(((int)$tick->format('n')) - 1, 3) * 3 + 1;
    $tick->setDate((int)$tick->format('Y'), $qStartMonth, 1);
    while ($tick->getTimestamp() <= $axisMaxTs) {
        if ($tick->getTimestamp() >= $axisMinTs) {
            $axisTicks[] = ['pct' => $toPct($tick->getTimestamp()), 'label' => $tick->format('M Y')];
        }
        $tick->modify('+3 months');
    }

    return [
        'phaseGroups' => $phaseGroups,
        'axisTicks'   => $axisTicks,
        'todayPct'    => $toPct($todayTs),
        'toPct'       => $toPct,
    ];
}
