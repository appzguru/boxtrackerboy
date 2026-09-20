<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
  </div>
  <form id="destform" class="content" method="post" action="<?= base_url('verplaats') ?>" style="gap:22px;">
    <h1 style="font-size:32px;font-weight:700;letter-spacing:-0.035em;">Waar naartoe?</h1>
    <?php if ($recent): ?>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <?php foreach ($recent as $naam): ?>
        <button type="button" class="card row pick-dest" data-value="<?= esc($naam) ?>" style="display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:62px;padding:0 18px;">
          <span style="font-size:17px;font-weight:600;"><?= esc($naam) ?></span>
          <span class="dest-check" style="width:26px;height:26px;flex:none;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;"></span>
        </button>
      <?php endforeach ?>
    </div>
    <?php endif ?>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <label class="label" for="dest-typed">Nieuwe plek typen</label>
      <input class="field" type="text" id="dest-typed" name="bestemming" placeholder="Bijv. Opslag · rij 3" autocomplete="off">
    </div>
  </form>
  <div class="bottombar">
    <button type="submit" form="destform" class="btn btn-primary" id="go-btn" disabled>Doorgaan naar scannen</button>
  </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var typed = document.getElementById('dest-typed');
var goBtn = document.getElementById('go-btn');
function refresh() { goBtn.disabled = typed.value.trim() === ''; }
typed.addEventListener('input', function () {
  document.querySelectorAll('.pick-dest').forEach(function (b) { b.querySelector('.dest-check').innerHTML = ''; });
  refresh();
});
document.querySelectorAll('.pick-dest').forEach(function (btn) {
  btn.addEventListener('click', function () {
    typed.value = btn.dataset.value;
    document.querySelectorAll('.pick-dest .dest-check').forEach(function (c) { c.innerHTML = ''; c.style.background = ''; });
    var check = btn.querySelector('.dest-check');
    check.style.background = 'var(--blue)';
    check.innerHTML = <?= json_encode(icon('check', 16)) ?>;
    refresh();
  });
});
</script>
<?= $this->endSection() ?>
