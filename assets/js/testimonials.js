(() => {
  const track = document.querySelector(".testimonials-track");
  const viewport = document.querySelector(".testimonials-viewport");
  const buttons = document.querySelectorAll(".testimonials-controls .testimonials-arrow");
  const dots = document.querySelectorAll(".testimonials-dot");

  if (!track || !viewport || buttons.length === 0) return;

  const getStep = () => {
    const first = track.querySelector(".testi-card");
    if (!first) return 0;
    const styles = window.getComputedStyle(track);
    const gap = Number.parseFloat(styles.columnGap || styles.gap || "0") || 16;
    return first.getBoundingClientRect().width + gap;
  };

  let index = 0;

  const maxIndex = () => {
    const step = getStep();
    if (!step) return 0;
    const cards = track.querySelectorAll(".testi-card").length;
    const visible = Math.max(1, Math.round(viewport.getBoundingClientRect().width / step));
    return Math.max(0, cards - visible);
  };
  

  const scrollToIndex = (behavior = "smooth") => {
    const step = getStep();
    if (!step) return;
    viewport.scrollTo({ left: index * step, behavior });
  };

  const clamp = () => {
    index = Math.max(0, Math.min(index, maxIndex()));
  };

  buttons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const dir = Number(btn.getAttribute("data-dir") || "0");
      index += dir;
      clamp();
      scrollToIndex("smooth");
    });
  });

  dots.forEach((dot, i) => {
    dot.addEventListener("click", () => {
      index = i;
      clamp();
      scrollToIndex("smooth");
    });
  });

  const syncFromScroll = () => {
    const step = getStep();
    if (!step) return;
    index = Math.round(viewport.scrollLeft / step);
    clamp();
    dots.forEach((d, i) => d.classList.toggle("is-active", i === index));
  };

  viewport.addEventListener("scroll", () => {
    window.requestAnimationFrame(syncFromScroll);
  });

  window.addEventListener("resize", () => {
    clamp();
    scrollToIndex("auto");
  });

  // Initial state: sync dots from current scroll position
  syncFromScroll();
})();
