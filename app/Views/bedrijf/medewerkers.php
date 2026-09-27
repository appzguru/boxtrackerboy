<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php $rollen = ['planner' => 'Planner', 'sales' => 'Sales', 'inpakker' => 'Inpakker', 'sjouwer' => 'Sjouwer']; ?>
<div class="screen">
  <div class="content" style="gap:24px;">
    <?= $this->include('bedrijf/_nav') ?>
    <h1 class="page-title">Medewerkers</h1>
    <?php if ($message): ?><div class="msg msg-info"><?= esc($message) ?></div><?php endif ?>
    <?php if ($fout): ?><div class="msg msg-error"><?= esc($fout) ?></div><?php endif ?>

    <div class="beheer-grid">
      <div class="beheer-kolom">
        <div class="card" style="padding:0;overflow:auto;">
          <table class="beheer-tabel">
            <thead><tr><th>Naam</th><th>E-mail</th><th>Rol</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($medewerkers as $m): ?>
                <tr style="<?= $m['actief'] ? '' : 'opacity:.5;' ?>">
                  <td><?= esc($m['naam']) ?><?= (int) $m['user_id'] === $ik ? ' <span class="dim">(jij)</span>' : '' ?></td>
                  <td class="dim"><?= esc($m['email']) ?></td>
                  <td>
                    <form method="post" action="<?= base_url('bedrijf/medewerkers/' . $m['id'] . '/rol') ?>" style="margin:0;">
                      <?= csrf_field() ?>
                      <select name="rol" class="chip" onchange="this.form.submit()" aria-label="Rol van <?= esc($m['naam'], 'attr') ?>">
                        <?php foreach ($rollen as $waarde => $label): ?>
                          <option value="<?= $waarde ?>" <?= $m['rol'] === $waarde ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach ?>
                      </select>
                    </form>
                  </td>
                  <td class="num">
                    <?php if ((int) $m['user_id'] !== $ik): ?>
                      <form method="post" action="<?= base_url('bedrijf/medewerkers/' . $m['id'] . '/actief') ?>" style="margin:0;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="actief" value="<?= $m['actief'] ? '0' : '1' ?>">
                        <button type="submit" class="btn-ghost" style="<?= $m['actief'] ? 'color:var(--red-fg);' : 'color:var(--blue);' ?>"><?= $m['actief'] ? 'Deactiveren' : 'Activeren' ?></button>
                      </form>
                    <?php endif ?>
                  </td>
                </tr>
              <?php endforeach ?>
              <?php foreach ($uitnodigingen as $u): ?>
                <tr>
                  <td class="dim"><em>uitgenodigd</em></td>
                  <td class="dim"><?= esc($u['email']) ?></td>
                  <td><?= esc($rollen[$u['rol']] ?? $u['rol']) ?></td>
                  <td class="num">
                    <form method="post" action="<?= base_url('bedrijf/uitnodigingen/' . $u['id'] . '/intrekken') ?>" style="margin:0;">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn-ghost" style="color:var(--red-fg);">Intrekken</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
        <div style="font-size:13px;color:var(--text-dim);line-height:1.5;">Planner en sales zien alle verhuizingen. Inpakkers en sjouwers alleen de verhuizingen waar je ze bij de planning aan toewijst; sjouwers zien geen inhoud. Een gedeactiveerde medewerker kan nergens meer bij.</div>
      </div>

      <div class="beheer-kolom">
        <div class="card form-stack">
          <div style="font-size:19px;font-weight:700;">Uitnodigen</div>
          <?php if ($nieuweLink): ?>
            <input class="field mono" type="text" value="<?= esc($nieuweLink, 'attr') ?>" readonly style="font-size:12px;" onclick="this.select()" aria-label="Uitnodigingslink">
          <?php endif ?>
          <form method="post" action="<?= base_url('bedrijf/medewerkers/uitnodigen') ?>" class="form-stack">
            <?= csrf_field() ?>
            <input class="field" type="email" name="email" placeholder="naam@bedrijf.nl" aria-label="E-mailadres" required>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
              <?php foreach ($rollen as $waarde => $label): ?>
                <label class="chip<?= $waarde === 'inpakker' ? ' on' : '' ?>" style="cursor:pointer;"><input type="radio" name="rol" value="<?= $waarde ?>" <?= $waarde === 'inpakker' ? 'checked' : '' ?> style="margin-right:6px;"><?= $label ?></label>
              <?php endforeach ?>
            </div>
            <button type="submit" class="btn btn-secondary"><?= icon('share') ?> Uitnodiging versturen</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
