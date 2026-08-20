<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$db = db();

$milestones = $db->query(
    "SELECT * FROM pm_milestones ORDER BY (status = 'complete'), target_date IS NULL, target_date"
)->fetchAll();

$roadmap = null;
$roadmapMilestones = array_values(array_filter($milestones, fn($m) => !empty($m['target_date'])));
if (count($roadmapMilestones) >= 2) {
    $timestamps  = array_map(fn($m) => strtotime($m['target_date']), $roadmapMilestones);
    $todayTs     = strtotime(date('Y-m-d'));
    $rangeMinTs  = min(min($timestamps), $todayTs);
    $rangeMaxTs  = max(max($timestamps), $todayTs);
    $pad         = max((int)round(($rangeMaxTs - $rangeMinTs) * 0.06), 86400 * 14);
    $axisMinTs   = $rangeMinTs - $pad;
    $axisMaxTs   = $rangeMaxTs + $pad;
    $axisSpan    = $axisMaxTs - $axisMinTs;
    $toPct       = fn(int $ts): float => $axisSpan > 0 ? max(0, min(100, ($ts - $axisMinTs) / $axisSpan * 100)) : 50.0;

    $phaseGroups = [];
    foreach ($roadmapMilestones as $m) {
        $phase = ($m['phase'] !== null && $m['phase'] !== '') ? $m['phase'] : 'Unphased';
        $phaseGroups[$phase][] = $m;
    }
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

    $roadmap = [
        'phaseGroups' => $phaseGroups,
        'axisTicks'   => $axisTicks,
        'todayPct'    => $toPct($todayTs),
        'toPct'       => $toPct,
    ];
}

$pageTitle  = 'Milestones';
$activePage = 'milestones';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Milestones</h1>
    <p>Key dates across Phase 2 — build, integration, pilot, and go-live markers.</p>
  </div>
  <?php if (is_admin()): ?>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/milestones/create.php" class="btn btn--primary">+ Add milestone</a>
  </div>
  <?php endif; ?>
</div>

<?php if ($roadmap): ?>
<div class="card" style="margin-bottom:1rem;">
  <div class="card-title-row">
    <h2>Roadmap</h2>
    <span class="roadmap-legend">
      <span><i class="roadmap-swatch roadmap-swatch--upcoming"></i>Upcoming</span>
      <span><i class="roadmap-swatch roadmap-swatch--at_risk"></i>At risk</span>
      <span><i class="roadmap-swatch roadmap-swatch--complete"></i>Complete</span>
    </span>
  </div>
  <div class="roadmap-scroll">
  <div class="roadmap-grid">
    <div class="roadmap-row roadmap-row--axis">
      <div class="roadmap-row-label"></div>
      <div class="roadmap-row-track">
        <?php foreach ($roadmap['axisTicks'] as $t): ?>
        <div class="roadmap-axis-tick" style="left:<?= $t['pct'] ?>%"><span><?= e($t['label']) ?></span></div>
        <?php endforeach; ?>
        <div class="roadmap-today-line" style="left:<?= $roadmap['todayPct'] ?>%"><span>Today</span></div>
      </div>
    </div>
    <?php foreach ($roadmap['phaseGroups'] as $phase => $items): ?>
    <div class="roadmap-row">
      <div class="roadmap-row-label"><?= e($phase) ?></div>
      <div class="roadmap-row-track">
        <div class="roadmap-today-line roadmap-today-line--plain" style="left:<?= $roadmap['todayPct'] ?>%"></div>
        <?php foreach ($items as $m): ?>
        <a class="roadmap-dot roadmap-dot--<?= e($m['status']) ?>"
           style="left:<?= $roadmap['toPct'](strtotime($m['target_date'])) ?>%"
           href="<?= APP_URL ?>/milestones/view.php?id=<?= (int)$m['id'] ?>"
           title="<?= e($m['title']) ?> &mdash; <?= e(format_date($m['target_date'])) ?> &mdash; <?= e(str_replace('_', ' ', ucfirst($m['status']))) ?>"></a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  </div>
</div>
<?php endif; ?>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Title</th>
        <th>Phase</th>
        <th>Target date</th>
        <th>Status</th>
        <?php if (is_admin()): ?><th class="col-actions">Actions</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (!$milestones): ?>
      <tr><td colspan="5" class="empty-note">No milestones recorded yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($milestones as $m): ?>
      <tr class="table-row--clickable" data-href="<?= APP_URL ?>/milestones/view.php?id=<?= (int)$m['id'] ?>">
        <td><a href="<?= APP_URL ?>/milestones/view.php?id=<?= (int)$m['id'] ?>" class="table-entity-link"><?= e($m['title']) ?></a></td>
        <td><?= e($m['phase'] ?: '—') ?></td>
        <td><?= format_date($m['target_date']) ?></td>
        <td><span class="pill pill--<?= e($m['status']) ?>"><?= e(str_replace('_', ' ', $m['status'])) ?></span></td>
        <?php if (is_admin()): ?>
        <td class="col-actions"><a href="<?= APP_URL ?>/milestones/edit.php?id=<?= (int)$m['id'] ?>" class="icon-btn" title="Edit" aria-label="Edit"><?= icon_edit() ?></a></td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
