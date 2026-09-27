<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$acties = [
    'bedrijf_aangemaakt'      => 'Bedrijf aangemaakt',
    'bedrijf_geblokkeerd'     => 'Geblokkeerd',
    'bedrijf_geactiveerd'     => 'Geactiveerd',
    'medewerker_uitgenodigd'  => 'Uitgenodigd',
    'uitnodiging_ingetrokken' => 'Uitnodiging ingetrokken',
    'meekijken_start'         => 'Meekijken gestart',
    'meekijken_stop'          => 'Meekijken gestopt',
];
$geblokkeerd = $bedrijf['status'] === 'geblokkeerd';
?>
<div class="screen">
  <div class="header">
    <a href="<?= base_url('beheer') ?>" class="btn-icon" aria-label="Terug"><?= icon('back') ?></a>
  </div>
  <div class="content" style="gap:20px;">
    <div style="display:flex;flex-direction:column;gap:6px;">
      <h1 class="page-title"><?= esc($bedrijf['naam']) ?></h1>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <span class="beheer-status <?= esc($bedrijf['status'], 'attr') ?>"><?= esc($bedrijf['status']) ?></span>
        <a href="<?= esc($url, 'attr') ?>" class="mono" style="color:var(--blue);font-size:14px;" target="_blank" rel="noopener"><?= esc(preg_replace('#^https?://#', '', rtrim($url, '/'))) ?></a>
      </div>
    </div>
    <?php if ($message): ?><div class="msg msg-info"><?= esc($message) ?></div><?php endif ?>
    <?php if ($fout): ?><div class="msg msg-error"><?= esc($fout) ?></div><?php endif ?>
    <?php if ($nieuweLink): ?>
      <div class="card form-stack">
        <div class="label">Uitnodigingslink</div>
        <input class="field mono" type="text" value="<?= esc($nieuweLink, 'attr') ?>" readonly style="font-size:13px;" onclick="this.select()">
      </div>
    <?php endif ?>

    <div style="display:flex;flex-direction:column;gap:10px;">
      <div class="label">Medewerkers</div>
      <div class="card" style="padding:0;overflow:hidden;">
        <?php foreach ($medewerkers as $m): ?>
          <div class="menu-row" style="padding-top:10px;padding-bottom:10px;<?= $m['actief'] ? '' : 'opacity:.5;' ?>">
            <span style="flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;">
              <span><?= esc($m['naam']) ?><?= $m['actief'] ? '' : ' (gedeactiveerd)' ?></span>
              <span class="sub" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc($m['email']) ?></span>
            </span>
            <span class="chip"><?= esc($m['rol']) ?></span>
          </div>
        <?php endforeach ?>
        <?php foreach ($uitnodigingen as $u): ?>
          <div class="menu-row" style="padding-top:10px;padding-bottom:10px;">
            <span style="flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;">
              <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc($u['email']) ?></span>
              <span class="sub">Uitgenodigd als <?= esc($u['rol']) ?> · geldig tot <?= esc(nl_datetime($u['expires_at'])) ?></span>
            </span>
            <form method="post" action="<?= base_url('beheer/uitnodigingen/' . $u['id'] . '/intrekken') ?>" style="margin:0;">
              <?= csrf_field() ?>
              <button type="submit" class="btn-ghost" style="color:var(--red-fg);">Intrekken</button>
            </form>
          </div>
        <?php endforeach ?>
        <?php if (! $medewerkers && ! $uitnodigingen): ?>
          <div style="padding:16px;color:var(--text-mid);">Nog niemand. Nodig de eerste planner uit.</div>
        <?php endif ?>
      </div>
    </div>

    <div class="card form-stack">
      <div style="font-size:19px;font-weight:700;">Uitnodigen</div>
      <div style="font-size:15px;color:var(--text-mid);line-height:1.45;">Stuurt een mail met een link naar <span class="mono"><?= esc(preg_replace('#^https?://#', '', rtrim($url, '/'))) ?></span>. Alleen dit e-mailadres kan hem gebruiken. Andere medewerkers nodigt de planner later zelf uit.</div>
      <form method="post" action="<?= base_url('beheer/bedrijven/' . $bedrijf['id'] . '/uitnodigen') ?>" class="form-stack">
        <?= csrf_field() ?>
        <input class="field" type="email" name="email" placeholder="planner@verhuizer.nl" aria-label="E-mailadres" required>
        <div style="display:flex;gap:8px;">
          <label class="chip on" style="cursor:pointer;"><input type="radio" name="rol" value="planner" checked style="margin-right:6px;">Planner</label>
          <label class="chip" style="cursor:pointer;"><input type="radio" name="rol" value="sales" style="margin-right:6px;">Sales</label>
        </div>
        <button type="submit" class="btn btn-secondary"><?= icon('share') ?> Uitnodiging versturen</button>
      </form>
    </div>

    <div style="display:flex;flex-direction:column;gap:10px;">
      <div class="label">Verhuizingen</div>
      <div class="card" style="padding:0;overflow:hidden;">
        <?php foreach ($verhuizingen as $v): ?>
          <div class="menu-row" style="padding-top:10px;padding-bottom:10px;">
            <span style="flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;">
              <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc($v['naam']) ?></span>
              <span class="sub"><?= $v['verhuisdatum'] ? esc(date('d-m-Y', strtotime($v['verhuisdatum']))) . ' · ' : '' ?><?= (int) $v['dozen'] ?> <?= (int) $v['dozen'] === 1 ? 'doos' : 'dozen' ?></span>
            </span>
            <form method="post" action="<?= base_url('beheer/meekijken/' . $v['id']) ?>" style="margin:0;">
              <?= csrf_field() ?>
              <button type="submit" class="btn-ghost" style="color:var(--blue);"><?= icon('search', 18) ?> Meekijken</button>
            </form>
          </div>
        <?php endforeach ?>
        <?php if (! $verhuizingen): ?>
          <div style="padding:16px;color:var(--text-mid);">Nog geen verhuizingen.</div>
        <?php endif ?>
      </div>
      <div class="sub" style="font-size:13px;color:var(--text-dim);">Meekijken is alleen-lezen en wordt gelogd.</div>
    </div>

    <div class="card form-stack">
      <div style="font-size:19px;font-weight:700;"><?= $geblokkeerd ? 'Geblokkeerd' : 'Blokkeren' ?></div>
      <div style="font-size:15px;color:var(--text-mid);line-height:1.45;"><?= $geblokkeerd
          ? 'Niemand kan nu bij dit bedrijf inloggen of stickers scannen; bezoekers zien "tijdelijk niet beschikbaar". Er is niets verwijderd.'
          : 'Zet de omgeving van dit bedrijf direct dicht (bv. bij een betalingsachterstand). Er wordt niets verwijderd; activeren zet alles terug.' ?></div>
      <form method="post" action="<?= base_url('beheer/bedrijven/' . $bedrijf['id'] . '/status') ?>" style="margin:0;"<?= $geblokkeerd ? '' : ' onsubmit="return confirm(' . esc(json_encode($bedrijf['naam'] . ' blokkeren?'), 'attr') . ');"' ?>>
        <?= csrf_field() ?>
        <input type="hidden" name="status" value="<?= $geblokkeerd ? 'actief' : 'geblokkeerd' ?>">
        <button type="submit" class="btn <?= $geblokkeerd ? 'btn-primary' : 'btn-secondary' ?>" style="<?= $geblokkeerd ? '' : 'color:var(--red-fg);' ?>"><?= $geblokkeerd ? 'Weer activeren' : 'Blokkeren' ?></button>
      </form>
    </div>

    <?php if ($log): ?>
      <div style="display:flex;flex-direction:column;gap:10px;">
        <div class="label">Logboek</div>
        <div class="card" style="padding:0;overflow:hidden;">
          <?php foreach ($log as $l): ?>
            <div class="menu-row" style="padding-top:8px;padding-bottom:8px;">
              <span style="flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;">
                <span><?= esc($acties[$l['actie']] ?? $l['actie']) ?><?= $l['verhuizing_naam'] ? ' · ' . esc($l['verhuizing_naam']) : '' ?><?= $l['detail'] ? ' · ' . esc($l['detail']) : '' ?></span>
                <span class="sub"><?= esc($l['door_naam'] ?? '?') ?> · <?= esc(nl_datetime($l['op'])) ?></span>
              </span>
            </div>
          <?php endforeach ?>
        </div>
      </div>
    <?php endif ?>
  </div>
</div>
<?= $this->endSection() ?>
