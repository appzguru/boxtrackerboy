<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <a href="<?= base_url('/') ?>" class="btn-icon" aria-label="Terug"><?= icon('back') ?></a>
  </div>
  <div class="content" style="gap:20px;">
    <h1 class="page-title">Beheer</h1>
    <?php if ($message): ?><div class="msg msg-info"><?= esc($message) ?></div><?php endif ?>

    <div class="card" style="padding:0;overflow:hidden;">
      <?php if ($bedrijven): ?>
        <table class="beheer-tabel">
          <thead><tr><th>Bedrijf</th><th>Status</th><th class="num">Mw.</th><th class="num">Verh.</th></tr></thead>
          <tbody>
            <?php foreach ($bedrijven as $b): ?>
              <tr>
                <td>
                  <a href="<?= base_url('beheer/bedrijven/' . $b['id']) ?>" style="color:var(--blue);font-weight:600;"><?= esc($b['naam']) ?></a>
                  <div class="sub mono" style="font-size:13px;color:var(--text-dim);"><?= esc($b['subdomein']) ?></div>
                </td>
                <td><span class="beheer-status <?= esc($b['status'], 'attr') ?>"><?= esc($b['status']) ?></span></td>
                <td class="num"><?= (int) $b['medewerkers'] ?></td>
                <td class="num"><?= (int) $b['verhuizingen'] ?></td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      <?php else: ?>
        <div style="padding:16px;color:var(--text-mid);">Nog geen bedrijven.</div>
      <?php endif ?>
    </div>

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
          <div class="sub" style="font-size:13px;color:var(--text-dim);">Wordt <span class="mono">&lt;subdomein&gt;.<?= esc(config('Boxtracker')->tenantDomein) ?></span>. Werkt meteen (wildcard).</div>
        </div>
        <button type="submit" class="btn btn-secondary"><?= icon('plus') ?> Aanmaken</button>
      </form>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
