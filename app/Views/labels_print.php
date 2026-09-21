<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<title><?= esc($title) ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;700&family=Geist+Mono:wght@600;700&display=swap">
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
<style>
  *{box-sizing:border-box;}
  body{margin:0;background:#E8E6E1;color:#16181C;font-family:'Geist',system-ui,sans-serif;}
  .toolbar{max-width:210mm;margin:0 auto;padding:20px;display:flex;flex-wrap:wrap;align-items:center;gap:12px;}
  .toolbar h1{font-size:20px;margin:0;flex:1 1 100%;}
  .toolbar .note{font-size:13px;color:#4A4F57;flex:1 1 100%;}
  .btn{font:inherit;font-weight:600;padding:10px 18px;border:1px solid #16181C;background:#16181C;color:#fff;cursor:pointer;border-radius:10px;}
  .btn.ghost{background:#fff;color:#16181C;}
  a.btn{display:inline-block;text-decoration:none;}
  .sheet{background:#fff;margin:0 auto 18px;width:210mm;height:297mm;position:relative;box-shadow:0 1px 4px rgba(0,0,0,.15);}
  .sheet-inner{display:grid;}
  .label{position:relative;display:flex;align-items:center;gap:3mm;padding:3mm;overflow:hidden;}
  .label canvas{display:block;}
  .meta{flex:1 1 auto;min-width:0;display:flex;flex-direction:column;justify-content:center;gap:1mm;}
  .num{font-family:'Geist Mono',monospace;font-weight:700;line-height:.9;letter-spacing:-0.02em;}
  .writeline{border-bottom:0.4mm solid #16181C;height:5mm;}
  .writehint{font-size:2.4mm;color:#6B7078;margin-top:-1mm;}
  @media print{
    @page{size:A4;margin:0;}
    body{background:#fff;}
    .toolbar{display:none;}
    .sheet{box-shadow:none;margin:0;width:210mm;height:297mm;page-break-after:always;break-after:page;}
    .sheet:last-child{page-break-after:auto;break-after:auto;}
  }
</style>
</head>
<body>
<div class="toolbar">
  <h1>Boxtracker — <?= $aantal ?> <?= $aantal === 1 ? 'doos' : 'dozen' ?> (<?= $aantal * 2 ?> stickers), <?= count($sheets) ?> <?= count($sheets) === 1 ? 'vel' : 'vellen' ?></h1>
  <button type="button" class="btn" onclick="window.print()">Printen</button>
  <a href="<?= base_url('labels/csv') ?>" class="btn ghost">Lijst downloaden (CSV)</a>
  <a href="<?= base_url('labels') ?>" class="btn ghost">Andere batch</a>
  <div class="note">Controleer de eerste keer met één vel op gewoon papier of de stickers precies onder de vakjes vallen. Zet in de printer de schaal op 100 procent, niet op passend maken.</div>
</div>

<?php foreach ($sheets as $sheet): ?>
  <div class="sheet">
    <div class="sheet-inner" style="grid-template-columns:repeat(<?= $preset['cols'] ?>, <?= $preset['w'] ?>mm);grid-auto-rows:<?= $preset['h'] ?>mm;column-gap:<?= $preset['gx'] ?>mm;row-gap:<?= $preset['gy'] ?>mm;padding-top:<?= $preset['mt'] ?>mm;padding-left:<?= $preset['ml'] ?>mm;">
      <?php foreach ($sheet as $item): ?>
        <?php $qrSize = round($preset['h'] * 3.2); $qrMm = ($preset['h'] - 8) . 'mm'; ?>
        <?php // Twee keer dezelfde doos naast elkaar (grid vult van links naar rechts), voor
              // een sticker op elke kant van de doos. ?>
        <?php for ($side = 0; $side < 2; $side++): ?>
          <div class="label">
            <div class="qr" style="width:<?= $qrMm ?>;height:<?= $qrMm ?>;flex:none;">
              <canvas data-url="<?= esc($baseUrl . '/d/' . $item['nummer'] . '-' . $item['token']) ?>" data-size="<?= $qrSize ?>" style="width:100%;height:100%;"></canvas>
            </div>
            <div class="meta">
              <div class="num" style="font-size:<?= round($preset['h'] * 0.46) ?>mm;">#<?= box_nr($item['nummer']) ?></div>
              <div class="writeline"></div>
              <div class="writehint">ruimte</div>
            </div>
          </div>
        <?php endfor ?>
      <?php endforeach ?>
    </div>
  </div>
<?php endforeach ?>

<script>
document.querySelectorAll('canvas[data-url]').forEach(function (c) {
  new QRious({ element: c, value: c.dataset.url, size: parseInt(c.dataset.size, 10), level: 'M', padding: 0 });
});
</script>
</body>
</html>
