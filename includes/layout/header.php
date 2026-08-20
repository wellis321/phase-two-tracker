<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Dashboard') ?> — Phase 2 Delivery Tracker</title>
<?php if (is_logged_in()): ?><meta name="csrf-token" content="<?= e(csrf_token()) ?>"><?php endif; ?>
<?php require __DIR__ . '/favicon.php'; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset_url('/assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
  <div class="header-inner">
    <a href="<?= APP_URL ?>/index.php" class="site-logo">
      <span class="logo-org">Repairs Delivery — Phase 2</span>
      <span class="logo-app">Programme Tracker</span>
    </a>
    <?php
    $workPages    = ['tasks', 'risks', 'decisions', 'supplier', 'milestones', 'tags'];
    $reportsPages = ['updates', 'agendas', 'discussion'];
    $workActive    = in_array($activePage ?? '', $workPages, true);
    $reportsActive = in_array($activePage ?? '', $reportsPages, true);
    ?>
    <nav class="main-nav">
      <a href="<?= APP_URL ?>/index.php" class="<?= ($activePage ?? '') === 'dashboard' ? 'nav--active' : '' ?>">Dashboard</a>

      <div class="nav-group">
        <button type="button" class="nav-group-toggle <?= $workActive ? 'nav--active' : '' ?>" data-nav-toggle aria-haspopup="true" aria-expanded="false">Work <span class="nav-caret">&#9662;</span></button>
        <div class="nav-group-menu">
          <a href="<?= APP_URL ?>/tasks/index.php" class="<?= ($activePage ?? '') === 'tasks' ? 'nav--active' : '' ?>">Tasks</a>
          <a href="<?= APP_URL ?>/risks/index.php" class="<?= ($activePage ?? '') === 'risks' ? 'nav--active' : '' ?>">Risks &amp; Issues</a>
          <a href="<?= APP_URL ?>/decisions/index.php" class="<?= ($activePage ?? '') === 'decisions' ? 'nav--active' : '' ?>">Decisions</a>
          <a href="<?= APP_URL ?>/supplier/index.php" class="<?= ($activePage ?? '') === 'supplier' ? 'nav--active' : '' ?>">Supplier Activity</a>
          <a href="<?= APP_URL ?>/milestones/index.php" class="<?= ($activePage ?? '') === 'milestones' ? 'nav--active' : '' ?>">Milestones</a>
          <?php if (is_admin()): ?>
          <a href="<?= APP_URL ?>/tags/index.php" class="nav-group-menu-divider <?= ($activePage ?? '') === 'tags' ? 'nav--active' : '' ?>">Manage tags</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="nav-group">
        <button type="button" class="nav-group-toggle <?= $reportsActive ? 'nav--active' : '' ?>" data-nav-toggle aria-haspopup="true" aria-expanded="false">Reports <span class="nav-caret">&#9662;</span></button>
        <div class="nav-group-menu">
          <a href="<?= APP_URL ?>/updates/index.php" class="<?= ($activePage ?? '') === 'updates' ? 'nav--active' : '' ?>">Weekly Archive</a>
          <a href="<?= APP_URL ?>/agenda/index.php" class="<?= ($activePage ?? '') === 'agendas' ? 'nav--active' : '' ?>">Agendas</a>
          <a href="<?= APP_URL ?>/discussion/index.php" class="<?= ($activePage ?? '') === 'discussion' ? 'nav--active' : '' ?>">Discussion</a>
        </div>
      </div>

      <a href="<?= APP_URL ?>/help.php" class="<?= ($activePage ?? '') === 'help' ? 'nav--active' : '' ?>">Help</a>
    </nav>
    <?php if (is_logged_in()): ?>
    <div class="nav-user">
      <span><?= e($_SESSION['pm_user'] ?? '') ?></span>
      <span class="role-pill"><?= is_admin() ? 'Admin' : 'Viewer' ?></span>
      <a href="<?= APP_URL ?>/logout.php">Sign out</a>
    </div>
    <?php endif; ?>
  </div>
</header>
<main class="page-main">
  <div class="page-container">
    <?= render_flash() ?>
