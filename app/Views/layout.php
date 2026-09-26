<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
<?php $dev = config('Boxtracker')->isDev(); ?>
<title><?= $dev ? 'DEV · ' : '' ?><?= esc($title ?? 'Boxtracker') ?></title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<link rel="manifest" href="<?= base_url($dev ? 'manifest-dev.json' : 'manifest.json') ?>">
<meta name="theme-color" content="<?= $dev ? '#3E6F87' : '#CA9E67' ?>">
<link rel="icon" href="<?= base_url('assets/icons/favicon.ico') ?>">
<link rel="apple-touch-icon" href="<?= base_url('assets/icons/apple-touch-icon.png') ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="<?= $dev ? 'Boxtracker DEV' : 'Boxtracker' ?>">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
</head>
<body<?= $dev ? ' class="env-dev"' : '' ?>>
<?php if ($dev): ?><div class="env-strip">DEV · testomgeving — niet je echte verhuizing</div><?php endif ?>
<?php if (access()->verhuizingId()): ?>
<div class="app-topbar" id="app-topbar">
  <a href="<?= base_url('/') ?>" class="app-topbar-btn" aria-label="Naar start"><?= icon('box', 20) ?></a>
  <a href="<?= base_url('menu') ?>" class="vh-switch" style="flex:1;min-width:0;justify-content:center;" aria-label="Menu"><?= esc(access()->verhuizing()['naam']) ?></a>
  <a href="<?= base_url('menu') ?>" class="app-topbar-btn" aria-label="Menu"><?= icon('menu', 20) ?></a>
  <button type="button" id="app-scan-btn" class="app-topbar-btn app-topbar-btn-primary" aria-label="Doos scannen"><?= icon('camera', 20) ?></button>
</div>
<div id="scan-overlay"></div>
<?php endif ?>
<?= $this->renderSection('content') ?>
<?php if (access()->verhuizingId()): ?>
<script src="<?= base_url('assets/js/scan.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<?php endif ?>
<?= $this->renderSection('scripts') ?>
</body>
</html>
