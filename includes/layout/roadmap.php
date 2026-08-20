<?php
declare(strict_types=1);
/**
 * Renders the roadmap timeline. Expects $roadmap (build_milestone_roadmap()
 * output) in scope; optionally $roadmapHeading and $roadmapViewAllUrl.
 */
if ($roadmap): ?>
<div class="card" style="margin-bottom:1rem;">
  <div class="card-title-row">
    <h2><?= e($roadmapHeading ?? 'Roadmap') ?></h2>
    <span class="roadmap-legend">
      <span><i class="roadmap-swatch roadmap-swatch--upcoming"></i>Upcoming</span>
      <span><i class="roadmap-swatch roadmap-swatch--at_risk"></i>At risk</span>
      <span><i class="roadmap-swatch roadmap-swatch--complete"></i>Complete</span>
      <?php if (!empty($roadmapViewAllUrl)): ?>
      <a href="<?= e($roadmapViewAllUrl) ?>" class="btn btn--outline btn--sm">View full roadmap</a>
      <?php endif; ?>
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
