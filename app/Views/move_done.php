<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="content" style="padding:56px 24px 24px;gap:18px;">
    <div class="pop" style="width:56px;height:56px;border-radius:18px;background:var(--green-bg);color:var(--green-fg);display:flex;align-items:center;justify-content:center;"><?= icon('check', 30) ?></div>
    <div class="mono" style="font-size:88px;font-weight:600;letter-spacing:-0.06em;line-height:.9;"><?= count($items) ?></div>
    <div style="display:flex;flex-direction:column;gap:6px;">
      <div style="font-size:18px;font-weight:500;color:var(--text-mid);">dozen staan nu in</div>
      <div style="font-size:30px;font-weight:700;letter-spacing:-0.035em;"><?= esc($bestemming) ?></div>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;">
      <?php foreach ($items as $it): ?>
        <span class="mono tag" style="height:36px;padding:0 12px;font-size:16px;"><?= esc($it['nummer']) ?></span>
      <?php endforeach ?>
    </div>
  </div>
  <div class="bottombar">
    <a href="<?= base_url('verplaats') ?>" class="btn btn-primary"><?= icon('move') ?> Nieuwe ronde</a>
    <a href="<?= base_url('/') ?>" class="btn-ghost" style="text-align:center;">Terug naar start</a>
  </div>
</div>
<?= $this->endSection() ?>
