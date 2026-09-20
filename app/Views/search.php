<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
    <form class="field-search" action="<?= base_url('zoek') ?>" method="get" style="flex:1;">
      <span class="icon"><?= icon('search', 20) ?></span>
      <label class="sr-only" for="q-search">Zoeken</label>
      <input class="field" type="search" id="q-search" name="q" value="<?= esc($q) ?>" placeholder="Zoek: orgel, Irma, 47" autocomplete="off" style="height:48px;padding-left:44px;font-size:16px;" autofocus>
    </form>
  </div>
  <div class="content" style="padding-top:8px;gap:12px;">
    <?php if ($q === ''): ?>
      <div style="font-size:15px;line-height:1.45;color:var(--text-dim);padding:8px 2px;">Zoek op inhoud, eigenaar of nummer. Het antwoord is altijd de plek.</div>
    <?php elseif ($results): ?>
      <div style="font-size:14px;font-weight:500;color:var(--text-dim);"><?= count($results) ?> gevonden</div>
      <?php foreach ($results as $r): ?>
        <a href="<?= base_url('d/' . $r['nummer'] . '-' . $r['token']) ?>" class="card row" style="display:flex;flex-direction:column;gap:10px;padding:16px 18px;">
          <span style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
            <span class="mono" style="font-size:15px;font-weight:600;background:#F0F2F6;border-radius:8px;padding:4px 10px;">#<?= box_nr($r['nummer']) ?></span>
            <span style="font-size:14px;font-weight:500;color:var(--text-dim);">Moet naar <?= esc($r['einddoel'] ?: '—') ?></span>
          </span>
          <span style="font-size:25px;font-weight:700;letter-spacing:-0.03em;"><?= esc($r['huidige_locatie'] ?: '—') ?></span>
          <span style="font-size:15px;color:var(--text-mid);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc(first_line($r['omschrijving'])) ?></span>
        </a>
      <?php endforeach ?>
    <?php else: ?>
      <div class="card" style="display:flex;flex-direction:column;gap:12px;">
        <div style="font-size:21px;font-weight:700;">Niks gevonden voor "<?= esc($q) ?>"</div>
        <div style="font-size:15px;line-height:1.5;color:var(--text-mid);">Inhoud is alleen vindbaar als die is ingevuld. Probeer een ander woord, of blader door alle dozen.</div>
        <a href="<?= base_url('overzicht/lijst') ?>" class="btn btn-secondary"><?= icon('list') ?> Alle dozen bekijken</a>
      </div>
    <?php endif ?>
  </div>
</div>
<?= $this->endSection() ?>
