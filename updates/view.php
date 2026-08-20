<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$db = db();

$id   = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare(
    'SELECT s.*, u.display_name, u.username
     FROM pm_weekly_snapshots s
     LEFT JOIN users u ON u.id = s.created_by_user_id
     WHERE s.id = ?'
);
$stmt->execute([$id]);
$s = $stmt->fetch();
if (!$s) {
    flash('error', 'Update not found.');
    redirect(APP_URL . '/updates/index.php');
}

$pageTitle  = $s['period_label'];
$activePage = 'updates';
require __DIR__ . '/../includes/layout/header.php';

function section(string $label, ?string $body): void
{
    if (!$body) return;
    echo '<div class="status-block"><h3>' . e($label) . '</h3><p>' . e($body) . '</p></div>';
}
?>

<div class="page-header">
  <div>
    <h1><?= e($s['period_label']) ?></h1>
    <p>Published <?= e(date('j M Y, g:ia', strtotime($s['created_at']))) ?> by <?= e($s['display_name'] ?: $s['username'] ?: 'unknown') ?></p>
  </div>
  <div class="page-header-actions"><?= rag_badge($s['overall_status']) ?></div>
</div>

<div class="card">
  <?php
  section('Current focus — next couple of weeks', $s['current_focus']);
  section('Progress this week', $s['progress_narrative']);
  section('Achievements', $s['achievements']);
  section('Key decisions', $s['key_decisions']);
  section('Risks raised', $s['risks_raised']);
  section('Lessons learned', $s['lessons_learned']);
  section('Looking ahead — next 60–90 days', $s['looking_ahead_notes']);
  ?>
</div>

<p class="back-nav"><a class="back-link" href="<?= APP_URL ?>/updates/index.php"><?= icon_arrow_left() ?> Back to archive</a></p>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
