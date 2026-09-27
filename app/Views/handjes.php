<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <a href="<?= base_url('menu') ?>" class="btn-icon" aria-label="Terug"><?= icon('back') ?></a>
  </div>
  <div class="content" style="gap:20px;">
    <h1 class="page-title">Handjes</h1>
    <p class="page-sub">Laat iemand zonder account meehelpen: hij scant een QR-code op jouw scherm en kan meteen aan de slag op zijn eigen telefoon.</p>

    <form method="post" action="<?= base_url('handjes') ?>" class="card form-stack">
      <?= csrf_field() ?>
      <div class="form-field">
        <span class="label">Wat gaat hij doen?</span>
        <label class="chip" style="cursor:pointer;justify-content:flex-start;height:auto;padding:12px 14px;"><input type="radio" name="rol" value="sjouwer" checked style="margin-right:10px;"><span><strong>Sjouwen</strong> — ziet alleen nummer en bestemming, kan verplaatsen</span></label>
        <label class="chip" style="cursor:pointer;justify-content:flex-start;height:auto;padding:12px 14px;"><input type="radio" name="rol" value="helper" style="margin-right:10px;"><span><strong>Inpakken</strong> — kan dozen vullen, foto's maken en verplaatsen</span></label>
      </div>
      <div class="form-field">
        <label class="label" for="dagen">Hoe lang?</label>
        <select class="field" id="dagen" name="dagen">
          <?php foreach ([1 => '1 dag', 2 => '2 dagen', 3 => '3 dagen', 7 => 'Een week'] as $d => $label): ?>
            <option value="<?= $d ?>"><?= $label ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><?= icon('qr') ?> QR-code tonen</button>
    </form>

    <div style="display:flex;flex-direction:column;gap:10px;">
      <div class="label">Actief</div>
      <?php if ($guests): ?>
        <div class="card" style="padding:0;overflow:hidden;">
          <?php foreach ($guests as $g): ?>
            <div class="menu-row">
              <span style="flex:1;display:flex;flex-direction:column;gap:2px;">
                <span><?= esc($g['naam']) ?> <span class="sub">· <?= $g['rol'] === 'helper' ? 'inpakken' : 'sjouwen' ?></span></span>
                <span class="sub">Tot <?= esc(nl_datetime($g['expires_at'])) ?> · laatst gezien <?= esc(nl_datetime($g['last_used_at'])) ?></span>
              </span>
              <form method="post" action="<?= base_url('handjes/' . $g['id'] . '/intrekken') ?>" style="margin:0;">
                <?= csrf_field() ?>
                <button type="submit" class="btn-ghost" style="color:var(--red-fg);">Intrekken</button>
              </form>
            </div>
          <?php endforeach ?>
        </div>
      <?php else: ?>
        <div class="card" style="font-size:15px;color:var(--text-mid);">Nog niemand.</div>
      <?php endif ?>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
