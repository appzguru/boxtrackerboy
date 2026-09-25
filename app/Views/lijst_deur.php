<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header no-print">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
    <div style="flex:1;"></div>
    <button type="button" class="btn-icon" aria-label="Printen" onclick="window.print()"><?= icon('print', 20) ?></button>
  </div>
  <div class="content" style="gap:16px;">
    <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;">
      <h1 style="font-size:28px;font-weight:700;letter-spacing:-0.035em;">Bij de voordeur</h1>
      <span style="font-size:14px;color:var(--text-dim);"><?= count($rows) ?> dozen</span>
    </div>
    <p class="no-print" style="font-size:14px;line-height:1.5;color:var(--text-mid);margin-top:-8px;">Hang dit bij de deur. Wie een doos oppakt, kijkt het nummer op en weet meteen de kamer.</p>

    <?php if ($rows): ?>
      <div class="card print-list-card" style="padding:0;overflow:hidden;">
        <?php foreach ($rows as $i => $r): ?>
          <div class="row" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 18px;<?= $i ? 'border-top:1px solid var(--border);' : '' ?>">
            <span class="mono" style="font-size:20px;font-weight:600;letter-spacing:-0.02em;">#<?= box_nr($r['nummer']) ?></span>
            <span style="font-size:16px;font-weight:600;color:<?= $r['einddoel'] ? 'var(--blue)' : 'var(--text-dim)' ?>;text-align:right;"><?= esc($r['einddoel'] ?: 'Nog niet bepaald') ?></span>
          </div>
        <?php endforeach ?>
      </div>
    <?php else: ?>
      <div class="card" style="font-size:15px;color:var(--text-mid);">Nog geen dozen met een bestemming.</div>
    <?php endif ?>
  </div>
</div>
<?= $this->endSection() ?>
