<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';

require_login();

$pageTitle  = 'Help & Guide';
$activePage = 'help';
require __DIR__ . '/includes/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1>Help &amp; guide</h1>
    <p>What everything in this tool means, and how the pieces relate to each other.</p>
  </div>
</div>

<div class="help-layout">

  <nav class="help-nav" id="helpNav" data-help-doc-nav aria-label="Help sections">
    <p class="help-nav-section">Getting started</p>
    <a href="#overview">What this tool is for</a>
    <p class="help-nav-section">Concepts</p>
    <a href="#building-blocks">The building blocks</a>
    <a href="#tell-apart">How to tell them apart</a>
    <p class="help-nav-section">Reference</p>
    <a href="#statuses">What the statuses mean</a>
    <a href="#roles">Who can do what</a>
    <p class="help-nav-section">Workflow</p>
    <a href="#typical-week">A typical week</a>
  </nav>

  <div class="help-main">

    <section class="card help-card" id="overview">
      <p class="help-card-label">Getting started</p>
      <h2>What this tool is for</h2>
      <p>
        A single place to see how Phase 2 of the repairs system delivery (moving from Servitor to ROCC) is
        actually going — refreshed on whatever cadence the team settles on, weekly by default. Instead of
        chasing updates across email and meetings, anyone on the team can check the dashboard and catch up
        in a couple of minutes.
      </p>
    </section>

    <section class="card help-card" id="building-blocks">
      <p class="help-card-label">Concepts</p>
      <h2>The building blocks</h2>
      <p class="hint" style="margin:0 0 1rem;">Six kinds of record, each doing a different job.</p>

      <div class="detail-grid" style="grid-template-columns: 1fr; gap: 1.1rem;">
        <div>
          <span class="dl-label">Milestone</span>
          <p class="dl-value">A fixed checkpoint the programme needs to hit by a date — "Design Authority sign-off," "Pilot go-live." Milestones mark real progress; they don't move often, and slipping one is usually a big deal.</p>
        </div>
        <div>
          <span class="dl-label">Task</span>
          <p class="dl-value">A piece of work someone needs to do to get toward a milestone — usually short-lived, assigned to one person, with a status and (optionally) a due date. Most of the day-to-day activity lives here.</p>
        </div>
        <div>
          <span class="dl-label">Risk &amp; issue</span>
          <p class="dl-value">A <strong>risk</strong> is something that could go wrong but hasn't yet — rated red/amber/green by how serious it is. Once it actually happens, log it as (or change its type to) an <strong>issue</strong> instead. Either way it stays open until it's mitigated or closed.</p>
        </div>
        <div>
          <span class="dl-label">Decision required</span>
          <p class="dl-value">A fork in the road — something that needs a call from a specific person by a specific date. Decisions often exist <em>because</em> of a risk or a milestone: "we can't hit the integration milestone until we decide the sequencing order."</p>
        </div>
        <div>
          <span class="dl-label">Supplier activity</span>
          <p class="dl-value">Work that's on the supplier's plate (ROCC, or any other named supplier), not the council's — kept separate from Tasks so it's always clear whose side of the fence something sits on.</p>
        </div>
        <div>
          <span class="dl-label">Weekly update</span>
          <p class="dl-value">The narrative snapshot — status, focus, progress, achievements, decisions, risks, lessons learned, and the 60&ndash;90 day lookahead, in plain English. It doesn't replace the other five — it summarises what's true across all of them at that point in time, and gets archived so you can look back later.</p>
        </div>
      </div>
    </section>

    <section class="card help-card" id="tell-apart">
      <p class="help-card-label">Concepts</p>
      <h2>How to tell them apart</h2>
      <p class="hint" style="margin:0 0 1rem;">A quick way to decide where something belongs.</p>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>If it's&hellip;</th><th>It's a&hellip;</th></tr>
          </thead>
          <tbody>
            <tr><td>A fixed date or checkpoint the programme needs to hit</td><td><strong>Milestone</strong></td></tr>
            <tr><td>A piece of work someone on the team needs to do</td><td><strong>Task</strong></td></tr>
            <tr><td>Something that could go wrong, but hasn't yet</td><td><strong>Risk</strong></td></tr>
            <tr><td>Something that has already gone wrong</td><td><strong>Issue</strong></td></tr>
            <tr><td>A choice that needs to be made by someone, by a date</td><td><strong>Decision</strong></td></tr>
            <tr><td>Work that's on the supplier's plate, not ours</td><td><strong>Supplier activity</strong></td></tr>
            <tr><td>A summary of where things stand right now</td><td><strong>Weekly update</strong></td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="card help-card" id="statuses">
      <p class="help-card-label">Reference</p>
      <h2>What the statuses mean</h2>
      <div class="detail-grid">
        <div>
          <span class="dl-label">Overall status (RAG)</span>
          <p class="dl-value">
            <?= rag_badge('green') ?> on track &nbsp;
            <?= rag_badge('amber') ?> needs watching &nbsp;
            <?= rag_badge('red') ?> needs attention now
          </p>
        </div>
        <div>
          <span class="dl-label">Task status</span>
          <p class="dl-value"><span class="pill pill--todo">To do</span> <span class="pill pill--in_progress">In progress</span> <span class="pill pill--done">Done</span></p>
        </div>
        <div>
          <span class="dl-label">Risk &amp; issue status</span>
          <p class="dl-value"><span class="pill pill--open">Open</span> <span class="pill pill--mitigated">Mitigated</span> <span class="pill pill--closed">Closed</span></p>
        </div>
        <div>
          <span class="dl-label">Decision status</span>
          <p class="dl-value"><span class="pill pill--open">Open</span> <span class="pill pill--decided">Decided</span></p>
        </div>
        <div>
          <span class="dl-label">Supplier activity status</span>
          <p class="dl-value"><span class="pill pill--planned">Planned</span> <span class="pill pill--in_progress">In progress</span> <span class="pill pill--complete">Complete</span> <span class="pill pill--blocked">Blocked</span></p>
        </div>
        <div>
          <span class="dl-label">Milestone status</span>
          <p class="dl-value"><span class="pill pill--upcoming">Upcoming</span> <span class="pill pill--at_risk">At risk</span> <span class="pill pill--complete">Complete</span></p>
        </div>
      </div>
    </section>

    <section class="card help-card" id="roles">
      <p class="help-card-label">Reference</p>
      <h2>Who can do what</h2>
      <div class="detail-grid">
        <div>
          <span class="dl-label">Admin</span>
          <p class="dl-value">Can add, edit and delete everything, and publish weekly updates.</p>
        </div>
        <div>
          <span class="dl-label">Viewer</span>
          <p class="dl-value">Read-only access to everything — the right level for the wider team to stay informed without needing training on how to use the tool. Your role shows next to your name in the header.</p>
        </div>
        <div>
          <span class="dl-label">Signing in</span>
          <p class="dl-value">Uses the same username and password as the SOR System — no separate account to manage.</p>
        </div>
      </div>
    </section>

    <section class="card help-card" id="typical-week">
      <p class="help-card-label">Workflow</p>
      <h2>A typical week</h2>
      <ol style="margin:0; padding-left:1.25rem; line-height:1.9;">
        <li>Through the week: log tasks, risks, decisions, supplier activity and milestones as they come up — don't save it all for one sitting.</li>
        <li>At your chosen cadence (weekly by default): an admin publishes a <a href="<?= APP_URL ?>/updates/create.php">weekly update</a>, using the live counts on that page as a reference.</li>
        <li>The dashboard always shows the most recent update — anyone can check it without digging through the archive.</li>
        <li>Past updates stay in the <a href="<?= APP_URL ?>/updates/index.php">weekly archive</a> so you can see how things have progressed (or not) over time.</li>
      </ol>
    </section>

  </div>
</div>

<?php
$includeHelpNavJs = true;
require __DIR__ . '/includes/layout/footer.php';
?>
