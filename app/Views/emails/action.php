<?php
/**
 * Basislayout voor de systeemmails (bevestigen, wachtwoord reset). Table-layout en
 * inline CSS voor brede mailclient-compatibiliteit; volgt de kartonlook-kleuren van
 * de app (zie assets/css/app.css) maar zonder het custom lettertype, dat mailclients
 * niet laden.
 */
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($subject) ?></title>
</head>
<body style="margin:0;padding:0;background:#F3E9D8;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3E9D8;">
<tr><td align="center" style="padding:32px 16px;">
<table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:480px;width:100%;background:#FFFDF8;border:2px solid #1A140E;border-radius:14px;">
  <tr><td style="background:#CA9E67;padding:16px 28px;border-radius:11px 11px 0 0;">
    <span style="font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:700;color:#1A140E;">Boxtracker</span>
  </td></tr>
  <tr><td style="padding:28px 28px 8px;font-family:Arial,Helvetica,sans-serif;">
    <p style="margin:0 0 16px;font-size:15px;line-height:1.5;color:#1A140E;"><?= $naam !== '' ? 'Hoi ' . esc($naam) . ',' : 'Hallo,' ?></p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.5;color:#1A140E;"><?= esc($intro) ?></p>
    <table role="presentation" cellpadding="0" cellspacing="0">
      <tr><td style="border-radius:10px;background:#936037;border:2px solid #1A140E;">
        <a href="<?= esc($url, 'attr') ?>" style="display:inline-block;padding:12px 22px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;color:#FFFDF8;text-decoration:none;">
          <?= esc($buttonLabel) ?>
        </a>
      </td></tr>
    </table>
    <p style="margin:24px 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#6B5C4A;word-break:break-all;">
      Of plak deze link in je browser:<br><?= esc($url) ?>
    </p>
  </td></tr>
  <tr><td style="padding:16px 28px 24px;border-top:1px solid #E7DAC4;font-family:Arial,Helvetica,sans-serif;">
    <p style="margin:0;font-size:12px;line-height:1.5;color:#6B5C4A;"><?= esc($footer) ?></p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
