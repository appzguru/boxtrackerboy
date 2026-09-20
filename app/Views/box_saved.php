<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px;padding:0 32px;text-align:center;">
    <div class="pop" style="width:92px;height:92px;border-radius:50%;background:var(--blue-tint);color:var(--blue);display:flex;align-items:center;justify-content:center;"><?= icon('check', 44) ?></div>
    <div class="mono" style="font-size:56px;font-weight:600;letter-spacing:-0.05em;">#<?= esc($nummer) ?></div>
    <div style="font-size:22px;font-weight:700;">Doos opgeslagen</div>
    <div style="font-size:16px;color:var(--text-dim);"><?= esc($sub) ?></div>
  </div>
  <div class="bottombar">
    <button type="button" class="btn btn-primary" id="scan-next"><?= icon('newscan') ?> Volgende doos scannen</button>
    <a href="<?= base_url('/') ?>" class="btn-ghost" style="text-align:center;">Naar start</a>
  </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.getElementById('scan-next').addEventListener('click', function () {
  Boxtracker.scanOnce(document.getElementById('scan-overlay'), function (url) {
    window.location.href = url;
  });
});
</script>
<?= $this->endSection() ?>
