<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="content" style="gap:22px;padding-top:40px;">
    <h1 class="page-title">Nieuw wachtwoord</h1>
    <?php if (! empty($fout)): ?><div class="msg msg-error"><?= esc($fout) ?></div><?php endif ?>
    <form method="post" action="<?= base_url('wachtwoord/' . $token) ?>" class="form-stack">
      <?= csrf_field() ?>
      <div class="form-field">
        <label class="label" for="password">Nieuw wachtwoord</label>
        <input class="field" type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
      </div>
      <button type="submit" class="btn btn-primary">Opslaan en inloggen</button>
    </form>
  </div>
</div>
<?= $this->endSection() ?>
