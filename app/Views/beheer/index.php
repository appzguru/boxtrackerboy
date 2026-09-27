<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php $statusLabel = ['actief' => 'Actief', 'softblock' => 'Softblock', 'hardblock' => 'Hardblock']; ?>
<div class="screen">
  <div class="content" style="gap:24px;">
    <div class="beheer-kop">
      <a href="<?= base_url('/') ?>" class="btn-icon" aria-label="Naar de app"><?= icon('back') ?></a>
      <h1 class="page-title">Beheer</h1>
    </div>
    <?php if ($message): ?><div class="msg msg-info"><?= esc($message) ?></div><?php endif ?>

    <div class="beheer-grid breed">
      <div class="beheer-kolom">
        <div class="label">Bedrijven</div>
        <div class="card" style="padding:0;overflow:auto;">
          <?php if ($bedrijven): ?>
            <table class="beheer-tabel">
              <thead><tr><th>Bedrijf</th><th>Status</th><th class="num" title="Actieve medewerkers">Mw.</th><th class="num" title="Verhuizingen">Verh.</th><th>Sinds</th></tr></thead>
              <tbody>
                <?php foreach ($bedrijven as $b): ?>
                  <?php $url = base_url('beheer/bedrijven/' . $b['id']); ?>
                  <tr class="klik" onclick="location.href='<?= esc($url, 'js') ?>'">
                    <td>
                      <a href="<?= esc($url, 'attr') ?>" style="color:var(--blue);font-weight:600;"><?= esc($b['naam']) ?></a>
                      <div class="dim mono"><?= esc($b['subdomein']) ?>.<?= esc(config('Boxtracker')->tenantDomein) ?></div>
                    </td>
                    <td>
                      <span class="beheer-status <?= esc($b['status'], 'attr') ?>"><?= esc($statusLabel[$b['status']] ?? $b['status']) ?></span>
                      <?php if ($b['blok_memo']): ?><div class="memo" title="<?= esc($b['blok_memo'], 'attr') ?>"><?= esc($b['blok_memo']) ?></div><?php endif ?>
                    </td>
                    <td class="num"><?= (int) $b['medewerkers'] ?></td>
                    <td class="num"><?= (int) $b['verhuizingen'] ?></td>
                    <td class="dim"><?= esc(date('d-m-Y', strtotime($b['blok_sinds'] ?? $b['created_at']))) ?></td>
                  </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          <?php else: ?>
            <div style="padding:16px;color:var(--text-mid);">Nog geen bedrijven.</div>
          <?php endif ?>
        </div>
      </div>

      <div class="beheer-kolom">
        <div class="card form-stack">
          <div style="font-size:19px;font-weight:700;">Nieuw bedrijf</div>
          <?php if ($fout): ?><div class="msg msg-error"><?= esc($fout) ?></div><?php endif ?>
          <form method="post" action="<?= base_url('beheer/bedrijven') ?>" class="form-stack">
            <?= csrf_field() ?>
            <div class="form-field">
              <label class="label" for="naam">Naam</label>
              <input class="field" type="text" id="naam" name="naam" value="<?= esc($old['naam'] ?? '') ?>" maxlength="120" required>
            </div>
            <div class="form-field">
              <label class="label" for="subdomein">Subdomein</label>
              <input class="field mono" type="text" id="subdomein" name="subdomein" value="<?= esc($old['subdomein'] ?? '') ?>" maxlength="40" pattern="[a-z0-9]([a-z0-9\-]*[a-z0-9])?" placeholder="vandam" autocapitalize="off" required>
              <div style="font-size:13px;color:var(--text-dim);">Wordt <span class="mono">&lt;subdomein&gt;.<?= esc(config('Boxtracker')->tenantDomein) ?></span>.</div>
            </div>
            <button type="submit" class="btn btn-secondary"><?= icon('plus') ?> Aanmaken</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
