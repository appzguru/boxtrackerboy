<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
  </div>
  <div class="content" style="gap:20px;">
    <h1 style="font-size:32px;font-weight:700;letter-spacing:-0.035em;">Lijsten</h1>
    <p style="font-size:15px;line-height:1.5;color:var(--text-mid);">Voor als er geen telefoon bij de hand is: print deze lijsten en hang ze op.</p>

    <a href="<?= base_url('lijsten/deur') ?>" class="card row" style="display:flex;align-items:center;gap:14px;padding:18px;">
      <span style="width:40px;height:40px;flex:none;border-radius:12px;background:var(--blue-tint);color:var(--blue-tint-fg);display:flex;align-items:center;justify-content:center;"><?= icon('door', 20) ?></span>
      <span style="flex:1;min-width:0;">
        <span style="display:block;font-size:16px;font-weight:600;">Bij de voordeur</span>
        <span style="display:block;font-size:13px;color:var(--text-dim);margin-top:2px;">Nummer → bestemming, op volgorde. Wie een doos oppakt zoekt het nummer op.</span>
      </span>
    </a>

    <a href="<?= base_url('lijsten/kamers') ?>" class="card row" style="display:flex;align-items:center;gap:14px;padding:18px;">
      <span style="width:40px;height:40px;flex:none;border-radius:12px;background:var(--blue-tint);color:var(--blue-tint-fg);display:flex;align-items:center;justify-content:center;"><?= icon('list', 20) ?></span>
      <span style="flex:1;min-width:0;">
        <span style="display:block;font-size:16px;font-weight:600;">Per kamer</span>
        <span style="display:block;font-size:13px;color:var(--text-dim);margin-top:2px;">Eén lijst per bestemming, om in die kamer op te hangen.</span>
      </span>
    </a>

    <a href="<?= base_url('lijsten/controle') ?>" class="card row" style="display:flex;align-items:center;gap:14px;padding:18px;">
      <span style="width:40px;height:40px;flex:none;border-radius:12px;background:var(--blue-tint);color:var(--blue-tint-fg);display:flex;align-items:center;justify-content:center;"><?= icon('clipboard', 20) ?></span>
      <span style="flex:1;min-width:0;">
        <span style="display:block;font-size:16px;font-weight:600;">Controle</span>
        <span style="display:block;font-size:13px;color:var(--text-dim);margin-top:2px;">Staat alles wat naar een kamer moest er ook echt? Lijst met wat nog ontbreekt.</span>
      </span>
    </a>
  </div>
</div>
<?= $this->endSection() ?>
