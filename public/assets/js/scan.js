/* QR-scannen in de browser met jsQR (CDN, alleen geladen als dit bestand gebruikt wordt).
 * Zie handoff.md §5.3 / boxtracker-ontwerp §6.5. */
window.Boxtracker = window.Boxtracker || {};

(function () {
  var jsQRPromise = null;
  function loadJsQR() {
    if (window.jsQR) return Promise.resolve();
    if (jsQRPromise) return jsQRPromise;
    jsQRPromise = new Promise(function (resolve, reject) {
      var s = document.createElement('script');
      s.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
      s.onload = resolve;
      s.onerror = reject;
      document.head.appendChild(s);
    });
    return jsQRPromise;
  }

  /** Trekt "047-ab12" uit een gescande URL of platte tekst. Null als niks herkend wordt. */
  function extractCode(text) {
    var m = String(text).match(/(\d{1,6})-([a-zA-Z0-9]{2,10})(?:$|[/?#])/);
    return m ? { nummer: m[1], token: m[2], code: m[1] + '-' + m[2] } : null;
  }
  Boxtracker.extractCode = extractCode;

  /**
   * Start een camera + decodeloop op de gegeven <video>. Roept onDecode(text, code) aan
   * voor elk gedecodeerde frame (de aanroeper ontdubbelt zelf). Geeft een stop-functie terug.
   */
  Boxtracker.startScanner = function (video, canvas, onDecode, onState) {
    var stream = null, raf = null, stopped = false;
    var ctx = canvas.getContext('2d', { willReadFrequently: true });

    function tick() {
      if (stopped) return;
      if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        var img = ctx.getImageData(0, 0, canvas.width, canvas.height);
        var result = window.jsQR(img.data, img.width, img.height);
        if (result && result.data) {
          var code = extractCode(result.data);
          if (code) onDecode(result.data, code);
        }
      }
      raf = requestAnimationFrame(tick);
    }

    loadJsQR().then(function () {
      return navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
    }).then(function (s) {
      stream = s;
      video.srcObject = stream;
      video.play();
      onState('ok');
      raf = requestAnimationFrame(tick);
    }).catch(function (err) {
      onState(err && err.name === 'NotAllowedError' ? 'denied' : 'error');
    });

    return function stop() {
      stopped = true;
      if (raf) cancelAnimationFrame(raf);
      if (stream) stream.getTracks().forEach(function (t) { t.stop(); });
    };
  };

  /** Fullscreen scan-overlay voor één scan; roept onResult(url) aan en ruimt zichzelf op. */
  Boxtracker.scanOnce = function (container, onResult) {
    container.innerHTML =
      '<div style="position:fixed;inset:0;background:#0F1216;z-index:50;display:flex;flex-direction:column;">' +
      '<div style="position:relative;flex:1;overflow:hidden;">' +
      '<video playsinline muted style="width:100%;height:100%;object-fit:cover;"></video>' +
      '<canvas style="display:none;"></canvas>' +
      '<div style="position:absolute;left:0;right:0;bottom:24px;text-align:center;color:#fff;font-size:14px;">Richt op de QR-code op de sticker</div>' +
      '</div>' +
      '<button type="button" style="height:64px;color:#fff;font-size:16px;font-weight:600;text-align:center;">Annuleren</button>' +
      '</div>';
    var video = container.querySelector('video');
    var canvas = container.querySelector('canvas');
    var stop = Boxtracker.startScanner(video, canvas, function (text, code) {
      stop();
      container.innerHTML = '';
      onResult('/d/' + code.nummer + '-' + code.token);
    }, function () {});
    container.querySelector('button').addEventListener('click', function () {
      stop();
      container.innerHTML = '';
    });
  };
})();
