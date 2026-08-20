<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/includes/db.php';

require_login();
header('Content-Type: application/json');

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'errors' => ['You do not have permission to do that.']]);
    exit;
}

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'users') {
    $users = $db->query(
        "SELECT id, username, display_name FROM users WHERE is_active = 1 ORDER BY COALESCE(display_name, username)"
    )->fetchAll();
    echo json_encode(['users' => array_map(
        fn($u) => ['id' => (int)$u['id'], 'name' => $u['display_name'] ?: $u['username']],
        $users
    )]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'categories') {
    echo json_encode(['categories' => get_tag_categories($db)]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Method not allowed.']]);
    exit;
}

if (!csrf_verify()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => ['Invalid form token — refresh the page and try again.']]);
    exit;
}

$type   = $_POST['type'] ?? '';
$title  = trim($_POST['title'] ?? '');
$errors = [];
if ($title === '') $errors[] = 'Title is required.';

switch ($type) {
    case 'task':
        if (!$errors) {
            $db->prepare(
                'INSERT INTO pm_tasks (title, assignee_user_id, status, due_date, created_by) VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $title, $_POST['assignee_user_id'] ?: null, 'todo', $_POST['due_date'] ?: null, get_current_user_id(),
            ]);
            $tagIds = array_map('intval', $_POST['tag_ids'] ?? []);
            save_tags_for($db, 'task', (int)$db->lastInsertId(), $tagIds);
        }
        break;

    case 'milestone':
        if (!$errors) {
            $db->prepare(
                'INSERT INTO pm_milestones (title, phase, target_date, status, created_by) VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $title, trim($_POST['phase'] ?? '') ?: null, $_POST['target_date'] ?: null, 'upcoming', get_current_user_id(),
            ]);
        }
        break;

    case 'risk':
        $riskType = $_POST['risk_type'] ?? 'risk';
        $severity = $_POST['severity'] ?? 'amber';
        if (!in_array($riskType, ['risk', 'issue'], true)) $errors[] = 'Invalid type.';
        if (!in_array($severity, ['red', 'amber', 'green'], true)) $errors[] = 'Invalid severity.';
        if (!$errors) {
            $db->prepare(
                'INSERT INTO pm_risks_issues (type, title, severity, status, raised_date, created_by) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$riskType, $title, $severity, 'open', date('Y-m-d'), get_current_user_id()]);
        }
        break;

    case 'decision':
        if (!$errors) {
            $db->prepare(
                'INSERT INTO pm_decisions (title, needed_by_date, decision_owner_user_id, status, created_by) VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $title, $_POST['needed_by_date'] ?: null, $_POST['decision_owner_user_id'] ?: null, 'open', get_current_user_id(),
            ]);
        }
        break;

    case 'supplier':
        if (!$errors) {
            $db->prepare(
                'INSERT INTO pm_supplier_activities (supplier, title, status, due_date, created_by) VALUES (?, ?, ?, ?, ?)'
            )->execute([
                trim($_POST['supplier'] ?? '') ?: 'ROCC', $title, 'planned', $_POST['due_date'] ?: null, get_current_user_id(),
            ]);
        }
        break;

    default:
        $errors[] = 'Unknown item type.';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

echo json_encode(['success' => true, 'message' => "'{$title}' added."]);
