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

  let ticking = false;

  const getNavOffset = () => {
    const navbarHeight = navbar.getBoundingClientRect().height || 64;
    return navbarHeight + 32;
  };

  const syncActiveFromScroll = () => {
    const currentY = window.scrollY + getNavOffset();
    let activeId = sections[0]?.id || "beranda";

    for (const section of sections) {
      if (section.offsetTop <= currentY) activeId = section.id;
    }

    setActive(activeId);
    ticking = false;
  };

  const scrollToSection = (id) => {
    const section = document.getElementById(id);
    if (!section) return;

    const top = Math.max(0, section.offsetTop - getNavOffset() + 1);
    window.history.pushState(null, "", `#${id}`);
    window.scrollTo({ top, behavior: "smooth" });
  };

  // Update active immediately on click and move scroll to a navbar-safe position.
  links.forEach((a) => {
    a.addEventListener("click", (event) => {
      const href = a.getAttribute("href") || "";
      if (!href.startsWith("#")) return;

      event.preventDefault();
      const id = href.slice(1);
      setActive(id);
      scrollToSection(id);
    });
  });

  window.addEventListener("scroll", () => {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(syncActiveFromScroll);
  }, { passive: true });

  syncActiveFromScroll();
})();
