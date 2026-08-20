<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$db = db();

$agendas = $db->query(
    "SELECT a.*, u.display_name, u.username
     FROM pm_agendas a
     LEFT JOIN users u ON u.id = a.created_by_user_id
     ORDER BY a.created_at DESC"
)->fetchAll();

$pageTitle  = 'Agendas';
$activePage = 'agendas';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Agendas</h1>
    <p>Meeting agendas drafted from live data — status, decisions, risks, and milestones at the time — then published.</p>
  </div>
  <?php if (is_admin()): ?>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/agenda/create.php" class="btn btn--primary">+ Generate agenda</a>
  </div>
  <?php endif; ?>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Title</th>
        <th>Meeting date</th>
        <th>Published</th>
        <th>By</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$agendas): ?>
      <tr><td colspan="4" class="empty-note">No agendas published yet.
        <?= is_admin() ? '<a href="' . APP_URL . '/agenda/create.php">Generate the first one</a>.' : '' ?>
      </td></tr>
      <?php endif; ?>
      <?php foreach ($agendas as $a): ?>
      <tr class="table-row--clickable" data-href="<?= APP_URL ?>/agenda/view.php?id=<?= (int)$a['id'] ?>">
        <td><a href="<?= APP_URL ?>/agenda/view.php?id=<?= (int)$a['id'] ?>" class="table-entity-link"><?= e($a['title']) ?></a></td>
        <td><?= format_date($a['meeting_date']) ?></td>
        <td><?= e(date('j M Y', strtotime($a['created_at']))) ?></td>
        <td><?= e($a['display_name'] ?: $a['username'] ?: '—') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
