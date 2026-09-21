/* Verkleint een foto client-side (max 1600px lange zijde, JPEG 0.8) en uploadt
 * 'm met een voortgangsbalk. Zie handoff.md §7. */
window.Boxtracker = window.Boxtracker || {};

Boxtracker.compressImage = function (file, maxSize, quality) {
  return new Promise(function (resolve, reject) {
    var img = new Image();
    var reader = new FileReader();
    reader.onload = function (e) {
      img.onload = function () {
        var w = img.width, h = img.height;
        if (w > h && w > maxSize) { h = Math.round(h * maxSize / w); w = maxSize; }
        else if (h > maxSize) { w = Math.round(w * maxSize / h); h = maxSize; }
        var canvas = document.createElement('canvas');
        canvas.width = w; canvas.height = h;
        canvas.getContext('2d').drawImage(img, 0, 0, w, h);
        canvas.toBlob(function (blob) { resolve(blob); }, 'image/jpeg', quality);
      };
      img.onerror = reject;
      img.src = e.target.result;
    };
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
};

/** Voegt meteen een tegel met voortgangsbalk toe aan `grid`, en vervangt die door de echte foto na upload. */
Boxtracker.uploadPhoto = function (file, uploadUrl, grid) {
  var tile = document.createElement('div');
  tile.style.cssText = 'position:relative;aspect-ratio:1/1;border-radius:16px;overflow:hidden;background:#E4F1EC;';
  tile.innerHTML = '<div style="position:absolute;inset:0;background:rgba(255,255,255,.85);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;">' +
    '<div style="font-size:12px;font-weight:600;">Uploaden</div>' +
    '<div class="progress-track" style="width:60%;"><div class="progress-bar" style="width:4%;"></div></div></div>';
  grid.insertBefore(tile, grid.firstChild);
  var bar = tile.querySelector('.progress-bar');

  Boxtracker.compressImage(file, 1600, 0.8).then(function (blob) {
    var fd = new FormData();
    fd.append('foto', blob, 'foto.jpg');
    fd.append('csrf_token', Boxtracker.csrfCookie());

    var xhr = new XMLHttpRequest();
    xhr.open('POST', uploadUrl);
    xhr.upload.addEventListener('progress', function (e) {
      if (e.lengthComputable) bar.style.width = Math.max(4, Math.round(e.loaded / e.total * 100)) + '%';
    });
    xhr.onload = function () {
      try {
        var res = JSON.parse(xhr.responseText);
        if (res.ok) {
          tile.style.background = 'transparent';
          tile.innerHTML = '<img src="' + res.url + '" alt="Foto van de doos" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">';
        } else {
          tile.innerHTML = '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:12px;color:#A4262C;text-align:center;padding:8px;">Mislukt</div>';
        }
      } catch (err) {
        tile.innerHTML = '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:12px;color:#A4262C;">Mislukt</div>';
      }
    };
    xhr.onerror = function () {
      tile.innerHTML = '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:12px;color:#A4262C;">Mislukt</div>';
    };
    xhr.send(fd);
  });
};
