<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <a href="<?= base_url('menu') ?>" class="btn-icon" aria-label="Terug"><?= icon('back') ?></a>
  </div>
  <div class="content" style="gap:20px;">
    <h1 class="page-title">Mijn account</h1>
    <?php if ($message): ?><div class="msg msg-ok"><?= esc($message) ?></div><?php endif ?>
    <?php if ($fout): ?><div class="msg msg-error"><?= esc($fout) ?></div><?php endif ?>

    <form method="post" action="<?= base_url('account') ?>" class="card form-stack">
      <?= csrf_field() ?>
      <div class="form-field">
        <label class="label" for="naam">Naam</label>
        <input class="field" type="text" id="naam" name="naam" value="<?= esc($user['naam']) ?>" maxlength="60" required>
      </div>
      <div class="form-field">
        <span class="label">E-mailadres</span>
        <div style="font-size:16px;"><?= esc($user['email']) ?></div>
        <?php if (! $user['email_verified_at']): ?>
          <div style="font-size:14px;color:var(--text-dim);">Nog niet bevestigd.</div>
        <?php endif ?>
      </div>
      <button type="submit" class="btn btn-secondary">Opslaan</button>
    </form>
    <?php if (! $user['email_verified_at']): ?>
      <form method="post" action="<?= base_url('verifieer/opnieuw') ?>" style="margin:0;"><?= csrf_field() ?><button type="submit" class="btn-ghost">Stuur de bevestigingsmail opnieuw</button></form>
    <?php endif ?>

    <form method="post" action="<?= base_url('account/wachtwoord') ?>" class="card form-stack">
      <?= csrf_field() ?>
      <div style="font-size:19px;font-weight:700;">Wachtwoord wijzigen</div>
      <div class="form-field">
        <label class="label" for="huidig">Huidig wachtwoord</label>
        <input class="field" type="password" id="huidig" name="huidig" autocomplete="current-password" required>
      </div>
      <div class="form-field">
        <label class="label" for="nieuw">Nieuw wachtwoord</label>
        <input class="field" type="password" id="nieuw" name="nieuw" autocomplete="new-password" minlength="8" required>
      </div>
      <button type="submit" class="btn btn-secondary">Wijzigen</button>
    </form>

    <form method="post" action="<?= base_url('account/verwijderen') ?>" class="card form-stack" onsubmit="return confirm('Je account definitief verwijderen?');">
      <?= csrf_field() ?>
      <div style="font-size:19px;font-weight:700;color:var(--red-fg);">Account verwijderen</div>
      <div style="font-size:15px;color:var(--text-mid);line-height:1.45;">Verhuizingen waar alleen jij in zit, worden ook verwijderd, met alle dozen en foto's. Bij gedeelde verhuizingen ga je er alleen uit.</div>
      <label class="sr-only" for="wachtwoord">Wachtwoord</label>
      <input class="field" type="password" id="wachtwoord" name="wachtwoord" placeholder="Je wachtwoord" autocomplete="current-password" required>
      <button type="submit" class="btn btn-primary" style="background:var(--red-fg);">Definitief verwijderen</button>
    </form>
  </div>
</div>
<?= $this->endSection() ?>
