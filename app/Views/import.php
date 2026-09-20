<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
  </div>
  <div class="content" style="gap:20px;">
    <h1 style="font-size:32px;font-weight:700;letter-spacing:-0.035em;">Labels importeren</h1>
    <p style="font-size:16px;line-height:1.5;color:var(--text-mid);">Kies een CSV-bestand met de stickernummers. Elke regel wordt een lege doos, klaar om te scannen.</p>

    <?php if (! empty($done)): ?>
      <div class="card pop" style="display:flex;flex-direction:column;gap:18px;">
        <div style="display:flex;align-items:baseline;gap:14px;"><span class="mono" style="font-size:64px;font-weight:600;letter-spacing:-0.05em;"><?= $added ?></span><span style="font-size:18px;font-weight:600;line-height:1.25;">dozen<br>toegevoegd</span></div>
        <div class="divider"></div>
        <div style="display:flex;align-items:baseline;gap:14px;"><span class="mono" style="font-size:32px;font-weight:600;color:var(--text-dim);"><?= $skipped ?></span><span style="font-size:16px;color:var(--text-mid);">overgeslagen, bestonden al</span></div>
        <?php if ($added === 0 && $skipped === 0): ?><div style="font-size:15px;color:var(--red-fg);">Geen nummers gevonden. Zet het nummer in de eerste kolom.</div><?php endif ?>
      </div>
      <div style="flex:1;"></div>
    <?php else: ?>
      <?php if (! empty($error)): ?><div style="font-size:15px;color:var(--red-fg);"><?= esc($error) ?></div><?php endif ?>
      <div class="card" style="display:flex;flex-direction:column;gap:10px;">
        <div class="label">Voorbeeld</div>
        <div class="mono" style="font-size:16px;line-height:1.6;">nummer;code;url<br>061;ab12;https://…<br>062;cd34;https://…</div>
        <div style="font-size:14px;color:var(--text-dim);">Het nummer staat in de eerste kolom. Nummers die al bestaan worden overgeslagen.</div>
      </div>
      <div style="flex:1;"></div>
    <?php endif ?>
  </div>
  <div class="bottombar">
    <?php if (! empty($done)): ?>
      <a href="<?= base_url('/') ?>" class="btn btn-primary">Naar start</a>
      <a href="<?= base_url('import') ?>" class="btn-ghost" style="text-align:center;">Nog een bestand</a>
    <?php else: ?>
      <form method="post" action="<?= base_url('import') ?>" enctype="multipart/form-data" id="impform">
        <label class="btn btn-primary" style="position:relative;cursor:pointer;">
          <?= icon('upload') ?> Bestand kiezen
          <input type="file" name="csv" accept=".csv,.txt,text/csv,text/plain" style="position:absolute;inset:0;opacity:0;cursor:pointer;" onchange="document.getElementById('impform').submit();this.closest('label').textContent='Bezig met inlezen…';">
        </label>
      </form>
    <?php endif ?>
  </div>
</div>
<?= $this->endSection() ?>
