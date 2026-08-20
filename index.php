<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/includes/db.php';

if (!is_logged_in()) {
    require __DIR__ . '/includes/layout/landing.php';
    exit;
}

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$db = db();

$latestSnapshot = $db->query(
    'SELECT s.*, u.display_name, u.username
     FROM pm_weekly_snapshots s
     LEFT JOIN users u ON u.id = s.created_by_user_id
     ORDER BY s.created_at DESC LIMIT 1'
)->fetch();

$statusHistory = $db->query(
    "SELECT overall_status, period_label, created_at FROM pm_weekly_snapshots ORDER BY created_at DESC LIMIT 14"
)->fetchAll();
$statusHistory = array_reverse($statusHistory);

$openTasks    = (int)$db->query("SELECT COUNT(*) FROM pm_tasks WHERE status != 'done'")->fetchColumn();
$openRisks    = (int)$db->query("SELECT COUNT(*) FROM pm_risks_issues WHERE status != 'closed'")->fetchColumn();
$openDecisions = (int)$db->query("SELECT COUNT(*) FROM pm_decisions WHERE status = 'open'")->fetchColumn();
$activeSupplier = (int)$db->query("SELECT COUNT(*) FROM pm_supplier_activities WHERE status IN ('planned','in_progress')")->fetchColumn();
$upcomingMilestones90 = (int)$db->query(
    "SELECT COUNT(*) FROM pm_milestones WHERE status != 'complete' AND target_date IS NOT NULL AND target_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)"
)->fetchColumn();
$redRiskCount     = (int)$db->query("SELECT COUNT(*) FROM pm_risks_issues WHERE status != 'closed' AND severity = 'red'")->fetchColumn();
$atRiskMilestones = (int)$db->query("SELECT COUNT(*) FROM pm_milestones WHERE status = 'at_risk'")->fetchColumn();
$overdueTasks     = (int)$db->query("SELECT COUNT(*) FROM pm_tasks WHERE status != 'done' AND due_date IS NOT NULL AND due_date < CURDATE()")->fetchColumn();

$healthContext = null;
if ($redRiskCount > 0) {
    $healthContext = $redRiskCount . ' red ' . ($redRiskCount === 1 ? 'risk' : 'risks') . ' open';
} elseif ($atRiskMilestones > 0) {
    $healthContext = $atRiskMilestones . ' ' . ($atRiskMilestones === 1 ? 'milestone' : 'milestones') . ' at risk';
} elseif ($overdueTasks > 0) {
    $healthContext = $overdueTasks . ' ' . ($overdueTasks === 1 ? 'task' : 'tasks') . ' overdue';
} elseif ($openDecisions > 0) {
    $healthContext = $openDecisions . ' ' . ($openDecisions === 1 ? 'decision' : 'decisions') . ' awaiting a call';
}

$myTasks = [];
if (get_current_user_id() > 0) {
    $stmt = $db->prepare(
        "SELECT * FROM pm_tasks WHERE assignee_user_id = ? AND status != 'done' ORDER BY due_date IS NULL, due_date LIMIT 6"
    );
    $stmt->execute([get_current_user_id()]);
    $myTasks = $stmt->fetchAll();
}

$upcomingMilestones = $db->query(
    "SELECT * FROM pm_milestones WHERE status != 'complete' AND target_date IS NOT NULL
     AND target_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
     ORDER BY target_date LIMIT 8"
)->fetchAll();

$allMilestones     = $db->query("SELECT * FROM pm_milestones ORDER BY target_date IS NULL, target_date")->fetchAll();
$roadmap           = build_milestone_roadmap($allMilestones, true);
$roadmapHeading     = "Roadmap — what's ahead";
$roadmapViewAllUrl  = APP_URL . '/milestones/index.php';

$topRisks = $db->query(
    "SELECT * FROM pm_risks_issues WHERE status != 'closed' ORDER BY FIELD(severity,'red','amber','green'), raised_date DESC LIMIT 6"
)->fetchAll();

$openDecisionsList = $db->query(
    "SELECT * FROM pm_decisions WHERE status = 'open' ORDER BY needed_by_date IS NULL, needed_by_date LIMIT 6"
)->fetchAll();

$supplierActivity = $db->query(
    "SELECT * FROM pm_supplier_activities WHERE status IN ('planned','in_progress') ORDER BY due_date IS NULL, due_date LIMIT 6"
)->fetchAll();

$currentUserId = get_current_user_id();
$riskDiscussion       = get_discussion_states_bulk($db, 'risk', array_column($topRisks, 'id'), $currentUserId);
$decisionDiscussion    = get_discussion_states_bulk($db, 'decision', array_column($openDecisionsList, 'id'), $currentUserId);
$supplierDiscussion    = get_discussion_states_bulk($db, 'supplier', array_column($supplierActivity, 'id'), $currentUserId);
$milestoneDiscussion   = get_discussion_states_bulk($db, 'milestone', array_column($upcomingMilestones, 'id'), $currentUserId);
$taskDiscussion        = get_discussion_states_bulk($db, 'task', array_column($myTasks, 'id'), $currentUserId);

require __DIR__ . '/includes/layout/header.php';
?>

<?php if (is_admin()): ?>
<div class="page-header-actions" style="margin-bottom:1rem; justify-content:flex-end; display:flex; gap:.6rem;">
  <a href="<?= APP_URL ?>/updates/create.php" class="btn btn--primary">+ Publish weekly update</a>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:1rem;">
  <?php if ($latestSnapshot): ?>
  <div class="status-hero">
    <?= health_indicator($latestSnapshot['overall_status'], $healthContext) ?>
    <div class="status-hero-text">
      <h2><?= e($latestSnapshot['period_label']) ?></h2>
      <div class="status-hero-meta">
        Published <?= e(date('j M Y, g:ia', strtotime($latestSnapshot['created_at']))) ?>
        by <?= e($latestSnapshot['display_name'] ?: $latestSnapshot['username'] ?: 'unknown') ?>
        · <a href="<?= APP_URL ?>/updates/index.php">View archive</a>
      </div>
    </div>
    <?php if (count($statusHistory) > 1): ?>
    <div class="status-history-inline">
      <span class="status-history-caption">Status history &middot; last <?= count($statusHistory) ?></span>
      <div class="status-history-strip">
        <?php foreach ($statusHistory as $h): $lvl = strtolower((string)$h['overall_status']); $letter = ['red'=>'R','amber'=>'A','green'=>'G'][$lvl] ?? '?'; ?>
        <span class="status-history-cell status-history-cell--<?= e($lvl) ?>" title="<?= e($h['period_label']) ?> &mdash; <?= e(ucfirst($lvl)) ?> &mdash; <?= e(date('j M Y', strtotime($h['created_at']))) ?>"><?= $letter ?></span>
        <?php endforeach; ?>
      </div>
      <div class="status-history-arrow" aria-hidden="true"><span class="status-history-arrow-line"></span><span class="status-history-arrow-head"></span></div>
      <span class="status-history-legend-compact">R off track &middot; A at risk &middot; G on track</span>
    </div>
    <?php endif; ?>
  </div>
  <?php if ($latestSnapshot['current_focus'] || $latestSnapshot['progress_narrative'] || $latestSnapshot['looking_ahead_notes']): ?>
  <div class="status-grid">
    <?php if ($latestSnapshot['current_focus']): ?>
    <div class="status-block">
      <h3>Current focus <span class="status-block-sub">next couple of weeks</span></h3>
      <p><?= e($latestSnapshot['current_focus']) ?></p>
    </div>
    <?php endif; ?>
    <?php if ($latestSnapshot['progress_narrative']): ?>
    <div class="status-block">
      <h3>Progress <span class="status-block-sub">this week</span></h3>
      <p><?= e($latestSnapshot['progress_narrative']) ?></p>
    </div>
    <?php endif; ?>
    <?php if ($latestSnapshot['looking_ahead_notes']): ?>
    <div class="status-block">
      <h3>Looking ahead <span class="status-block-sub">next 60–90 days</span></h3>
      <p><?= e($latestSnapshot['looking_ahead_notes']) ?></p>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php else: ?>
  <p class="empty-note">No weekly update has been published yet.
    <?= is_admin() ? '<a href="' . APP_URL . '/updates/create.php">Publish the first one</a>.' : '' ?>
  </p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout/roadmap.php'; ?>

<div class="stats-grid">
  <a href="<?= APP_URL ?>/tasks/index.php" class="stat-card">
    <span class="stat-label">Open tasks</span>
    <span class="stat-value"><?= $openTasks ?></span>
  </a>
  <a href="<?= APP_URL ?>/risks/index.php" class="stat-card">
    <span class="stat-label">Risks &amp; issues</span>
    <span class="stat-value"><?= $openRisks ?></span>
  </a>
  <a href="<?= APP_URL ?>/decisions/index.php" class="stat-card">
    <span class="stat-label">Decisions required</span>
    <span class="stat-value"><?= $openDecisions ?></span>
  </a>
  <a href="<?= APP_URL ?>/supplier/index.php" class="stat-card">
    <span class="stat-label">Active supplier items</span>
    <span class="stat-value"><?= $activeSupplier ?></span>
  </a>
  <a href="<?= APP_URL ?>/milestones/index.php" class="stat-card">
    <span class="stat-label">Milestones in 90 days</span>
    <span class="stat-value"><?= $upcomingMilestones90 ?></span>
  </a>
</div>

<div class="dash-grid">
  <div>
    <div class="card">
      <div class="card-title-row">
        <h2>Risks &amp; issues</h2>
        <a href="<?= APP_URL ?>/risks/index.php" class="btn btn--outline btn--sm">View all</a>
      </div>
      <?php if ($topRisks): ?>
      <ul class="list-simple">
        <?php foreach ($topRisks as $r): ?>
        <li>
          <span class="li-title"><?= rag_badge($r['severity']) ?> <?= e($r['title']) ?></span>
          <span class="li-meta"><?= e(ucfirst($r['type'])) ?> · <span class="pill pill--<?= e($r['status']) ?>"><?= e(str_replace('_',' ',$r['status'])) ?></span> <?= render_flag_button('risk', (int)$r['id'], $riskDiscussion[(int)$r['id']] ?? ['flaggedByMe' => false, 'flaggers' => []]) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><p class="empty-note">No open risks or issues.</p><?php endif; ?>
    </div>

    <div class="card">
      <div class="card-title-row">
        <h2>Decisions required</h2>
        <a href="<?= APP_URL ?>/decisions/index.php" class="btn btn--outline btn--sm">View all</a>
      </div>
      <?php if ($openDecisionsList): ?>
      <ul class="list-simple">
        <?php foreach ($openDecisionsList as $d): ?>
        <li>
          <span class="li-title"><?= e($d['title']) ?></span>
          <span class="li-meta">Needed by <?= format_date($d['needed_by_date']) ?> <?= render_flag_button('decision', (int)$d['id'], $decisionDiscussion[(int)$d['id']] ?? ['flaggedByMe' => false, 'flaggers' => []]) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><p class="empty-note">No decisions currently open.</p><?php endif; ?>
    </div>

    <div class="card">
      <div class="card-title-row">
        <h2>Supplier activity</h2>
        <a href="<?= APP_URL ?>/supplier/index.php" class="btn btn--outline btn--sm">View all</a>
      </div>
      <?php if ($supplierActivity): ?>
      <ul class="list-simple">
        <?php foreach ($supplierActivity as $s): ?>
        <li>
          <span class="li-title"><?= e($s['supplier']) ?>: <?= e($s['title']) ?></span>
          <span class="li-meta"><span class="pill pill--<?= e($s['status']) ?>"><?= e(str_replace('_',' ',$s['status'])) ?></span> · <?= format_date($s['due_date']) ?> <?= render_flag_button('supplier', (int)$s['id'], $supplierDiscussion[(int)$s['id']] ?? ['flaggedByMe' => false, 'flaggers' => []]) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><p class="empty-note">No planned or in-progress supplier activity.</p><?php endif; ?>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-title-row">
        <h2>Upcoming milestones</h2>
        <a href="<?= APP_URL ?>/milestones/index.php" class="btn btn--outline btn--sm">View all</a>
      </div>
      <?php if ($upcomingMilestones): ?>
      <ul class="list-simple">
        <?php foreach ($upcomingMilestones as $m): ?>
        <li>
          <span class="li-title"><?= e($m['title']) ?></span>
          <span class="li-meta"><span class="pill pill--<?= e($m['status']) ?>"><?= e(str_replace('_',' ',$m['status'])) ?></span> · <?= format_date($m['target_date']) ?> <?= render_flag_button('milestone', (int)$m['id'], $milestoneDiscussion[(int)$m['id']] ?? ['flaggedByMe' => false, 'flaggers' => []]) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><p class="empty-note">Nothing due in the next 90 days.</p><?php endif; ?>
    </div>

    <?php if ($myTasks): ?>
    <div class="card">
      <h2>My open tasks</h2>
      <ul class="list-simple">
        <?php foreach ($myTasks as $t): ?>
        <li>
          <span class="li-title"><?= e($t['title']) ?></span>
          <span class="li-meta"><span class="pill pill--<?= e($t['status']) ?>"><?= e(str_replace('_',' ',$t['status'])) ?></span> · <?= format_date($t['due_date']) ?> <?= render_flag_button('task', (int)$t['id'], $taskDiscussion[(int)$t['id']] ?? ['flaggedByMe' => false, 'flaggers' => []]) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/layout/footer.php'; ?>
