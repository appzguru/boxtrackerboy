<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <a href="<?= base_url('opname') ?>" class="btn-icon" aria-label="Terug naar opname"><?= icon('back') ?></a>
  </div>
  <div class="content" style="gap:22px;">
    <h1 class="page-title"><?= esc($item['omschrijving']) ?></h1>
    <?php if ($message): ?><div class="msg msg-info"><?= esc($message) ?></div><?php endif ?>

    <div style="display:flex;flex-direction:column;gap:10px;">
      <div class="label">Foto's</div>
      <div class="opname-grid" id="grid-item">
        <?php foreach ($fotos as $f): ?>
          <div class="opname-foto">
            <a href="<?= base_url('opname/foto/' . $f['id']) ?>" target="_blank" rel="noopener"><img src="<?= base_url('opname/foto/' . $f['id']) ?>" alt="Foto"></a>
            <form method="post" action="<?= base_url('opname/foto/' . $f['id'] . '/verwijderen') ?>" onsubmit="return confirm('Foto verwijderen?');">
              <?= csrf_field() ?><button type="submit" aria-label="Foto verwijderen"><?= icon('close', 14) ?></button>
            </form>
          </div>
        <?php endforeach ?>
        <?php if (count($fotos) < $maxFotos): ?>
          <label class="opname-plus"><?= icon('camera', 22) ?><span>Foto</span>
            <input type="file" accept="image/*" capture="environment" id="foto-input">
          </label>
        <?php endif ?>
      </div>
      <div style="font-size:13px;color:var(--text-dim);">Maak het item van een paar kanten; zet er iets naast voor de maat als dat kan.</div>
    </div>

    <form method="post" action="<?= base_url('opname/items/' . $item['id']) ?>" class="card form-stack">
      <?= csrf_field() ?>
      <div class="form-field">
        <label class="label" for="omschrijving">Omschrijving</label>
        <input class="field" type="text" id="omschrijving" name="omschrijving" value="<?= esc($item['omschrijving']) ?>" maxlength="200" required>
      </div>
      <div class="form-field">
        <label class="label" for="kamer">Kamer</label>
        <input class="field" type="text" id="kamer" name="kamer" value="<?= esc($item['kamer'] ?? '') ?>" maxlength="80">
      </div>
      <button type="submit" class="btn btn-primary">Opslaan</button>
    </form>

    <form method="post" action="<?= base_url('opname/items/' . $item['id'] . '/verwijderen') ?>" onsubmit="return confirm('Item met foto\'s verwijderen?');" style="margin:0;">
      <?= csrf_field() ?>
      <button type="submit" class="btn-ghost" style="color:var(--red-fg);"><?= icon('trash', 18) ?> Item verwijderen</button>
    </form>
  </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/photo.js') ?>"></script>
<script>
var input = document.getElementById('foto-input');
if (input) {
  input.addEventListener('change', function () {
    if (!input.files[0]) return;
    Boxtracker.uploadPhoto(input.files[0], '<?= base_url('opname/items/' . $item['id'] . '/foto') ?>', document.getElementById('grid-item'));
    input.value = '';
  });
}
</script>
<?= $this->endSection() ?>
