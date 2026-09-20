<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="content" style="padding:24px 20px 20px;gap:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:36px;height:36px;border-radius:11px;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;"><?= icon('box', 20) ?></div>
        <div style="font-size:17px;font-weight:600;letter-spacing:-0.02em;">Boxtracker</div>
      </div>
      <form method="post" action="<?= base_url('logout') ?>" style="margin:0;"><button type="submit" style="font-size:13px;font-weight:600;color:var(--text-dim);">niet <?= esc($account_naam) ?>?</button></form>
    </div>
    <h1 style="font-size:38px;line-height:1.02;letter-spacing:-0.035em;font-weight:700;">Waar staat<br>alles?</h1>
    <form class="field-search" action="<?= base_url('zoek') ?>" method="get">
      <span class="icon"><?= icon('search', 22) ?></span>
      <label class="sr-only" for="q-home">Zoeken</label>
      <input class="field" type="search" id="q-home" name="q" placeholder="Zoek: orgel, Irma, 47" autocomplete="off" style="padding-right:58px;">
      <button type="button" id="scan-btn" aria-label="Doos scannen" style="position:absolute;right:6px;top:6px;width:46px;height:46px;border-radius:12px;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;"><?= icon('camera', 22) ?></button>
    </form>

    <?php if ($hasAny): ?>
      <div style="display:grid;grid-template-columns:repeat(3, minmax(0,1fr));gap:10px;">
        <a href="<?= base_url('overzicht/lijst?kind=status&val=ingepakt') ?>" class="tile"><span class="mono" style="font-size:38px;font-weight:600;letter-spacing:-0.05em;"><?= $ingepakt ?></span><span style="font-size:14px;font-weight:500;color:var(--text-dim);">Ingepakt</span></a>
        <a href="<?= base_url('overzicht/lijst?kind=status&val=opgeslagen') ?>" class="tile"><span class="mono" style="font-size:38px;font-weight:600;letter-spacing:-0.05em;"><?= $opslag ?></span><span style="font-size:14px;font-weight:500;color:var(--text-dim);">In opslag</span></a>
        <a href="<?= base_url('overzicht/lijst?kind=status&val=uitgepakt') ?>" class="tile"><span class="mono" style="font-size:38px;font-weight:600;letter-spacing:-0.05em;"><?= $uitgepakt ?></span><span style="font-size:14px;font-weight:500;color:var(--text-dim);">Uitgepakt</span></a>
      </div>
      <div style="display:grid;grid-template-columns:repeat(2, minmax(0,1fr));gap:10px;">
        <a href="<?= base_url('overzicht') ?>" class="btn btn-secondary"><?= icon('grid') ?> Overzicht</a>
        <a href="<?= base_url('labels') ?>" class="btn btn-secondary"><?= icon('labels') ?> Labels</a>
      </div>
    <?php else: ?>
      <div class="card" style="display:flex;flex-direction:column;gap:18px;">
        <div style="font-size:22px;font-weight:700;letter-spacing:-0.03em;">Nog geen dozen</div>
        <div style="display:flex;gap:14px;align-items:flex-start;"><span class="mono" style="width:30px;height:30px;flex:none;border-radius:10px;background:var(--blue-tint);color:var(--blue-tint-fg);display:flex;align-items:center;justify-content:center;font-weight:600;">1</span><span style="font-size:16px;line-height:1.4;padding-top:3px;">Print de stickers met QR-code en nummer.</span></div>
        <div style="display:flex;gap:14px;align-items:flex-start;"><span class="mono" style="width:30px;height:30px;flex:none;border-radius:10px;background:var(--blue-tint);color:var(--blue-tint-fg);display:flex;align-items:center;justify-content:center;font-weight:600;">2</span><span style="font-size:16px;line-height:1.4;padding-top:3px;">Importeer de lijst met stickernummers als CSV.</span></div>
      </div>
    <?php endif ?>
  </div>
  <div class="bottombar">
    <?php if ($hasBoxes): ?>
      <a href="<?= base_url('verplaats') ?>" class="btn btn-primary"><?= icon('move') ?> Dozen verplaatsen</a>
    <?php else: ?>
      <a href="<?= base_url('import') ?>" class="btn btn-primary"><?= icon('upload') ?> Labels importeren</a>
    <?php endif ?>
  </div>
</div>
<div id="scan-overlay"></div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/scan.js') ?>"></script>
<script>
document.getElementById('scan-btn').addEventListener('click', function () {
  Boxtracker.scanOnce(document.getElementById('scan-overlay'), function (url) {
    window.location.href = url;
  });
});
</script>
<?= $this->endSection() ?>
