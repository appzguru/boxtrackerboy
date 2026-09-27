<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <a href="<?= base_url('menu') ?>" class="btn-icon" aria-label="Terug"><?= icon('back') ?></a>
  </div>
  <div class="content" style="gap:20px;">
    <h1 class="page-title">Leden</h1>
    <?php if ($message): ?><div class="msg msg-info"><?= esc($message) ?></div><?php endif ?>

    <?php if ($newLink): ?>
      <div class="card form-stack" style="border:2px solid var(--blue);">
        <div style="font-size:19px;font-weight:700;">Uitnodigingslink klaar</div>
        <div style="font-size:15px;color:var(--text-mid);line-height:1.45;">Stuur deze link naar de persoon die je wilt uitnodigen. Hij werkt één keer en is 7 dagen geldig.</div>
        <input class="field mono" type="text" id="invite-link" value="<?= esc($newLink) ?>" readonly style="font-size:14px;" onclick="this.select()">
        <button type="button" class="btn btn-primary" id="share-btn"><?= icon('share') ?> Delen</button>
      </div>
    <?php endif ?>

    <div class="card" style="padding:0;overflow:hidden;">
      <?php foreach ($members as $m): ?>
        <div class="menu-row" style="flex-wrap:wrap;padding-top:10px;padding-bottom:10px;">
          <span style="flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;">
            <span><?= esc($m['naam']) ?><?= (int) $m['user_id'] === (int) $me ? ' (jij)' : '' ?></span>
            <span class="sub" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc($m['email']) ?></span>
          </span>
          <form method="post" action="<?= base_url('leden/' . $m['id'] . '/rol') ?>" style="margin:0;">
            <?= csrf_field() ?>
            <select name="rol" class="chip" onchange="this.form.submit()" aria-label="Rol van <?= esc($m['naam'], 'attr') ?>">
              <option value="admin" <?= $m['rol'] === 'admin' ? 'selected' : '' ?>>Admin</option>
              <option value="helper" <?= $m['rol'] === 'helper' ? 'selected' : '' ?>>Helper</option>
            </select>
          </form>
          <form method="post" action="<?= base_url('leden/' . $m['id'] . '/verwijderen') ?>" style="margin:0;" onsubmit="return confirm(<?= esc(json_encode($m['naam'] . ' verwijderen uit deze verhuizing?'), 'attr') ?>);">
            <?= csrf_field() ?>
            <button type="submit" class="btn-icon" aria-label="<?= esc($m['naam'], 'attr') ?> verwijderen" style="color:var(--text-dim);"><?= icon('close', 18) ?></button>
          </form>
        </div>
      <?php endforeach ?>
    </div>

    <div class="card form-stack">
      <div style="font-size:19px;font-weight:700;">Iemand uitnodigen</div>
      <?php if (! $verified): ?>
        <div class="msg msg-info">Bevestig eerst je e-mailadres (check je mail), dan kun je anderen uitnodigen.</div>
      <?php else: ?>
        <div style="font-size:15px;color:var(--text-mid);line-height:1.45;">Helpers maken een account aan en kunnen dozen vullen en verplaatsen. Moet iemand alleen even sjouwen of een middag inpakken? Gebruik dan <a href="<?= base_url('handjes') ?>" style="color:var(--blue);font-weight:600;">handjes</a>, zonder account.</div>
        <form method="post" action="<?= base_url('leden/uitnodigen') ?>" class="form-stack">
          <?= csrf_field() ?>
          <div style="display:flex;gap:8px;">
            <label class="chip on" style="cursor:pointer;"><input type="radio" name="rol" value="helper" checked style="margin-right:6px;">Helper</label>
            <label class="chip" style="cursor:pointer;"><input type="radio" name="rol" value="admin" style="margin-right:6px;">Admin</label>
          </div>
          <button type="submit" class="btn btn-secondary"><?= icon('plus') ?> Link maken</button>
        </form>
      <?php endif ?>
    </div>

    <?php if ($invites): ?>
      <div style="display:flex;flex-direction:column;gap:10px;">
        <div class="label">Openstaande links</div>
        <div class="card" style="padding:0;overflow:hidden;">
          <?php foreach ($invites as $inv): ?>
            <div class="menu-row">
              <span style="flex:1;display:flex;flex-direction:column;gap:2px;">
                <span><?= $inv['rol'] === 'admin' ? 'Admin' : 'Helper' ?></span>
                <span class="sub">Geldig tot <?= esc(nl_datetime($inv['expires_at'])) ?></span>
              </span>
              <form method="post" action="<?= base_url('uitnodigingen/' . $inv['id'] . '/intrekken') ?>" style="margin:0;">
                <?= csrf_field() ?>
                <button type="submit" class="btn-ghost" style="color:var(--red-fg);">Intrekken</button>
              </form>
            </div>
          <?php endforeach ?>
        </div>
      </div>
    <?php endif ?>
  </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function () {
  var btn = document.getElementById('share-btn');
  if (!btn) return;
  var link = document.getElementById('invite-link').value;
  btn.addEventListener('click', function () {
    if (navigator.share) {
      navigator.share({ title: 'Help mee met onze verhuizing', text: 'Help je mee met onze verhuizing? Via deze link doe je mee in Boxtracker:', url: link }).catch(function () {});
    } else if (navigator.clipboard) {
      navigator.clipboard.writeText(link).then(function () { btn.textContent = 'Gekopieerd!'; });
    }
  });
})();
</script>
<?= $this->endSection() ?>
