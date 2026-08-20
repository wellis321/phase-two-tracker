<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Phase 2 Delivery Tracker — Repairs System Programme</title>
<?php require __DIR__ . '/favicon.php'; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset_url('/assets/css/style.css') ?>">
</head>
<body>

<?php require __DIR__ . '/notice-banner.php'; ?>

<header class="site-header">
  <div class="header-inner">
    <a href="<?= APP_URL ?>/" class="site-logo">
      <span class="logo-org">Repairs Delivery — Phase 2</span>
      <span class="logo-app">Programme Tracker</span>
    </a>
    <div class="nav-user" style="margin-left:auto;">
      <a href="<?= APP_URL ?>/login.php" class="btn btn--outline btn--sm" style="color:#fff;border-color:rgba(255,255,255,.5);">Sign in</a>
    </div>
  </div>
</header>

<section class="landing-hero">
  <div class="landing-hero-inner">
    <p class="landing-eyebrow">ROCC Repairs System &middot; Phase 2</p>
    <h1 class="landing-h1">One place to see how Phase 2 is actually going</h1>
    <p class="landing-lead">
      A weekly-refreshable view of the repairs housing system delivery — status, focus, progress,
      tasks, risks, decisions, supplier activity and milestones — for the team moving from discovery
      into build with ROCC.
    </p>
    <div class="landing-hero-actions">
      <a href="<?= APP_URL ?>/login.php" class="btn btn--primary btn--lg">Sign in</a>
    </div>
  </div>
</section>

<section class="landing-section">
  <div class="landing-section-inner">
    <h2 class="landing-h2">What this tracks</h2>
    <p class="landing-intro">
      Refreshed on whatever cadence the team settles on — weekly by default — so anyone can catch up
      in a couple of minutes instead of chasing updates across email and meetings.
    </p>
    <div class="landing-feature-grid">
      <article class="landing-feature-card">
        <h3>Overall status</h3>
        <p>A red/amber/green read on where the programme stands right now, with a short narrative.</p>
      </article>
      <article class="landing-feature-card">
        <h3>Current focus</h3>
        <p>What the team is actually working on over the next couple of weeks.</p>
      </article>
      <article class="landing-feature-card">
        <h3>Tasks</h3>
        <p>Work items with an owner, a status, and a due date — nothing falls through the cracks.</p>
      </article>
      <article class="landing-feature-card">
        <h3>Risks &amp; issues</h3>
        <p>What could go wrong, or already has, rated by severity so the important ones stand out.</p>
      </article>
      <article class="landing-feature-card">
        <h3>Decisions required</h3>
        <p>What needs a call, from whom, and by when.</p>
      </article>
      <article class="landing-feature-card">
        <h3>Supplier activity</h3>
        <p>What's in motion with ROCC — workshops, deliverables, sign-offs.</p>
      </article>
      <article class="landing-feature-card">
        <h3>Milestones &amp; lookahead</h3>
        <p>Key dates across the build, plus what's coming in the next 60&ndash;90 days.</p>
      </article>
      <article class="landing-feature-card">
        <h3>Weekly archive</h3>
        <p>Every past update — achievements, key decisions, risks raised, lessons learned — kept and browsable.</p>
      </article>
    </div>
  </div>
</section>

<section class="landing-cta">
  <h2>Same sign-in as the SOR System</h2>
  <p>Your existing username and password work here — no separate account needed.</p>
  <a href="<?= APP_URL ?>/login.php" class="btn btn--primary btn--lg">Sign in</a>
</section>

<footer class="site-footer">
  <div class="footer-inner">
    <span class="footer-env footer-env--<?= e(APP_ENV) ?>"><?= e(strtoupper(APP_ENV)) ?></span>
    <div class="footer-tools">
      <?php if (SOR_SYSTEM_URL !== ''): ?><a href="<?= e(SOR_SYSTEM_URL) ?>/">SOR System</a><?php endif; ?>
      <a href="<?= e(ERC_SITE_URL) ?>/">ERC Portal</a>
      <a href="<?= e(ASIS_SITE_URL) ?>/">AS-IS Mapping</a>
      <a href="<?= e(METRICS_SITE_URL) ?>/">Housing Metrics</a>
    </div>
  </div>
</footer>

</body>
</html>
