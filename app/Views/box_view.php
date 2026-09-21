<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
  </div>
  <div class="content">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px;">
      <div class="mono" style="font-size:64px;font-weight:600;letter-spacing:-0.05em;line-height:.95;"><span style="color:#7C8696;font-size:38px;">#</span><?= box_nr($box['nummer']) ?></div>
      <span class="pill" style="background:<?= $pill['bg'] ?>;color:<?= $pill['fg'] ?>;margin-bottom:6px;"><?= esc($pill['label']) ?></span>
    </div>

    <?php if ($box['fragiel'] || $box['eerst_openen']): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <?php if ($box['fragiel']): ?><span class="tag"><?= icon('fragile', 16) ?> Fragiel</span><?php endif ?>
      <?php if ($box['eerst_openen']): ?><span class="tag"><?= icon('first', 16) ?> Eerst openen</span><?php endif ?>
    </div>
    <?php endif ?>

    <div class="card" style="display:flex;flex-direction:column;gap:14px;">
      <div class="label">Staat nu</div>
      <div style="font-size:27px;font-weight:700;letter-spacing:-0.03em;"><?= esc($box['huidige_locatie'] ?: '—') ?></div>
      <div class="divider"></div>
      <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px;"><span class="label">Moet naar</span><span style="font-size:18px;font-weight:600;"><?= esc($box['einddoel'] ?: '—') ?></span></div>
      <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px;"><span class="label">Eigenaar</span><span style="font-size:16px;"><?= esc($box['eigenaar'] ?: '—') ?></span></div>
    </div>

    <div class="card" style="display:flex;flex-direction:column;gap:12px;">
      <div class="label">Inhoud</div>
      <div style="font-size:18px;line-height:1.5;white-space:pre-line;"><?= esc($box['omschrijving'] ?: 'Nog niet ingevuld.') ?></div>
      <?php if ($removed): ?>
      <div class="divider"></div>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div class="label">Eruit gehaald</div>
        <?php foreach ($removed as $r): ?>
          <div style="display:flex;justify-content:space-between;gap:12px;font-size:15px;"><span><?= esc($r['tekst']) ?></span><span style="color:var(--text-dim);flex:none;font-size:13px;"><?= esc($r['wanneer']) ?></span></div>
        <?php endforeach ?>
      </div>
      <?php endif ?>
    </div>

    <div style="display:flex;flex-direction:column;gap:10px;">
      <div class="label">Foto's</div>
      <div id="photo-grid" style="display:grid;grid-template-columns:repeat(3, minmax(0,1fr));gap:10px;">
        <?php foreach ($photos as $p): ?>
          <a href="<?= base_url('foto/' . $p['id']) ?>" target="_blank" rel="noopener" style="position:relative;aspect-ratio:1/1;border-radius:16px;overflow:hidden;display:block;">
            <img src="<?= base_url('foto/' . $p['id']) ?>" alt="Foto van de doos" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
          </a>
        <?php endforeach ?>
        <label style="position:relative;aspect-ratio:1/1;border-radius:16px;border:1.5px dashed #B3BBCB;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;color:var(--blue);font-size:13px;font-weight:600;cursor:pointer;">
          <?= icon('camera', 24) ?><span>Foto</span>
          <input type="file" accept="image/*" capture="environment" id="photo-input" style="position:absolute;inset:0;opacity:0;cursor:pointer;">
        </label>
      </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:12px;">
      <div class="label">Reis van de doos</div>
      <div class="card" style="padding:18px 18px 4px;">
        <?php foreach ($journey as $i => $j): ?>
          <div style="display:flex;gap:14px;">
            <div style="display:flex;flex-direction:column;align-items:center;width:14px;flex:none;">
              <div style="width:14px;height:14px;border-radius:50%;margin-top:4px;background:<?= $i === 0 ? 'var(--blue)' : '#C3C9D6' ?>;"></div>
              <?php if ($i < count($journey) - 1): ?><div style="flex:1;width:2px;background:var(--border);margin-top:4px;"></div><?php endif ?>
            </div>
            <div style="padding-bottom:18px;display:flex;flex-direction:column;gap:2px;min-width:0;">
              <div style="font-size:16px;font-weight:600;"><?= esc($j['titel']) ?></div>
              <div style="font-size:14px;color:var(--text-dim);"><?= esc($j['sub']) ?></div>
            </div>
          </div>
        <?php endforeach ?>
      </div>
    </div>

    <?php if ($box['status'] !== 'uitgepakt'): ?>
    <div style="display:grid;grid-template-columns:repeat(2, minmax(0,1fr));gap:10px;">
      <a href="<?= base_url('d/' . $box['nummer'] . '-' . $box['token'] . '?edit=1') ?>" class="btn btn-secondary"><?= icon('edit') ?> Bewerken</a>
      <button type="button" class="btn btn-secondary" onclick="document.getElementById('open-sheet').style.display='block';document.getElementById('open-backdrop').style.display='block';"><?= icon('unpack') ?> Doos geopend</button>
      <form method="post" action="<?= base_url('d/' . $box['nummer'] . '-' . $box['token'] . '/status') ?>" style="grid-column:span 2;">
        <input type="hidden" name="status" value="uitgepakt">
        <button type="submit" class="btn btn-secondary" style="width:100%;"><?= icon('box') ?> Doos uitgepakt</button>
      </form>
    </div>
    <?php else: ?>
      <div style="display:flex;gap:8px;">
        <a href="<?= base_url('d/' . $box['nummer'] . '-' . $box['token'] . '?edit=1') ?>" class="btn-ghost" style="display:inline-flex;align-items:center;gap:8px;"><?= icon('edit', 18) ?> Bewerken</a>
        <button type="button" class="btn-ghost" style="display:inline-flex;align-items:center;gap:8px;color:var(--red-fg);" onclick="document.getElementById('delete-sheet').style.display='block';document.getElementById('delete-backdrop').style.display='block';"><?= icon('trash', 18) ?> Verwijderen</button>
      </div>
    <?php endif ?>
  </div>

  <div class="bottombar">
    <?php if ($box['status'] !== 'uitgepakt'): ?>
      <a href="#move-sheet" onclick="document.getElementById('move-sheet').style.display='block';return false;" class="btn btn-primary"><?= icon('move') ?> Doos verplaatsen</a>
    <?php else: ?>
      <a href="<?= base_url('/') ?>" class="btn btn-secondary" style="height:58px;">Terug naar start</a>
    <?php endif ?>
  </div>

  <?php if ($box['status'] === 'uitgepakt'): ?>
  <button type="button" class="sheet-backdrop" id="delete-backdrop" style="display:none;" onclick="document.getElementById('delete-sheet').style.display='none';this.style.display='none';"></button>
  <div class="sheet rise" id="delete-sheet" style="display:none;">
    <div style="font-size:22px;font-weight:700;">Doos #<?= box_nr($box['nummer']) ?> verwijderen?</div>
    <div style="font-size:15px;color:var(--text-dim);">Dit verwijdert de doos, de reisgeschiedenis en de foto's definitief. Dit kan niet ongedaan worden gemaakt.</div>
    <form method="post" action="<?= base_url('d/' . $box['nummer'] . '-' . $box['token'] . '/verwijderen') ?>">
      <button type="submit" class="btn btn-primary" style="background:var(--red-fg);">Ja, verwijderen</button>
      <div style="height:8px;"></div>
      <button type="button" class="btn-ghost" style="width:100%;text-align:center;" onclick="document.getElementById('delete-sheet').style.display='none';document.getElementById('delete-backdrop').style.display='none';">Annuleren</button>
    </form>
  </div>
  <?php endif ?>

  <button type="button" class="sheet-backdrop" id="open-backdrop" style="display:none;" onclick="document.getElementById('open-sheet').style.display='none';this.style.display='none';"></button>
  <div class="sheet rise" id="open-sheet" style="display:none;">
    <div style="font-size:22px;font-weight:700;">Wat is eruit gehaald?</div>
    <form method="post" action="<?= base_url('d/' . $box['nummer'] . '-' . $box['token'] . '/status') ?>">
      <input type="hidden" name="status" value="geopend">
      <label class="sr-only" for="notitie">Wat is eruit gehaald</label>
      <textarea class="field" id="notitie" name="notitie" rows="3" placeholder="Bijv. twee kabels en de bladmuziek" style="background:var(--bg);"></textarea>
      <div style="height:14px;"></div>
      <button type="submit" class="btn btn-primary">Noteren</button>
      <div style="height:8px;"></div>
      <button type="button" class="btn-ghost" style="width:100%;text-align:center;" onclick="document.getElementById('open-sheet').style.display='none';document.getElementById('open-backdrop').style.display='none';">Annuleren</button>
    </form>
  </div>

  <button type="button" class="sheet-backdrop" id="move-backdrop" style="display:none;" onclick="document.getElementById('move-sheet').style.display='none';this.style.display='none';"></button>
  <div class="sheet rise" id="move-sheet" style="display:none;">
    <div style="font-size:22px;font-weight:700;">Doos verplaatsen naar</div>
    <form method="post" action="<?= base_url('d/' . $box['nummer'] . '-' . $box['token'] . '/move') ?>">
      <label class="sr-only" for="naar_locatie">Nieuwe plek</label>
      <input class="field" type="text" id="naar_locatie" name="naar_locatie" placeholder="Bijv. Opslag · rij 3" autofocus>
      <div style="height:14px;"></div>
      <button type="submit" class="btn btn-primary">Verplaatsen</button>
    </form>
  </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/photo.js') ?>"></script>
<script>
['open', 'move'].forEach(function (key) {
  var sheet = document.getElementById(key + '-sheet');
  if (sheet) {
    var open = function () { sheet.style.display = 'block'; document.getElementById(key + '-backdrop').style.display = 'block'; };
    document.querySelectorAll('[onclick*="' + key + '-sheet"]').forEach(function () {});
  }
});
var photoInput = document.getElementById('photo-input');
if (photoInput) {
  photoInput.addEventListener('change', function () {
    var file = photoInput.files[0];
    if (!file) return;
    Boxtracker.uploadPhoto(file, '<?= base_url('d/' . $box['nummer'] . '-' . $box['token'] . '/photo') ?>', document.getElementById('photo-grid'));
    photoInput.value = '';
  });
}
</script>
<?= $this->endSection() ?>
