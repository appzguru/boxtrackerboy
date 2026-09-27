# Boxtracker

Zie [handoff.md](handoff.md) voor het volledige bouwdocument v2 (rollen, datamodel, routes,
schermen). Dit bestand is alleen de operationele infrastructuur: lokaal draaien, database,
SSH en deployen.

## Omgevingen

| | dev — testomgeving | prd |
|---|---|---|
| URL | boxtracker.minisaas.nl (web0098) | app.boxtracker.nl + boxtracker.nl (web0171) |
| Code | v2, elke branch | v2, alleen `master` |
| Data | leeg, alleen testdata | de echte verhuizingen (o.a. Kopakker_1) |
| Deploy | `bash deploy-dev.sh` | `bash deploy-v2.sh` |
| Herkenbaar | gele DEV-balk, blauwgrijs, "DEV ·" in titel, TESTSTICKER | kraftbruin |

Het verschil zit in de server-`.env`: `boxtracker.omgeving = dev` (waarschuwingsbalk e.d.) en
`boxtracker.stickerFallbackURL = https://app.boxtracker.nl` — een gescande sticker die op dev
niet bestaat (de oude v1-stickers wijzen naar minisaas) gaat door naar prd. Op dev geen SMTP:
mail komt in `writable/mail/`. Test altijd eerst op dev, dan pas prd.

**v1 is uitgefaseerd** (2026-09-26): de data staat op prd (verhuizing Kopakker_1), minisaas
draait nu v2-dev. Branch `familie` / tag `familie-v1` blijven als archief. Back-up van de
v1-database: `u7872p5382_boxtracker_0393a.sql` (dump 2026-09-26 10:31).
**Gebruik `deploy.php` niet meer** — dat zet v1-code over FTP op de v2-dev-database.

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

- **prd**: `ssh boxtrackernl` — alias in `~/.ssh/config`, host `web0171.zxcs.nl` poort 7685,
  user `u7872p488700`. Zelfde hoofdaccount als `rondjebant`. Domeinmap:
  `~/domains/boxtracker.nl`; de app staat in `public_html/app`.
  Database `u7872p488700_boxtracker_j3ls` (gegevens in `env_prd`, niet in git).
- **dev**: `ssh boxtracker` — host `web0098.zxcs.nl`, user `u7872p5382`, app in
  `~/domains/minisaas.nl/public_html/boxtracker` (Document Root: `…/boxtracker/public`).
  Database `u7872p5382_boxtracker_0393a` (gegevens in `credentials.md`), v2-schema, leeg.

## Deployment

### dev (boxtracker.minisaas.nl)

```bash
bash deploy-dev.sh     # elke branch; deployt de gecommitte HEAD via SSH
```

Werkt als `deploy-v2.sh` (hieronder), maar weigert als de server-`.env` geen
`boxtracker.omgeving = dev` bevat — zo kan het nooit prd raken. Schemawijzigingen ook hier
apart draaien (phpMyAdmin of `mysql`).

### prd (app.boxtracker.nl)

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
  `cookie.secure`, en `email.*` — SMTP via `mail.boxtracker.nl:587` (tls) met de mailbox
  `noreply@boxtracker.nl` (wachtwoord in credentials.md). Mails gaan als HTML met tekst-fallback
  ([app/Views/emails/action.php](app/Views/emails/action.php)).
- SSL: één Let's Encrypt-certificaat (autorenew) voor boxtracker.nl, www., mail. en app.
  SPF, DKIM (selector `x`) en DMARC (`p=none`) staan in DNS, ingesteld door zxcs.
- `~/domains/boxtracker.nl/public_html/.htaccess` (niet in git): blokkeert `/app` (404) en
  stuurt sticker-URL's `boxtracker.nl/d/*` door naar `https://app.boxtracker.nl/d/*` (302).
  Stuurt `http://boxtracker.nl` nog **niet** door naar https.
- Landingspagina + privacyverklaring (`landing/`) staan live via `bash deploy-landing.sh`.

## Whitelabel (bedrijven op `<sub>.boxtracker.nl`)

Plan: [whitelabel-plan.md](whitelabel-plan.md). Eén codebase, twee ingangen:

- **Ingang = hostnaam** ([app/Libraries/Tenant.php](app/Libraries/Tenant.php), `tenant()`):
  klant-app (app., hoofddomein, gereserveerde subdomeinen) of een bedrijf. Onbekend subdomein →
  404 ([TenantFilter](app/Filters/TenantFilter.php)). Nergens anders naar hostnamen kijken.
- **Scheiding** in [Access](app/Libraries/Access.php): alleen verhuizingen van de eigen ingang
  (`bedrijf_id` NULL = particulier). Planner/sales = admin in alle verhuizingen van hun bedrijf;
  inpakker (helper), sjouwer en bewoner via `memberships`. Lektests:
  `tests/database/BedrijfScopingTest.php`, `BeheerTest.php`, `PlanningTest.php` — elke nieuwe
  bedrijfsroute krijgt een test "bedrijf B kan hier niet bij".
- **Blokkeren** (beheer): softblock = geen nieuwe verhuizingen; hardblock = medewerkers (en hun
  gasten) kunnen nergens bij. Klanten (bewoners) merken van beide niets.
- **Beheer** op app.boxtracker.nl/beheer voor `platform_admins` (met de hand vullen): bedrijven,
  blokkeren met memo, eerste planner uitnodigen, meekijken (alleen-lezen, gelogd in `beheer_log`).
- **Kantoor** op `<sub>/bedrijf` (planner, sales): planning, verhuizing aanmaken/toewijzen,
  medewerkers, opname met risico's. **Opname** op `/opname` (bewoner, inpakker).
- **Huisstijl** = handwerk per klant: `public/merken/<subdomein>/merk.css` + `logo.svg` (zie het
  fictieve demomerk `public/merken/kwiek`). Stickers krijgen dan logo en subdomein, de QR wijst
  naar het subdomein. Nooit een echt merk live zonder toestemming.
- **Wildcard-subdomein** `*.boxtracker.nl` moet in het zxcs-paneel naar `public_html/app/public`
  wijzen (nog regelen vóór de eerste klant).
- **Lokaal testen**: `boxtracker.tenantDomein = localtest.me` in `.env` (staat er), dan
  http://kwiek.localtest.me:8080. **Op dev**: `boxtracker.devBedrijf = <subdomein>` in de server-`.env`
  laat heel dev dat bedrijf zijn (`/beheer` werkt dan niet; weer leegmaken).
- **Schema-upgrades** voor bestaande databases: `sql/upgrade-2026-09-*.sql` (eenmalig), plus
  `sql/schema.sql` voor nieuwe tabellen. Dev en prd zijn bijgewerkt t/m de opname-tabellen: prd
  nog niet voor `opname_*` (draai `sql/schema.sql` vóór de volgende prd-deploy).

## Bewaking en back-up

- **`/health`** ([Controllers/Health.php](app/Controllers/Health.php)): JSON met database, opslag
  en mail als ok/fout; 200 of 503. Voor een externe uptimecheck (UptimeRobot / Better Stack) elke
  minuut op https://app.boxtracker.nl/health, plus de inlogpagina.
- **Back-up op de server**: [scripts/backup.sh](scripts/backup.sh) (database + uploads naar
  `~/backups/boxtracker`, 14 dagen). Cronjob instellen in het zxcs-paneel (geen crontab via SSH).
- **Buiten zxcs**: [scripts/backup-ophalen.sh](scripts/backup-ophalen.sh) op een eigen machine of
  NAS, dagelijks. Eén keer een terugzet-test doen.

## Repository

GitHub: `git@github.com:appzguru/boxtrackerboy.git` (zie credentials.md). `master`, `familie`
en tag `familie-v1` staan erop; de SSH-sleutel van deze machine hangt aan het account appzguru.
De geschiedenis bevat de v1-pincodes (`sql/schema.sql` op `familie`) — geaccepteerd, v1 verdwijnt.
