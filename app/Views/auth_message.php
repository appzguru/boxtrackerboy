<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="content" style="gap:22px;padding-top:40px;">
    <?= merk_mark(48, 24) ?>
    <h1 class="page-title"><?= esc($kop) ?></h1>
    <p class="page-sub"><?= esc($tekst) ?></p>
  </div>
  <?php if (! empty($knop)): ?>
  <div class="bottombar"><a href="<?= base_url(ltrim($knop['url'], '/')) ?>" class="btn btn-primary"><?= esc($knop['label']) ?></a></div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
