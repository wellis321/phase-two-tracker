<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();
header('Content-Type: application/json');

$db      = db();
$q       = trim($_GET['q'] ?? '');
$exclude = (int)($_GET['exclude'] ?? 0);

if ($q === '') {
    echo json_encode(['tasks' => []]);
    exit;
}

$stmt = $db->prepare(
    'SELECT id, title, status FROM pm_tasks WHERE title LIKE ? AND id != ? ORDER BY title LIMIT 15'
);
$stmt->execute(['%' . $q . '%', $exclude]);
echo json_encode(['tasks' => $stmt->fetchAll()]);
