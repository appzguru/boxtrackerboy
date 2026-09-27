<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <button type="button" class="btn-icon" aria-label="Terug" onclick="history.back()"><?= icon('back') ?></button>
  </div>
  <div class="content" style="gap:16px;">
    <div style="display:flex;flex-direction:column;gap:6px;">
      <h1 style="font-size:28px;font-weight:700;letter-spacing:-0.035em;"><?= esc($titel) ?></h1>
      <div style="font-size:15px;font-weight:500;color:var(--text-dim);"><?= count($rows) ?> <?= count($rows) === 1 ? 'doos' : 'dozen' ?></div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(2, minmax(0,1fr));gap:4px;padding:4px;border-radius:14px;background:#EFE3CF;">
      <a href="?kind=<?= esc($kind) ?>&val=<?= urlencode((string) $val) ?>&sort=nr" style="height:44px;border-radius:11px;font-size:15px;font-weight:600;display:flex;align-items:center;justify-content:center;<?= $sort === 'nr' ? 'background:#fff;box-shadow:0 1px 2px rgba(15,18,22,.12);' : 'color:var(--text-mid);' ?>">Nummer</a>
      <a href="?kind=<?= esc($kind) ?>&val=<?= urlencode((string) $val) ?>&sort=recent" style="height:44px;border-radius:11px;font-size:15px;font-weight:600;display:flex;align-items:center;justify-content:center;<?= $sort === 'recent' ? 'background:#fff;box-shadow:0 1px 2px rgba(15,18,22,.12);' : 'color:var(--text-mid);' ?>">Laatst gewijzigd</a>
    </div>
    <?php if ($rows): ?>
      <div class="card" style="padding:0;overflow:hidden;">
        <?php foreach ($rows as $i => $r): ?>
          <a href="<?= base_url('d/' . $r['nummer'] . '-' . $r['token']) ?>" class="row" style="display:flex;align-items:center;gap:14px;min-height:66px;padding:0 16px;<?= $i ? 'border-top:1px solid var(--border);' : '' ?>">
            <span class="mono" style="font-size:21px;font-weight:600;letter-spacing:-0.03em;width:48px;flex:none;"><?= box_nr($r['nummer']) ?></span>
            <span style="flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;">
              <span style="font-size:15px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc(access()->can('helper') ? (first_line($r['omschrijving']) ?: '(nog geen inhoud)') : 'Moet naar ' . ($r['einddoel'] ?: '—')) ?></span>
              <span style="font-size:13px;color:var(--text-dim);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc($r['huidige_locatie'] ?: '—') ?></span>
            </span>
            <?php if ($r['fragiel']): ?><span style="color:#A4271B;flex:none;" title="Fragiel"><?= icon('fragile') ?></span><?php endif ?>
            <?php if ($r['eerst_openen']): ?><span style="color:#6E4424;flex:none;" title="Eerst openen"><?= icon('first') ?></span><?php endif ?>
          </a>
        <?php endforeach ?>
      </div>
    <?php else: ?>
      <div class="card" style="font-size:16px;color:var(--text-mid);">Geen dozen in deze lijst.</div>
    <?php endif ?>
  </div>
</div>
<?= $this->endSection() ?>
