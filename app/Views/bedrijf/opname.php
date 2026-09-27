<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$punten = $opname['punten'];
$klaar  = count(array_filter($punten, static fn ($p) => (int) $p['fotos'] > 0));
?>
<div class="screen">
  <div class="header">
    <a href="<?= base_url('/') ?>" class="btn-icon" aria-label="Terug"><?= icon('back') ?></a>
  </div>
  <div class="content" style="gap:22px;">
    <div style="display:flex;flex-direction:column;gap:6px;">
      <h1 class="page-title">Opname</h1>
      <p class="page-sub" style="font-size:16px;">Maak foto's van deze punten en van meubels of spullen die mee moeten. Dan weet <?= esc(tenant()->merk()['naam']) ?> vooraf wat er nodig is.</p>
    </div>
    <?php if ($message): ?><div class="msg msg-info"><?= esc($message) ?></div><?php endif ?>

    <div style="display:flex;flex-direction:column;gap:10px;">
      <div class="label">Vaste punten · <?= $klaar ?> van <?= count(\App\Models\OpnameItemModel::PUNTEN) ?></div>
      <?php foreach (\App\Models\OpnameItemModel::PUNTEN as $punt => [$naam, $hint]): ?>
        <?php $item = $punten[$punt] ?? null; $fs = $item ? ($fotos[(int) $item['id']] ?? []) : []; ?>
        <div class="card form-stack" style="gap:10px;">
          <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:18px;font-weight:700;flex:1;"><?= $naam ?></span>
            <?php if ($fs): ?><span style="color:var(--green-fg);"><?= icon('check') ?></span><?php endif ?>
          </div>
          <div style="font-size:14px;color:var(--text-mid);line-height:1.4;"><?= $hint ?></div>
          <div class="opname-grid" id="grid-<?= $punt ?>">
            <?php foreach ($fs as $f): ?>
              <a href="<?= base_url('opname/foto/' . $f['id']) ?>" target="_blank" rel="noopener" class="opname-foto"><img src="<?= base_url('opname/foto/' . $f['id']) ?>" alt="Foto <?= esc($naam, 'attr') ?>"></a>
            <?php endforeach ?>
            <?php if (count($fs) < $maxFotos): ?>
              <label class="opname-plus"><?= icon('camera', 22) ?><span>Foto</span>
                <input type="file" accept="image/*" capture="environment" data-upload="<?= base_url('opname/punt/' . $punt . '/foto') ?>" data-grid="grid-<?= $punt ?>">
              </label>
            <?php endif ?>
          </div>
        </div>
      <?php endforeach ?>
    </div>

    <div style="display:flex;flex-direction:column;gap:10px;">
      <div class="label">Meubels en losse spullen</div>
      <?php if ($opname['items']): ?>
        <div class="card" style="padding:0;overflow:hidden;">
          <?php foreach ($opname['items'] as $it): ?>
            <a href="<?= base_url('opname/items/' . $it['id']) ?>" class="menu-row row">
              <span style="flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;">
                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc($it['omschrijving']) ?></span>
                <span class="sub"><?= $it['kamer'] ? esc($it['kamer']) . ' · ' : '' ?><?= (int) $it['fotos'] ?> foto<?= (int) $it['fotos'] === 1 ? '' : "'s" ?></span>
              </span>
              <?= icon('camera', 18) ?>
            </a>
          <?php endforeach ?>
        </div>
      <?php endif ?>
      <form method="post" action="<?= base_url('opname/items') ?>" class="card form-stack">
        <?= csrf_field() ?>
        <div style="font-size:18px;font-weight:700;">Item toevoegen</div>
        <input class="field" type="text" name="omschrijving" maxlength="200" placeholder="Bijv. hoekbank, piano, kast 2 m hoog" aria-label="Omschrijving" required>
        <input class="field" type="text" name="kamer" maxlength="80" placeholder="Kamer (optioneel)" aria-label="Kamer">
        <button type="submit" class="btn btn-secondary"><?= icon('plus') ?> Toevoegen en foto maken</button>
      </form>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/photo.js') ?>"></script>
<script>
document.querySelectorAll('input[data-upload]').forEach(function (input) {
  input.addEventListener('change', function () {
    if (!input.files[0]) return;
    Boxtracker.uploadPhoto(input.files[0], input.dataset.upload, document.getElementById(input.dataset.grid));
    input.value = '';
  });
});
</script>
<?= $this->endSection() ?>
