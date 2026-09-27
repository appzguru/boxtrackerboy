<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$acties = [
    'bedrijf_aangemaakt'      => 'Bedrijf aangemaakt',
    'bedrijf_softblock'       => 'Softblock',
    'bedrijf_hardblock'       => 'Hardblock',
    'bedrijf_geblokkeerd'     => 'Geblokkeerd',
    'bedrijf_geactiveerd'     => 'Weer actief',
    'medewerker_uitgenodigd'  => 'Uitgenodigd',
    'uitnodiging_ingetrokken' => 'Uitnodiging ingetrokken',
    'meekijken_start'         => 'Meekijken gestart',
    'meekijken_stop'          => 'Meekijken gestopt',
];
$statusLabel = ['actief' => 'Actief', 'softblock' => 'Softblock', 'hardblock' => 'Hardblock'];
$keuzes      = [
    'actief'    => ['Actief', 'Alles werkt normaal.'],
    'softblock' => ['Softblock', 'Lopende verhuizingen gaan gewoon door, ook dozen invoeren. Nieuwe verhuizingen aanmaken kan niet — alsof de credits op zijn. Medewerkers zien een melding.'],
    'hardblock' => ['Hardblock', 'Medewerkers kunnen per direct nergens meer bij. Klanten merken niets: die kunnen gewoon doorgaan met invoeren.'],
];
$host = preg_replace('#^https?://#', '', rtrim($url, '/'));
?>
<div class="screen">
  <div class="content" style="gap:24px;">
    <div class="beheer-kop">
      <a href="<?= base_url('beheer') ?>" class="btn-icon" aria-label="Terug naar beheer"><?= icon('back') ?></a>
      <h1 class="page-title"><?= esc($bedrijf['naam']) ?></h1>
      <span class="beheer-status <?= esc($bedrijf['status'], 'attr') ?>" style="font-size:14px;padding:4px 12px;"><?= esc($statusLabel[$bedrijf['status']] ?? $bedrijf['status']) ?></span>
      <a href="<?= esc($url, 'attr') ?>" class="mono" style="color:var(--blue);font-size:14px;" target="_blank" rel="noopener"><?= esc($host) ?></a>
    </div>
    <?php if ($message): ?><div class="msg msg-info"><?= esc($message) ?></div><?php endif ?>
    <?php if ($fout): ?><div class="msg msg-error"><?= esc($fout) ?></div><?php endif ?>

    <div class="beheer-grid">
      <div class="beheer-kolom">
        <div style="display:flex;flex-direction:column;gap:10px;">
          <div class="label">Verhuizingen</div>
          <div class="card" style="padding:0;overflow:auto;">
            <?php if ($verhuizingen): ?>
              <table class="beheer-tabel">
                <thead><tr><th>Verhuizing</th><th>Verhuisdatum</th><th class="num">Dozen</th><th></th></tr></thead>
                <tbody>
                  <?php foreach ($verhuizingen as $v): ?>
                    <tr>
                      <td><?= esc($v['naam']) ?></td>
                      <td class="dim"><?= $v['verhuisdatum'] ? esc(date('d-m-Y', strtotime($v['verhuisdatum']))) : '—' ?></td>
                      <td class="num"><?= (int) $v['dozen'] ?></td>
                      <td class="num">
                        <form method="post" action="<?= base_url('beheer/meekijken/' . $v['id']) ?>" style="margin:0;">
                          <?= csrf_field() ?>
                          <button type="submit" class="btn-ghost" style="color:var(--blue);white-space:nowrap;"><?= icon('search', 16) ?> Meekijken</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach ?>
                </tbody>
              </table>
            <?php else: ?>
              <div style="padding:16px;color:var(--text-mid);">Nog geen verhuizingen.</div>
            <?php endif ?>
          </div>
          <div style="font-size:13px;color:var(--text-dim);">Meekijken is alleen-lezen en wordt gelogd.</div>
        </div>

        <div style="display:flex;flex-direction:column;gap:10px;">
          <div class="label">Medewerkers</div>
          <div class="card" style="padding:0;overflow:auto;">
            <?php if ($medewerkers || $uitnodigingen): ?>
              <table class="beheer-tabel">
                <thead><tr><th>Naam</th><th>E-mail</th><th>Rol</th><th></th></tr></thead>
                <tbody>
                  <?php foreach ($medewerkers as $m): ?>
                    <tr style="<?= $m['actief'] ? '' : 'opacity:.5;' ?>">
                      <td><?= esc($m['naam']) ?></td>
                      <td class="dim"><?= esc($m['email']) ?></td>
                      <td><?= esc($m['rol']) ?></td>
                      <td class="dim"><?= $m['actief'] ? '' : 'gedeactiveerd' ?></td>
                    </tr>
                  <?php endforeach ?>
                  <?php foreach ($uitnodigingen as $u): ?>
                    <tr>
                      <td class="dim"><em>uitgenodigd</em></td>
                      <td class="dim"><?= esc($u['email']) ?><div>geldig tot <?= esc(nl_datetime($u['expires_at'])) ?></div></td>
                      <td><?= esc($u['rol']) ?></td>
                      <td class="num">
                        <form method="post" action="<?= base_url('beheer/uitnodigingen/' . $u['id'] . '/intrekken') ?>" style="margin:0;">
                          <?= csrf_field() ?>
                          <button type="submit" class="btn-ghost" style="color:var(--red-fg);">Intrekken</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach ?>
                </tbody>
              </table>
            <?php else: ?>
              <div style="padding:16px;color:var(--text-mid);">Nog niemand. Nodig de eerste planner uit.</div>
            <?php endif ?>
          </div>
        </div>

        <?php if ($log): ?>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <div class="label">Logboek</div>
            <div class="card" style="padding:0;overflow:auto;">
              <table class="beheer-tabel">
                <tbody>
                  <?php foreach ($log as $l): ?>
                    <tr>
                      <td class="dim" style="white-space:nowrap;"><?= esc(nl_datetime($l['op'])) ?></td>
                      <td><strong><?= esc($acties[$l['actie']] ?? $l['actie']) ?></strong><?= $l['verhuizing_naam'] ? ' · ' . esc($l['verhuizing_naam']) : '' ?><?php if ($l['detail']): ?><div class="dim"><?= esc($l['detail']) ?></div><?php endif ?></td>
                      <td class="dim"><?= esc($l['door_naam'] ?? '?') ?></td>
                    </tr>
                  <?php endforeach ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif ?>
      </div>

      <div class="beheer-kolom">
        <div class="card form-stack">
          <div style="font-size:19px;font-weight:700;">Status</div>
          <?php if ($bedrijf['status'] !== 'actief'): ?>
            <div class="beheer-memo"><?= esc($bedrijf['blok_memo'] ?? '') ?><div style="font-size:12px;color:var(--text-dim);margin-top:6px;">Sinds <?= esc(nl_datetime($bedrijf['blok_sinds'] ?? $bedrijf['created_at'])) ?></div></div>
          <?php endif ?>
          <form method="post" action="<?= base_url('beheer/bedrijven/' . $bedrijf['id'] . '/status') ?>" class="form-stack" onsubmit="var s=this.status.value;return s==='actief'||confirm(<?= esc(json_encode($bedrijf['naam'] . ': '), 'attr') ?>+s+'?');">
            <?= csrf_field() ?>
            <?php foreach ($keuzes as $waarde => [$titel, $uitleg]): ?>
              <label class="beheer-keuze">
                <input type="radio" name="status" value="<?= $waarde ?>" <?= $bedrijf['status'] === $waarde ? 'checked' : '' ?>>
                <span><strong><?= $titel ?></strong><?= esc($uitleg) ?></span>
              </label>
            <?php endforeach ?>
            <div class="form-field">
              <label class="label" for="memo">Memo — waarom? (alleen voor beheer)</label>
              <textarea class="field" id="memo" name="memo" rows="3" maxlength="500" placeholder="Bijv. factuur september 3 weken over tijd, gebeld op 2-10." style="resize:vertical;min-height:84px;"><?= esc($bedrijf['blok_memo'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-secondary">Opslaan</button>
          </form>
        </div>

        <div class="card form-stack">
          <div style="font-size:19px;font-weight:700;">Uitnodigen</div>
          <div style="font-size:14px;color:var(--text-mid);line-height:1.45;">Mail met een link naar <span class="mono"><?= esc($host) ?></span>, alleen bruikbaar voor dit e-mailadres. Andere medewerkers nodigt de planner zelf uit.</div>
          <?php if ($nieuweLink): ?>
            <input class="field mono" type="text" value="<?= esc($nieuweLink, 'attr') ?>" readonly style="font-size:12px;" onclick="this.select()" aria-label="Uitnodigingslink">
          <?php endif ?>
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
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
