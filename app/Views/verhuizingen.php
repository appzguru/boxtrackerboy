<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <?php if ($activeId): ?>
  <div class="header">
    <a href="<?= base_url('/') ?>" class="btn-icon" aria-label="Terug"><?= icon('back') ?></a>
  </div>
  <?php endif ?>
  <div class="content" style="gap:20px;<?= $activeId ? '' : 'padding-top:40px;' ?>">
    <h1 class="page-title"><?= $memberships ? 'Welke verhuizing?' : 'Nog geen verhuizing' ?></h1>
    <?php if (session()->getFlashdata('message')): ?><div class="msg msg-info"><?= esc(session()->getFlashdata('message')) ?></div><?php endif ?>

    <?php if ($memberships): ?>
      <div class="card" style="padding:0;overflow:hidden;">
        <?php foreach ($memberships as $m): ?>
          <form method="post" action="<?= base_url('verhuizingen/' . $m['id'] . '/kies') ?>" style="margin:0;">
            <?= csrf_field() ?>
            <button type="submit" class="menu-row row" style="width:100%;text-align:left;<?= (int) $m['id'] === $activeId ? 'background:var(--blue-tint);' : '' ?>">
              <span style="flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;">
                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc($m['naam']) ?></span>
                <span class="sub"><?= ['admin' => 'Admin', 'helper' => 'Helper', 'sjouwer' => 'Sjouwer'][$m['rol']] ?? 'Helper' ?> · <?= (int) $m['dozen'] ?> <?= (int) $m['dozen'] === 1 ? 'doos' : 'dozen' ?></span>
              </span>
              <?php if ((int) $m['id'] === $activeId): ?><span style="color:var(--blue);"><?= icon('check') ?></span><?php endif ?>
            </button>
          </form>
        <?php endforeach ?>
      </div>
    <?php else: ?>
      <p class="page-sub"><?= tenant()->isKlant()
          ? 'Je bent nog geen lid van een verhuizing. Start er zelf een, of vraag iemand om een uitnodigingslink.'
          : 'Je bent nog niet aan een verhuizing toegewezen. Zodra de planner dat doet, staat hij hier.' ?></p>
    <?php endif ?>

    <?php if ($magNieuw): ?>
    <div class="card form-stack">
      <div style="font-size:19px;font-weight:700;">Zelf een verhuizing starten</div>
      <form method="post" action="<?= base_url('verhuizingen') ?>" class="form-stack">
        <?= csrf_field() ?>
        <label class="sr-only" for="naam">Naam van de verhuizing</label>
        <input class="field" type="text" id="naam" name="naam" placeholder="Bijv. Jansen of Utrecht → Zwolle" maxlength="80" required>
        <button type="submit" class="btn btn-secondary"><?= icon('plus') ?> Starten</button>
      </form>
    </div>
    <?php endif ?>

    <form method="post" action="<?= base_url('logout') ?>" style="margin:0;"><?= csrf_field() ?><button type="submit" class="btn-ghost"><?= icon('logout', 18) ?> Uitloggen</button></form>
  </div>
</div>
<?= $this->endSection() ?>
