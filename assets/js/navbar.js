(() => {
  const button = document.querySelector(".hamburger");
  const navbar = document.querySelector(".navbar");
  const panel = document.querySelector(".nav-panel");
  const menu = document.querySelector(".nav-links");
  const backdrop = document.querySelector(".nav-backdrop");
  const accountToggle = document.querySelector("[data-nav-account-toggle]");
  const accountMenu = document.querySelector(".nav-account-menu");

  if (!button || !navbar || !panel || !menu || !backdrop) return;

  const setAccountExpanded = (expanded) => {
    if (!accountToggle || !accountMenu) return;

    accountToggle.setAttribute("aria-expanded", expanded ? "true" : "false");
    accountMenu.hidden = !expanded;
  };

  const setExpanded = (expanded) => {
    button.setAttribute("aria-expanded", expanded ? "true" : "false");
    navbar.classList.toggle("is-open", expanded);
    panel.setAttribute("aria-hidden", expanded ? "false" : "true");
    backdrop.setAttribute("aria-hidden", expanded ? "false" : "true");
    document.body.classList.toggle("no-scroll", expanded);
    if (!expanded) setAccountExpanded(false);

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

  if (accountToggle && accountMenu) {
    accountToggle.addEventListener("click", (event) => {
      event.stopPropagation();
      const isExpanded = accountToggle.getAttribute("aria-expanded") === "true";
      setAccountExpanded(!isExpanded);
    });
  }

  panel.addEventListener("click", (event) => {
    if (!navbar.classList.contains("is-open")) return;

    const target = event.target;
    if (!(target instanceof Element)) return;
    if (target.closest("a")) setExpanded(false);
  });

  window.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;

    const isAccountExpanded = accountToggle?.getAttribute("aria-expanded") === "true";
    if (isAccountExpanded) {
      setAccountExpanded(false);
      accountToggle.focus();
    }

    if (navbar.classList.contains("is-open")) setExpanded(false);
  });

  window.addEventListener("click", (event) => {
    if (!accountToggle || !accountMenu) return;

    const target = event.target;
    if (!(target instanceof Element)) return;
    if (target.closest(".nav-account-wrap")) return;

    setAccountExpanded(false);
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
