<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
  </div>
  <div class="content" style="gap:20px;padding-top:24px;">
    <div style="width:64px;height:64px;border-radius:20px;background:var(--red-bg);color:var(--red-fg);display:flex;align-items:center;justify-content:center;"><?= icon('warning', 32) ?></div>
    <h1 style="font-size:32px;font-weight:700;letter-spacing:-0.035em;">Dit mag je niet in deze verhuizing</h1>
    <p style="font-size:17px;line-height:1.5;color:var(--text-mid);">Je rol is hier niet genoeg voor. Vraag een admin van de verhuizing als je meer nodig hebt.</p>
  </div>
  <div class="bottombar"><a href="<?= base_url('/') ?>" class="btn btn-primary">Naar start</a></div>
</div>
<?= $this->endSection() ?>
