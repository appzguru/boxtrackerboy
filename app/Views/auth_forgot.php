<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <a href="<?= base_url('login') ?>" class="btn-icon" aria-label="Terug"><?= icon('back') ?></a>
  </div>
  <div class="content" style="gap:22px;">
    <h1 class="page-title">Wachtwoord vergeten</h1>
    <p class="page-sub">Vul je e-mailadres in. Je krijgt een link om een nieuw wachtwoord te kiezen.</p>
    <form method="post" action="<?= base_url('wachtwoord-vergeten') ?>" class="form-stack">
      <?= csrf_field() ?>
      <div class="form-field">
        <label class="label" for="email">E-mailadres</label>
        <input class="field" type="email" id="email" name="email" autocomplete="email" inputmode="email" required>
      </div>
      <button type="submit" class="btn btn-primary">Stuur link</button>
    </form>
  </div>
</div>
<?= $this->endSection() ?>
