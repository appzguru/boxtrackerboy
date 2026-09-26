(function () {
  var MERKEN = window.MERKEN;
  var frames = Array.prototype.slice.call(document.querySelectorAll('.phone iframe'));
  var kiezer = document.getElementById('kiezer');
  var huidig = null;

  function fontsUrl(m) { return m.fonts ? 'https://fonts.bunny.net/css?family=' + m.fonts : ''; }

  function zetLink(link, href) {
    if (!link) return;
    if (href) link.href = href; else link.removeAttribute('href');
  }

  function toepassen(doc, m) {
    if (!doc || !doc.getElementById('merk-css')) return; // iframe nog niet geladen
    zetLink(doc.getElementById('merk-css'), '../merken/' + m.id + '.css');
    zetLink(doc.getElementById('merk-fonts'), fontsUrl(m));
    doc.querySelectorAll('.merk-mark').forEach(function (e) { e.innerHTML = m.mark; });
    doc.querySelectorAll('.merk-naam').forEach(function (e) { e.textContent = m.naam; });
    doc.querySelectorAll('.merk-domein').forEach(function (e) { e.textContent = m.domein; });
    doc.querySelectorAll('[data-t]').forEach(function (e) {
      if (e.dataset.orig === undefined) e.dataset.orig = e.innerHTML;
      var t = m.tekst[e.dataset.t];
      e.innerHTML = t !== undefined ? t : e.dataset.orig;
    });
    doc.querySelectorAll('[data-tp]').forEach(function (e) {
      if (e.dataset.orig === undefined) e.dataset.orig = e.placeholder;
      var t = m.tekst[e.dataset.tp];
      e.placeholder = t !== undefined ? t : e.dataset.orig;
    });
  }

  function paneel(m) {
    var kop = m.letters.split(' · ')[0];
    zetLink(document.getElementById('merk-fonts'), fontsUrl(m));
    document.getElementById('m-naam').textContent = m.naam;
    document.getElementById('m-naam').style.fontFamily = "'" + kop + "', var(--sans)";
    document.getElementById('m-domein').textContent = m.domein;
    document.getElementById('m-soort').textContent = { origineel: 'Origineel', verhuizer: 'Verhuizer', opslag: 'Opslag' }[m.soort];
    document.getElementById('m-concept').textContent = m.concept;
    document.getElementById('m-letters').textContent = m.letters;
    document.getElementById('m-kleuren').innerHTML = m.kleuren.map(function (c) { return '<span style="background:' + c + '" title="' + c + '"></span>'; }).join('');
  }

  function kies(id) {
    var m = MERKEN.filter(function (x) { return x.id === id; })[0] || MERKEN[0];
    huidig = m;
    frames.forEach(function (f) { toepassen(f.contentDocument, m); });
    paneel(m);
    kiezer.querySelectorAll('.knop').forEach(function (k) { k.setAttribute('aria-pressed', k.dataset.id === m.id ? 'true' : 'false'); });
    if (history.replaceState) history.replaceState(null, '', '#' + m.id);
  }

  var groepen = [['origineel', ''], ['verhuizer', 'Verhuizers'], ['opslag', 'Opslag']];
  groepen.forEach(function (g) {
    if (g[1]) {
      var label = document.createElement('span');
      label.className = 'kiezer-groep';
      label.textContent = g[1];
      kiezer.appendChild(label);
    }
    MERKEN.filter(function (m) { return m.soort === g[0]; }).forEach(function (m) {
      var k = document.createElement('button');
      k.type = 'button';
      k.className = 'knop';
      k.dataset.id = m.id;
      k.innerHTML = '<span class="knop-mark" style="background:' + m.knop[0] + ';color:' + m.knop[1] + '">' + m.mark + '</span>' + m.naam;
      k.addEventListener('click', function () { kies(m.id); });
      kiezer.appendChild(k);
    });
  });

  frames.forEach(function (f) {
    f.addEventListener('load', function () { if (huidig) toepassen(f.contentDocument, huidig); });
  });

  kies(location.hash.slice(1) || 'boxtracker');
})();
