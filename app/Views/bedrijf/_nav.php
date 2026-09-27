<?php $pad = trim(service('request')->getUri()->getPath(), '/'); ?>
<div class="beheer-kop kantoor-nav">
  <?= merk_mark(40, 20) ?>
  <a href="<?= base_url('bedrijf') ?>" class="chip<?= $pad === 'bedrijf' || str_starts_with($pad, 'bedrijf/verhuizingen') ? ' on' : '' ?>">Planning</a>
  <?php if ($isPlanner): ?>
    <a href="<?= base_url('bedrijf/medewerkers') ?>" class="chip<?= str_starts_with($pad, 'bedrijf/medewerkers') ? ' on' : '' ?>">Medewerkers</a>
  <?php endif ?>
  <span style="flex:1;"></span>
  <span style="font-size:14px;color:var(--text-mid);"><?= esc(access()->user()['naam'] ?? '') ?> · <?= esc(access()->medewerker()['rol'] ?? '') ?></span>
  <form method="post" action="<?= base_url('logout') ?>" style="margin:0;"><?= csrf_field() ?><button type="submit" class="btn-ghost"><?= icon('logout', 18) ?> Uitloggen</button></form>
</div>
