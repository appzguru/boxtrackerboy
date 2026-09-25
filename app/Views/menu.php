<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="header">
    <a href="<?= base_url('/') ?>" class="btn-icon" aria-label="Terug"><?= icon('back') ?></a>
  </div>
  <div class="content" style="gap:20px;">
    <div style="display:flex;flex-direction:column;gap:6px;">
      <h1 class="page-title"><?= esc($verhuizing['naam'] ?? 'Boxtracker') ?></h1>
      <div class="page-sub" style="font-size:15px;">
        <?= esc($user['naam'] ?? $guest['naam'] ?? '') ?> · <?= ['admin' => 'Admin', 'helper' => 'Helper', 'sjouwer' => 'Sjouwer'][$rol] ?? '' ?>
        <?php if ($guest): ?> · toegang tot <?= esc(nl_datetime($guest['expires_at'])) ?><?php endif ?>
      </div>
    </div>

    <div class="card" style="padding:0;overflow:hidden;">
      <?php if ($rol === 'admin'): ?>
        <a href="<?= base_url('leden') ?>" class="menu-row row"><?= icon('users') ?><span style="flex:1;">Leden en uitnodigingen</span></a>
        <a href="<?= base_url('handjes') ?>" class="menu-row row"><?= icon('qr') ?><span style="flex:1;">Handjes (QR-toegang)</span></a>
        <a href="<?= base_url('verhuizing') ?>" class="menu-row row"><?= icon('settings') ?><span style="flex:1;">Verhuizing en export</span></a>
      <?php endif ?>
      <?php if ($user): ?>
        <a href="<?= base_url('verhuizingen') ?>" class="menu-row row"><?= icon('swap') ?><span style="flex:1;"><?= $aantal > 1 ? 'Andere verhuizing' : 'Nieuwe verhuizing starten' ?></span></a>
        <a href="<?= base_url('account') ?>" class="menu-row row"><?= icon('user') ?><span style="flex:1;">Mijn account</span></a>
      <?php endif ?>
      <form method="post" action="<?= base_url('logout') ?>" style="margin:0;">
        <?= csrf_field() ?>
        <button type="submit" class="menu-row row" style="width:100%;text-align:left;border-top:1px solid var(--border);"><?= icon('logout') ?><span style="flex:1;"><?= $guest ? 'Stoppen met helpen' : 'Uitloggen' ?></span></button>
      </form>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
