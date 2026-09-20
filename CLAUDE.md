# Boxtracker

Zie [handoff.md](handoff.md) voor het volledige bouwdocument (uitgangspunten, datamodel,
routes, schermen). Dit bestand is alleen de operationele infrastructuur: lokaal
draaien, database, SSH en deployen.

## Lokaal draaien

```bash
bash .local/mysql-start.sh   # host 127.0.0.1, port 3309, user root, geen wachtwoord
bash .local/mysql-stop.sh
php spark serve --port 8080  # app.baseURL in .env staat al op http://localhost:8080/
```

`.env` (lokaal, niet in git) wijst al naar de lokale database. Productie-credentials
staan er uitgecommentarieerd in, ter referentie — nooit lokaal gebruiken.

Portable MySQL-binaries worden gedeeld met het `rondjebant.nl`-project
(`../rondjebant.nl/.local/mysql`, niet gedupliceerd). Zie [.local/README.md](.local/README.md).

## Schema

Geen CI4-migraties. [sql/schema.sql](sql/schema.sql) is de canonieke bron (idempotent —
`CREATE TABLE IF NOT EXISTS` / `INSERT IGNORE`) en bevat ook de seed-accounts uit
handoff.md §2. Bij een wijziging: pas `sql/schema.sql` aan, herlaad lokaal (zie
`.local/README.md`), en geef het gewijzigde deel door zodat het ook live gedraaid
kan worden (via phpMyAdmin of `mysql` over SSH).

## SSH access

`ssh boxtracker` (alias in het lokale `~/.ssh/config`, key-based) reikt tot het zxcs-
hostingaccount. Zelfde hoofdaccount als de `lijstje`/`corvee`/`picto`/`centje`-projecten
(host `web0098.zxcs.nl`, user `u7872p5382`) — dit is dus **geen apart account**, alleen
een aparte alias voor de duidelijkheid. Projectmap daar:
`~/domains/minisaas.nl/public_html/boxtracker`.

Dit is een **ander account dan FTP** (`boxtrackerboy@minisaas.nl`); FTP en SSH zijn niet
inwisselbaar.

## Deployment

Deploys gaan over **FTP** naar `web0098.zxcs.nl` via [deploy.php](deploy.php) (zelfde
patroon als lijstje). Let op: het FTP-account `boxtrackerboy@minisaas.nl` is gechroot op
`~/domains/minisaas.nl/` (niet op de boxtracker-submap zelf, anders dan bij lijstje) —
`deploy.php` gebruikt daarom `public_html/boxtracker` als remote path. Geverifieerd met
een test-login op 2026-09-20.

```bash
php deploy.php          # verwerk de deploy-queue (deploy-queue.txt): upload alleen de
                        # genoemde bestanden, verwijder een regel na succes
php deploy.php all      # upload alles (skipt .git, node_modules, .local, tests,
                        # .claude, .github, sql, writable)
php deploy.php app      # upload één map/bestand, bv. 'app' of 'app/Config/Routes.php'
```

**Git-gated in queue-modus**: een bestand gaat alleen mee als het gecommit is én
identiek aan HEAD. Commit eerst, run daarna `php deploy.php`. Geldt niet voor `all`
of een losse map-/bestandsnaam.

**Belangrijk — Document Root**: net als bij lijstje moet de Document Root van
`boxtracker.minisaas.nl` in het hostingpaneel wijzen naar
`public_html/boxtracker/public` (de CI4 `public/`-submap), niet naar
`public_html/boxtracker` zelf. Zonder die instelling staat `.env` (en de rest van de
app) gewoon in het web-bereik. Dit is een eenmalige paneelinstelling, niet iets wat
via FTP/SSH te regelen is — nog te controleren/zetten.

> Security note: `deploy.php` bevat hardcoded FTP-credentials (zoals ook bij lijstje).
> Niet in logs of commits laten lekken buiten dit bestand; `credentials.md` staat al
> in `.gitignore`.

## Repository

GitHub: `git@github.com:appzguru/boxtrackerboy.git` (zie credentials.md). Lokaal nog
geen git-repo geïnitialiseerd.
