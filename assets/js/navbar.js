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
      return;
    }

    button.focus();
  };

  button.addEventListener("click", () => {
    const isOpen = navbar.classList.contains("is-open");
    setExpanded(!isOpen);
  });

  backdrop.addEventListener("click", () => setExpanded(false));

  panel.addEventListener("click", (event) => {
    if (!navbar.classList.contains("is-open")) return;

    const target = event.target;
    if (!(target instanceof Element)) return;
    if (target.closest("a")) setExpanded(false);
  });

  window.addEventListener("keydown", (event) => {
    if (event.key === "Escape") setExpanded(false);
  });

  const scrollToSection = (id) => {
    const section = document.getElementById(id);
    if (!section) return;

    window.history.pushState(null, "", `#${id}`);
    section.scrollIntoView({ behavior: "smooth", block: "start" });
  };

  const links = Array.from(panel.querySelectorAll(".nav-link"));

  links.forEach((link) => {
    link.addEventListener("click", (event) => {
      const href = link.getAttribute("href") || "";
      if (!href.startsWith("#")) return;

      event.preventDefault();
      const id = href.slice(1);
      scrollToSection(id);
    });
  });
})();
