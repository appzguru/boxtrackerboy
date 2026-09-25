<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header no-print">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
    <div style="flex:1;"></div>
    <button type="button" class="btn-icon" aria-label="Printen" onclick="window.print()"><?= icon('print', 20) ?></button>
  </div>
  <div class="content" style="gap:16px;">
    <h1 style="font-size:28px;font-weight:700;letter-spacing:-0.035em;">Controle</h1>
    <p class="no-print" style="font-size:14px;line-height:1.5;color:var(--text-mid);margin-top:-8px;">Per kamer: welke dozen zijn er al, en welke moeten nog komen. Ververs deze pagina tijdens het uitladen.</p>

    <?php if ($kamers): ?>
      <?php foreach ($kamers as $kamer): $klopt = $kamer['klopt'] ?? []; $ontbreekt = $kamer['ontbreekt'] ?? []; $totaal = count($klopt) + count($ontbreekt); ?>
        <div class="card print-list-card" style="padding:22px;display:flex;flex-direction:column;gap:14px;">
          <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;">
            <span style="font-size:22px;font-weight:700;letter-spacing:-0.03em;"><?= esc($kamer['naam']) ?></span>
            <span class="pill" style="background:<?= $ontbreekt ? 'var(--amber-bg)' : 'var(--green-bg)' ?>;color:<?= $ontbreekt ? 'var(--amber-fg)' : 'var(--green-fg)' ?>;"><?= count($klopt) ?>/<?= $totaal ?> binnen</span>
          </div>
          <?php if ($ontbreekt): ?>
            <div style="display:flex;flex-direction:column;gap:8px;">
              <span class="label">Moet nog komen</span>
              <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <?php foreach ($ontbreekt as $n): ?>
                  <span class="mono" style="font-size:17px;font-weight:600;background:var(--amber-bg);color:var(--amber-fg);border-radius:10px;padding:8px 12px;">#<?= box_nr($n) ?></span>
                <?php endforeach ?>
              </div>
            </div>
          <?php else: ?>
            <div style="display:flex;align-items:center;gap:8px;color:var(--green-fg);font-size:15px;font-weight:600;"><?= icon('check', 18) ?> Alles binnen</div>
          <?php endif ?>
        </div>
      <?php endforeach ?>
    <?php else: ?>
      <div class="card" style="font-size:15px;color:var(--text-mid);">Nog geen dozen met een bestemming.</div>
    <?php endif ?>
  </div>
</div>
<?= $this->endSection() ?>
