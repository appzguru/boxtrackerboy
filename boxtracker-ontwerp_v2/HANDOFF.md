# Boxtracker — handoff voor Claude Code

Ontwerp: kartonlook, afgeleid van het logo (bruine doos, dikke zwarte lijnen, kraftpapier).
Twee opdrachten in deze handoff:

1. **Website `boxtracker.nl`**: nieuw bouwen, statisch (map `landing/`).
2. **App `app.boxtracker.nl`**: bestaande CodeIgniter 4-views en PWA opnieuw stylen. Functionaliteit blijft zoals hij is.

Werk in die volgorde. Vraag het na als iets in deze handoff botst met bestaande code of routes. Ga niet zelf gokken.

---

## 0. Wat zit er in dit pakket

| Pad | Wat |
|---|---|
| `design/Main.dc.html` | Home, desktop (1440 px), **leidend voor de website** |
| `design/Home-mobiel.dc.html` | Home, mobiel (390 px) |
| `design/Privacy.dc.html` | Privacy, desktop |
| `design/App-*.dc.html` | 13 appschermen (390×844) |
| `assets/logo-512.png` | Logo, origineel 512×512 |
| `assets/hero-scan.jpg` | Herofoto (standaard), 2400 px breed |
| `assets/hero-stapel.jpg`, `hero-inhoud.jpg`, `hero-muur.jpg` | Alternatieve herofoto's (zie §3.3) |
| `assets/qr.svg` | Placeholder-QR (codeert `https://boxtracker.nl/d/195`) |

### Zo lees je de `.dc.html`-bestanden

Dit zijn ontwerpbestanden uit een designtool. Ze zijn **referentie, geen productiecode**:

- Maten, kleuren, afstanden, teksten en volgorde kloppen exact. Neem ze over.
- Alles staat in inline `style="…"`. Zet dat om naar klassen in één stylesheet met de tokens uit §1. Geen inline styles in productie.
- Neem deze tool-onderdelen **niet** over: `<script src="./support.js">`, `<x-dc>`, `<helmet>`, `<sc-if>`, `<sc-for>`, `{{…}}`-placeholders, het `<script type="text/x-dc">`-blok, `data-props`.
- `/_blob/<id>`-adressen zijn afbeeldingen in de designtool. Gebruik de bestanden uit `assets/`:
  - `/_blob/0d50963b…` → `logo-512.png`
  - `/_blob/1d60b883…` → `hero-scan.jpg`
  - `/_blob/356ce45b…` → `hero-stapel.jpg`
  - `/_blob/58f1f938…` → `hero-inhoud.jpg`
  - `/_blob/697d134a…` → `hero-muur.jpg`
  - `/_blob/3750ac98…` → `qr.svg`
- Links naar `Xxx.dc.html` zijn prototype-links tussen schermen. Vertaal ze naar de echte routes (§3.1 en §4).
- De artboards hebben een vaste hoogte. Productie is natuurlijk gewoon vloeiend.

---

## 1. Design tokens

Zet deze in `:root` van zowel de website als de app. Alleen lichte modus.

```css
:root {
  /* kleuren */
  --ink:        #1A140E;  /* tekst, lijnen, harde schaduw */
  --paper:      #F3E9D8;  /* pagina-achtergrond (licht kraft) */
  --card:       #FFFDF8;  /* kaarten, velden */
  --kraft:      #CA9E67;  /* logo-achtergrond: band, kopbalk app */
  --kraft-soft: #E6D2B0;  /* "samen verhuizen"-band, tape */
  --box:        #936037;  /* merkbruin: primaire knop, accent */
  --box-dark:   #6E4424;  /* links, stempel, cijfers in bruin */
  --tint:       #F0DFC4;  /* icoonvakjes */
  --tint-2:     #EBD5B3;  /* helper-badge, "eerst openen" */
  --text-2:     #54483A;  /* broodtekst */
  --text-3:     #6B5C4A;  /* labels, bijschriften */
  --line:       #D2BC98;  /* randen van secundaire knoppen */
  --line-soft:  #EFE3CF;  /* scheidingslijnen in lijsten */
  --field-line: #9A8A74;  /* randen van invoervelden */
  --fragile-bg: #F8E1D9;  --fragile: #A4271B;   /* fragiel-stempel */
  --danger-bg:  #FDECEA;  --danger:  #B42318;   --danger-line: #F4CFCB;
  --ok:         #1A7F4B;

  /* vorm */
  --r-sm: 12px; --r-md: 16px; --r-lg: 20px;
  --outline: 2px solid var(--ink);
  --drop: 0 3px 0 var(--ink);           /* knoppen */
  --drop-lg: 0 5px 0 var(--ink);        /* zwevende kaart in hero */

  /* golfkarton-textuur */
  --corrugated: repeating-linear-gradient(90deg, rgba(120,80,40,.035) 0 2px, transparent 2px 11px);
  --corrugated-strong: repeating-linear-gradient(90deg, rgba(26,20,14,.07) 0 2px, transparent 2px 10px);
}
body { background-color: var(--paper); background-image: var(--corrugated); color: var(--ink); }
```

Contrast is gecontroleerd: wit op `--box` ≈ 5,3:1, `--ink` op `--kraft` ≈ 7,4:1, `--text-3` op `--paper` ≈ 5,3:1. Gebruik voor tekst in het bruin op crème altijd `--box-dark`, niet `--box`.

## 2. Typografie en componenten

**Fonts:** Geist (400/500/600/700) en Geist Mono (400/500/600) via Google Fonts. Doosnummers, aantallen en timers altijd in Geist Mono, 600, letter-spacing negatief (−0,02 tot −0,05 em).

| Rol | Desktop | Mobiel |
|---|---|---|
| H1 hero | 76/1.0, 600, −0.04em | 44/1.02 |
| H2 sectie | 52/1.05, 600, −0.035em | 34/1.08 |
| H3 kaart | 22–24, 600 | 22 |
| Broodtekst | 16–18/1.55 | 15–17 |
| App-titel | 28–32, 600, −0.03em | — |

**Componenten** (alles staat uitgewerkt in de `.dc.html`-bestanden):

- **Primaire knop:** `--box`-vulling, witte tekst, `--outline`, `--drop`, radius 16, hoogte 56 (44 in de nav). Voor `:active`: `translateY(3px)` en de schaduw weghalen, zodat hij "indrukt".
- **Secundaire knop:** `--card`, `inset 0 0 0 1px var(--line)`.
- **Kaart:** `--card`, `--outline`, radius 20 (18 op mobiel).
- **Invoerveld:** hoogte 52, radius 14, `1.5px solid var(--field-line)`, font 17 px (voorkomt inzoomen op iOS). Altijd met `<label>`.
- **Badge:** Admin (`--ink` met `--paper`-tekst), Helper (`--tint-2` met `--box-dark`), Handjes (`--card` met rand). Hoogte 24–30, radius 7–9.
- **Fragiel / eerst openen:** `--fragile-bg`/`--fragile` en `--tint-2`/`--box-dark`.
- **Stempel** ("Voor verhuizen en opslag"): Geist Mono 600, hoofdletters, letter-spacing .08em, `2px solid var(--box-dark)`, radius 6, `rotate(-2deg)`.
- **Plakband:** decoratief, `aria-hidden="true"`, absolute positie, 120–180×36 px, `rgba(214,178,122,.85)`, gedraaid −3° tot 2°, steekt 10 px boven de kaart uit. Staat op de herofoto, de drie stapkaarten en het CTA-blok. Niet vaker gebruiken.
- **Sticker (nagebouwd):** wit label, QR plus nummer in Geist Mono, dunne lijn eronder. Het is beeld, geen stockfoto.
- **Iconen:** inline stroke-SVG, `stroke-width` 1.8–2, `currentColor`, geen emoji. De paden staan in de ontwerpbestanden.

## 3. Website — `boxtracker.nl`

### 3.1 Randvoorwaarden

- Statische HTML en CSS, geen build-stap, geen framework. JavaScript alleen als het echt nodig is (op deze pagina's is het niet nodig).
- Geen trackers, geen analytics, geen cookiebanner. Alleen Google Fonts is extern.
- `/d/*` en `/app` zijn gereserveerd voor stickers en de app. Daar komen geen pagina's.
- Links: "Gratis beginnen" → `https://app.boxtracker.nl/registreren`, "Inloggen" → `https://app.boxtracker.nl/login`.

Voorgestelde structuur:

```
landing/
  index.html
  privacy/index.html
  assets/
    css/style.css
    img/logo-512.png, logo-192.png, favicon-32.png, apple-touch-icon.png
    img/hero-scan-1200.webp, hero-scan-2400.webp (+ jpg-fallback)
    qr.svg
```

### 3.2 Home — secties in volgorde

Bron: `design/Main.dc.html` (desktop) en `design/Home-mobiel.dc.html` (mobiel).

1. **Nav:** logo met woordmerk, anchors (Hoe het werkt `#hoe`, Samen verhuizen `#samen`, Vragen `#faq`), Inloggen, Gratis beginnen. Op mobiel alleen logo en Inloggen (geen hamburger nodig).
2. **Hero:** stempel, H1 "Weet over een jaar nog wat waar staat.", intro, primaire en secundaire knop, drie vinkjes. Daaronder de herofoto (hoogte 600 desktop, 300 mobiel, `--outline` 3 px, plakband) met de zwevende **doos-kaart #195** erop: linksonder op desktop, op mobiel onder de foto met −56 px overlap.
3. **Zo werkt het** (`#hoe`): drie kaarten met plakband: printen (A4-vel), scannen en vullen (formulier), zoeken en vinden (zoekresultaat #047).
4. **Samen verhuizen** (`#samen`): band in `--kraft-soft` met textuur en zwarte lijn boven en onder. Drie rolkaarten (Admin, Helper, Handjes) en het blok "Wat erin zit gaat een sjouwer niets aan" met de vergelijking tussen sjouwer en inpakker.
5. **Wat het kan:** 3×3 kaarten (op mobiel één lijst).
6. **Vertrouwensband:** `--kraft` met sterke textuur. Vier items: Gratis, In Nederland, Geen tracking, Zelf verwijderen.
7. **FAQ** (`#faq`): zes vragen. Gewone koppen en tekst zijn prima; `<details>` mag ook.
8. **CTA:** sticker #001 gedraaid met "Begin voordat de eerste doos dichtgaat." en de knop.
9. **Footer:** logo, tagline "Waar ligt verdorie mijn snelkookpan? Nu weet je het.", links (Privacy, Voor helpers, Stickers printen, Inloggen), © en "Gehost in Nederland · Geen tracking".

**Responsief:** ontworpen op 1440 en 390. Contentbreedte max 1200 px (120 px marge op 1440). Foto, band en CTA lopen tot 32 px van de rand. Breekpunt rond 900 px: grids met 3 kolommen worden 1 kolom (stappen, rollen, features) of 2 kolommen (vertrouwensband). De twee kolommen in de hero worden gestapeld.

### 3.3 Herofoto

- Standaard `hero-scan.jpg`, `object-fit: cover`, `object-position: 60% 50%` (mobiel 62% 50%).
- Alt-tekst: "Iemand scant de QR-sticker op een verhuisdoos met haar telefoon".
- Lever 1200 en 2400 px breed als WebP met jpg-fallback via `srcset`, met `width`/`height` om layoutverschuiving te voorkomen. Gebruik `fetchpriority="high"` en geen lazy loading, want dit is de LCP.
- **Gebruik `hero-muur.jpg` niet**: op alle dozen staat #100. Bij `hero-stapel.jpg` komen nummers dubbel voor op verschillende dozen.

### 3.4 Privacy

Bron: `design/Privacy.dc.html`. Vertaal het naar mobiel zoals de home: de inhoudsopgave gaat boven de tekst in plaats van ernaast.

### 3.5 Metadata

`<html lang="nl">`, een eigen `<title>` en `<meta name="description">` per pagina, `theme-color` `#CA9E67`, favicon en apple-touch-icon gemaakt uit `logo-512.png`, Open Graph met de herofoto.

## 4. App — `app.boxtracker.nl` (CI4-views + PWA)

Alleen de **stijl** verandert: tokens, componenten en layout volgens de schermen. Routes, controllers en logica blijven hetzelfde. Zoek per scherm de bestaande view op. Staat een scherm nog niet in de code, meld dat dan en bouw het niet zelf.

| Ontwerp | Scherm |
|---|---|
| `App-Registreren` | Registreren, met variant voor uitnodiging (banner, geen veld voor verhuisnaam) |
| `App-Inloggen` | Inloggen, met stickermelding als de gebruiker via `/d/*` binnenkomt |
| `App-Wachtwoord` | Wachtwoord vergeten en nieuw wachtwoord |
| `App-Uitnodiging` | Uitnodiging accepteren |
| `App-Kies-verhuizing` | Verhuizing kiezen |
| `App-Menu` | Menu |
| `App-Leden` | Leden en links |
| `App-Handjes` | Handjes: rol kiezen, dagen 1–7 (stepper), actieve gasten |
| `App-Handjes-QR` | QR op volledig scherm met timer van 15 min |
| `App-Gast-welkom` | Gast-welkom (naam invullen) |
| `App-Doos-sjouwer` | Doos in de sjouwer-weergave |
| `App-Account` | Mijn account |
| `App-Verhuizing` | Instellingen van de verhuizing (naam, export, verwijderen) |

**Vaste regels voor elk scherm:**

- Kopbalk van 64 px: `--kraft` met sterke textuur, zwarte lijn van 2 px eronder. Links menu (48×48), in het midden de naam van de verhuizing, rechts de scanknop (48×48, `--card` met `--outline`).
- **Eén primaire actie per scherm**, onderaan op volle breedte, binnen bereik van de duim. Ondermarge: `padding-bottom: calc(28px + env(safe-area-inset-bottom))`.
- Raakvlakken van minstens 44×44 (knoppen 48–56).
- Het doosnummer staat altijd groot bovenaan (88 px Geist Mono in de sjouwer-weergave).
- Sjouwers krijgen **nooit** inhoud of foto's in de HTML, ook niet verborgen. Filter dat op de server.
- Gevarenzones (verwijderen): `--danger`-kleuren en een aparte bevestiging.

**PWA:**

```json
{
  "name": "Boxtracker",
  "short_name": "Boxtracker",
  "start_url": "/",
  "display": "standalone",
  "theme_color": "#CA9E67",
  "background_color": "#F3E9D8",
  "icons": [
    { "src": "/icons/192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/512.png", "sizes": "512x512", "type": "image/png" },
    { "src": "/icons/512-maskable.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
  ]
}
```

Maak de iconen uit `logo-512.png`. Het logo heeft al een volle kraftachtergrond met de doos binnen de veilige zone, dus het werkt ook als maskable icon. Controleer dat de doos binnen de middelste 80% valt. Werk ook de `<meta name="theme-color">` in de app-layout bij.

## 5. Nog in te vullen (placeholders tussen haken)

`[CONTACT-E-MAIL]`, `[TYPE ETIKETPAPIER]`, `[N]` (minimale wachtwoordlengte, neem die uit de validatie in CI4), `[HOSTINGPARTIJ]`, `[BACK-UPTERMIJN]`, `[NAAM / KVK]`, `[NAAM VERANTWOORDELIJKE]`, `[ADRES / KVK]`, `[DATUM]`. Laat de placeholders zichtbaar staan tot Edwin ze invult.

## 6. Aannames in het ontwerp die gecontroleerd moeten worden

Check deze tegen de bestaande app en pas de tekst aan als ze niet kloppen:

- De CSV-export bevat geen foto's.
- Als je je account verwijdert, verdwijnen ook de verhuizingen waar je de enige admin bent.
- Een admin kan handjes "stoppen" en een nieuwe QR-code maken.
- De privacytekst noemt een "versleuteld" wachtwoord. Technisch is dat gehasht.
- Google Fonts geeft het IP-adres van de bezoeker door aan Google. Dat botst met "geen externe partijen". Aanbeveling: host Geist en Geist Mono zelf (woff2, beide OFL). Dan kan de zin over Google Fonts uit de privacytekst.

## 7. Klaar als

- [ ] Home en Privacy komen visueel overeen met de ontwerpen op 1440 en 390 px, zonder horizontale scroll daartussen.
- [ ] Geen inline styles. Alle kleuren komen uit de tokens.
- [ ] Alle knoppen en links zijn te bedienen met toetsenbord en hebben een zichtbare focus (bijv. `outline: 3px solid var(--box-dark); outline-offset: 2px`).
- [ ] Afbeeldingen hebben alt-tekst. Decoratieve tape en stempels hebben `aria-hidden` of `alt=""`.
- [ ] Lighthouse: Performance ≥ 90 op mobiel, Accessibility ≥ 95.
- [ ] Geen requests naar andere domeinen dan Google Fonts (of nul, als je de fonts zelf host).
- [ ] App: alle 13 schermen in de nieuwe stijl, PWA-manifest en iconen bijgewerkt, installatie getest op Android en iOS.
