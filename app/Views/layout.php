<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
<title><?= esc($title ?? 'Boxtracker') ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@500;600&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<?php if (current_account()): ?>
<div class="app-topbar" id="app-topbar">
  <a href="<?= base_url('/') ?>" class="app-topbar-btn" aria-label="Naar start"><?= icon('box', 20) ?></a>
  <div style="flex:1;"></div>
  <button type="button" id="app-scan-btn" class="app-topbar-btn app-topbar-btn-primary" aria-label="Doos scannen"><?= icon('camera', 20) ?></button>
</div>
<div id="scan-overlay"></div>
<?php endif ?>
<?= $this->renderSection('content') ?>
<?php if (current_account()): ?>
<script src="<?= base_url('assets/js/scan.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<?php endif ?>
<?= $this->renderSection('scripts') ?>
</body>
</html>
