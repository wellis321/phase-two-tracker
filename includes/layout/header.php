<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Dashboard') ?> — Phase 2 Delivery Tracker</title>
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
    <nav class="main-nav">
      <a href="<?= APP_URL ?>/index.php" class="<?= ($activePage ?? '') === 'dashboard' ? 'nav--active' : '' ?>">Dashboard</a>
      <a href="<?= APP_URL ?>/tasks/index.php" class="<?= ($activePage ?? '') === 'tasks' ? 'nav--active' : '' ?>">Tasks</a>
      <a href="<?= APP_URL ?>/risks/index.php" class="<?= ($activePage ?? '') === 'risks' ? 'nav--active' : '' ?>">Risks &amp; Issues</a>
      <a href="<?= APP_URL ?>/decisions/index.php" class="<?= ($activePage ?? '') === 'decisions' ? 'nav--active' : '' ?>">Decisions</a>
      <a href="<?= APP_URL ?>/supplier/index.php" class="<?= ($activePage ?? '') === 'supplier' ? 'nav--active' : '' ?>">Supplier Activity</a>
      <a href="<?= APP_URL ?>/milestones/index.php" class="<?= ($activePage ?? '') === 'milestones' ? 'nav--active' : '' ?>">Milestones</a>
      <a href="<?= APP_URL ?>/updates/index.php" class="<?= ($activePage ?? '') === 'updates' ? 'nav--active' : '' ?>">Weekly Archive</a>
      <a href="<?= APP_URL ?>/agenda/index.php" class="<?= ($activePage ?? '') === 'agendas' ? 'nav--active' : '' ?>">Agendas</a>
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
