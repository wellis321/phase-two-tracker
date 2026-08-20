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
    'SELECT a.*, u.display_name, u.username
     FROM pm_agendas a
     LEFT JOIN users u ON u.id = a.created_by_user_id
     WHERE a.id = ?'
);
$stmt->execute([$id]);
$agenda = $stmt->fetch();
if (!$agenda) {
    flash('error', 'Agenda not found.');
    redirect(APP_URL . '/agenda/index.php');
}
$attendees = get_agenda_attendees($db, $id);

$pageTitle  = $agenda['title'];
$activePage = 'agendas';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="agenda-letterhead">
  <span class="agenda-letterhead-mark"><?= icon_checkmark_badge() ?></span>
  <span class="agenda-letterhead-text">
    <span class="agenda-letterhead-org">Repairs Delivery — Phase 2</span>
    <span class="agenda-letterhead-app">Programme Tracker &middot; Meeting Agenda</span>
  </span>
</div>

<div class="page-header">
  <div>
    <h1><?= e($agenda['title']) ?></h1>
    <p class="status-hero-meta">
      Meeting date <?= format_date($agenda['meeting_date']) ?>
      <?php if ($agenda['location']): ?>&middot; <?= e($agenda['location']) ?><?php endif; ?>
      &middot; Published <?= e(date('j M Y, g:ia', strtotime($agenda['created_at']))) ?>
      by <?= e($agenda['display_name'] ?: $agenda['username'] ?: 'unknown') ?>
    </p>
  </div>
  <div class="page-header-actions no-print">
    <button type="button" id="agenda-copy-link" class="btn btn--outline btn--sm" data-url="<?= e(APP_URL . '/agenda/view.php?id=' . (int)$agenda['id']) ?>">Copy link</button>
    <a href="<?= APP_URL ?>/agenda/pdf.php?id=<?= (int)$agenda['id'] ?>" class="btn btn--outline btn--sm" target="_blank" rel="noopener">Download PDF</a>
  </div>
</div>

<?php if ($attendees['attending'] || $attendees['apologies']): ?>
<div class="card" style="margin-bottom:1rem;">
  <div class="detail-grid"<?= (!$attendees['attending'] || !$attendees['apologies']) ? ' style="grid-template-columns: 1fr;"' : '' ?>>
    <?php if ($attendees['attending']): ?>
    <div>
      <span class="dl-label">Attending (<?= count($attendees['attending']) ?>)</span>
      <p class="dl-value" style="margin-bottom:0;"><?= implode(', ', array_map('e', array_column($attendees['attending'], 'name'))) ?></p>
    </div>
    <?php endif; ?>
    <?php if ($attendees['apologies']): ?>
    <div>
      <span class="dl-label">Apologies (<?= count($attendees['apologies']) ?>)</span>
      <p class="dl-value" style="margin-bottom:0;"><?= implode(', ', array_map('e', array_column($attendees['apologies'], 'name'))) ?></p>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <pre class="agenda-content"><?= e($agenda['content']) ?></pre>
</div>

<p class="back-nav no-print"><a class="back-link" href="<?= APP_URL ?>/agenda/index.php"><?= icon_arrow_left() ?> Back to agendas</a></p>

<script src="<?= asset_url('/assets/js/agenda-view.js') ?>"></script>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
