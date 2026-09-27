<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php $nieuw = $verhuizing === null; ?>
<div class="screen">
  <div class="content" style="gap:24px;">
    <?= $this->include('bedrijf/_nav') ?>
    <div class="beheer-kop">
      <a href="<?= base_url($nieuw ? 'bedrijf' : 'bedrijf/verhuizingen/' . $verhuizing['id']) ?>" class="btn-icon" aria-label="Terug"><?= icon('back') ?></a>
      <h1 class="page-title"><?= $nieuw ? 'Nieuwe verhuizing' : esc($verhuizing['naam']) ?></h1>
    </div>
    <?php if (! empty($fout)): ?><div class="msg msg-error"><?= esc($fout) ?></div><?php endif ?>

    <form method="post" action="<?= base_url($nieuw ? 'bedrijf/verhuizingen' : 'bedrijf/verhuizingen/' . $verhuizing['id']) ?>" class="card form-stack" style="max-width:640px;">
      <?= csrf_field() ?>
      <div class="form-field">
        <label class="label" for="naam">Klant</label>
        <input class="field" type="text" id="naam" name="naam" value="<?= esc($old['naam'] ?? '') ?>" maxlength="80" placeholder="Bijv. Fam. De Vries" required autofocus>
      </div>
      <div class="form-field">
        <label class="label" for="verhuisdatum">Verhuisdatum</label>
        <input class="field" type="date" id="verhuisdatum" name="verhuisdatum" value="<?= esc($old['verhuisdatum'] ?? '') ?>">
      </div>
      <div class="form-field">
        <label class="label" for="adres_van">Van</label>
        <input class="field" type="text" id="adres_van" name="adres_van" value="<?= esc($old['adres_van'] ?? '') ?>" maxlength="160" placeholder="Straat en huisnummer, plaats">
      </div>
      <div class="form-field">
        <label class="label" for="adres_naar">Naar</label>
        <input class="field" type="text" id="adres_naar" name="adres_naar" value="<?= esc($old['adres_naar'] ?? '') ?>" maxlength="160" placeholder="Straat en huisnummer, plaats">
      </div>
      <button type="submit" class="btn btn-primary"><?= $nieuw ? 'Aanmaken' : 'Opslaan' ?></button>
    </form>
  </div>
</div>
<?= $this->endSection() ?>
