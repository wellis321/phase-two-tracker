<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/db.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

require_login();
$db = db();

$id   = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM pm_agendas WHERE id = ?');
$stmt->execute([$id]);
$agenda = $stmt->fetch();
if (!$agenda) {
    flash('error', 'Agenda not found.');
    redirect(APP_URL . '/agenda/index.php');
}
$attendees = get_agenda_attendees($db, $id);

$metaParts = [];
if ($agenda['meeting_date']) $metaParts[] = 'Meeting date ' . format_date($agenda['meeting_date']);
if ($agenda['location']) $metaParts[] = e($agenda['location']);
$metaLine = implode(' &middot; ', $metaParts);

$attendeesHtml = '';
if ($attendees['attending'] || $attendees['apologies']) {
    $showBoth = $attendees['attending'] && $attendees['apologies'];
    $cellStyle = $showBoth ? '' : ' style="width:100%;"';
    $cells = '';
    if ($attendees['attending']) {
        $attendingNames = implode(', ', array_map('e', array_column($attendees['attending'], 'name')));
        $cells .= '<td class="attendees-cell' . ($showBoth ? ' attendees-cell--border' : '') . '"' . $cellStyle . '>
          <div class="attendees-label">Attending (' . count($attendees['attending']) . ')</div>
          <div class="attendees-names">' . $attendingNames . '</div>
        </td>';
    }
    if ($attendees['apologies']) {
        $apologiesNames = implode(', ', array_map('e', array_column($attendees['apologies'], 'name')));
        $cells .= '<td class="attendees-cell"' . $cellStyle . '>
          <div class="attendees-label">Apologies (' . count($attendees['apologies']) . ')</div>
          <div class="attendees-names">' . $apologiesNames . '</div>
        </td>';
    }
    $attendeesHtml = '
    <table class="attendees-table">
      <tr>' . $cells . '</tr>
    </table>';
}

$html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  @page { margin: 26px 34px; }
  body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #1a2420; margin: 0; }
  table.letterhead { width: 100%; border-bottom: 3px solid #006A51; margin-bottom: 18px; padding-bottom: 10px; }
  .letterhead-mark { width: 26px; height: 26px; background: #006A51; color: #fff; text-align: center; vertical-align: middle; font-weight: bold; font-size: 13pt; }
  .letterhead-org { font-weight: bold; font-size: 13pt; color: #1a2420; }
  .letterhead-app { font-size: 8.5pt; color: #4a5550; text-transform: uppercase; letter-spacing: .5px; }
  h1 { font-size: 17pt; margin: 0 0 4px; }
  .meta { font-size: 9.5pt; color: #4a5550; margin: 0 0 16px; }
  table.attendees-table { width: 100%; border: 1px solid #dde4e1; margin-bottom: 16px; border-collapse: collapse; }
  .attendees-cell { width: 50%; padding: 10px 14px; vertical-align: top; }
  .attendees-cell--border { border-right: 1px solid #dde4e1; }
  .attendees-label { font-size: 8pt; font-weight: bold; text-transform: uppercase; letter-spacing: .4px; color: #4a5550; margin-bottom: 3px; }
  .attendees-names { font-size: 9.5pt; }
  .content { white-space: pre-wrap; font-size: 10pt; line-height: 1.55; }
</style>
</head>
<body>
  <table class="letterhead">
    <tr>
      <td style="width:34px;"><div class="letterhead-mark">&#10003;</div></td>
      <td style="padding-left:10px;">
        <div class="letterhead-org">Repairs Delivery &mdash; Phase 2</div>
        <div class="letterhead-app">Programme Tracker &middot; Meeting Agenda</div>
      </td>
    </tr>
  </table>

  <h1>' . e($agenda['title']) . '</h1>
  <p class="meta">' . $metaLine . '</p>

  ' . $attendeesHtml . '

  <div class="content">' . e($agenda['content']) . '</div>
</body>
</html>';

$dompdf = new Dompdf\Dompdf(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false, 'defaultPaperSize' => 'a4']);
$dompdf->loadHtml($html);
$dompdf->render();

$filenameBase = preg_replace('/[^a-z0-9]+/i', '-', $agenda['title'] . ($agenda['meeting_date'] ? '-' . $agenda['meeting_date'] : ''));
$filename = trim($filenameBase, '-') . '.pdf';

$dompdf->stream($filename, ['Attachment' => false]);
