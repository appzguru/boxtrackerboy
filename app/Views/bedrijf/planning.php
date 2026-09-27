<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php $vandaag = date('Y-m-d'); ?>
<div class="screen">
  <div class="content" style="gap:24px;">
    <?= $this->include('bedrijf/_nav') ?>
    <div class="beheer-kop">
      <h1 class="page-title">Planning</h1>
      <?php if ($isPlanner && $magNieuw): ?>
        <a href="<?= base_url('bedrijf/verhuizingen/nieuw') ?>" class="btn btn-primary" style="width:auto;padding:0 22px;"><?= icon('plus') ?> Nieuwe verhuizing</a>
      <?php endif ?>
    </div>
    <?php if ($message): ?><div class="msg msg-info"><?= esc($message) ?></div><?php endif ?>

    <div class="card" style="padding:0;overflow:auto;">
      <?php if ($verhuizingen): ?>
        <table class="beheer-tabel">
          <thead><tr><th>Verhuisdatum</th><th>Klant</th><th>Van → naar</th><th class="num" title="Dozen met inhoud / stickers">Dozen</th><th class="num">Fragiel</th><th>Ploeg</th></tr></thead>
          <tbody>
            <?php foreach ($verhuizingen as $v): ?>
              <?php $url = base_url('bedrijf/verhuizingen/' . $v['id']); $voorbij = $v['verhuisdatum'] && $v['verhuisdatum'] < $vandaag; ?>
              <tr class="klik" onclick="location.href='<?= esc($url, 'js') ?>'" style="<?= $voorbij ? 'opacity:.55;' : '' ?>">
                <td style="white-space:nowrap;font-weight:600;"><?= $v['verhuisdatum'] ? esc(nl_date($v['verhuisdatum'])) : '<span class="dim">nog geen datum</span>' ?></td>
                <td><a href="<?= esc($url, 'attr') ?>" style="color:var(--blue);font-weight:600;"><?= esc($v['naam']) ?></a></td>
                <td class="dim" style="white-space:normal;"><?= esc($v['adres_van'] ?? '—') ?> → <?= esc($v['adres_naar'] ?? '—') ?></td>
                <td class="num"><?= (int) $v['ingepakt'] ?><span class="dim"> / <?= (int) $v['stickers'] ?></span></td>
                <td class="num"><?= (int) $v['fragiel'] ?: '<span class="dim">0</span>' ?></td>
                <td class="dim" style="white-space:normal;"><?= esc($v['ploeg'] ?? '—') ?></td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      <?php else: ?>
        <div style="padding:20px;color:var(--text-mid);">Nog geen verhuizingen.<?= $isPlanner && $magNieuw ? ' Maak de eerste aan met "Nieuwe verhuizing".' : '' ?></div>
      <?php endif ?>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
