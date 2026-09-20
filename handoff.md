# Boxtracker — bouwdocument

Verhuis- en opslagregistratie voor dozen. Doel: over een jaar nog steeds weten wat waar staat.

**Stack:** CodeIgniter 4, MySQL, vanilla JS. Geen build-step, geen framework in de frontend.
**Domein:** boxtracker.minisaas.nl
**Context:** inpakken begint nu, verhuizing over ongeveer een jaar, met opslag ertussen. Dozen verplaatsen meerdere keren.

---

## 1. Uitgangspunten

- Toegang via de URL, geen wachtwoorden, geen rechtenniveaus. Wie de link heeft, mag alles. De beveiliging van een doos zit in een niet te raden token per doos.
- Mobiel-eerst. Elk scherm wordt met één hand bediend terwijl de andere een doos vasthoudt.
- Van scan tot opgeslagen verplaatsing: maximaal twee handelingen.
- Meerdere mensen gebruiken de app tegelijk (gezinsleden, helpers). Wie iets doet, wordt vastgelegd via een pincode-account (zie §2) — geen wachtwoord, geen losse naam typen.
- De opslag heeft bereik, dus alles mag online. Geen offline modus.

---

## 2. Accounts en pincode

Geen wachtwoorden, geen gebruikersnamen, geen rollen. Een klein, vooraf vastgelegd groepje mensen (gezin, helpers) heeft elk een 4-cijferige pincode. Intypen van de pincode identificeert wie er bezig is — het is geen toegangsbeveiliging. De beveiliging blijft het token per doos uit §1; wie geen pincode intypt kan alsnog elke doos-URL openen als hij die heeft.

- **Aanmaken:** accounts worden vooraf aangemaakt (seeder of een simpel beheerschermpje), niet via zelfregistratie. Irma krijgt bijvoorbeeld `1234`.
- **Inloggen:** `/login` toont een groot numpad (4 cijfers, één hand bedienbaar). Klopt de pincode, dan wordt een cookie gezet die naar het account wijst. Vervaldatum ver in de toekomst (bijv. 10 jaar) — de cookie gaat niet dood.
- **Overal waar eerder "voorinvullen uit cookie" stond** (inpakker, `door` in `movements`), wordt nu automatisch de naam van het ingelogde account gebruikt.
- **Wisselen van persoon:** een link "niet {naam}?" (`/logout`) wist de cookie en toont opnieuw het numpad. Nodig zodra een toestel gedeeld wordt.
- Geen rollen, geen rechten: elk account mag alles.

### accounts
| kolom | type | opmerking |
|---|---|---|
| id | int PK | |
| naam | varchar(60) | |
| pincode | varchar(4) | 4 cijfers, uniek |
| created_at | datetime | |

### account_sessions
Cookie-waarde is een los token, niet het account-id zelf, zodat een sessie ingetrokken kan worden zonder de pincode te wijzigen (bijvoorbeeld als een toestel kwijtraakt).

| kolom | type | opmerking |
|---|---|---|
| id | int PK | |
| account_id | int FK | |
| token | varchar(64) unique | in de cookie, httponly, langlevend |
| created_at | datetime | |

---

## 3. Labels en URL's

Stickers worden vooruit geprint op A4-stickervellen (8 per vel, 99,1 × 67,7 mm) en op lege dozen geplakt. Een doos bestaat pas in de database zodra hij voor het eerst gescand wordt, of zodra de CSV is ingeladen.

**URL-patroon:** `https://boxtracker.minisaas.nl/d/{nummer}-{token}`

- `nummer` is oplopend, getoond als `#001`
- `token` is 4 willekeurige tekens uit het alfabet `abcdefghjkmnpqrstuvwxyz23456789` (zonder i, l, o, 0 en 1, zodat een code overtypbaar is)
- De sticker toont alleen de QR-code, het nummer en een schrijfregel voor de bestemmingsruimte. De URL staat niet op de sticker.

**CSV-import:** de labelgenerator levert een CSV met kolommen `nummer;code;url`, puntkomma-gescheiden, met BOM. Een importscherm leest die in en maakt lege dozen aan. Import moet idempotent zijn: bestaande nummers worden overgeslagen, niet overschreven.

---

## 4. Datamodel

### boxes
| kolom | type | opmerking |
|---|---|---|
| id | int PK | |
| nummer | int unique | getoond als #001 |
| token | varchar(8) | |
| omschrijving | text | vrije tekst, de inhoud van de doos |
| eigenaar | varchar(60) | van wie zijn de spullen |
| einddoel | varchar(80) | ruimte in het nieuwe huis |
| huidige_locatie | varchar(120) | afgeleid van de laatste verplaatsing, hier gedenormaliseerd voor snel zoeken |
| status | enum | `leeg`, `ingepakt`, `opgeslagen`, `geopend`, `uitgepakt` |
| fragiel | bool | |
| eerst_openen | bool | |
| ingepakt_door | varchar(60) | |
| ingepakt_op | datetime | |
| created_at / updated_at | datetime | |

### movements
| kolom | type | opmerking |
|---|---|---|
| id | int PK | |
| box_id | int FK | |
| van_locatie | varchar(120) | leeg bij de eerste registratie |
| naar_locatie | varchar(120) | |
| door | varchar(60) | |
| op | datetime | |
| batch_id | varchar(36) | null bij losse verplaatsing |

### photos
| kolom | type | opmerking |
|---|---|---|
| id | int PK | |
| box_id | int FK | |
| bestandsnaam | varchar(120) | |
| op | datetime | |

### locations
| kolom | type | opmerking |
|---|---|---|
| id | int PK | |
| naam | varchar(120) unique | bijvoorbeeld `opslag / achterwand / stapel 2` |
| soort | enum | `huis`, `opslag`, `nieuw huis`, `overig` |
| actief | bool | |

Locaties vullen zichzelf aan: een nieuwe naam die wordt ingetypt, wordt opgeslagen en daarna als suggestie aangeboden.

**Let op:** `huidige_locatie` is bewust dubbel opgeslagen. De kolom dient het zoeken, de tabel `movements` is de waarheid en de geschiedenis. Werk ze altijd in dezelfde transactie bij.

---

## 5. Routes

| methode | route | doel |
|---|---|---|
| GET | `/login` | pincode-numpad |
| POST | `/login` | pincode valideren, cookie zetten |
| POST | `/logout` | cookie en sessie verwijderen |
| GET | `/d/{nummer}-{token}` | doospagina, leeg of gevuld |
| POST | `/d/{nummer}-{token}` | inhoud opslaan |
| POST | `/d/{nummer}-{token}/photo` | foto toevoegen |
| POST | `/d/{nummer}-{token}/status` | status wijzigen |
| POST | `/d/{nummer}-{token}/move` | losse verplaatsing |
| GET | `/verplaats` | batchmodus: bestemming kiezen |
| POST | `/verplaats/{batch}/scan` | doos toevoegen aan de batch |
| POST | `/verplaats/{batch}/sluit` | batch afronden |
| GET | `/zoek?q=` | zoeken |
| GET | `/overzicht` | aantallen per locatie en per einddoel |
| GET/POST | `/import` | CSV inlezen |
| GET | `/export` | alles als CSV |

**Tokencontrole:** elke `/d/`-route valideert nummer én token. Een geldig nummer met verkeerd token geeft 404, niet een foutmelding over de code. Anders zijn nummers af te tasten.

---

## 6. Schermen

### 6.1 Doos, leeg
Groot nummer bovenaan. Daaronder:
- omschrijving (textarea, met spraakknop via de Web Speech API waar beschikbaar)
- eigenaar (suggesties uit eerdere invoer)
- einddoel (suggesties uit `locations`, soort `nieuw huis`)
- huidige locatie (voorinvullen met de locatie van de vorige doos in deze sessie)
- inpakker: naam van het ingelogde account (zie §2), geen los invoerveld meer
- fragiel / eerst openen
- camera-knop
- één opslagknop

Opslaan zet de status op `ingepakt`, vult `ingepakt_door` en `ingepakt_op`, en schrijft een eerste regel in `movements` met lege `van_locatie`.

### 6.2 Doos, gevuld
- nummer, status, huidige locatie, einddoel
- omschrijving en foto's
- geschiedenis van verplaatsingen, nieuwste bovenaan
- knoppen: bewerken, foto toevoegen, verplaatsen, status wijzigen

De statusknop `geopend` is belangrijk: bij een jaar opslag wordt er gegarandeerd een doos opengemaakt. Bij het zetten van die status wordt gevraagd wat eruit gehaald is; dat wordt als regel aan de omschrijving toegevoegd met datum.

### 6.3 Verplaatsen (batch)
1. Kies of typ de bestemming.
2. Scanscherm blijft open. Elke gescande doos komt in een lijst met een teller.
3. Al gescande dozen worden genegeerd bij een tweede scan, met een zichtbare melding.
4. Afsluiten legt alle verplaatsingen vast onder één `batch_id`.

Dit is het enige scherm met een doorlopende camera. Gebruik een scanbibliotheek in de browser. De camera-API werkt alleen over https.

### 6.4 Zoeken
Eén veld. Zoekt in omschrijving, eigenaar en nummer. Resultaten tonen nummer, huidige locatie, einddoel en de eerste regel van de omschrijving. Zoeken is de hoofdfunctie tijdens het jaar opslag.

### 6.5 Overzicht
- aantal dozen per huidige locatie
- aantal dozen per einddoel
- aantal per status
- doorklikken geeft de lijst

Dit is het telscherm bij het ophalen: kloppen alle dozen die mee terug moeten?

---

## 7. Batch afronden

Een batch is pas definitief als hij wordt afgesloten. Zolang dat niet gebeurd is, staan de gescande dozen in de batch maar zijn de verplaatsingen nog niet weggeschreven.

- Een openstaande batch blijft zichtbaar als je het scherm per ongeluk sluit; bied hem aan om te hervatten.
- Dubbel scannen van dezelfde doos binnen een batch wordt genegeerd, met een zichtbare melding.
- `batch_id` plus `box_id` is een uniek paar, zodat een dubbele verzending geen dubbele verplaatsing oplevert.

## 8. Foto's

- Verkleinen in de browser vóór het uploaden, maximaal 1600 px aan de lange zijde, JPEG kwaliteit 0,8.
- Opslaan buiten de webroot, uitserveren via een controller.
- Meerdere foto's per doos toegestaan.
- Een foto van de open doos is vaak nuttiger dan een getypte lijst. Maak de camera-knop dus prominent.

---

## 9. Back-up

Een jaar is lang genoeg om een server te verliezen.

- Wekelijkse databasedump naar een bestand, bewaar de laatste acht.
- `/export` levert alle dozen, inhoud en locaties als CSV.
- Foto's meenemen in de back-up.

---

## 10. Bouwvolgorde

1. **Basis** — migraties, pincode-login (§2), CSV-import, doospagina lezen en schrijven, tokencontrole
2. **Foto's** — uploaden, verkleinen, tonen
3. **Batch verplaatsen** — met scanner in de browser
4. **Zoeken en overzichten**
5. **Back-up en export**

Na stap 1 en 2 kan het inpakken beginnen. De rest is pas nodig als de dozen gaan bewegen.

---

## 11. Wat er niet in zit

Bewust weggelaten, om het klein te houden:

- wachtwoorden, rollen en rechten (pincode-accounts zijn er wel, zie §2, maar zonder rechtenniveaus — elk account mag alles)
- inhoud als losse regels per voorwerp; het blijft één vrij tekstveld
- barcodescanners, native apps, notificaties
- offline werken en synchronisatie
- koppelingen met verhuisbedrijven of inboedelverzekeringen