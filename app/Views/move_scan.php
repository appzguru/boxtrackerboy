<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="if(confirm('Batch openlaten en terug?')) history.back();"><?= icon('back') ?></button>
    <div class="header-title">Dozen scannen</div>
  </div>
  <div class="content" style="padding:0 16px 18px;gap:14px;">
    <div id="cam-wrap" style="position:relative;flex:none;height:248px;border-radius:24px;background:#E6D2B0;overflow:hidden;">
      <video id="cam-video" playsinline muted style="width:100%;height:100%;object-fit:cover;"></video>
      <canvas id="cam-canvas" style="display:none;"></canvas>
      <div style="position:absolute;left:26px;top:26px;width:30px;height:30px;border-top:3px solid #1A140E;border-left:3px solid #1A140E;border-top-left-radius:10px;"></div>
      <div style="position:absolute;right:26px;top:26px;width:30px;height:30px;border-top:3px solid #1A140E;border-right:3px solid #1A140E;border-top-right-radius:10px;"></div>
      <div style="position:absolute;left:26px;bottom:26px;width:30px;height:30px;border-bottom:3px solid #1A140E;border-left:3px solid #1A140E;border-bottom-left-radius:10px;"></div>
      <div style="position:absolute;right:26px;bottom:26px;width:30px;height:30px;border-bottom:3px solid #1A140E;border-right:3px solid #1A140E;border-bottom-right-radius:10px;"></div>
      <div class="scanline" id="scanline" style="position:absolute;left:44px;right:44px;height:3px;border-radius:2px;background:var(--blue);"></div>
      <div style="position:absolute;left:0;right:0;bottom:16px;text-align:center;font-size:14px;font-weight:500;color:#54483A;">Richt op de QR-code op de sticker</div>
      <div id="flash" class="pop" style="display:none;position:absolute;inset:0;flex-direction:column;align-items:center;justify-content:center;gap:8px;text-align:center;">
        <div id="flash-big" class="mono" style="font-size:64px;font-weight:600;letter-spacing:-0.05em;"></div>
        <div id="flash-small" style="font-size:19px;font-weight:600;"></div>
      </div>
      <div id="cam-denied" style="display:none;position:absolute;inset:0;background:#fff;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:22px;text-align:center;">
        <div style="font-size:19px;font-weight:700;">Camera staat uit</div>
        <div style="font-size:15px;line-height:1.45;color:var(--text-mid);">Zet de camera voor deze pagina op Toestaan in je browserinstellingen en probeer het opnieuw.</div>
        <button type="button" class="btn btn-secondary" id="retry-cam"><?= icon('refresh') ?> Opnieuw proberen</button>
      </div>
    </div>

    <div class="card" style="flex:none;padding:16px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
      <div style="min-width:0;flex:1;"><div class="label">Naar</div><div style="font-size:22px;font-weight:700;letter-spacing:-0.03em;margin-top:4px;"><?= esc($bestemming) ?></div></div>
      <div style="text-align:right;flex:none;"><div class="mono" id="scan-count" style="font-size:56px;font-weight:600;line-height:1;"><?= count($items) ?></div><div style="font-size:13px;font-weight:500;color:var(--text-dim);">gescand</div></div>
    </div>

    <div id="scan-list-wrap" style="flex:none;display:flex;flex-direction:column;gap:8px;<?= $items ? '' : 'display:none;' ?>">
      <div class="label">Gescand</div>
      <div class="card" id="scan-list" style="padding:0;overflow:hidden;">
        <?php foreach (array_reverse($items) as $i => $it): ?>
          <div class="scan-row" style="display:flex;align-items:center;gap:14px;min-height:58px;padding:0 16px;<?= $i ? 'border-top:1px solid var(--border);' : '' ?>">
            <span class="mono" style="font-size:22px;font-weight:600;letter-spacing:-0.03em;width:52px;flex:none;"><?= esc($it['nummer']) ?></span>
            <span style="flex:1;min-width:0;font-size:15px;color:var(--text-mid);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc($it['line']) ?></span>
            <span style="color:var(--green-fg);flex:none;"><?= icon('check') ?></span>
          </div>
        <?php endforeach ?>
      </div>
    </div>
  </div>
  <div class="bottombar">
    <form method="post" action="<?= base_url('verplaats/' . $batch . '/sluit') ?>">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-primary" id="finish-btn" <?= $items ? '' : 'disabled' ?>>Klaar, <?= count($items) ?> dozen verplaatst</button>
    </form>
  </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var scanUrl = <?= json_encode(base_url('verplaats/' . $batch . '/scan')) ?>;
var count = <?= count($items) ?>;
var known = {};
<?php foreach ($items as $it): ?>known[<?= json_encode($it['nummer']) ?>] = true;<?php endforeach ?>

var lastCode = null, lastAt = 0, busy = false;
var flash = document.getElementById('flash'), flashBig = document.getElementById('flash-big'), flashSmall = document.getElementById('flash-small');

function showFlash(big, small, ok) {
  flashBig.textContent = big;
  flashSmall.textContent = small;
  flash.style.background = ok ? '#DFF3E8' : '#FDECEA';
  flash.style.color = ok ? '#1A7F4B' : '#B42318';
  flash.style.display = 'flex';
  if (navigator.vibrate) navigator.vibrate(ok ? 60 : [40, 60, 40]);
  setTimeout(function () { flash.style.display = 'none'; }, 700);
}

function onDecode(text, code) {
  var now = Date.now();
  if (code.code === lastCode && now - lastAt < 1500) return;
  lastCode = code.code; lastAt = now;
  if (busy) return;
  busy = true;

  var fd = new FormData();
  fd.append('code', code.code);
  fd.append('csrf_token', Boxtracker.csrfCookie());
  fetch(scanUrl, { method: 'POST', body: fd }).then(function (r) { return r.json(); }).then(function (res) {
    busy = false;
    if (!res.ok) { showFlash('?', res.reason === 'andere_verhuizing' ? 'Hoort bij een andere verhuizing' : 'Onbekende code', false); return; }
    if (res.dubbel) { showFlash('#' + res.nummer, 'Al gescand', false); return; }
    count = res.count;
    document.getElementById('scan-count').textContent = count;
    var finishBtn = document.getElementById('finish-btn');
    finishBtn.disabled = false;
    finishBtn.textContent = 'Klaar, ' + count + ' dozen verplaatst';
    var wrap = document.getElementById('scan-list-wrap');
    wrap.style.display = '';
    var list = document.getElementById('scan-list');
    var row = document.createElement('div');
    row.className = 'scan-row';
    row.style.cssText = 'display:flex;align-items:center;gap:14px;min-height:58px;padding:0 16px;' + (list.children.length ? 'border-top:1px solid var(--border);' : '');
    row.innerHTML = '<span class="mono" style="font-size:22px;font-weight:600;letter-spacing:-0.03em;width:52px;flex:none;">' + res.nummer + '</span>' +
      '<span style="flex:1;min-width:0;font-size:15px;color:var(--text-mid);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>' +
      '<span style="color:var(--green-fg);flex:none;">' + <?= json_encode(icon('check')) ?> + '</span>';
    row.children[1].textContent = res.line;
    list.insertBefore(row, list.firstChild);
    showFlash('#' + res.nummer, 'Toegevoegd', true);
  }).catch(function () {
    busy = false;
    showFlash('!', 'Geen verbinding', false);
  });
}

var video = document.getElementById('cam-video'), canvas = document.getElementById('cam-canvas');
var stopScanner = null;
function startCam() {
  document.getElementById('cam-denied').style.display = 'none';
  stopScanner = Boxtracker.startScanner(video, canvas, onDecode, function (state) {
    document.getElementById('cam-denied').style.display = state === 'ok' ? 'none' : 'flex';
    document.getElementById('scanline').style.display = state === 'ok' ? 'block' : 'none';
  });
}
startCam();
document.getElementById('retry-cam').addEventListener('click', function () { if (stopScanner) stopScanner(); startCam(); });

document.querySelector('.bottombar form').addEventListener('submit', function () {
  document.getElementById('finish-btn').disabled = true;
  document.getElementById('finish-btn').textContent = 'Bezig…';
  if (stopScanner) stopScanner();
});
</script>
<?= $this->endSection() ?>
