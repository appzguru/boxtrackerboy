<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="content" style="padding:24px 20px 20px;gap:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
      <div style="display:flex;align-items:center;gap:10px;">
        <?= merk_mark(36, 20) ?>
        <div style="font-size:17px;font-weight:600;letter-spacing:-0.02em;">Boxtracker</div>
      </div>
      <a href="<?= base_url('menu') ?>" style="font-size:13px;font-weight:600;color:var(--text-dim);"><?= esc($account_naam) ?></a>
    </div>
    <?php $u = access()->user(); if ($u && ! $u['email_verified_at']): ?>
      <form method="post" action="<?= base_url('verifieer/opnieuw') ?>" class="msg msg-info" style="margin:0;display:flex;flex-direction:column;gap:6px;">
        <?= csrf_field() ?>
        <span>Bevestig je e-mailadres via de link in je mail (<?= esc($u['email']) ?>).</span>
        <button type="submit" style="align-self:flex-start;font-weight:600;color:inherit;text-decoration:underline;">Mail opnieuw sturen</button>
      </form>
    <?php endif ?>
    <?php if (session()->getFlashdata('message')): ?><div class="msg msg-info"><?= esc(session()->getFlashdata('message')) ?></div><?php endif ?>
    <h1 style="font-size:38px;line-height:1.02;letter-spacing:-0.035em;font-weight:700;">Waar staat<br>alles?</h1>
    <form class="field-search" action="<?= base_url('zoek') ?>" method="get">
      <span class="icon"><?= icon('search', 22) ?></span>
      <label class="sr-only" for="q-home">Zoeken</label>
      <input class="field" type="search" id="q-home" name="q" placeholder="<?= access()->can('helper') ? 'Zoek: orgel, Irma, 47' : 'Doosnummer, bijv. 47' ?>" autocomplete="off">
    </form>

    <?php if ($hasAny): ?>
      <div style="display:grid;grid-template-columns:repeat(3, minmax(0,1fr));gap:10px;">
        <a href="<?= base_url('overzicht/lijst?kind=status&val=ingepakt,geopend') ?>" class="tile"><span class="mono" style="font-size:38px;font-weight:600;letter-spacing:-0.05em;"><?= $openDoos ?></span><span style="font-size:14px;font-weight:500;color:var(--text-dim);">Open doos</span></a>
        <a href="<?= base_url('overzicht/lijst?kind=status&val=opgeslagen') ?>" class="tile"><span class="mono" style="font-size:38px;font-weight:600;letter-spacing:-0.05em;"><?= $opslag ?></span><span style="font-size:14px;font-weight:500;color:var(--text-dim);">In opslag</span></a>
        <a href="<?= base_url('overzicht/lijst?kind=status&val=uitgepakt') ?>" class="tile"><span class="mono" style="font-size:38px;font-weight:600;letter-spacing:-0.05em;"><?= $uitgepakt ?></span><span style="font-size:14px;font-weight:500;color:var(--text-dim);">Uitgepakt</span></a>
      </div>
      <div style="display:grid;grid-template-columns:repeat(2, minmax(0,1fr));gap:10px;">
        <a href="<?= base_url('overzicht') ?>" class="btn btn-secondary"><?= icon('grid') ?> Overzicht</a>
        <?php if (access()->can('helper')): ?><a href="<?= base_url('labels') ?>" class="btn btn-secondary"><?= icon('labels') ?> Labels</a><?php endif ?>
      </div>
    <?php else: ?>
      <div class="card" style="display:flex;flex-direction:column;gap:18px;">
        <div style="font-size:22px;font-weight:700;letter-spacing:-0.03em;">Nog geen dozen</div>
        <div style="display:flex;gap:14px;align-items:flex-start;"><span class="mono" style="width:30px;height:30px;flex:none;border-radius:10px;background:var(--blue-tint);color:var(--blue-tint-fg);display:flex;align-items:center;justify-content:center;font-weight:600;">1</span><span style="font-size:16px;line-height:1.4;padding-top:3px;">Maak stickers met QR-code en nummer, en print ze.</span></div>
        <div style="display:flex;gap:14px;align-items:flex-start;"><span class="mono" style="width:30px;height:30px;flex:none;border-radius:10px;background:var(--blue-tint);color:var(--blue-tint-fg);display:flex;align-items:center;justify-content:center;font-weight:600;">2</span><span style="font-size:16px;line-height:1.4;padding-top:3px;">Plak ze op de dozen en scan een doos om hem te vullen.</span></div>
      </div>
    <?php endif ?>
  </div>
  <div class="bottombar">
    <?php if ($hasBoxes): ?>
      <a href="<?= base_url('verplaats') ?>" class="btn btn-primary"><?= icon('move') ?> Dozen verplaatsen</a>
    <?php else: ?>
      <?php if (access()->can('helper')): ?><a href="<?= base_url('labels') ?>" class="btn btn-primary"><?= icon('labels') ?> Stickers maken</a><?php endif ?>
    <?php endif ?>
  </div>
</div>
<?= $this->endSection() ?>
