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
    <a href="#tags">Tags — organise things your way</a>
    <p class="help-nav-section">Reference</p>
    <a href="#statuses">What the statuses mean</a>
    <a href="#roles">Who can do what</a>
    <p class="help-nav-section">Features</p>
    <a href="#big-picture">Seeing the big picture</a>
    <a href="#quick-add">Adding things quickly</a>
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
          <p class="dl-value">A <strong>risk</strong> is something that might go wrong — it may well never happen, but it's worth tracking in case it does. Rate it red/amber/green by how serious it would be if it did. If it actually happens, log it as (or change its type to) an <strong>issue</strong> instead. Either way it stays open until it's mitigated or closed.</p>
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
            <tr><td>Something that might go wrong (but might not)</td><td><strong>Risk</strong></td></tr>
            <tr><td>Something that's happening now and needs fixing</td><td><strong>Issue</strong></td></tr>
            <tr><td>A choice that needs to be made by someone, by a date</td><td><strong>Decision</strong></td></tr>
            <tr><td>Work that's on the supplier's plate, not ours</td><td><strong>Supplier activity</strong></td></tr>
            <tr><td>A summary of where things stand right now</td><td><strong>Weekly update</strong></td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="card help-card" id="tags">
      <p class="help-card-label">Concepts</p>
      <h2>Tags — organise things your way</h2>
      <p class="dl-value" style="margin-bottom:1rem;">Tasks can carry your own labels — there's no fixed list, you build the whole thing. Any tag can have child tags nested under it, as many levels deep as you need, and any tag can carry its own custom fields for extra detail worth keeping. For example:</p>
      <div class="tag-tree-demo">
        <ul>
          <li><span class="tag-pill">System</span>
            <ul>
              <li><span class="tag-pill">ROCC</span></li>
              <li><span class="tag-pill">NECH</span></li>
              <li><span class="tag-pill">APEX</span></li>
            </ul>
          </li>
          <li><span class="tag-pill">Stakeholder</span>
            <ul>
              <li><span class="tag-pill">Tenants</span></li>
              <li><span class="tag-pill">Staff</span></li>
            </ul>
          </li>
          <li><span class="tag-pill">Section</span>
            <ul>
              <li><span class="tag-pill">Property Services</span> <span class="tag-tree-demo-field">+ custom field — Address: 2 Spiersbridge Way</span></li>
              <li><span class="tag-pill">Business Support</span></li>
            </ul>
          </li>
        </ul>
      </div>
      <p class="dl-value" style="margin:.75rem 0 1rem;">Here, <strong>System</strong>, <strong>Stakeholder</strong> and <strong>Section</strong> are top-level tags; everything under them is a child tag of that parent. A task tagged <span class="tag-pill">ROCC</span> sits under <strong>System</strong> without being tagged <strong>System</strong> itself — the hierarchy is just for organising the tags, not for inheriting them onto tasks.</p>
      <div class="detail-grid">
        <div>
          <span class="dl-label">Managing tags</span>
          <p class="dl-value">Admins go to <a href="<?= APP_URL ?>/tags/index.php">Tags</a> (linked from the Tasks page) to add tags, nest them under a parent, add custom fields to any tag, rename anything with the pencil icon, or delete it (deleting a tag also removes its children and fields).</p>
        </div>
        <div>
          <span class="dl-label">Applying tags</span>
          <p class="dl-value">Pick any number of tags, shown indented to match the hierarchy, when adding or editing a task — or via the quick-add button. They show as small pills under the task title.</p>
        </div>
        <div>
          <span class="dl-label">Filtering by tag</span>
          <p class="dl-value">The Tag dropdown on the Tasks page shows only tasks carrying that exact tag — handy for "everything NECH-related" or "everything for Tenants."</p>
        </div>
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

    <section class="card help-card" id="big-picture">
      <p class="help-card-label">Features</p>
      <h2>Seeing the big picture</h2>
      <div class="detail-grid">
        <div>
          <span class="dl-label">Status history</span>
          <p class="dl-value">The dashboard shows a strip of small squares next to the latest update — one per past weekly update, coloured red/amber/green — so you can spot a trend (steady, improving, slipping) at a glance instead of reading back through the archive.</p>
        </div>
        <div>
          <span class="dl-label">Milestone roadmap</span>
          <p class="dl-value">The <a href="<?= APP_URL ?>/milestones/index.php">Milestones</a> page opens with a timeline of every milestone, grouped by phase and coloured by status, with a dashed line marking today — the whole programme's shape in one view. Click any marker to jump to that milestone.</p>
        </div>
      </div>
    </section>

    <section class="card help-card" id="quick-add">
      <p class="help-card-label">Features</p>
      <h2>Adding things quickly</h2>
      <p class="dl-value">
        Admins get a <strong>+</strong> button in the bottom-right corner of every page. Click it, pick what you're
        adding (task, milestone, risk/issue, decision, or supplier activity), fill in just the essentials, and hit
        <strong>Add &amp; add another</strong> — the dialog stays open and clears itself so you can log several
        things back-to-back without navigating anywhere. Press <strong>Done</strong> when you're finished and the
        page refreshes to show what you added.
      </p>
    </section>

    <section class="card help-card" id="typical-week">
      <p class="help-card-label">Workflow</p>
      <h2>A typical week</h2>
      <ol style="margin:0; padding-left:1.25rem; line-height:1.9;">
        <li>Through the week: log tasks, risks, decisions, supplier activity and milestones as they come up — don't save it all for one sitting. The <strong>+</strong> button is the fastest way in.</li>
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
