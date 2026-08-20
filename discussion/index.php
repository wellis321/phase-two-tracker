<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Invalid form token, please try again.');
        redirect(APP_URL . '/discussion/index.php');
    }

    $itemId = (int)($_POST['item_id'] ?? 0);

    if (isset($_POST['save_note']) && $itemId > 0) {
        $note = trim($_POST['note'] ?? '');
        $db->prepare('UPDATE pm_discussion_items SET note = ? WHERE id = ?')->execute([$note !== '' ? $note : null, $itemId]);
        flash('success', 'Note updated.');
    } elseif (isset($_POST['add_to_agenda']) && is_admin() && $itemId > 0) {
        $db->prepare("UPDATE pm_discussion_items SET status = 'added_to_agenda' WHERE id = ?")->execute([$itemId]);
        flash('success', 'Added to the next agenda draft.');
    } elseif (isset($_POST['return_to_open']) && is_admin() && $itemId > 0) {
        $db->prepare("UPDATE pm_discussion_items SET status = 'open' WHERE id = ? AND agenda_id IS NULL")->execute([$itemId]);
        flash('success', 'Moved back to open discussion.');
    }

    redirect(APP_URL . '/discussion/index.php');
}

$openItems = get_discussion_items($db, 'open');

$queuedAll   = get_discussion_items($db, 'added_to_agenda');
$queuedItems = array_values(array_filter($queuedAll, fn($i) => $i['agendaId'] === null));

$currentUserId = get_current_user_id();

$pageTitle  = 'Discussion';
$activePage = 'discussion';
require __DIR__ . '/../includes/layout/header.php';
?>

<?php if (is_admin() && $openItems): ?>
<form id="discussion-agenda-form" method="POST" action="<?= APP_URL ?>/agenda/create.php"><?= csrf_field() ?><input type="hidden" name="generate_discussion" value="1"></form>
<?php endif; ?>

<div class="page-header">
  <div>
    <h1>Discussion</h1>
    <p>Anything flagged by the team as worth talking about. Flag something from its page or the dashboard; once you've discussed it, an admin can add it to the next agenda draft, or generate a standalone agenda just for talking through what's on this list.</p>
  </div>
  <?php if (is_admin() && $openItems): ?>
  <div class="page-header-actions">
    <button type="submit" form="discussion-agenda-form" class="btn btn--primary">Generate discussion agenda</button>
  </div>
  <?php endif; ?>
</div>

<h2>Flagged for discussion</h2>
<?php if ($openItems): ?>
<?php if (is_admin()): ?>
<p class="empty-note" style="margin-top:-.5rem;">All items below are included in the discussion agenda by default — untick any you'd like to leave out.</p>
<?php endif; ?>
<?php foreach ($openItems as $item): ?>
<?= render_discussion_item_card($item, $currentUserId, false) ?>
<?php endforeach; ?>
<?php else: ?>
<p class="empty-note">Nothing's been flagged yet. Use the flag icon on a task, risk, decision, milestone, or supplier item to raise it here.</p>
<?php endif; ?>

<?php if ($queuedItems): ?>
<h2 style="margin-top:2rem;">Queued for the next agenda</h2>
<?php foreach ($queuedItems as $item): ?>
<?= render_discussion_item_card($item, $currentUserId, true) ?>
<?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
