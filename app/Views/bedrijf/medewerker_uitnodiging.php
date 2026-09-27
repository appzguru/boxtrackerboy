<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="screen">
  <div class="content" style="gap:22px;padding-top:40px;">
    <div style="width:48px;height:48px;border-radius:14px;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;"><?= icon('users', 24) ?></div>
    <h1 class="page-title">Aan de slag bij <?= esc($invite['bedrijf_naam']) ?>?</h1>
    <p class="page-sub">Je bent uitgenodigd als <?= esc($invite['rol']) ?>. Daarna zie je hier de verhuizingen van <?= esc($invite['bedrijf_naam']) ?> waar je bij hoort.</p>
  </div>
  <div class="bottombar">
    <form method="post" action="<?= base_url('medewerker/' . $invite['token']) ?>" style="margin:0;">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-primary" style="width:100%;">Ja, doe ik</button>
    </form>
  </div>
</div>
<?= $this->endSection() ?>
