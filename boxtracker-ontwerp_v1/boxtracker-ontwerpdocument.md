# Boxtracker — ontwerpdocument

Voor het ontwerpen van de schermen. De technische kant staat in het bouwdocument; dit document gaat over wat de gebruiker ziet en doet.

---

## 1. Waar dit voor is

Een gezin pakt nu al dozen in voor een verhuizing die over ongeveer een jaar plaatsvindt. De dozen gaan eerst naar een opslag en blijven daar maanden staan. Daarna gaan ze naar het nieuwe huis.

De app beantwoordt drie vragen:

1. Wat zit er in deze doos?
2. Waar staat doos 47 nu?
3. Waar ligt verdorie mijn orgel?

Elke doos heeft een geprinte sticker met een QR-code en een nummer. Scannen opent de pagina van die doos. Er is geen app-installatie en geen instellingenscherm. Wel een pincode: een keer 4 cijfers intypen om te zeggen wie je bent, daarna onthoudt een cookie dat voorgoed (zie §6.0). Dat is geen beveiliging — de code is puur om te weten wie iets doet, niet om toegang te regelen.

## 2. Wie het gebruikt

- **Edwin en zijn gezin**, die de meeste dozen inpakken en alles willen kunnen terugvinden.
- **Helpers op verhuisdag**, die de app nooit eerder hebben gezien en een doos scannen terwijl ze hem vasthouden.

De tweede groep bepaalt het ontwerp. Alles moet begrijpelijk zijn zonder uitleg, met één hand, in een gang, met een doos onder de arm.

## 3. Uitgangspunten voor het ontwerp

- **Mobiel is de norm.** Desktop is een uitzondering, alleen handig bij zoeken en overzichten.
- **Lichte modus.** Schermen worden ook in een donkere opslagbox gebruikt, waar een licht scherm bijlicht.
- **Grote raakvlakken.** Vingers, handschoenen, haast.
- **Eén hoofdactie per scherm**, duidelijk zichtbaar, onderaan binnen duimbereik.
- **Het doosnummer is de ankerpunt.** Het staat altijd groot bovenaan, zodat je het scherm en de sticker in één blik kunt vergelijken.
- **Tekst is kort en actief.** Knoppen zeggen wat er gebeurt: "Doos opslaan", niet "Verzenden".

---

## 4. Wat de app kan

### Doos vullen
Een gescande lege doos vragen om inhoud, eigenaar, bestemming en huidige plek. Vastleggen wie hem inpakte en wanneer. Foto's toevoegen. Markeren als fragiel of als doos die als eerste open moet.

### Doos bekijken
Alles wat over een doos bekend is: inhoud, foto's, waar hij staat, waar hij heen moet, en zijn hele reis tot nu toe.

### Verplaatsen in serie
Een bestemming kiezen en daarna dozen achter elkaar scannen. Alles gaat in één keer naar de nieuwe plek.

### Losse verplaatsing
Eén doos verplaatsen vanaf zijn eigen pagina.

### Doos openen tijdens de opslag
Registreren dat er iets uit een doos is gehaald, zodat de inhoud blijft kloppen.

### Zoeken
Zoeken op inhoud, eigenaar of nummer. Het antwoord is altijd: dit nummer, op deze plek.

### Overzicht
Aantallen per plek, per bestemming en per status. Het telscherm voor verhuisdag.

### Labels en import
Stickers worden vooruit geprint. Een CSV vult de app met lege dozen.

---

## 5. De processen

### 5.1 Doos inpakken

1. Helper pakt een sticker, plakt hem op een lege doos.
2. Scant de code met de telefooncamera.
3. Ziet: **#047 — nieuwe doos**.
4. Typt of dicteert wat erin zit.
5. Kiest eigenaar en bestemming.
6. Maakt eventueel een foto van de open doos.
7. Slaat op.
8. Ziet een korte bevestiging met het nummer, en de mogelijkheid om meteen de volgende doos te scannen.

Wat het ontwerp hier moet oplossen: stap 4 is het enige dat echt werk kost. Alles eromheen moet voorgeïnvuld of één tik zijn. Eigenaar, bestemming en huidige plek staan meestal al goed omdat de vorige doos hetzelfde had.

### 5.2 Dozen verplaatsen

1. Gebruiker opent de app en kiest "Dozen verplaatsen".
2. Kiest of typt de nieuwe plek.
3. Krijgt een scanscherm dat openblijft.
4. Scant doos na doos. Elke doos verschijnt in een lijst, met een teller die oploopt.
5. Sluit af.
6. Ziet: 18 dozen staan nu in de opslag.

Wat het ontwerp hier moet oplossen: dit gebeurt tijdens het sjouwen. Het scherm moet vanaf een meter afstand leesbaar zijn, de bevestiging per scan moet opvallen zonder dat je hoeft te kijken (geluid of trilling), en een dubbele scan moet duidelijk maar niet storend zijn.

### 5.3 Iets zoeken

1. Gebruiker opent de app, typt "orgel".
2. Ziet: **#012 — opslag, achterwand, stapel 2**.
3. Tikt door voor de volledige inhoud en de foto.

Wat het ontwerp hier moet oplossen: het antwoord is de plek, niet de doos. De plek moet het grootste element in het resultaat zijn.

### 5.4 Een doos openen in de opslag

1. Gebruiker scant een doos, haalt er iets uit.
2. Tikt op "Doos geopend".
3. Noteert kort wat eruit is.
4. De inhoud van de doos klopt weer.

---

## 6. De schermen

### 6.0 Inloggen (pincode)
Het scherm dat je ziet zolang er nog geen cookie staat — daarna kom je hier nooit meer, tenzij je zelf wisselt van persoon.

- Groot numpad, 4 cijfers, één hand bedienbaar
- Geen namenlijst, geen "wie ben ik" — gewoon de code intypen
- Klopt de code niet: kort en rustig opnieuw laten proberen, geen foutcode
- Na een geslaagde code: rechtstreeks door naar waar je heen wilde (gescande doos, of het startscherm)

Vanaf elk scherm waar de naam van de gebruiker zichtbaar is (bijvoorbeeld bij "wie pakte deze doos in"), staat een kleine link "niet {naam}?" die terugbrengt naar dit scherm — voor als een toestel gedeeld wordt.

### 6.1 Startscherm
Het scherm waar je terechtkomt als je de app opent zonder te scannen.

- Zoekveld, prominent
- Grote knop: Dozen verplaatsen
- Drie getallen: hoeveel dozen ingepakt, hoeveel in de opslag, hoeveel uitgepakt
- Kleine ingang naar het overzicht en naar de labels

**Leeg**: er zijn nog geen dozen. Toon wat er moet gebeuren: labels printen en importeren.

### 6.2 Doospagina — nieuwe doos
Het belangrijkste scherm van de app.

- Nummer groot bovenaan, met de melding dat dit een nieuwe doos is
- Invoerveld voor de inhoud, met knop om te dicteren
- Eigenaar (keuze uit eerder gebruikte namen, plus vrij invullen)
- Bestemming in het nieuwe huis (idem)
- Huidige plek (voorgevuld)
- Twee schakelaars: fragiel, eerst openen
- Fotoknop
- Naam van de inpakker: komt automatisch uit het ingelogde account (zie §6.0), klein getoond, geen los invoerveld meer
- Onderaan vast: Doos opslaan

**Toestanden**: leeg formulier, tijdens het dicteren, foto wordt verkleind en geüpload, opslaan bezig, opgeslagen.

### 6.3 Doospagina — bestaande doos

- Nummer groot bovenaan, met status
- Waar hij nu staat, als grootste informatie na het nummer
- Waar hij heen moet
- Inhoud
- Foto's, aantikbaar om te vergroten
- Reis van de doos: elke verplaatsing met plek, persoon en datum
- Acties: verplaatsen, bewerken, foto toevoegen, doos geopend

**Toestanden**: normaal, tijdens bewerken, foto wordt geladen, doos is al uitgepakt (dan is het scherm rustiger en zijn acties minder prominent).

### 6.4 Verplaatsen — bestemming kiezen

- Lijst met eerder gebruikte plekken, meest recente bovenaan
- Veld om een nieuwe plek te typen
- Doorgaan naar scannen

### 6.5 Verplaatsen — scanscherm
Het scherm dat het meest onder tijdsdruk wordt gebruikt.

- Camerabeeld
- Groot en vast in beeld: de gekozen bestemming
- Teller: hoeveel dozen tot nu toe
- Lijst met de gescande nummers, nieuwste bovenaan
- Afsluiten

**Toestanden**: camera vraagt toestemming, camera geweigerd (met uitleg hoe het alsnog kan), doos herkend (kort, opvallend, met trilling), doos al gescand (andere melding, niet als fout gebracht), onbekende code.

### 6.6 Verplaatsen — klaar

- Wat er is gebeurd: 18 dozen staan nu in de opslag
- Lijst van de dozen
- Terug naar start, of meteen een nieuwe ronde beginnen

### 6.7 Zoekresultaten

- Per resultaat: nummer, huidige plek als grootste element, bestemming, eerste regel van de inhoud
- Aantikbaar naar de doospagina

**Leeg**: niets gevonden. Bied aan om in alle dozen te bladeren, en herinner eraan dat de inhoud alleen vindbaar is als die is ingevuld.

### 6.8 Overzicht

- Per plek: aantal dozen, aantikbaar naar de lijst
- Per bestemming: aantal dozen
- Per status: aantal dozen
- Filter op eigenaar

Dit scherm wordt ook op een laptop bekeken, dus het mag op een breed scherm meer tegelijk tonen.

### 6.9 Dozenlijst
De lijst achter elk getal uit het overzicht.

- Compacte rijen: nummer, inhoud in één regel, plek, markeringen
- Sorteren op nummer of op laatste wijziging

### 6.10 Labels importeren

- Uitleg in twee zinnen
- Bestand kiezen
- Voortgang tijdens het inlezen
- Resultaat: hoeveel dozen toegevoegd, hoeveel overgeslagen omdat ze al bestonden

### 6.11 Foutpagina's

- **Onbekende of ongeldige code**: geen technische taal. Deze sticker hoort niet bij deze app, of de code is verkeerd overgetypt. Met een zoekveld eronder.
- **Server onbereikbaar**: wat je nu kunt doen, en een knop om het opnieuw te proberen.

---

## 7. Laden, wachten en bevestigen

- **Pagina laden**: toon meteen de vorm van het scherm met lege vlakken op de plek van de inhoud, niet een draaiend rondje in het midden.
- **Opslaan**: knop wordt inactief en toont dat hij bezig is. Nooit twee keer kunnen indrukken.
- **Foto uploaden**: voortgang per foto. De rest van het formulier blijft bruikbaar.
- **Scan herkend**: de bevestiging moet merkbaar zijn zonder te kijken. Trilling plus een duidelijke verandering op het scherm.
- **Bevestiging na opslaan**: kort en concreet, met het nummer erin. Daarna direct de volgende actie aanbieden, want dit werk gebeurt in series.
- **Fouten**: leg uit wat er misging en wat de gebruiker nu kan doen. Geen excuses, geen foutcodes.

---

## 8. Wat er niet in zit

- Wachtwoorden, rollen en rechten — de pincode uit §6.0 identificeert alleen, ze beveiligt niets en iedereen mag alles
- Inhoud als afvinkbare lijst per voorwerp; het is één tekstveld
- Notificaties
- Offline werken
- Instellingenscherm

Als een ontwerp een van deze dingen nodig lijkt te hebben, is er iets te ingewikkeld geworden.
