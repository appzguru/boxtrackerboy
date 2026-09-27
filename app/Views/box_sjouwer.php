<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php /* Doos voor een sjouwer (handoff.md §8): waar moet hij heen — geen inhoud, foto's of eigenaar. */ ?>
<div class="screen">
  <div class="header">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
  </div>
  <div class="content">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px;">
      <div class="mono" style="font-size:64px;font-weight:600;letter-spacing:-0.05em;line-height:.95;"><span style="color:#9A8A74;font-size:38px;">#</span><?= box_nr($box['nummer']) ?></div>
      <span class="pill" style="background:<?= $pill['bg'] ?>;color:<?= $pill['fg'] ?>;margin-bottom:6px;"><?= esc($pill['label']) ?></span>
    </div>

    <?php if ($box['status'] === 'leeg'): ?>
      <div class="card" style="font-size:18px;line-height:1.5;">Deze doos is nog niet ingepakt. Vraag een inpakker om hem eerst in te vullen.</div>
    <?php else: ?>
      <?php if ($box['fragiel'] || $box['eerst_openen']): ?>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php if ($box['fragiel']): ?><span class="tag" style="font-size:17px;padding:10px 14px;"><?= icon('fragile', 20) ?> Fragiel</span><?php endif ?>
        <?php if ($box['eerst_openen']): ?><span class="tag" style="font-size:17px;padding:10px 14px;"><?= icon('first', 20) ?> Eerst openen</span><?php endif ?>
      </div>
      <?php endif ?>

      <div class="card" style="display:flex;flex-direction:column;gap:14px;">
        <div class="label">Moet naar</div>
        <div style="font-size:34px;font-weight:700;letter-spacing:-0.03em;line-height:1.1;"><?= esc($box['einddoel'] ?: '—') ?></div>
        <div class="divider"></div>
        <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px;"><span class="label">Staat nu</span><span style="font-size:18px;font-weight:600;"><?= esc($box['huidige_locatie'] ?: '—') ?></span></div>
      </div>
    <?php endif ?>
  </div>

  <?php if ($box['status'] !== 'leeg' && $box['status'] !== 'uitgepakt'): ?>
  <div class="bottombar">
    <a href="#move-sheet" onclick="document.getElementById('move-sheet').style.display='block';document.getElementById('move-backdrop').style.display='block';return false;" class="btn btn-primary"><?= icon('move') ?> Doos verplaatsen</a>
  </div>

  <button type="button" class="sheet-backdrop" id="move-backdrop" style="display:none;" onclick="document.getElementById('move-sheet').style.display='none';this.style.display='none';"></button>
  <div class="sheet rise" id="move-sheet" style="display:none;">
    <div style="font-size:22px;font-weight:700;">Doos verplaatsen naar</div>
    <form method="post" action="<?= base_url('d/' . $box['nummer'] . '-' . $box['token'] . '/move') ?>">
      <?= csrf_field() ?>
      <label class="sr-only" for="naar_locatie">Nieuwe plek</label>
      <input class="field" type="text" id="naar_locatie" name="naar_locatie" placeholder="Bijv. Opslag · rij 3" value="<?= esc($box['einddoel'] ?? '') ?>">
      <div style="height:14px;"></div>
      <button type="submit" class="btn btn-primary">Verplaatsen</button>
    </form>
  </div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
