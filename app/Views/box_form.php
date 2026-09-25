<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
  </div>
  <form id="boxform" class="content" method="post" action="<?= base_url('d/' . $box['nummer'] . '-' . $box['token']) ?>" enctype="multipart/form-data" style="gap:26px;">
    <?= csrf_field() ?>
    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px;">
      <div class="mono" style="font-size:64px;font-weight:600;letter-spacing:-0.05em;line-height:.95;"><span style="color:#7C8696;font-size:38px;">#</span><?= box_nr($box['nummer']) ?></div>
      <span class="pill" style="background:<?= $isNew ? '#0F1216' : '#FFF1D0' ?>;color:<?= $isNew ? '#fff' : '#7A4B00' ?>;margin-bottom:6px;"><?= $isNew ? 'Nieuwe doos' : 'Bewerken' ?></span>
    </div>

    <div style="display:flex;flex-direction:column;gap:10px;">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <label class="label" for="f-content">Inhoud</label>
        <button type="button" id="dictate-btn" style="display:inline-flex;align-items:center;gap:8px;min-height:44px;padding:0 16px;border-radius:999px;font-size:15px;font-weight:600;border:1px solid var(--border);background:#fff;">
          <?= icon('mic') ?> <span id="dictate-label">Dicteren</span>
        </button>
      </div>
      <textarea class="field" id="f-content" name="omschrijving" rows="4" placeholder="Wat zit erin?"><?= esc($box['omschrijving'] ?? '') ?></textarea>
    </div>

    <div style="display:flex;flex-direction:column;gap:10px;">
      <div class="label">Eigenaar</div>
      <div id="owner-chips" style="display:flex;flex-wrap:wrap;gap:8px;">
        <?php foreach ($ownerChips as $c): ?>
          <button type="button" class="chip pick-chip" data-target="eigenaar" data-value="<?= esc($c) ?>"><?= esc($c) ?></button>
        <?php endforeach ?>
        <button type="button" class="chip" onclick="document.getElementById('eigenaar-other').closest('div').style.display='block';document.getElementById('eigenaar-other').focus();"><?= icon('plus', 16) ?> Andere</button>
      </div>
      <div><input class="field" type="text" id="eigenaar-other" name="eigenaar" placeholder="Naam" value="<?= esc($prefillOwner) ?>" style="height:52px;"></div>
    </div>

    <div style="display:flex;flex-direction:column;gap:10px;">
      <div class="label">Bestemming in het nieuwe huis</div>
      <div id="dest-chips" style="display:flex;flex-wrap:wrap;gap:8px;">
        <?php foreach ($destChips as $c): ?>
          <button type="button" class="chip pick-chip" data-target="einddoel" data-value="<?= esc($c) ?>"><?= esc($c) ?></button>
        <?php endforeach ?>
        <button type="button" class="chip" onclick="document.getElementById('einddoel-other').focus();"><?= icon('plus', 16) ?> Andere</button>
      </div>
      <div><input class="field" type="text" id="einddoel-other" name="einddoel" placeholder="Kamer of plek" value="<?= esc($prefillDest) ?>" style="height:52px;"></div>
    </div>

    <?php if ($isNew): ?>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <label class="label" for="f-place">Huidige plek</label>
      <input class="field" type="text" id="f-place" name="huidige_locatie" value="<?= esc($prefillPlace) ?>" style="height:54px;">
    </div>
    <?php endif ?>

    <div class="card" style="padding:0;overflow:hidden;">
      <label style="display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:64px;padding:0 18px;cursor:pointer;">
        <span style="display:flex;align-items:center;gap:12px;font-size:17px;font-weight:600;"><?= icon('fragile', 22) ?> Fragiel</span>
        <input type="checkbox" class="switch" name="fragiel" value="1" <?= ! empty($box['fragiel']) ? 'checked' : '' ?>>
      </label>
      <div class="divider"></div>
      <label style="display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:64px;padding:0 18px;cursor:pointer;">
        <span style="display:flex;align-items:center;gap:12px;font-size:17px;font-weight:600;"><?= icon('first', 22) ?> Eerst openen</span>
        <input type="checkbox" class="switch" name="eerst_openen" value="1" <?= ! empty($box['eerst_openen']) ? 'checked' : '' ?>>
      </label>
    </div>

    <?php if ($isNew): ?>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <div class="label">Foto's</div>
      <div id="photo-grid" style="display:grid;grid-template-columns:repeat(3, minmax(0,1fr));gap:10px;">
        <label style="position:relative;aspect-ratio:1/1;border-radius:16px;border:1.5px dashed #B3BBCB;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;color:var(--blue);font-size:13px;font-weight:600;cursor:pointer;">
          <?= icon('camera', 24) ?><span>Foto</span>
          <input type="file" accept="image/*" capture="environment" id="photo-input" style="position:absolute;inset:0;opacity:0;cursor:pointer;">
        </label>
      </div>
      <div style="font-size:14px;color:var(--text-dim);">Ingepakt door <strong><?= esc(access()->naam()) ?></strong></div>
    </div>
    <?php endif ?>
  </form>

  <div class="bottombar">
    <button type="submit" form="boxform" class="btn btn-primary" id="save-btn"><?= $isNew ? 'Doos opslaan' : 'Wijzigingen opslaan' ?></button>
  </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/photo.js') ?>"></script>
<script>
document.querySelectorAll('.pick-chip').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var target = document.getElementsByName(btn.dataset.target)[0];
    target.value = btn.dataset.value;
    document.querySelectorAll('.pick-chip[data-target="' + btn.dataset.target + '"]').forEach(function (b) {
      b.classList.toggle('on', b === btn);
    });
  });
});

(function () {
  if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
    var btn = document.getElementById('dictate-btn');
    if (btn) btn.style.display = 'none';
    return;
  }
  var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  var rec = new SR();
  rec.lang = 'nl-NL';
  rec.interimResults = false;
  var listening = false;
  var ta = document.getElementById('f-content');
  var label = document.getElementById('dictate-label');
  document.getElementById('dictate-btn').addEventListener('click', function () {
    if (listening) { rec.stop(); return; }
    rec.start();
  });
  rec.addEventListener('start', function () { listening = true; label.textContent = 'Luistert…'; });
  rec.addEventListener('end', function () { listening = false; label.textContent = 'Dicteren'; });
  rec.addEventListener('result', function (e) {
    var text = e.results[0][0].transcript;
    ta.value = (ta.value ? ta.value + ' ' : '') + text;
  });
})();

<?php if ($isNew): ?>
var photoInput = document.getElementById('photo-input');
var photoGrid = document.getElementById('photo-grid');
if (photoInput) {
  photoInput.addEventListener('change', function () {
    var file = photoInput.files[0];
    if (!file) return;
    Boxtracker.uploadPhoto(file, '<?= base_url('d/' . $box['nummer'] . '-' . $box['token'] . '/photo') ?>', photoGrid);
    photoInput.value = '';
  });
}
<?php endif ?>

document.getElementById('boxform').addEventListener('submit', function () {
  document.getElementById('save-btn').disabled = true;
  document.getElementById('save-btn').textContent = 'Bezig met opslaan…';
});
</script>
<?= $this->endSection() ?>
