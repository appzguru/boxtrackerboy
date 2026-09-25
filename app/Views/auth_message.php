<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="content" style="gap:22px;padding-top:40px;">
    <div style="width:48px;height:48px;border-radius:14px;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;"><?= icon('box', 24) ?></div>
    <h1 class="page-title"><?= esc($kop) ?></h1>
    <p class="page-sub"><?= esc($tekst) ?></p>
  </div>
  <?php if (! empty($knop)): ?>
  <div class="bottombar"><a href="<?= base_url(ltrim($knop['url'], '/')) ?>" class="btn btn-primary"><?= esc($knop['label']) ?></a></div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
