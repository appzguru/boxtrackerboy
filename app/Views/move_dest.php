<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
  </div>
  <form id="destform" class="content" method="post" action="<?= base_url('verplaats') ?>" style="gap:22px;">
    <?= csrf_field() ?>
    <h1 style="font-size:32px;font-weight:700;letter-spacing:-0.035em;">Waar naartoe?</h1>
    <?php if ($recent): ?>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <?php foreach ($recent as $naam): ?>
        <button type="button" class="card row pick-dest" data-value="<?= esc($naam) ?>" style="display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:62px;padding:0 18px;transition:opacity .2s,background-color .2s;">
          <span style="font-size:17px;font-weight:600;"><?= esc($naam) ?></span>
          <span class="dest-check" style="width:26px;height:26px;flex:none;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;"></span>
        </button>
      <?php endforeach ?>
    </div>
    <div style="font-size:13px;color:var(--text-dim);margin-top:-4px;">Typo? Houd een plek ingedrukt om 'm te verwijderen.</div>
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
var hideUrl = <?= json_encode(base_url('verplaats/plek/verwijderen')) ?>;
function refresh() { goBtn.disabled = typed.value.trim() === ''; }
typed.addEventListener('input', function () {
  document.querySelectorAll('.pick-dest').forEach(function (b) { b.querySelector('.dest-check').innerHTML = ''; });
  refresh();
});

function hideDestination(btn) {
  btn.disabled = true;
  btn.style.opacity = '0.35';
  var fd = new FormData();
  fd.append('naam', btn.dataset.value);
  fd.append('csrf_token', Boxtracker.csrfCookie());
  fetch(hideUrl, { method: 'POST', body: fd }).then(function () {
    if (typed.value === btn.dataset.value) { typed.value = ''; refresh(); }
    btn.remove();
  }).catch(function () {
    btn.disabled = false;
    btn.style.opacity = '';
  });
}

document.querySelectorAll('.pick-dest').forEach(function (btn) {
  var pressTimer = null;
  var longPressed = false;

  function startPress() {
    longPressed = false;
    btn.style.backgroundColor = '#FDECEA';
    pressTimer = setTimeout(function () {
      longPressed = true;
      if (navigator.vibrate) navigator.vibrate(30);
      hideDestination(btn);
    }, 550);
  }
  function cancelPress() {
    clearTimeout(pressTimer);
    btn.style.backgroundColor = '';
  }

  btn.addEventListener('pointerdown', startPress);
  btn.addEventListener('pointerup', cancelPress);
  btn.addEventListener('pointerleave', cancelPress);
  btn.addEventListener('pointercancel', cancelPress);

  btn.addEventListener('click', function (e) {
    if (longPressed) {
      e.preventDefault();
      e.stopPropagation();
      longPressed = false;
      return;
    }
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
