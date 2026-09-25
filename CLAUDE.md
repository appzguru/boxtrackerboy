# Boxtracker

Zie [handoff.md](handoff.md) voor het volledige bouwdocument v2 (rollen, datamodel, routes,
schermen). Dit bestand is alleen de operationele infrastructuur: lokaal draaien, database,
SSH en deployen.

## Twee versies

| | v1 — familie | v2 — openbaar |
|---|---|---|
| Code | branch `familie` (tag `familie-v1`) | `master` |
| Live | boxtracker.minisaas.nl (web0098) | app.boxtracker.nl + boxtracker.nl (web0171) — **nog niet live** |
| Login | 4 pincode-accounts | accounts, verhuizingen, rollen admin/helper/sjouwer |
| Deploy | `php deploy.php` (alleen vanaf branch `familie`) | nog in te richten (fase 8) |

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
  `~/domains/boxtracker.nl` (bevat nu alleen de standaard `public_html/index.html`).
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

### v2 (app.boxtracker.nl) — nog in te richten

Open (handoff.md §12 fase 8): subdomein `app.boxtracker.nl` met Document Root op de CI4
`public/`-map, SSL voor beide domeinen, `.htaccess` op boxtracker.nl die `/d/*` doorstuurt
naar de app, SMTP (`noreply@boxtracker.nl`) + SPF/DKIM, en een eigen deploy-doel. In de
live `.env` o.a. `boxtracker.stickerBaseURL = 'https://boxtracker.nl'` en de `email.*`-instellingen.

## Repository

GitHub: `git@github.com:appzguru/boxtrackerboy.git` (zie credentials.md). **Nog niet
gepusht** — bewuste keuze, niet iets wat automatisch gebeurt.
