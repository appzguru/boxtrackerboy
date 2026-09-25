<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="content" style="gap:22px;padding-top:40px;">
    <div style="width:48px;height:48px;border-radius:14px;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;"><?= icon('box', 24) ?></div>
    <h1 class="page-title">Inloggen</h1>
    <?php if (str_starts_with($next, '/d/')): ?>
      <div class="msg msg-info">Deze doos hoort bij een verhuizing. Log in als je lid bent, of vraag de admin om een uitnodiging of een handjes-QR.</div>
    <?php endif ?>

    <?php if (! empty($fout)): ?><div class="msg msg-error"><?= esc($fout) ?></div><?php endif ?>

    <form method="post" action="<?= base_url('login') ?>" class="form-stack">
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= esc($next) ?>">
      <div class="form-field">
        <label class="label" for="email">E-mailadres</label>
        <input class="field" type="email" id="email" name="email" value="<?= esc($email) ?>" autocomplete="email" inputmode="email" required>
      </div>
      <div class="form-field">
        <label class="label" for="password">Wachtwoord</label>
        <input class="field" type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-primary">Inloggen</button>
    </form>

    <div style="display:flex;flex-direction:column;gap:10px;font-size:15px;">
      <a href="<?= base_url('wachtwoord-vergeten') ?>" style="color:var(--blue);font-weight:600;">Wachtwoord vergeten?</a>
      <span class="page-sub" style="font-size:15px;">Nog geen account? <a href="<?= base_url('registreren') ?>" style="color:var(--blue);font-weight:600;">Account aanmaken</a></span>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
