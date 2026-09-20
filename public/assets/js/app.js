/* Vaste topbar: verbergt bij scrollen naar beneden, komt terug bij scrollen naar boven.
 * Scanknop erin gebruikt dezelfde scanner als de rest van de app (scan.js). */
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
