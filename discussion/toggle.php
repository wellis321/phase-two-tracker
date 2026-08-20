<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
header('Content-Type: application/json');

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

$type  = $_POST['type'] ?? '';
$id    = (int)($_POST['id'] ?? 0);
$types = discussion_flaggable_types();

if (!isset($types[$type]) || $id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => ['Invalid record.']]);
    exit;
}

$db = db();
$exists = $db->prepare("SELECT COUNT(*) FROM {$types[$type]['table']} WHERE id = ?");
$exists->execute([$id]);
if (!$exists->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'errors' => ['Record not found.']]);
    exit;
}

$state = toggle_discussion_flag($db, $type, $id, get_current_user_id());
echo json_encode(['success' => true, 'state' => $state]);
