// Header shadow on scroll
const header = document.querySelector(".site-header");
window.addEventListener("scroll", () => {
  header.classList.toggle("is-scrolled", window.scrollY > 10);
});

// Mobile nav toggle (hamburger)
const toggle = document.querySelector(".nav-toggle");
const nav = document.querySelector(".header-nav");
if (toggle && nav) {
  const setOpen = (open) => {
    nav.classList.toggle("is-open", open);
    toggle.classList.toggle("is-open", open);
    toggle.setAttribute("aria-expanded", open);
  };
  toggle.addEventListener("click", () => setOpen(!nav.classList.contains("is-open")));
  nav.querySelectorAll("a").forEach((a) =>
    a.addEventListener("click", () => setOpen(false))
  );
}

// Responsive hero video: desktop/mobile source switching
document.querySelectorAll("video[data-src-desktop]").forEach((video) => {
  const mq = window.matchMedia("(max-width: 720px)");
  const apply = () => {
    const mobile = mq.matches;
    const src = mobile ? video.dataset.srcMobile : video.dataset.srcDesktop;
    const poster = mobile ? video.dataset.posterMobile : video.dataset.posterDesktop;
    if (video.getAttribute("src") !== src) {
      video.poster = poster;
      video.setAttribute("src", src);
      video.load();
      video.play().catch(() => {});
    }
  };
  apply();
  mq.addEventListener("change", apply);
});

// Hero slider
const slider = document.querySelector(".hero-slider");
if (slider) {
  const slides = Array.from(slider.querySelectorAll(".hero-slide"));
  const dotsWrap = slider.querySelector(".hero-dots");
  let current = 0;
  let timer;

  slides.forEach((_, i) => {
    const dot = document.createElement("button");
    dot.setAttribute("aria-label", `Slayt ${i + 1}`);
    dot.addEventListener("click", () => goTo(i, true));
    dotsWrap.appendChild(dot);
  });
  const dots = Array.from(dotsWrap.children);

  // videolu slaytlar video süresi kadar (en fazla 40 sn) ekranda kalır,
  // görselli slaytlar 6.5 sn sonra geçer
  function slideDuration(slide) {
    const video = slide.querySelector("video");
    if (!video) return 6500;
    const d = video.duration;
    return d && isFinite(d) ? Math.min(d * 1000, 40000) : 15000;
  }

  function goTo(i, manual = false) {
    current = (i + slides.length) % slides.length;
    slides.forEach((s, j) => {
      s.classList.toggle("is-active", j === current);
      const video = s.querySelector("video");
      if (video && video.readyState > 0) {
        if (j === current) {
          video.currentTime = 0;
          video.play().catch(() => {});
        } else {
          video.pause();
        }
      }
    });
    dots.forEach((d, j) => d.classList.toggle("is-active", j === current));
    restart();
  }

  function restart() {
    clearTimeout(timer);
    timer = setTimeout(() => goTo(current + 1), slideDuration(slides[current]));
  }

  slider.querySelector(".hero-arrow--prev").addEventListener("click", () => goTo(current - 1, true));
  slider.querySelector(".hero-arrow--next").addEventListener("click", () => goTo(current + 1, true));

  // video süresi sonradan öğrenilirse aktif slaytın zamanlamasını güncelle
  slides.forEach((s, j) => {
    const video = s.querySelector("video");
    if (video) video.addEventListener("loadedmetadata", () => { if (j === current) restart(); });
  });

  goTo(0);
}

// Galeri ok tuşları
const galleryTrack = document.querySelector(".gallery__track");
if (galleryTrack) {
  const step = () => {
    const img = galleryTrack.querySelector("img");
    return img ? img.clientWidth + 18 : 320;
  };
  document.querySelector(".gallery-arrow--prev")?.addEventListener("click", () =>
    galleryTrack.scrollBy({ left: -step() * 2, behavior: "smooth" })
  );
  document.querySelector(".gallery-arrow--next")?.addEventListener("click", () =>
    galleryTrack.scrollBy({ left: step() * 2, behavior: "smooth" })
  );
}

// Scroll reveal
const observer = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.12 }
);

document.querySelectorAll(".reveal").forEach((el) => observer.observe(el));
