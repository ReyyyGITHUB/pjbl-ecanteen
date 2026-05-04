(() => {
  const button = document.querySelector(".hamburger");
  const navbar = document.querySelector(".navbar");
  const panel = document.querySelector(".nav-panel");
  const menu = document.querySelector(".nav-links");
  const backdrop = document.querySelector(".nav-backdrop");

  if (!button || !navbar || !panel || !menu || !backdrop) return;

  const setExpanded = (expanded) => {
    button.setAttribute("aria-expanded", expanded ? "true" : "false");
    navbar.classList.toggle("is-open", expanded);
    panel.setAttribute("aria-hidden", expanded ? "false" : "true");
    backdrop.setAttribute("aria-hidden", expanded ? "false" : "true");
    document.body.classList.toggle("no-scroll", expanded);
    if (expanded) {
      const firstLink = menu.querySelector("a");
      if (firstLink) firstLink.focus();
    } else {
      button.focus();
    }
  };

  button.addEventListener("click", () => {
    const isOpen = navbar.classList.contains("is-open");
    setExpanded(!isOpen);
  });

  backdrop.addEventListener("click", () => setExpanded(false));

  menu.addEventListener("click", (e) => {
    const target = e.target;
    if (!(target instanceof Element)) return;
    if (target.closest("a")) setExpanded(false);
  });

  window.addEventListener("keydown", (e) => {
    if (e.key === "Escape") setExpanded(false);
  });

  // Scrollspy (active link highlight)
  const sections = ["beranda", "tentang", "cara-pakai", "testimoni"]
    .map((id) => document.getElementById(id))
    .filter(Boolean);

  const links = Array.from(menu.querySelectorAll("a"));
  const setActive = (id) => {
    links.forEach((a) => a.classList.toggle("is-active", a.getAttribute("href") === `#${id}`));
  };

  const syncActiveFromScroll = () => {
    if (window.scrollY < 80) {
      setActive("beranda");
      return;
    }

    let best = null;
    let bestDist = Number.POSITIVE_INFINITY;
    for (const s of sections) {
      const r = s.getBoundingClientRect();
      const dist = Math.abs(r.top - 96);
      if (dist < bestDist) {
        bestDist = dist;
        best = s;
      }
    }
    if (best && best.id) setActive(best.id);
  };

  // Update active immediately on click (prevents "stuck active" feel)
  links.forEach((a) => {
    a.addEventListener("click", () => {
      const href = a.getAttribute("href") || "";
      if (href.startsWith("#")) setActive(href.slice(1));
      window.setTimeout(syncActiveFromScroll, 350);
    });
  });

  if ("IntersectionObserver" in window && sections.length > 0) {
    const observer = new IntersectionObserver(
      () => syncActiveFromScroll(),
      { rootMargin: "-20% 0px -70% 0px", threshold: [0, 0.1, 0.2] }
    );
    sections.forEach((s) => observer.observe(s));
  }

  window.addEventListener("scroll", () => {
    window.requestAnimationFrame(syncActiveFromScroll);
  });

  syncActiveFromScroll();
})();
