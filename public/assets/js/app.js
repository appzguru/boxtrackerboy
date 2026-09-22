/* Vaste topbar: verbergt bij scrollen naar beneden, komt terug bij scrollen naar boven.
 * Scanknop erin gebruikt dezelfde scanner als de rest van de app (scan.js). */
window.Boxtracker = window.Boxtracker || {};

/* CSRF-cookie uitlezen voor AJAX-POSTs (fetch/XHR) die geen paginaherlading krijgen,
 * zoals de batch-scanlus en foto-upload. De cookie is bewust niet httponly (CI4's
 * 'cookie'-beschermingsmethode) juist zodat JS 'm hier kan lezen. Elke aanroep leest
 * live uit document.cookie in plaats van een waarde te cachen, want CI4 ververst de
 * token na elke request (regenerate = true in Config/Security.php). */
Boxtracker.csrfCookie = function () {
  var m = document.cookie.match('(?:^|; )csrf_cookie=([^;]*)');
  return m ? decodeURIComponent(m[1]) : '';
};

/* Een pagina kan meerdere formulieren hebben terwijl er ook AJAX-POSTs op diezelfde
 * pagina lopen (foto-upload, batch-scannen). Elke geslaagde POST ververst het
 * CSRF-token server-side (regenerate = true in Config/Security.php), dus het bij
 * paginalaad ingebakken veld van een nog-niet-verstuurd formulier kan intussen
 * verouderd zijn geraakt. Vlak voor elke formulierverzending het veld verversen
 * met de actuele cookie-waarde voorkomt dat zo'n verzending stil faalt (redirect
 * terug, geen zichtbare fout, niets weggeschreven). Capture-phase, want dit moet
 * vóór de browser het formulier daadwerkelijk verstuurt. */
document.addEventListener('submit', function (e) {
  var input = e.target.querySelector && e.target.querySelector('input[name="csrf_token"]');
  if (input) input.value = Boxtracker.csrfCookie();
}, true);

(function () {
  var bar = document.getElementById('app-topbar');
  if (bar) {
    var lastY = window.scrollY;
    var ticking = false;
    window.addEventListener('scroll', function () {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(function () {
        var y = Math.max(0, window.scrollY);
        if (y < 40) {
          bar.classList.remove('hidden');
        } else if (y > lastY + 4) {
          bar.classList.add('hidden');
        } else if (y < lastY - 4) {
          bar.classList.remove('hidden');
        }
        lastY = y;
        ticking = false;
      });
    }, { passive: true });
  }

  var scanBtn = document.getElementById('app-scan-btn');
  var overlay = document.getElementById('scan-overlay');
  if (scanBtn && overlay) {
    scanBtn.addEventListener('click', function () {
      Boxtracker.scanOnce(overlay, function (url) {
        window.location.href = url;
      });
    });
  }
})();
