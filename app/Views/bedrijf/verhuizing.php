<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$v        = $verhuizing;
$ploegIds = array_map('intval', array_column(array_filter($leden, static fn ($l) => $l['medewerker_rol'] !== null), 'user_id'));
$bewoners = array_filter($leden, static fn ($l) => $l['medewerker_rol'] === null);
$rolNaam  = ['inpakker' => 'Inpakker', 'sjouwer' => 'Sjouwer'];
?>
<div class="screen">
  <div class="content" style="gap:24px;">
    <?= $this->include('bedrijf/_nav') ?>
    <div class="beheer-kop">
      <a href="<?= base_url('bedrijf') ?>" class="btn-icon" aria-label="Terug naar planning"><?= icon('back') ?></a>
      <h1 class="page-title"><?= esc($v['naam']) ?></h1>
      <form method="post" action="<?= base_url('verhuizingen/' . $v['id'] . '/kies') ?>" style="margin:0;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-secondary" style="width:auto;padding:0 18px;"><?= icon('box') ?> Openen in de app</button>
      </form>
    </div>
    <?php if ($message): ?><div class="msg msg-info"><?= esc($message) ?></div><?php endif ?>
    <?php if ($fout): ?><div class="msg msg-error"><?= esc($fout) ?></div><?php endif ?>

    <div class="beheer-grid">
      <div class="beheer-kolom">
        <div class="card form-stack">
          <div style="display:flex;align-items:center;gap:12px;">
            <div style="font-size:19px;font-weight:700;flex:1;">Gegevens</div>
            <?php if ($isPlanner): ?><a href="<?= base_url('bedrijf/verhuizingen/' . $v['id'] . '/bewerken') ?>" class="btn-ghost" style="color:var(--blue);"><?= icon('edit', 18) ?> Bewerken</a><?php endif ?>
          </div>
          <table class="beheer-tabel">
            <tr><td class="dim">Verhuisdatum</td><td><?= $v['verhuisdatum'] ? esc(nl_date($v['verhuisdatum'])) : '—' ?></td></tr>
            <tr><td class="dim">Van</td><td><?= esc($v['adres_van'] ?? '—') ?></td></tr>
            <tr><td class="dim">Naar</td><td><?= esc($v['adres_naar'] ?? '—') ?></td></tr>
          </table>
        </div>

        <div class="card form-stack">
          <div style="font-size:19px;font-weight:700;">Ploeg</div>
          <?php if (! $kandidaten): ?>
            <div style="color:var(--text-mid);">Nog geen inpakkers of sjouwers.<?= $isPlanner ? ' Nodig ze uit bij <a href="' . base_url('bedrijf/medewerkers') . '" style="color:var(--blue);font-weight:600;">Medewerkers</a>.' : '' ?></div>
          <?php else: ?>
            <form method="post" action="<?= base_url('bedrijf/verhuizingen/' . $v['id'] . '/ploeg') ?>" class="form-stack">
              <?= csrf_field() ?>
              <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <?php foreach ($kandidaten as $k): ?>
                  <label class="chip<?= in_array((int) $k['user_id'], $ploegIds, true) ? ' on' : '' ?>" style="cursor:pointer;">
                    <input type="checkbox" name="ploeg[]" value="<?= (int) $k['user_id'] ?>" <?= in_array((int) $k['user_id'], $ploegIds, true) ? 'checked' : '' ?> <?= $isPlanner ? '' : 'disabled' ?> style="margin-right:6px;">
                    <?= esc($k['naam']) ?> <span class="dim" style="margin-left:4px;font-weight:400;">· <?= $rolNaam[$k['rol']] ?></span>
                  </label>
                <?php endforeach ?>
              </div>
              <div style="font-size:13px;color:var(--text-dim);">Inpakkers zien en vullen de inhoud; sjouwers zien alleen nummer en bestemming.</div>
              <?php if ($isPlanner): ?><button type="submit" class="btn btn-secondary">Ploeg opslaan</button><?php endif ?>
            </form>
          <?php endif ?>
        </div>
      </div>

      <div class="beheer-kolom">
        <div class="card form-stack">
          <div style="font-size:19px;font-weight:700;">Bewoner</div>
          <?php foreach ($bewoners as $b): ?>
            <div style="display:flex;flex-direction:column;gap:2px;">
              <span style="font-weight:600;"><?= esc($b['naam']) ?> <span class="dim" style="font-weight:400;">· <?= $b['rol'] === 'admin' ? 'admin' : 'helper' ?></span></span>
              <span class="dim" style="font-size:13px;color:var(--text-dim);"><?= esc($b['email']) ?></span>
            </div>
          <?php endforeach ?>
          <?php foreach ($invites as $i): ?>
            <div class="dim" style="font-size:13px;color:var(--text-dim);">Openstaande uitnodiging (<?= $i['rol'] ?>), geldig tot <?= esc(nl_datetime($i['expires_at'])) ?></div>
          <?php endforeach ?>
          <?php if (! $bewoners && ! $invites): ?>
            <div style="color:var(--text-mid);font-size:15px;">Optioneel: nodig de bewoner uit, dan pakt die zelf in met de app.</div>
          <?php endif ?>
          <?php if ($nieuweLink): ?>
            <input class="field mono" type="text" value="<?= esc($nieuweLink, 'attr') ?>" readonly style="font-size:12px;" onclick="this.select()" aria-label="Uitnodigingslink">
          <?php endif ?>
          <?php if ($isPlanner): ?>
            <form method="post" action="<?= base_url('bedrijf/verhuizingen/' . $v['id'] . '/bewoner') ?>" class="form-stack">
              <?= csrf_field() ?>
              <input class="field" type="email" name="email" placeholder="E-mailadres (optioneel)" aria-label="E-mailadres bewoner">
              <div style="display:flex;gap:8px;">
                <label class="chip on" style="cursor:pointer;"><input type="radio" name="rol" value="admin" checked style="margin-right:6px;">Admin</label>
                <label class="chip" style="cursor:pointer;"><input type="radio" name="rol" value="helper" style="margin-right:6px;">Helper</label>
              </div>
              <button type="submit" class="btn btn-secondary"><?= icon('share') ?> Uitnodigen</button>
            </form>
          <?php endif ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
