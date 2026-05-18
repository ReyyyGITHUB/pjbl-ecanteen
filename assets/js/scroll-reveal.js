(() => {
  const revealItems = Array.from(document.querySelectorAll("[data-scroll-reveal]"));
  if (!revealItems.length) return;

  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  const reveal = (item) => {
    item.classList.add("is-revealed");
  };

  if (reduceMotion || !("IntersectionObserver" in window)) {
    document.documentElement.classList.remove("scroll-reveal-ready");
    revealItems.forEach(reveal);
    return;
  }

  document.documentElement.classList.add("scroll-reveal-ready");

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;

        reveal(entry.target);
        observer.unobserve(entry.target);
      });
    },
    {
      threshold: 0.15,
      rootMargin: "0px 0px -8% 0px",
    }
  );

  window.requestAnimationFrame(() => {
    window.requestAnimationFrame(() => {
      revealItems.forEach((item) => observer.observe(item));
    });
  });
})();
