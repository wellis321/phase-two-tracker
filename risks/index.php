<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$db = db();

$statusFilter = $_GET['status'] ?? 'open_mitigated';
$where  = [];
$params = [];
if ($statusFilter === 'open_mitigated') {
    $where[] = "r.status != 'closed'";
} elseif (in_array($statusFilter, ['open', 'mitigated', 'closed'], true)) {
    $where[]  = 'r.status = ?';
    $params[] = $statusFilter;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$risks = $db->prepare(
    "SELECT r.*, u.display_name, u.username
     FROM pm_risks_issues r
     LEFT JOIN users u ON u.id = r.owner_user_id
     $whereSql
     ORDER BY (r.status = 'closed'), FIELD(r.severity,'red','amber','green'), r.raised_date DESC"
);
$risks->execute($params);
$risks = $risks->fetchAll();

$pageTitle  = 'Risks & Issues';
$activePage = 'risks';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Risks &amp; issues</h1>
    <p>Anything that could threaten the programme (risk) or already has (issue), rated red/amber/green.</p>
  </div>
  <?php if (is_admin()): ?>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/risks/create.php" class="btn btn--primary">+ Add risk / issue</a>
  </div>
  <?php endif; ?>
</div>

<div class="filter-bar">
  <form method="GET" style="display:contents;">
    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status" onchange="this.form.submit()">
        <option value="open_mitigated" <?= $statusFilter === 'open_mitigated' ? 'selected' : '' ?>>Open &amp; mitigated</option>
        <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open only</option>
        <option value="mitigated" <?= $statusFilter === 'mitigated' ? 'selected' : '' ?>>Mitigated</option>
        <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
      </select>
    </div>
  </form>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Severity</th>
        <th>Type</th>
        <th>Title</th>
        <th>Owner</th>
        <th>Status</th>
        <th>Raised</th>
        <?php if (is_admin()): ?><th class="col-actions">Actions</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (!$risks): ?>
      <tr><td colspan="7" class="empty-note">Nothing recorded yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($risks as $r): ?>
      <tr>
        <td><?= rag_badge($r['severity']) ?></td>
        <td><?= e(ucfirst($r['type'])) ?></td>
        <td><?= e($r['title']) ?></td>
        <td><?= e($r['display_name'] ?: $r['username'] ?: '—') ?></td>
        <td><span class="pill pill--<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
        <td><?= format_date($r['raised_date']) ?></td>
        <?php if (is_admin()): ?>
        <td class="col-actions"><a href="<?= APP_URL ?>/risks/edit.php?id=<?= (int)$r['id'] ?>">Edit</a></td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
