<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="content" style="gap:22px;padding-top:40px;">
    <?= merk_mark(48, 24) ?>
    <h1 class="page-title">Je helpt bij verhuizing <?= esc($pass['verhuizing_naam']) ?></h1>
    <p class="page-sub">
      <?= $pass['rol'] === 'helper' ? 'Je kunt dozen inpakken, vullen en verplaatsen.' : 'Scan een doos en je ziet meteen waar hij heen moet.' ?>
      Je toegang loopt <?= (int) $pass['access_days'] === 1 ? 'een dag' : (int) $pass['access_days'] . ' dagen' ?>, geen account nodig.
    </p>
    <?php if (! empty($fout)): ?><div class="msg msg-error"><?= esc($fout) ?></div><?php endif ?>
    <form method="post" action="<?= base_url('h/' . $pass['code']) ?>" class="form-stack">
      <?= csrf_field() ?>
      <div class="form-field">
        <label class="label" for="naam">Hoe heet je?</label>
        <input class="field" type="text" id="naam" name="naam" autocomplete="given-name" maxlength="60" required autofocus>
      </div>
      <button type="submit" class="btn btn-primary">Aan de slag</button>
    </form>
  </div>
</div>
<?= $this->endSection() ?>
