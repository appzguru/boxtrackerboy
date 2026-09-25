<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="content" style="gap:22px;padding-top:40px;">
    <div style="width:48px;height:48px;border-radius:14px;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;"><?= icon('users', 24) ?></div>
    <h1 class="page-title">Meehelpen met verhuizing <?= esc($invite['verhuizing_naam']) ?>?</h1>
    <p class="page-sub"><?= esc($invite['door_naam'] ?? 'Iemand') ?> nodigt je uit als <?= $invite['rol'] === 'admin' ? 'admin' : 'helper' ?>. Je kunt dan dozen vullen, verplaatsen en terugvinden.</p>
  </div>
  <div class="bottombar">
    <form method="post" action="<?= base_url('uitnodiging/' . $invite['token']) ?>" style="margin:0;">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-primary" style="width:100%;">Ja, ik help mee</button>
    </form>
  </div>
</div>
<?= $this->endSection() ?>
