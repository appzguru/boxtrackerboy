<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <a href="<?= base_url('menu') ?>" class="btn-icon" aria-label="Terug"><?= icon('back') ?></a>
  </div>
  <div class="content" style="gap:20px;">
    <h1 class="page-title">Verhuizing</h1>
    <?php if (session()->getFlashdata('message')): ?><div class="msg msg-info"><?= esc(session()->getFlashdata('message')) ?></div><?php endif ?>

    <form method="post" action="<?= base_url('verhuizing') ?>" class="card form-stack">
      <?= csrf_field() ?>
      <div class="form-field">
        <label class="label" for="naam">Naam</label>
        <input class="field" type="text" id="naam" name="naam" value="<?= esc($verhuizing['naam']) ?>" maxlength="80" required>
      </div>
      <button type="submit" class="btn btn-secondary">Opslaan</button>
    </form>

    <a href="<?= base_url('export') ?>" class="card row" style="display:flex;align-items:center;gap:14px;">
      <?= icon('upload') ?>
      <div>
        <div style="font-size:16px;font-weight:600;">Exporteren als CSV</div>
        <div style="font-size:14px;color:var(--text-dim);margin-top:2px;">Alle dozen met inhoud en plek, voor je eigen back-up.</div>
      </div>
    </a>

    <form method="post" action="<?= base_url('verhuizing/verwijderen') ?>" class="card form-stack">
      <?= csrf_field() ?>
      <div style="font-size:19px;font-weight:700;color:var(--red-fg);">Verhuizing verwijderen</div>
      <div style="font-size:15px;color:var(--text-mid);line-height:1.45;">Alle dozen, foto's, leden en handjes worden definitief verwijderd. Typ <strong><?= esc($verhuizing['naam']) ?></strong> om te bevestigen.</div>
      <label class="sr-only" for="bevestig">Naam van de verhuizing</label>
      <input class="field" type="text" id="bevestig" name="bevestig" autocomplete="off">
      <button type="submit" class="btn btn-primary" style="background:var(--red-fg);">Definitief verwijderen</button>
    </form>
  </div>
</div>
<?= $this->endSection() ?>
