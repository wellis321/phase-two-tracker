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

function days_until(?string $date): ?int
{
    if (!$date) return null;
    $ts = strtotime($date);
    if (!$ts) return null;
    return (int)floor((strtotime(date('Y-m-d', $ts)) - strtotime(date('Y-m-d'))) / 86400);
}
