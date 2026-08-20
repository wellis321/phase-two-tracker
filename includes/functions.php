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

    $lines[] = '5. ANY OTHER BUSINESS';
    $lines[] = '';
    $lines[] = '';
    $lines[] = '6. NEXT MEETING';
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
