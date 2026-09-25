<?php
$maxStatus = $perStatus ? max($perStatus) : 1;
$maxPlek   = $perPlek ? max($perPlek) : 1;
$maxDoel   = $perDoel ? max($perDoel) : 1;
?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
  </div>
  <div class="content" style="gap:24px;">
    <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;">
      <h1 style="font-size:32px;font-weight:700;letter-spacing:-0.035em;">Overzicht</h1>
      <span style="font-size:15px;font-weight:500;color:var(--text-dim);"><?= $totaal ?> dozen</span>
    </div>

    <?php if ($eigenaars): ?>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
      <a href="<?= base_url('overzicht') ?>" class="chip <?= $eigenaar === '' ? 'on' : '' ?>">Iedereen</a>
      <?php foreach ($eigenaars as $e): ?>
        <a href="<?= base_url('overzicht?eigenaar=' . urlencode($e)) ?>" class="chip <?= $eigenaar === $e ? 'on' : '' ?>"><?= esc($e) ?></a>
      <?php endforeach ?>
    </div>
    <?php endif ?>

    <a href="<?= base_url('lijsten') ?>" class="card row" style="display:flex;align-items:center;gap:12px;padding:16px 18px;">
      <?= icon('print', 20) ?>
      <span style="flex:1;font-size:15px;font-weight:600;">Papieren lijsten printen</span>
      <?= icon('back', 16) ?>
    </a>

    <div class="grid-wide" style="display:flex;flex-direction:column;gap:24px;">
      <div style="display:flex;flex-direction:column;gap:10px;">
        <div class="label">Per status</div>
        <div class="card" style="padding:0;overflow:hidden;">
          <?php $i = 0; foreach ($perStatus as $status => $n): $pill = status_pill($status); ?>
            <a href="<?= base_url('overzicht/lijst?kind=status&val=' . urlencode($status)) ?>" class="row" style="display:flex;flex-direction:column;gap:9px;padding:15px 18px;<?= $i++ ? 'border-top:1px solid var(--border);' : '' ?>">
              <span style="display:flex;justify-content:space-between;align-items:center;"><span style="font-size:16px;font-weight:600;"><?= esc($pill['label']) ?></span><span class="mono" style="font-size:22px;font-weight:600;"><?= $n ?></span></span>
              <span class="bar-track"><span class="bar-fill" style="width:<?= max(4, round($n / $maxStatus * 100)) ?>%;"></span></span>
            </a>
          <?php endforeach ?>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:10px;">
        <div class="label">Per plek</div>
        <div class="card" style="padding:0;overflow:hidden;">
          <?php $i = 0; foreach ($perPlek as $plek => $n): ?>
            <a href="<?= base_url('overzicht/lijst?kind=plek&val=' . urlencode($plek)) ?>" class="row" style="display:flex;flex-direction:column;gap:9px;padding:15px 18px;<?= $i++ ? 'border-top:1px solid var(--border);' : '' ?>">
              <span style="display:flex;justify-content:space-between;align-items:center;"><span style="font-size:16px;font-weight:600;"><?= esc($plek) ?></span><span class="mono" style="font-size:22px;font-weight:600;"><?= $n ?></span></span>
              <span class="bar-track"><span class="bar-fill" style="width:<?= max(4, round($n / $maxPlek * 100)) ?>%;"></span></span>
            </a>
          <?php endforeach ?>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:10px;">
        <div class="label">Per bestemming</div>
        <div class="card" style="padding:0;overflow:hidden;">
          <?php $i = 0; foreach ($perDoel as $doel => $n): ?>
            <a href="<?= base_url('overzicht/lijst?kind=doel&val=' . urlencode($doel)) ?>" class="row" style="display:flex;flex-direction:column;gap:9px;padding:15px 18px;<?= $i++ ? 'border-top:1px solid var(--border);' : '' ?>">
              <span style="display:flex;justify-content:space-between;align-items:center;"><span style="font-size:16px;font-weight:600;"><?= esc($doel) ?></span><span class="mono" style="font-size:22px;font-weight:600;"><?= $n ?></span></span>
              <span class="bar-track"><span class="bar-fill" style="width:<?= max(4, round($n / $maxDoel * 100)) ?>%;"></span></span>
            </a>
          <?php endforeach ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
