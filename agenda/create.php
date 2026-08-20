<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();
$db = db();

$users = $db->query(
    "SELECT id, username, display_name FROM users WHERE is_active = 1 ORDER BY COALESCE(display_name, username)"
)->fetchAll();

$f = [
    'title'        => 'Programme Board',
    'meeting_date' => date('Y-m-d'),
    'location'     => '',
    'content'      => generate_agenda_draft($db),
    'kind'         => 'main',
    'includeIds'   => [],
];
$attendeeRows = []; // array of ['user_id'=>?int, 'name'=>string, 'status'=>'attending'|'apologies']
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid form token, please try again.';
        goto render;
    }

    if (isset($_POST['generate_discussion'])) {
        $includeIds = array_map('intval', $_POST['include_ids'] ?? []);
        $f['title']        = 'Team Discussion — ' . date('j M Y');
        $f['meeting_date'] = date('Y-m-d');
        $f['location']     = '';
        $f['kind']         = 'discussion';
        $f['includeIds']   = $includeIds;
        $f['content']      = generate_discussion_agenda_draft($db, $includeIds);
        goto render;
    }

    $f['title']        = trim($_POST['title'] ?? '');
    $f['meeting_date'] = $_POST['meeting_date'] ?? '';
    $f['location']     = trim($_POST['location'] ?? '');
    $f['content']      = $_POST['content'] ?? '';
    $f['kind']         = ($_POST['agenda_kind'] ?? '') === 'discussion' ? 'discussion' : 'main';
    $f['includeIds']   = array_map('intval', $_POST['include_ids'] ?? []);

    $postUids     = $_POST['attendee_user_id'] ?? [];
    $postNames    = $_POST['attendee_name'] ?? [];
    $postStatuses = $_POST['attendee_status'] ?? [];
    $rowCount     = min(count($postUids), count($postNames), count($postStatuses));
    for ($i = 0; $i < $rowCount; $i++) {
        $name = trim((string)$postNames[$i]);
        if ($name === '') continue;
        $attendeeRows[] = [
            'user_id' => $postUids[$i] !== '' ? (int)$postUids[$i] : null,
            'name'    => $name,
            'status'  => $postStatuses[$i] === 'apologies' ? 'apologies' : 'attending',
        ];
    }

    if (isset($_POST['regenerate'])) {
        $f['content'] = $f['kind'] === 'discussion'
            ? generate_discussion_agenda_draft($db, $f['includeIds'])
            : generate_agenda_draft($db);
        goto render;
    }

    if ($f['title'] === '') $errors[] = 'Title is required.';
    if (trim($f['content']) === '') $errors[] = 'Agenda content is required.';

    if (!$errors) {
        $db->prepare(
            'INSERT INTO pm_agendas (title, meeting_date, location, content, created_by_user_id) VALUES (?, ?, ?, ?, ?)'
        )->execute([$f['title'], $f['meeting_date'] ?: null, $f['location'] ?: null, $f['content'], get_current_user_id()]);
        $agendaId = (int)$db->lastInsertId();
        save_agenda_attendees($db, $agendaId, $attendeeRows);
        if ($f['kind'] !== 'discussion') {
            $db->prepare("UPDATE pm_discussion_items SET agenda_id = ? WHERE status = 'added_to_agenda' AND agenda_id IS NULL")->execute([$agendaId]);
        }

        flash('success', "Agenda '{$f['title']}' published.");
        redirect(APP_URL . '/agenda/index.php');
    }
}

render:
$pageTitle  = 'Generate Agenda';
$activePage = 'agendas';
require __DIR__ . '/../includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Generate agenda</h1>
    <p><?= $f['kind'] === 'discussion'
      ? 'Drafted from the discussion topics you selected. Edit anything below before publishing.'
      : 'Drafted from current status, decisions, risks, and milestones. Edit anything below before publishing.' ?></p>
  </div>
</div>

<?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>

<div class="card">
  <form method="POST" action="" id="agenda-form">
    <?= csrf_field() ?>
    <input type="hidden" name="agenda_kind" value="<?= e($f['kind']) ?>">
    <?php foreach ($f['includeIds'] as $includeId): ?>
    <input type="hidden" name="include_ids[]" value="<?= (int)$includeId ?>">
    <?php endforeach; ?>
    <div class="form-grid">
      <div class="field">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= e($f['title']) ?>" required>
      </div>
      <div class="field">
        <label for="meeting_date">Meeting date</label>
        <input type="date" id="meeting_date" name="meeting_date" value="<?= e($f['meeting_date']) ?>">
      </div>
      <div class="field form-full">
        <label for="location">Location</label>
        <input type="text" id="location" name="location" value="<?= e($f['location']) ?>" placeholder="e.g. Council HQ, Room 4, or a video call link">
      </div>

      <div class="field form-full">
        <label>Attendees &amp; apologies</label>
        <div class="attendee-add-row">
          <select id="attendee-select">
            <option value="">Add a person&hellip;</option>
            <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>"><?= e($u['display_name'] ?: $u['username']) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" id="attendee-add-user" class="btn btn--outline btn--sm">+ Add</button>
          <span class="attendee-add-or">or</span>
          <input type="text" id="attendee-external-name" placeholder="External person, e.g. ROCC — Account Manager">
          <button type="button" id="attendee-add-external" class="btn btn--outline btn--sm">+ Add</button>
        </div>
        <div id="attendee-list" class="attendee-list">
          <?php foreach ($attendeeRows as $i => $row): ?>
          <div class="attendee-chip" data-uid="<?= $row['user_id'] !== null ? (int)$row['user_id'] : '' ?>">
            <span class="attendee-chip-name"><?= e($row['name']) ?></span>
            <div class="attendee-chip-toggle" role="group">
              <button type="button" class="attendee-toggle-btn <?= $row['status'] === 'attending' ? 'attendee-toggle-btn--active' : '' ?>" data-status="attending">Attending</button>
              <button type="button" class="attendee-toggle-btn <?= $row['status'] === 'apologies' ? 'attendee-toggle-btn--active' : '' ?>" data-status="apologies">Apologies</button>
            </div>
            <button type="button" class="attendee-chip-remove" aria-label="Remove <?= e($row['name']) ?>">&times;</button>
            <input type="hidden" name="attendee_user_id[]" value="<?= $row['user_id'] !== null ? (int)$row['user_id'] : '' ?>">
            <input type="hidden" name="attendee_name[]" value="<?= e($row['name']) ?>">
            <input type="hidden" name="attendee_status[]" value="<?= e($row['status']) ?>">
          </div>
          <?php endforeach; ?>
        </div>
        <p class="empty-note" id="attendee-empty" <?= $attendeeRows ? 'hidden' : '' ?>>No one added yet.</p>
      </div>

      <div class="field form-full">
        <label for="content">Agenda</label>
        <textarea id="content" name="content" class="agenda-textarea" rows="24"><?= e($f['content']) ?></textarea>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn--primary">Publish agenda</button>
      <button type="submit" name="regenerate" value="1" class="btn btn--outline" onclick="return confirm('Replace the agenda text below with a fresh draft from current data? Your edits to it will be lost — everything else on this form is kept.');">Regenerate from current data</button>
      <a href="<?= APP_URL ?>/agenda/index.php" class="btn btn--outline" style="margin-left:auto;">Cancel</a>
    </div>
  </form>
</div>

<script src="<?= asset_url('/assets/js/agenda.js') ?>"></script>

<?php require __DIR__ . '/../includes/layout/footer.php'; ?>
