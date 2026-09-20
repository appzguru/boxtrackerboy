<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
  </div>
  <div class="content" style="gap:20px;">
    <h1 style="font-size:32px;font-weight:700;letter-spacing:-0.035em;">Labels</h1>

    <div class="card" style="display:flex;flex-direction:column;gap:16px;">
      <div>
        <div style="font-size:19px;font-weight:700;">Nieuwe stickers genereren</div>
        <div style="font-size:14px;color:var(--text-dim);margin-top:4px;">Begint bij <strong>#<?= box_nr($next) ?></strong>. Elk nummer wordt meteen als lege doos aangemaakt, klaar om te scannen.</div>
      </div>
      <form method="post" action="<?= base_url('labels') ?>" style="display:flex;flex-direction:column;gap:14px;">
        <div style="display:flex;flex-direction:column;gap:8px;">
          <label class="label" for="aantal">Aantal stickers</label>
          <input class="field" type="number" id="aantal" name="aantal" value="12" min="1" max="600" style="height:52px;">
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <label class="label" for="preset">Stickervel</label>
          <select class="field" id="preset" name="preset" style="height:52px;">
            <?php foreach ($presets as $key => $p): ?>
              <option value="<?= esc($key) ?>" <?= $key === '12' ? 'selected' : '' ?>><?= esc($p['label']) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary"><?= icon('labels') ?> Genereren en printen</button>
      </form>
    </div>

    <a href="<?= base_url('import') ?>" class="card row" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
      <div>
        <div style="font-size:16px;font-weight:600;">CSV importeren</div>
        <div style="font-size:14px;color:var(--text-dim);margin-top:2px;">Heb je elders al een lijst met nummer/code gemaakt? Lees die hier in.</div>
      </div>
    </a>
  </div>
</div>
<?= $this->endSection() ?>
