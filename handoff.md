# Boxtracker — bouwdocument v2 (openbaar, meerdere verhuizingen)

Verhuis- en opslagregistratie voor dozen. Doel: over een jaar nog steeds weten wat waar staat.
Vanaf v2 kan iedereen een account aanmaken, een verhuizing starten en anderen laten meehelpen.

**Stack:** CodeIgniter 4, MySQL, vanilla JS. Geen build-step, geen framework in de frontend.
**Domeinen:**
- `boxtracker.nl` — statische landingspagina + doorverwijzing van sticker-URL's naar de app
- `app.boxtracker.nl` — de app (CI4, document root `public/`)
- `boxtracker.minisaas.nl` — de oude familie-versie (v1). Blijft draaien voor de eigen verhuizing,
  alleen bugfixes, eigen database. Code: tag `familie-v1` / branch `familie`. Geen datamigratie
  naar v2 nodig.

**Naamgeving:** in de app heet het product gewoon *Boxtracker*. Het ding waar je lid van bent heet
in de code en in communicatie (mails, uitnodigingen) een **verhuizing**. Een verhuizing heeft een
naam die de admin kiest (bijv. "Veenboer"); die naam staat in de wisselaar.

---

## 1. Uitgangspunten

- Mobiel-eerst. Elk scherm wordt met één hand bediend terwijl de andere een doos vasthoudt.
- Van scan tot opgeslagen verplaatsing: maximaal twee handelingen.
- Meerdere mensen werken tegelijk aan dezelfde verhuizing. Wie iets doet, wordt vastgelegd.
- **Verhuizingen zijn strikt van elkaar gescheiden.** Elke query is gescoped op de actieve
  verhuizing. Een doos, locatie, foto of batch van een andere verhuizing is voor jou een 404.
- Toegang tot een doos vereist een rol in de verhuizing van die doos. De sticker-URL alleen
  is niet meer genoeg (anders dan v1).
- Meedoen moet licht zijn: niet iedereen hoeft een account (zie handjes-QR, §3).
- Alles online, geen offline modus. Gratis; als het uit de hand loopt, grijpen we in (limieten, §9).

---

## 2. Rollen

Een rol hoort bij **(persoon, verhuizing)**, niet bij een persoon. Wie zelf een verhuizing start
is daar admin; in de verhuizing van een ander kan dezelfde persoon helper zijn.

| | **Admin** | **Helper** (inpakker) | **Sjouwer** |
|---|---|---|---|
| Hoe erin | zelf registreren / gepromoveerd | uitnodigingslink (account) of handjes-QR | alleen handjes-QR |
| Account nodig | ja | via link: ja, via QR: nee | nee |
| Doosnummer, bestemming (einddoel), huidige locatie, fragiel, eerst openen, status | ✅ | ✅ | ✅ |
| Omschrijving, foto's, eigenaar, geschiedenis | ✅ | ✅ | ❌ |
| Doos vullen / bewerken / foto toevoegen / status | ✅ | ✅ | ❌ |
| Verplaatsen (los + batch-scan) | ✅ | ✅ | ✅ |
| Zoeken | ✅ | ✅ | alleen op nummer |
| Overzicht / telscherm | ✅ | ✅ | ✅ aantallen, lijst zonder omschrijving |
| Labels genereren/printen | ✅ | ✅ | ❌ |
| Doos verwijderen, export | ✅ | ❌ | ❌ |
| Leden, uitnodigingen, handjes, verhuizing beheren | ✅ | ❌ | ❌ |

- **Meerdere admins** per verhuizing toegestaan. Admin kan een helper promoveren en een admin
  degraderen. Er moet altijd minstens één admin overblijven.
- Zoeken is voor sjouwers beperkt tot nummer, omdat zoeken op omschrijving de inhoud zou lekken.
- De rechten worden op één plek afgedwongen: een route-filter met argument
  (`role:admin`, `role:helper` = helper of hoger, `role:sjouwer` = iedereen met toegang),
  plus in de doos-view het verbergen van inhoud voor sjouwers.

---

## 3. Accounts en toegang

### 3.1 Registreren en inloggen (accounts)
- `/registreren`: naam, e-mail, wachtwoord. Maakt een account + een eerste verhuizing waarvan je
  admin bent (naam vragen in hetzelfde formulier, voorinvullen met achternaam-achtige default).
- Verificatiemail met link. Zolang niet geverifieerd: app wel bruikbaar, balk "bevestig je e-mail".
  (Niet blokkeren — drempel laag houden. Wel verplicht vóór uitnodigen van anderen.)
- `/login`: e-mail + wachtwoord. Sessie-cookie langlevend (1 jaar, verlengd bij gebruik), token in
  `sessions`, niet het user-id zelf. Rate limit: max 10 pogingen per 15 min per IP + e-mail.
- Wachtwoord vergeten: mail met resetlink (1 uur geldig, eenmalig).
- Wachtwoorden met `password_hash()` (default algoritme).
- `/account`: naam, e-mail, wachtwoord wijzigen, account verwijderen (zie §9).

### 3.2 Uitnodigingslink (helper met account)
- Admin drukt "Iemand uitnodigen", kiest rol (helper of admin), krijgt een **link** om zelf te
  delen (WhatsApp, mail, …). Geen e-mailadres nodig vooraf.
- Link is 7 dagen geldig en **eenmalig** bruikbaar.
- Openen: niet ingelogd → registreren (kort formulier, géén eigen verhuizing aangemaakt) of
  inloggen; daarna direct lid. Wel ingelogd → "Word helper bij verhuizing Veenboer?" → ja.
- Openstaande links zijn zichtbaar op `/leden` en in te trekken.

### 3.3 Handjes-QR (helper of sjouwer zonder account)
1. Admin drukt "Handjes toevoegen", kiest rol (**sjouwer** of **inpakker/helper**) en duur (1–7
   dagen). Er verschijnt een grote QR-code op het scherm.
2. De QR-code zelf is **15 minuten** geldig en meermaals scanbaar (een ploeg sjouwers scant om de
   beurt). Een doorgestuurde foto van de QR werkt na een kwartier dus niet meer.
3. Scannen → "Hoe heet je?" (één veld) → cookie op dat toestel met een gast-sessie die de gekozen
   dagen geldig is. De naam wordt gebruikt als `door` bij verplaatsingen / `ingepakt_door`.
4. Admin ziet op `/handjes` wie er actief is (naam, rol, verloopt op, laatst gezien) en kan
   intrekken.
5. Een gast-sessie geeft toegang tot precies één verhuizing. Geen wisselaar, geen account-pagina.

### 3.4 Actieve verhuizing kiezen
- Na inloggen: één verhuizing → direct erin. Meer → keuzescherm. Laatste keuze wordt onthouden.
- Bovenaan elk scherm: naam van de actieve verhuizing + wisselknop (alleen bij >1).
- **Scan van een sticker uit een andere verhuizing** waar je lid van bent → automatisch wisselen
  (het token is globaal uniek, dus de verhuizing is bekend). Geen lid → 404.
- Wie via een uitnodiging binnenkomt heeft geen eigen verhuizing; op het keuzescherm staat
  "Zelf een verhuizing starten".

---

## 4. Labels en URL's

**Sticker-URL:** `https://boxtracker.nl/d/{nummer}-{token}` — bijv. `boxtracker.nl/d/124-k7m3p9`

- `nummer`: oplopend **per verhuizing** (elke verhuizing begint bij #001), getoond als `#124`.
- `token`: 6 tekens uit `abcdefghjkmnpqrstuvwxyz23456789` (31⁶ ≈ 887 mln), **globaal uniek**.
  Opzoeken gebeurt op token; het nummer moet erbij kloppen, anders 404 (geen hint welk deel fout is).
- In de QR staat bewust het **hoofddomein**: `boxtracker.nl/d/*` wordt via `.htaccess` doorgestuurd
  naar `app.boxtracker.nl/d/*`. Zo blijven stickers werken als de app ooit verhuist.
- Sticker toont QR, nummer en een schrijfregel voor de bestemming (zoals v1).
- Labels genereren maakt lege dozen aan in de actieve verhuizing (max 60 per keer).
- CSV-import/-export (`nummer;code;url`) werkt per verhuizing.

---

## 5. Datamodel

Canonieke bron blijft `sql/schema.sql` (idempotent, geen CI4-migraties). v2 is een verse database;
de v1-tabellen `accounts` / `account_sessions` en de pincode-seed vervallen.

### users
| kolom | type | opmerking |
|---|---|---|
| id | int PK | |
| naam | varchar(60) | |
| email | varchar(190) unique | |
| password_hash | varchar(255) | |
| email_verified_at | datetime null | |
| created_at | datetime | |

### user_tokens
Eenmalige tokens voor e-mail bevestigen (7 dagen) en wachtwoord reset (1 uur). Alleen de
sha256-hash staat in de database: `user_id`, `soort` enum(`verify`,`reset`), `token_hash`,
`expires_at`, `used_at`.

### sessions
| kolom | type | opmerking |
|---|---|---|
| id | int PK | |
| user_id | int FK | cascade |
| token | char(64) unique | in de cookie, httponly, secure, SameSite=Lax |
| active_verhuizing_id | int null | laatst gekozen verhuizing |
| created_at / last_used_at | datetime | |

### verhuizingen
| kolom | type | opmerking |
|---|---|---|
| id | int PK | |
| naam | varchar(80) | |
| created_by | int FK users | |
| created_at | datetime | |

### memberships
| kolom | type | opmerking |
|---|---|---|
| id | int PK | |
| verhuizing_id | int FK | cascade |
| user_id | int FK | cascade |
| rol | enum(`admin`,`helper`) | |
| created_at | datetime | |
| | | unique (verhuizing_id, user_id) |

### invites
| kolom | type | opmerking |
|---|---|---|
| id | int PK | |
| verhuizing_id | int FK | cascade |
| token | char(32) unique | in de link |
| rol | enum(`admin`,`helper`) | |
| created_by | int FK users | |
| expires_at | datetime | +7 dagen |
| used_by / used_at | int null / datetime null | eenmalig |
| revoked_at | datetime null | |

### guest_passes (de QR)
| kolom | type | opmerking |
|---|---|---|
| id | int PK | |
| verhuizing_id | int FK | cascade |
| code | char(32) unique | in de QR |
| rol | enum(`helper`,`sjouwer`) | |
| access_days | tinyint | 1–7 |
| code_expires_at | datetime | +15 min |
| created_by | int FK users | |
| created_at | datetime | |

### guest_sessions
| kolom | type | opmerking |
|---|---|---|
| id | int PK | |
| guest_pass_id | int FK | |
| verhuizing_id | int FK | cascade |
| rol | enum(`helper`,`sjouwer`) | kopie van de pass |
| naam | varchar(60) | zelf ingevuld |
| token | char(64) unique | in de cookie |
| expires_at | datetime | |
| revoked_at | datetime null | |
| created_at / last_used_at | datetime | |

### boxes (gewijzigd t.o.v. v1)
- `+ verhuizing_id` int FK (cascade)
- `nummer` unique → **unique (verhuizing_id, nummer)**
- `token` varchar(8) → **unique** (globaal)
- `ingepakt_door` blijft een naam-snapshot (werkt voor accounts én gasten)
- rest ongewijzigd (omschrijving, eigenaar, einddoel, huidige_locatie, status, fragiel,
  eerst_openen, ingepakt_op, timestamps)

### locations (gewijzigd)
- `+ verhuizing_id` int FK (cascade)
- `naam` unique → **unique (verhuizing_id, naam)**

### movements, photos
`+ verhuizing_id` (cascade). Ze horen via `box_id` al bij een verhuizing, maar krijgen de kolom
ook zelf zodat elke query er direct op scopet (§6). `door` blijft een naam-snapshot.
`huidige_locatie` en `movements` altijd in dezelfde transactie bijwerken (zoals v1).

---

## 6. Scoping — het belangrijkste risico

Eén vergeten `where verhuizing_id = ?` = data van een ander zichtbaar. Daarom:

- Eén `AccessFilter` bepaalt per request: wie (user of gast), welke verhuizing, welke rol. Resultaat
  in een request-scoped `Access`-object (`access()->verhuizingId()`, `->rol()`, `->naam()`, `->can()`).
- Models voor verhuizing-data (`BoxModel`, `LocationModel`, …) gaan via een basisclass die
  **standaard** op `access()->verhuizingId()` filtert en `verhuizing_id` bij insert zet. Een query
  zonder scope moet expliciet (`->unscoped()`), zodat hij opvalt in review.
- Relaties via `box_id` (movements, photos): altijd eerst de doos gescoped ophalen.
- **Foto's**: `/foto/{id}` staat in v1 buiten de login-filter en gebruikt oplopende id's → in v2
  achter de filter, scoped via de doos, niet toegankelijk voor sjouwers. Bestanden per verhuizing
  in `writable/uploads/{verhuizing_id}/`.
- **Batch verplaatsen**: batch in de sessie krijgt `verhuizing_id`; gescande dozen van een andere
  verhuizing worden geweigerd ("hoort niet bij deze verhuizing").
- **Lektest** (PHPUnit, feature-test): twee verhuizingen met data, per rol en per route controleren
  dat niets van de ander zichtbaar of wijzigbaar is. Draait bij elke fase mee.

---

## 7. Routes (app.boxtracker.nl)

| methode | route | rol | doel |
|---|---|---|---|
| GET/POST | `/registreren` | – | account + eerste verhuizing |
| GET/POST | `/login` | – | inloggen |
| POST | `/logout` | – | sessie weg |
| GET/POST | `/wachtwoord-vergeten` | – | resetmail |
| GET/POST | `/wachtwoord/{token}` | – | nieuw wachtwoord |
| GET | `/verifieer/{token}` | – | e-mail bevestigen |
| GET | `/uitnodiging/{token}` | – | uitnodiging bekijken/accepteren |
| GET/POST | `/h/{code}` | – | handjes-QR: naam invullen, gast-sessie |
| GET | `/menu` | sjouwer+ | menu (leden, handjes, wisselen, account, uitloggen) |
| GET | `/verhuizingen` | account | keuzescherm |
| POST | `/verhuizingen` | account | nieuwe verhuizing starten |
| POST | `/verhuizingen/{id}/kies` | account | actieve verhuizing wisselen |
| GET/POST | `/account` | account | profiel, wachtwoord, verwijderen |
| GET | `/leden` | admin | leden + openstaande links |
| POST | `/leden/uitnodigen` | admin | uitnodigingslink maken |
| POST | `/leden/{id}/rol` · `/leden/{id}/verwijderen` | admin | |
| POST | `/uitnodigingen/{id}/intrekken` | admin | |
| GET | `/handjes` | admin | actieve gasten |
| POST | `/handjes` | admin | QR maken (rol, dagen) → toont QR |
| POST | `/handjes/{id}/intrekken` | admin | |
| GET/POST | `/verhuizing` | admin | naam wijzigen, verwijderen, export |
| GET | `/d/{nummer}-{token}` | sjouwer+ | doospagina (inhoud alleen helper+). Route-filter `access:any`: de verhuizing volgt uit het token, de rol wordt in de controller gecontroleerd |
| POST | `/d/{nummer}-{token}` | helper+ | inhoud opslaan |
| POST | `/d/…/photo` · `/d/…/status` | helper+ | |
| POST | `/d/…/move` | sjouwer+ | losse verplaatsing |
| POST | `/d/…/verwijderen` | admin | |
| GET | `/foto/{id}` | helper+ | foto (gescoped) |
| GET/POST | `/verplaats…` | sjouwer+ | batch verplaatsen |
| GET | `/zoek` | sjouwer+ | sjouwer: alleen nummer |
| GET | `/overzicht…` | sjouwer+ | |
| GET/POST | `/labels…` | helper+ | |
| GET/POST | `/import` | admin | |
| GET | `/export` | admin | |

Niet ingelogd op een `/d/`-URL → login-scherm met "vraag de admin om een uitnodiging of
handjes-QR", daarna terug naar de doos.

---

## 8. Schermen (nieuw of gewijzigd t.o.v. v1)

De v1-schermen (doos leeg/gevuld, batch, zoeken, overzicht) blijven qua opzet gelijk; ze krijgen
de verhuizing-naam + wisselaar in de kop en respecteren de rol.

- **Doos voor sjouwer**: groot nummer, grote bestemming (einddoel), fragiel/eerst openen als
  opvallende badges, huidige locatie, knop "Verplaatsen". Geen omschrijving, foto's, eigenaar.
- **Leden**: lijst met naam + rol (wijzigen/verwijderen), knop "Iemand uitnodigen" → link met
  deelknop (Web Share API, fallback kopiëren).
- **Handjes**: knop "Handjes toevoegen" → rol + dagen → schermvullende QR met aftellende 15 min;
  daaronder actieve gasten met intrek-knop.
- **Keuzescherm verhuizingen**: kaarten met naam, jouw rol, aantal dozen.
- **Gast-welkom** (`/h/{code}`): "Je helpt bij verhuizing Veenboer als sjouwer. Hoe heet je?"

---

## 9. Foto's, limieten en privacy

- Max **3 foto's per doos**. Verkleinen in de browser: max **1280 px** lange zijde, JPEG kwaliteit
  **0,7** (≈ 120–180 KB). Geen WebP: Safari kan dat niet encoderen via canvas.
- Server controleert ook: max 1 MB per upload, alleen JPEG, anders weigeren.
- Opslag buiten de webroot, per verhuizing in een eigen map, uitserveren via controller.
- Limieten (instelbaar in config): max 10 verhuizingen per account, max 1000 dozen per verhuizing,
  max 60 labels per keer. Ruim genoeg voor normaal gebruik, remt misbruik.
- **Verwijderen**: admin kan een verhuizing verwijderen (alle dozen, foto's, leden, gasten weg —
  bevestigen door de naam te typen). Account verwijderen: kan alleen als je nergens de enige admin
  bent van een verhuizing met andere leden (anders eerst overdragen of verhuizing verwijderen).
- Privacyverklaring op boxtracker.nl (foto's van persoonlijke spullen = persoonsgegevens-achtig).
- **Inactiviteit — vereist voor v2, nog niet gebouwd**: de privacyverklaring belooft dat een account
  automatisch verdwijnt na 6 maanden niet inloggen (`users` × `sessions.last_used_at`, die al bestaat).
  Nodig: een geplande taak (cron) die dit uitvoert via dezelfde route als handmatig account verwijderen
  (`Account::delete`, incl. het cascaderen van verhuizingen waar de gebruiker de enige bij is).
  **Open vraag, nog niet besloten:** wat gebeurt er met een inactieve gebruiker die de enige admin is
  van een verhuizing met andere leden? Handmatig verwijderen wordt daar nu geblokkeerd (er moet eerst
  een andere admin komen) — dat kan een geautomatiseerde taak niet vragen. Opties: (a) die gebruiker
  overslaan totdat het is opgelost (verwijdert dan nooit vanzelf), (b) automatisch de langst-actieve
  medeadmin/helper promoveren en dan pas verwijderen, (c) een waarschuwingsmail sturen vóór de
  deadline. De privacytekst noemt bewust geen waarschuwingsmail — als die er komt, moet de tekst mee.
- Back-up-bewaartermijn (§11): dagelijkse dump, laatste 14 bewaard — dit getal staat al zo in de
  privacyverklaring, dus bij wijziging daar ook aanpassen.

---

## 10. Mail

SMTP via zxcs, afzender `noreply@boxtracker.nl`. Mails: e-mail bevestigen, wachtwoord reset.
Uitnodigingen gaan **niet** per mail (admin deelt de link zelf), dus mail is geen harde
afhankelijkheid voor samenwerken. SPF/DKIM voor boxtracker.nl instellen.

---

## 11. Back-up

- Dagelijkse databasedump, bewaar de laatste 14. Foto-map mee in de back-up.
- `/export` per verhuizing (CSV) voor de admin.

---

## 12. Bouwvolgorde

**Stand 2026-09-25:** 0–6 gebouwd en lokaal getest (curl-rooktest + `tests/database/ScopingTest.php`).
Van 7 zijn account/verhuizing verwijderen en de limieten klaar; landingspagina en privacyverklaring
nog niet. 8 wacht op DNS/hosting voor boxtracker.nl.

0. **Voorbereiden** — openstaande v1-wijzigingen committen, tag `familie-v1`, branch `familie`
   (daar deployt minisaas voortaan vanaf). v2 op `main`.
1. **Schema + scoping** — nieuwe `schema.sql`, `Access`-object + filter, gescopede basis-model,
   bestaande controllers omzetten, lektest. Tijdelijk een seed-user om mee te testen.
2. **Accounts** — registreren, inloggen, verificatie, wachtwoord vergeten, mail, rate limit. Pincode weg.
3. **Verhuizingen + uitnodigingen + rollen** — keuzescherm, wisselaar, leden, uitnodigingslinks,
   rechten per route en in de views (sjouwer-weergave van de doos).
4. **Handjes-QR** — guest_passes, guest_sessions, `/h/{code}`, beheerscherm.
5. **Nieuwe tokens en labels** — 6-teken globaal token, labelgenerator, CSV, auto-wissel bij scan.
6. **Foto's** — limiet 3, 1280 px / 0,7, per-verhuizing-map, gescoped uitserveren.
7. **Openbaar** — landingspagina + privacyverklaring op boxtracker.nl, account/verhuizing
   verwijderen, limieten.
8. **Livegang** — `app.boxtracker.nl` (document root `public/`, HTTPS), `boxtracker.nl` + `.htaccess`
   redirect `/d/*`, SMTP + SPF/DKIM, tweede doel in `deploy.php`, CLAUDE.md bijwerken.

---

## 13. Wat er niet in zit

- betaalde abonnementen (gratis; limieten in §9 als rem)
- inloggen met Google/Apple, magic links
- mails voor uitnodigingen (link wordt zelf gedeeld)
- rechten fijner dan de drie rollen
- inhoud als losse regels per voorwerp; het blijft één vrij tekstveld
- native apps, notificaties, offline werken
- migratie van de v1-data (minisaas blijft apart draaien)
