<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
  </div>
  <div class="content" style="gap:20px;padding-top:24px;">
    <div style="width:64px;height:64px;border-radius:20px;background:var(--red-bg);color:var(--red-fg);display:flex;align-items:center;justify-content:center;"><?= icon('warning', 32) ?></div>
    <h1 style="font-size:32px;font-weight:700;letter-spacing:-0.035em;">Deze sticker kennen we niet</h1>
    <p style="font-size:17px;line-height:1.5;color:var(--text-mid);">Hij hoort niet bij deze app, of de code is verkeerd overgetypt. Zoek de doos op nummer of inhoud.</p>
    <?php if (! empty($badCode)): ?><div class="mono" style="font-size:15px;color:var(--text-dim);">Gelezen: <?= esc($badCode) ?></div><?php endif ?>
    <form class="field-search" action="<?= base_url('zoek') ?>" method="get">
      <span class="icon"><?= icon('search', 22) ?></span>
      <label class="sr-only" for="q-bad">Zoeken</label>
      <input class="field" type="search" id="q-bad" name="q" placeholder="Zoek: orgel, Irma, 47" autocomplete="off">
    </form>
  </div>
  <div class="bottombar"><a href="<?= base_url('/') ?>" class="btn btn-primary">Naar start</a></div>
</div>
<?= $this->endSection() ?>
