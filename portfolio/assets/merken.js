/* Fictieve merken voor de portfolio-demo. Alle namen, logo's en domeinen zijn verzonnen.
   tekst: overschrijft [data-t="…"] in de schermen; ontbrekende sleutels = app-standaard. */
window.MERKEN = [
  {
    id: 'boxtracker', knop: ['#936037', '#fff'], naam: 'Boxtracker', soort: 'origineel', domein: 'boxtracker.nl',
    concept: 'De standaardlook: kraftpapier, dikke lijnen, alles in één oogopslag.',
    fonts: '', letters: 'Geist · Geist Mono',
    kleuren: ['#936037', '#F3E9D8', '#1A140E', '#FFFDF8'],
    mark: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7.5l9-4.5 9 4.5v9l-9 4.5-9-4.5z"/><path d="M3 7.5l9 4.5 9-4.5M12 12v9"/></svg>',
    tekst: {}
  },
  {
    id: 'vandam', knop: ['#1E4D3A', '#F5F0E3'], naam: 'Van Dam & Zn.', soort: 'verhuizer', domein: 'vandam.boxtracker.nl',
    concept: 'Familiebedrijf sinds 1962. Klassiek en betrouwbaar: flessengroen, goud, schreefletters en een kasboek-overzicht.',
    fonts: 'fraunces:400,400i,500,500i,600,700|source-sans-3:400,600,700', letters: 'Fraunces · Source Sans 3',
    kleuren: ['#1E4D3A', '#B8903A', '#F5F0E3', '#1D2B24'],
    mark: '<svg viewBox="0 0 32 32"><path d="M16 2l12 4v9c0 8-5.4 13-12 15C9.4 28 4 23 4 15V6z" fill="currentColor"/><path d="M16 5.2l9.2 3.1v6.9c0 6.4-4.1 10.4-9.2 12-5.1-1.6-9.2-5.6-9.2-12V8.3z" fill="none" stroke="#B8903A" stroke-width="1"/><text x="16" y="20" text-anchor="middle" font-family="Fraunces, Georgia, serif" font-weight="600" font-size="11" fill="#B8903A">VD</text></svg>',
    tekst: {
      home_titel: 'Waar staat<br>uw inboedel?', zoek_placeholder: 'Zoek op inhoud, naam of nummer',
      stat_open: 'Onderweg', stat_opslag: 'In depot', stat_uit: 'Op zijn plek',
      btn_verplaatsen: 'Inboedel verplaatsen', btn_labels: 'Etiketten',
      lbl_eigenaar: 'Van', ploeg_wie: 'Verhuizer Kees', sticker_hint: 'vertrek',
      sticker_uitleg: 'Onze verhuizers scannen elke doos bij in- en uitladen. U ziet thuis precies waar alles staat.'
    }
  },
  {
    id: 'kwiek', knop: ['#FFD23F', '#FF5A4E'], naam: 'Kwiek Verhuist', soort: 'verhuizer', domein: 'kwiek.boxtracker.nl',
    concept: 'Jong, snel en een beetje brutaal. Koraal, zonnegeel, dikke randen en kaarten die scheef mogen hangen.',
    fonts: 'baloo-2:600,700,800|nunito:400,600,700,800', letters: 'Baloo 2 · Nunito',
    kleuren: ['#FF5A4E', '#FFD23F', '#B79CFF', '#2A1A4A'],
    mark: '<svg viewBox="0 0 32 32"><path d="M2 11h6M1 16h6M2 21h6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><circle cx="19" cy="16" r="12" fill="currentColor"/><path d="M15.5 9.5v13M15.5 17l6.5-6.5M17.6 15l5 7.5" stroke="#fff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>',
    tekst: {
      home_titel: 'Waar is al<br>je spul?', zoek_placeholder: 'Zoek: lego, jassen, 12…',
      stat_open: 'dozen onderweg', stat_opslag: 'geparkeerd', stat_uit: 'klaar!',
      btn_verplaatsen: 'Let’s go: verplaatsen', tag_eerst: 'Eerst open!',
      ploeg_wie: 'Crew Kees', sticker_hint: 'kamer',
      sticker_uitleg: 'Plakken, scannen, klaar. Onze crew weet per doos in welke kamer hij moet.'
    }
  },
  {
    id: 'noordlicht', knop: ['#F6F6F3', '#1F2328'], naam: 'Noordlicht Movers', soort: 'verhuizer', domein: 'noordlicht.boxtracker.nl',
    concept: 'Scandinavisch minimalisme. Veel lucht, dunne lijnen, lijsten in plaats van tegels, één rustig blauw.',
    fonts: 'manrope:200,300,400,500,600', letters: 'Manrope',
    kleuren: ['#36577A', '#F6F6F3', '#1F2328', '#E4E4DF'],
    mark: '<svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="16" cy="16" r="13.5"/><path d="M6.5 21c3-6.5 6.2-9.5 9.5-9.5s6.5 3 9.5 9.5"/><path d="M10.5 22.5c1.9-3.8 3.7-5.6 5.5-5.6s3.6 1.8 5.5 5.6"/></svg>',
    tekst: {
      home_titel: 'Alles op<br>zijn plek.', zoek_placeholder: 'Zoeken',
      stat_open: 'Onderweg', stat_opslag: 'Opgeslagen', stat_uit: 'Uitgepakt',
      btn_verplaatsen: 'Verplaatsen', btn_overzicht: 'Overzicht →', btn_labels: 'Labels →',
      ploeg_wie: 'Team Kees', sticker_uitleg: 'Eén label per doos. Scan het, en je weet waar hij thuishoort.'
    }
  },
  {
    id: 'grachten', knop: ['#14213D', '#fff'], naam: 'De Grachtenverhuizer', soort: 'verhuizer', domein: 'grachtenverhuizer.boxtracker.nl',
    concept: 'Amsterdams, zelfverzekerd. Marineblauw met Andreaskruis-rood, een kopblok als gevel en scherpe hoeken.',
    fonts: 'dm-serif-display:400|dm-sans:400,500,700', letters: 'DM Serif Display · DM Sans',
    kleuren: ['#14213D', '#C8102E', '#F3EFE6', '#FFFFFF'],
    mark: '<svg viewBox="0 0 32 32"><path d="M7 31V15h2.5v-3.5H12V8h2.5V3.5h3V8H20v3.5h2.5V15H25v16z" fill="currentColor"/><rect x="14" y="11" width="4" height="5.5" rx="2" fill="#F3EFE6"/><g stroke="#C8102E" stroke-width="1.7" stroke-linecap="round"><path d="M10.2 21.5l2.6 2.6M12.8 21.5l-2.6 2.6M14.7 21.5l2.6 2.6M17.3 21.5l-2.6 2.6M19.2 21.5l2.6 2.6M21.8 21.5l-2.6 2.6"/></g></svg>',
    tekst: {
      home_titel: 'Van gracht<br>tot gracht.', zoek_placeholder: 'Zoek een doos',
      stat_open: 'Op de boot', stat_opslag: 'In het pakhuis', stat_uit: 'Binnen',
      btn_verplaatsen: 'Dozen verplaatsen', plek_nu: 'Verhuiswagen · Prinsengracht', ploeg_wie: 'Schipper Kees',
      sticker_uitleg: 'Ook op drie hoog achter: onze sjouwers zien per doos welke verdieping en kamer.'
    }
  },
  {
    id: 'vonk', knop: ['#0D0F14', '#C8FF2E'], naam: 'Vonk Verhuizingen', soort: 'verhuizer', domein: 'vonk.boxtracker.nl',
    concept: 'Elektrisch en digitaal. Donkere modus, neongroen, cijfers in code-letters en voortgangsbalken die gloeien.',
    fonts: 'space-grotesk:400,500,700|jetbrains-mono:400,500,700', letters: 'Space Grotesk · JetBrains Mono',
    kleuren: ['#C8FF2E', '#0D0F14', '#161A22', '#EEF1F6'],
    mark: '<svg viewBox="0 0 32 32"><path d="M18.5 2L6 18h8.5l-2.5 12L26 13h-9z" fill="currentColor"/></svg>',
    tekst: {
      home_titel: 'Live<br>overzicht.', zoek_placeholder: 'zoek / doos / plek',
      stat_open: 'in transit', stat_opslag: 'in storage', stat_uit: 'done',
      btn_verplaatsen: 'Scan &amp; verplaats', status_ingepakt: '● Ingepakt',
      ploeg_wie: 'Crew 07 · Kees', sticker_uitleg: '100% elektrisch verhuizen, 100% digitaal bijgehouden. Scan en de status staat live.'
    }
  },
  {
    id: 'zorgzaam', knop: ['#E6EFE9', '#3F6B57'], naam: 'Zorgzaam Verhuizen', soort: 'verhuizer', domein: 'zorgzaam.boxtracker.nl',
    concept: 'Voor senioren en zorgverhuizingen. Grote letters, rustige kleuren, alles onder elkaar en veel ruimte om te tikken.',
    fonts: 'atkinson-hyperlegible:400,700', letters: 'Atkinson Hyperlegible',
    kleuren: ['#3F6B57', '#E6EFE9', '#FAF7F2', '#222222'],
    mark: '<svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"><path d="M4 15L16 5l12 10v13H4z"/><path d="M16 24.5s-6-3.6-6-7.6A3.1 3.1 0 0 1 16 15.3a3.1 3.1 0 0 1 6 1.6c0 4-6 7.6-6 7.6z" fill="currentColor" stroke="none"/></svg>',
    tekst: {
      home_titel: 'Waar staan<br>uw spullen?', zoek_placeholder: 'Typ wat u zoekt',
      stat_open: 'dozen nog dicht', stat_opslag: 'dozen in opslag', stat_uit: 'dozen uitgepakt',
      btn_verplaatsen: 'Dozen verplaatsen', lbl_eigenaar: 'Van wie', ploeg_wie: 'Uw verhuizer: Kees',
      sticker_uitleg: 'Op elke doos een duidelijk nummer. U of uw familie ziet op de telefoon precies wat erin zit.'
    }
  },
  {
    id: 'boxbunker', knop: ['#E4E7EB', '#2E353F'], naam: 'BoxBunker Opslag', soort: 'opslag', domein: 'boxbunker.boxtracker.nl',
    concept: 'Industriële self-storage. Staalgrijs, signaaloranje, waarschuwingsstrepen en smalle hoofdletters.',
    fonts: 'barlow:400,500,600,700|barlow-condensed:600,700,800|ibm-plex-mono:400,500,600', letters: 'Barlow Condensed · Barlow · IBM Plex Mono',
    kleuren: ['#FF6B00', '#2E353F', '#E4E7EB', '#1C2127'],
    mark: '<svg viewBox="0 0 32 32"><path d="M16 2l12.5 7v14L16 30 3.5 23V9z" fill="currentColor"/><rect x="10" y="11" width="12" height="10" rx="1" fill="none" stroke="#FF6B00" stroke-width="2"/><path d="M10 16h12M16 11v10" stroke="#FF6B00" stroke-width="2"/></svg>',
    tekst: {
      home_titel: 'Wat ligt<br>waar?', zoek_placeholder: 'Zoek op inhoud of doosnr.',
      stat_open: 'Open', stat_opslag: 'In unit', stat_uit: 'Opgehaald',
      btn_verplaatsen: 'Inslaan / uitslaan', btn_labels: 'Labels printen',
      vh_naam: 'Unit B-14 · Jansen', plek_nu: 'Laadperron 2', plek_naar: 'Unit B-14 · vak 3', plek_van: 'Ophaaladres Jansen',
      lbl_moetnaar: 'Naar vak', inhoud: 'Kerstversiering, de piek en twee lichtsnoeren',
      reis_1: 'Magazijn Ahmed · vandaag 08:42', reis_2: 'Opgehaald door BoxBunker · gisteren 16:10', ploeg_wie: 'Magazijn Ahmed',
      sticker_hint: 'vak', sticker_uitleg: 'Elke doos in je unit een nummer. Nooit meer de hele box leeghalen voor die ene doos.'
    }
  },
  {
    id: 'zolderruimte', knop: ['#0E7C7B', '#fff'], naam: 'Zolderruimte', soort: 'opslag', domein: 'zolderruimte.boxtracker.nl',
    concept: 'Vriendelijke opslag om de hoek. Petrol en mosterdgeel, zachte schaduwen, geen harde lijnen.',
    fonts: 'poppins:400,500,600,700', letters: 'Poppins',
    kleuren: ['#0E7C7B', '#F2B134', '#EFF7F6', '#12302F'],
    mark: '<svg viewBox="0 0 32 32"><path d="M3 15.5L16 4.5l13 11" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/><rect x="9" y="15.5" width="14" height="12" rx="2.2" fill="#F2B134"/><path d="M9 20.5h14M16 15.5v5" stroke="#12302F" stroke-width="1.4"/></svg>',
    tekst: {
      home_titel: 'Wat staat er<br>in je box?', zoek_placeholder: 'Zoek in je box',
      stat_open: 'Open', stat_opslag: 'In je box', stat_uit: 'Weer thuis',
      btn_verplaatsen: 'Dozen verplaatsen', vh_naam: 'Box 27 · Jansen',
      plek_nu: 'Bij de balie', plek_naar: 'Box 27 · bovenste plank', plek_van: 'Thuis',
      lbl_moetnaar: 'Gaat naar', inhoud: 'Zomerkleding, zwemspullen en de strandtent',
      reis_1: 'Balie Fatima · vandaag 08:42', reis_2: 'Ingepakt door Sanne · gisteren 20:30', ploeg_wie: 'Balie Fatima',
      sticker_uitleg: 'Een sticker op elke doos en je vindt alles terug, ook na een jaar.'
    }
  }
];
