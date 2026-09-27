<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <a href="<?= base_url('handjes') ?>" class="btn-icon" aria-label="Terug"><?= icon('back') ?></a>
  </div>
  <div class="content" style="gap:18px;align-items:center;text-align:center;">
    <h1 class="page-title">Laat dit scannen</h1>
    <p class="page-sub" style="margin-top:-8px;"><?= $pass['rol'] === 'helper' ? 'Inpakken' : 'Sjouwen' ?> · <?= (int) $pass['access_days'] ?> <?= (int) $pass['access_days'] === 1 ? 'dag' : 'dagen' ?> toegang</p>
    <div id="qr-wrap" style="background:#fff;padding:18px;border-radius:24px;border:1px solid var(--border);width:min(78vw, 340px);aspect-ratio:1/1;box-sizing:border-box;">
      <canvas id="qr" style="width:100%;height:100%;"></canvas>
    </div>
    <div id="qr-timer" class="mono" style="font-size:18px;font-weight:600;"></div>
    <p class="page-sub" style="font-size:14px;">Meerdere mensen kunnen deze code scannen zolang hij geldig is. Daarna werkt hij niet meer, ook niet als iemand er een foto van heeft.</p>
  </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
<script>
(function () {
  new QRious({ element: document.getElementById('qr'), value: <?= json_encode($url) ?>, size: 600, level: 'M', padding: 0 });
  var left = <?= (int) $secondsLeft ?>;
  var timer = document.getElementById('qr-timer');
  function tick() {
    if (left <= 0) {
      document.getElementById('qr-wrap').style.opacity = '.15';
      timer.textContent = 'Verlopen — maak een nieuwe';
      timer.style.color = 'var(--red-fg)';
      return;
    }
    var m = Math.floor(left / 60), s = left % 60;
    timer.textContent = 'Nog ' + m + ':' + (s < 10 ? '0' : '') + s + ' geldig';
    left--;
    setTimeout(tick, 1000);
  }
  tick();
})();
</script>
<?= $this->endSection() ?>
