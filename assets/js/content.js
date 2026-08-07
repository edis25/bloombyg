// content/site.json'daki içeriği sayfaya uygular.
// HTML'deki mevcut içerik varsayılandır; JSON farklıysa üzerine yazılır.
// Panelden yapılan düzenlemeler bu dosya sayesinde siteye yansır.
(function () {
  var get = function (obj, path) {
    return path.split(".").reduce(function (o, k) {
      return o && o[k] !== undefined ? o[k] : undefined;
    }, obj);
  };

  fetch("content/site.json?t=" + Date.now())
    .then(function (r) { return r.ok ? r.json() : null; })
    .then(function (data) {
      if (!data) return;

      document.querySelectorAll("[data-bind]").forEach(function (el) {
        var v = get(data, el.getAttribute("data-bind"));
        if (typeof v === "string" && v !== el.textContent) el.textContent = v;
      });

      document.querySelectorAll("[data-bind-src]").forEach(function (el) {
        var v = get(data, el.getAttribute("data-bind-src"));
        if (typeof v === "string" && !el.getAttribute("src").endsWith(v)) el.setAttribute("src", v);
      });

      document.querySelectorAll("[data-bind-href]").forEach(function (el) {
        var v = get(data, el.getAttribute("data-bind-href"));
        if (typeof v === "string") el.setAttribute("href", v);
      });

      // telefon: metin + tel: linki
      var phone = get(data, "contact.phone");
      if (phone) {
        document.querySelectorAll("[data-bind-phone]").forEach(function (el) {
          el.textContent = phone;
          el.setAttribute("href", "tel:+9" + "0" + phone.replace(/\D/g, "").replace(/^0/, ""));
        });
      }

      // instagram: @kullanıcı + profil linki
      var ig = get(data, "contact.instagram");
      if (ig) {
        document.querySelectorAll("[data-bind-instagram]").forEach(function (el) {
          if (el.getAttribute("data-bind-instagram") === "handle") el.textContent = "@" + ig;
          el.setAttribute("href", "https://instagram.com/" + ig);
        });
      }

      // özel davetler listesi
      var listEl = document.querySelector("[data-bind-list]");
      var list = listEl && get(data, listEl.getAttribute("data-bind-list"));
      if (listEl && Array.isArray(list) && list.length) {
        listEl.innerHTML = "";
        list.forEach(function (item) {
          var li = document.createElement("li");
          li.textContent = item;
          listEl.appendChild(li);
        });
      }

      // galeri
      var track = document.querySelector("[data-bind-gallery]");
      var gallery = track && get(data, track.getAttribute("data-bind-gallery"));
      if (track && Array.isArray(gallery) && gallery.length) {
        var current = Array.from(track.querySelectorAll("img")).map(function (im) {
          return im.getAttribute("src");
        });
        if (JSON.stringify(current) !== JSON.stringify(gallery)) {
          track.innerHTML = "";
          gallery.forEach(function (src) {
            var im = document.createElement("img");
            im.src = src;
            im.loading = "lazy";
            im.alt = "Bloom'dan kare";
            track.appendChild(im);
          });
        }
      }

      // slider 1 videoları (masaüstü/mobil kaynakları güncelle)
      var v1 = document.querySelector("video[data-src-desktop]");
      if (v1) {
        var s1 = data.slider && data.slider.slide1;
        if (s1) {
          if (s1.video_desktop) v1.dataset.srcDesktop = s1.video_desktop;
          if (s1.video_mobile) v1.dataset.srcMobile = s1.video_mobile;
          if (s1.poster_desktop) v1.dataset.posterDesktop = s1.poster_desktop;
          if (s1.poster_mobile) v1.dataset.posterMobile = s1.poster_mobile;
          var mobile = window.matchMedia("(max-width: 720px)").matches;
          var want = mobile ? v1.dataset.srcMobile : v1.dataset.srcDesktop;
          if (want && !v1.getAttribute("src").endsWith(want)) {
            v1.poster = mobile ? v1.dataset.posterMobile : v1.dataset.posterDesktop;
            v1.setAttribute("src", want);
            v1.load();
            v1.play().catch(function () {});
          }
        }
      }

      // özel davetler videosu
      var ev = document.querySelector("[data-bind-video]");
      if (ev) {
        var src = get(data, ev.getAttribute("data-bind-video"));
        var poster = get(data, ev.getAttribute("data-bind-video-poster") || "");
        if (poster) ev.poster = poster;
        if (src && !ev.getAttribute("src").endsWith(src)) {
          ev.setAttribute("src", src);
          ev.load();
          ev.play().catch(function () {});
        }
      }
    })
    .catch(function () { /* JSON yoksa HTML'deki varsayılan içerik kalır */ });
})();
