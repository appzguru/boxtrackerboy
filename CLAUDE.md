# Boxtracker

Zie [handoff.md](handoff.md) voor het volledige bouwdocument v2 (rollen, datamodel, routes,
schermen). Dit bestand is alleen de operationele infrastructuur: lokaal draaien, database,
SSH en deployen.

## Twee versies

| | v1 — familie | v2 — openbaar |
|---|---|---|
| Code | branch `familie` (tag `familie-v1`) | `master` |
| Live | boxtracker.minisaas.nl (web0098) | app.boxtracker.nl + boxtracker.nl (web0171) — gedeployd, wacht op DNS/SSL |
| Login | 4 pincode-accounts | accounts, verhuizingen, rollen admin/helper/sjouwer |
| Deploy | `php deploy.php` (alleen vanaf branch `familie`) | `bash deploy-v2.sh` (alleen vanaf `master`) |

v1 blijft draaien voor de eigen verhuizing en krijgt alleen bugfixes, op branch `familie`.
Een fix die ook voor v2 geldt: apart op `master` doorvoeren (het schema verschilt).

## Lokaal draaien

```bash
bash .local/mysql-start.sh   # host 127.0.0.1, port 3309, user root, geen wachtwoord
bash .local/mysql-stop.sh
php spark serve --port 8080  # app.baseURL in .env staat al op http://localhost:8080/
vendor/bin/phpunit           # o.a. tests/database/ScopingTest.php (lektest, handoff.md §6)
```

`.env` (lokaal, niet in git) wijst naar de lokale database `boxtracker2` (v2). De oude
database `boxtracker` (v1-schema) staat er nog voor werk op branch `familie` — zet dan
`database.default.database` terug. Tests draaien tegen `boxtracker_test`; de lektest laadt
het schema daar zelf opnieuw in.

Zonder SMTP-instelling schrijft de app mails (bevestigen, wachtwoord reset) naar
`writable/mail/*.txt` in plaats van ze te versturen.

Portable MySQL-binaries worden gedeeld met het `rondjebant.nl`-project
(`../rondjebant.nl/.local/mysql`, niet gedupliceerd). Zie [.local/README.md](.local/README.md).

## Schema

Geen CI4-migraties. [sql/schema.sql](sql/schema.sql) is de canonieke bron (idempotent —
`CREATE TABLE IF NOT EXISTS`). Bij een wijziging: pas `sql/schema.sql` aan, herlaad lokaal:

```bash
"/d/Projecten/rondjebant.nl/.local/mysql/bin/mysql.exe" --no-defaults -h 127.0.0.1 -P 3309 -u root boxtracker2 < sql/schema.sql
```

en geef het gewijzigde deel door zodat het ook live gedraaid kan worden. Productie is
MariaDB 10.6.

## SSH

- **v2**: `ssh boxtrackernl` — alias in `~/.ssh/config`, host `web0171.zxcs.nl` poort 7685,
  user `u7872p488700`. Zelfde hoofdaccount als `rondjebant`. Domeinmap:
  `~/domains/boxtracker.nl`; de app staat in `public_html/app`.
  Database `u7872p488700_boxtracker_j3ls` (gegevens in `env_prd`, niet in git).
- **v1**: `ssh boxtracker` — host `web0098.zxcs.nl`, user `u7872p5382`, projectmap
  `~/domains/minisaas.nl/public_html/boxtracker`.

## Deployment

### v1 (boxtracker.minisaas.nl)

Deploys gaan over **FTP** naar `web0098.zxcs.nl` via `deploy.php` (niet in git, staat in
`.gitignore`; credentials in `credentials.md`). Het script **weigert** te draaien als je niet
op branch `familie` staat — anders zou v2-code op de v1-database terechtkomen.

```bash
git checkout familie
php deploy.php          # verwerk de deploy-queue (deploy-queue.txt)
php deploy.php all      # upload alles (skipt .git, node_modules, .local, tests, …)
php deploy.php app      # upload één map/bestand
```

Queue-modus is git-gated: een bestand gaat alleen mee als het gecommit is én identiek aan
HEAD. Het FTP-account `boxtrackerboy@minisaas.nl` is gechroot op `~/domains/minisaas.nl/`,
dus remote path `public_html/boxtracker`. Document Root van boxtracker.minisaas.nl moet naar
`public_html/boxtracker/public` wijzen.

### v2 (app.boxtracker.nl)

```bash
bash deploy-v2.sh      # alleen vanaf master; deployt de gecommitte HEAD via SSH
```

Zet HEAD via `git archive` op de server en synct met `rsync --delete` naar
`~/domains/boxtracker.nl/public_html/app` (Document Root van app.boxtracker.nl:
`public_html/app/public`). `.env`, `writable/` en `vendor/` blijven op de server staan;
`composer install --no-dev` draait daar. De gedeployde revisie staat in `writable/REVISION`.
Schemawijzigingen gaan niet mee — apart live draaien.

- Live `.env` staat alleen op de server (rechten 600): productie-DB, `app.baseURL`
  https://app.boxtracker.nl/, `boxtracker.stickerBaseURL` https://boxtracker.nl,
  `cookie.secure`, en `email.*` — **SMTPHost/SMTPPass nog leeg**, dus mails komen tot die tijd
  in `writable/mail/` terecht in plaats van verstuurd te worden.
- `~/domains/boxtracker.nl/public_html/.htaccess` (niet in git): blokkeert `/app` (404) en
  stuurt sticker-URL's `boxtracker.nl/d/*` door naar `https://app.boxtracker.nl/d/*` (302).
- Nog open: SSL voor beide domeinen, landingspagina + privacyverklaring op boxtracker.nl,
  mailbox `noreply@boxtracker.nl` + SPF/DKIM.

## Repository

GitHub: `git@github.com:appzguru/boxtrackerboy.git` (zie credentials.md). **Nog niet
gepusht** — bewuste keuze, niet iets wat automatisch gebeurt.
