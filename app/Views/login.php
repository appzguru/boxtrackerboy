<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen" style="justify-content:center;">
  <div class="content" style="flex:none;align-items:center;text-align:center;padding-top:64px;gap:28px;">
    <div style="width:48px;height:48px;border-radius:14px;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;"><?= icon('box', 24) ?></div>
    <h1 style="font-size:28px;font-weight:700;letter-spacing:-0.03em;">Wie ben je?</h1>
    <p style="font-size:15px;color:var(--text-dim);margin-top:-16px;">Typ je pincode. Dat is geen wachtwoord — het zegt alleen wie iets doet.</p>

    <div id="dots" style="display:flex;gap:16px;">
      <span class="pin-dot" data-i="0"></span>
      <span class="pin-dot" data-i="1"></span>
      <span class="pin-dot" data-i="2"></span>
      <span class="pin-dot" data-i="3"></span>
    </div>

    <div id="pinerror" style="font-size:14px;font-weight:600;color:var(--red-fg);min-height:20px;<?= $fout ? '' : 'visibility:hidden;' ?>">Onbekende pincode, probeer opnieuw</div>

    <div id="numpad" style="display:grid;grid-template-columns:repeat(3, 76px);gap:14px;">
      <?php foreach ([1,2,3,4,5,6,7,8,9] as $n): ?>
        <button type="button" class="numkey" data-n="<?= $n ?>" style="height:76px;border-radius:22px;background:var(--card);border:1px solid var(--border);font-size:26px;font-weight:600;display:flex;align-items:center;justify-content:center;">
          <?= $n ?>
        </button>
      <?php endforeach ?>
      <div></div>
      <button type="button" class="numkey" data-n="0" style="height:76px;border-radius:22px;background:var(--card);border:1px solid var(--border);font-size:26px;font-weight:600;display:flex;align-items:center;justify-content:center;">0</button>
      <button type="button" id="pinback" aria-label="Wis laatste cijfer" style="height:76px;border-radius:22px;display:flex;align-items:center;justify-content:center;color:var(--text-dim);">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 4H8l-7 8 7 8h13a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/><path d="M15 10l-4 4M11 10l4 4"/></svg>
      </button>
    </div>

    <form id="pinform" method="post" action="<?= base_url('login') ?>" style="display:none;">
      <input type="hidden" name="pincode" id="pincode">
      <input type="hidden" name="next" value="<?= esc($next) ?>">
    </form>
  </div>
</div>
<style>
.pin-dot { width: 16px; height: 16px; border-radius: 50%; background: var(--border); transition: background .1s; }
.pin-dot.filled { background: var(--blue); }
.numkey:active { transform: scale(.94); }
</style>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function () {
  var digits = '';
  var dots = document.querySelectorAll('.pin-dot');
  var form = document.getElementById('pinform');
  var field = document.getElementById('pincode');

  function render() {
    dots.forEach(function (d, i) { d.classList.toggle('filled', i < digits.length); });
  }
  function submit() {
    field.value = digits;
    form.submit();
  }
  document.querySelectorAll('.numkey').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (digits.length >= 4) return;
      digits += btn.dataset.n;
      render();
      if (digits.length === 4) setTimeout(submit, 120);
    });
  });
  document.getElementById('pinback').addEventListener('click', function () {
    digits = digits.slice(0, -1);
    render();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key >= '0' && e.key <= '9' && digits.length < 4) {
      digits += e.key;
      render();
      if (digits.length === 4) setTimeout(submit, 120);
    } else if (e.key === 'Backspace') {
      digits = digits.slice(0, -1);
      render();
    }
  });
})();
</script>
<?= $this->endSection() ?>
