<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="content" style="gap:22px;padding-top:40px;">
    <div style="width:48px;height:48px;border-radius:14px;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;"><?= icon('box', 24) ?></div>
    <?php if ($invite): ?>
      <h1 class="page-title">Help mee met verhuizing <?= esc($invite['verhuizing_naam']) ?></h1>
      <p class="page-sub"><?= esc($invite['door_naam'] ?? 'Iemand') ?> nodigt je uit als <?= $invite['rol'] === 'admin' ? 'admin' : 'helper' ?>. Maak een account aan, dan doe je meteen mee.</p>
    <?php else: ?>
      <h1 class="page-title">Account aanmaken</h1>
      <p class="page-sub">Weet over een jaar nog steeds wat waar staat. Gratis.</p>
    <?php endif ?>

    <?php if (! empty($fout)): ?><div class="msg msg-error"><?= esc($fout) ?></div><?php endif ?>

    <form method="post" action="<?= base_url('registreren') ?>" class="form-stack">
      <?= csrf_field() ?>
      <?php if ($invite): ?><input type="hidden" name="uitnodiging" value="<?= esc($invite['token']) ?>"><?php endif ?>
      <div class="form-field">
        <label class="label" for="naam">Je naam</label>
        <input class="field" type="text" id="naam" name="naam" value="<?= esc($old['naam'] ?? '') ?>" autocomplete="given-name" maxlength="60" required>
      </div>
      <div class="form-field">
        <label class="label" for="email">E-mailadres</label>
        <input class="field" type="email" id="email" name="email" value="<?= esc($old['email'] ?? '') ?>" autocomplete="email" inputmode="email" required>
      </div>
      <div class="form-field">
        <label class="label" for="password">Wachtwoord</label>
        <input class="field" type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
      </div>
      <?php if (! $invite): ?>
      <div class="form-field">
        <label class="label" for="verhuizing">Naam van je verhuizing</label>
        <input class="field" type="text" id="verhuizing" name="verhuizing" value="<?= esc($old['verhuizing'] ?? '') ?>" placeholder="Bijv. Jansen of Utrecht → Zwolle" maxlength="80">
      </div>
      <?php endif ?>
      <button type="submit" class="btn btn-primary">Account aanmaken</button>
    </form>

    <p class="page-sub" style="font-size:15px;">Heb je al een account? <a href="<?= base_url('login' . ($invite ? '?next=' . urlencode('/uitnodiging/' . $invite['token']) : '')) ?>" style="color:var(--blue);font-weight:600;">Inloggen</a></p>
  </div>
</div>
<?= $this->endSection() ?>
