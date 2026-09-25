<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header no-print">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
    <div style="flex:1;"></div>
    <button type="button" class="btn-icon" aria-label="Printen" onclick="window.print()"><?= icon('print', 20) ?></button>
  </div>
  <div class="content" style="gap:16px;">
    <h1 style="font-size:28px;font-weight:700;letter-spacing:-0.035em;">Per kamer</h1>
    <p class="no-print" style="font-size:14px;line-height:1.5;color:var(--text-mid);margin-top:-8px;">Elke kamer op een eigen pagina — knip los en hang op in die kamer.</p>

    <?php if ($perKamer): ?>
      <?php foreach ($perKamer as $kamer => $nummers): ?>
        <div class="card print-list-card" style="padding:22px;display:flex;flex-direction:column;gap:14px;">
          <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;">
            <span style="font-size:22px;font-weight:700;letter-spacing:-0.03em;"><?= esc($kamer) ?></span>
            <span style="font-size:14px;color:var(--text-dim);"><?= count($nummers) ?> <?= count($nummers) === 1 ? 'doos' : 'dozen' ?></span>
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <?php foreach ($nummers as $n): ?>
              <span class="mono" style="font-size:17px;font-weight:600;background:var(--bg);border-radius:10px;padding:8px 12px;">#<?= box_nr($n) ?></span>
            <?php endforeach ?>
          </div>
        </div>
      <?php endforeach ?>
    <?php else: ?>
      <div class="card" style="font-size:15px;color:var(--text-mid);">Nog geen dozen met een bestemming.</div>
    <?php endif ?>
  </div>
</div>
<?= $this->endSection() ?>
