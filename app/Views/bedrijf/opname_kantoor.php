<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php
$v    = $verhuizing;
$rijen = [];
foreach (\App\Models\OpnameItemModel::PUNTEN as $punt => [$naam, $hint]) {
    $rijen[] = ['titel' => $naam, 'sub' => $hint, 'item' => $opname['punten'][$punt] ?? null];
}
foreach ($opname['items'] as $it) {
    $rijen[] = ['titel' => $it['omschrijving'], 'sub' => $it['kamer'] ?? '', 'item' => $it];
}
?>
<div class="screen">
  <div class="content" style="gap:24px;">
    <?= $this->include('bedrijf/_nav') ?>
    <div class="beheer-kop">
      <a href="<?= base_url('bedrijf/verhuizingen/' . $v['id']) ?>" class="btn-icon" aria-label="Terug"><?= icon('back') ?></a>
      <h1 class="page-title">Opname · <?= esc($v['naam']) ?></h1>
    </div>
    <?php if ($message): ?><div class="msg msg-info"><?= esc($message) ?></div><?php endif ?>
    <div style="font-size:14px;color:var(--text-mid);"><?= $v['adres_van'] ? esc($v['adres_van']) . ' → ' . esc($v['adres_naar'] ?? '—') . ' · ' : '' ?><?= $v['verhuisdatum'] ? esc(nl_date($v['verhuisdatum'])) : 'nog geen verhuisdatum' ?></div>

    <?php foreach ($rijen as $r): ?>
      <?php $item = $r['item']; $fs = $item ? ($fotos[(int) $item['id']] ?? []) : []; ?>
      <div class="card opname-rij<?= $item && $item['risico'] ? ' risico' : '' ?>" id="<?= $item ? 'item-' . $item['id'] : '' ?>">
        <div class="opname-rij-kop">
          <div style="flex:1;min-width:0;">
            <div style="font-size:18px;font-weight:700;"><?= esc($r['titel']) ?></div>
            <?php if ($r['sub'] !== ''): ?><div style="font-size:13px;color:var(--text-dim);"><?= esc($r['sub']) ?></div><?php endif ?>
          </div>
          <?php if ($item): ?>
            <form method="post" action="<?= base_url('bedrijf/verhuizingen/' . $v['id'] . '/opname/' . $item['id'] . '/risico') ?>" class="opname-risico">
              <?= csrf_field() ?>
              <label class="chip" style="cursor:pointer;"><input type="checkbox" name="risico" value="1" <?= $item['risico'] ? 'checked' : '' ?> style="margin-right:6px;"><?= icon('warning', 16) ?> Risico</label>
              <input class="field" type="text" name="risico_notitie" value="<?= esc($item['risico_notitie'] ?? '') ?>" maxlength="200" placeholder="Bijv. verhuislift nodig" aria-label="Notitie">
              <button type="submit" class="btn-ghost" style="color:var(--blue);">Opslaan</button>
            </form>
          <?php endif ?>
        </div>
        <?php if ($fs): ?>
          <div class="opname-grid breed">
            <?php foreach ($fs as $f): ?>
              <a href="<?= base_url('opname/foto/' . $f['id']) ?>" target="_blank" rel="noopener" class="opname-foto"><img src="<?= base_url('opname/foto/' . $f['id']) ?>" alt="" loading="lazy"></a>
            <?php endforeach ?>
          </div>
        <?php else: ?>
          <div style="font-size:14px;color:var(--text-dim);">Nog geen foto's.</div>
        <?php endif ?>
      </div>
    <?php endforeach ?>
  </div>
</div>
<?= $this->endSection() ?>
