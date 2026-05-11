(() => {
  const overlayId = "page-loader-overlay";
  let overlay = null;
  let label = null;
  let hideTimer = 0;

  const createOverlay = () => {
    if (overlay) return overlay;

    overlay = document.createElement("div");
    overlay.id = overlayId;
    overlay.className = "page-loader";
    overlay.setAttribute("aria-hidden", "true");
    overlay.innerHTML = `
      <div class="page-loader-card" role="status" aria-live="polite" aria-atomic="true">
        <span class="page-loader-spinner" aria-hidden="true"></span>
        <span class="page-loader-text">Memuat halaman...</span>
      </div>
    `;

    label = overlay.querySelector(".page-loader-text");
    document.body.appendChild(overlay);
    return overlay;
  };

  const ensureOverlay = () => {
    if (overlay && document.body.contains(overlay)) return overlay;
    if (!document.body) return null;
    return createOverlay();
  };

  const hidePageLoader = () => {
    window.clearTimeout(hideTimer);

    if (!overlay) {
      document.body?.classList.remove("is-page-loading");
      return;
    }

    overlay.classList.remove("is-visible");
    overlay.setAttribute("aria-hidden", "true");
    document.body?.classList.remove("is-page-loading");
  };

  const showPageLoader = (message = "Memuat halaman...") => {
    const root = ensureOverlay();
    if (!root) return;

    if (label) {
      label.textContent = message;
    }

    window.clearTimeout(hideTimer);
    document.body.classList.add("is-page-loading");
    root.setAttribute("aria-hidden", "false");
    window.requestAnimationFrame(() => {
      root.classList.add("is-visible");
    });
  };

  const isModifiedClick = (event) =>
    event.defaultPrevented ||
    event.button !== 0 ||
    event.metaKey ||
    event.ctrlKey ||
    event.shiftKey ||
    event.altKey;

  const shouldHandleLink = (link, event) => {
    if (!(link instanceof HTMLAnchorElement) || isModifiedClick(event)) return false;
    if (link.hasAttribute("download")) return false;
    if (link.hasAttribute("data-skip-page-loader")) return false;

    const rawHref = (link.getAttribute("href") || "").trim();
    if (!rawHref || rawHref === "#" || rawHref.startsWith("javascript:")) return false;

    const target = (link.getAttribute("target") || "").trim();
    if (target && target.toLowerCase() !== "_self") return false;

    let url;
    try {
      url = new URL(link.href, window.location.href);
    } catch (error) {
      return false;
    }

    if (!/^https?:$/.test(url.protocol)) return false;

    const isHashOnlyNavigation =
      url.origin === window.location.origin &&
      url.pathname === window.location.pathname &&
      url.search === window.location.search &&
      url.hash !== "";

    return !isHashOnlyNavigation;
  };

  document.addEventListener(
    "click",
    (event) => {
      const target = event.target;
      if (!(target instanceof Element)) return;

      const link = target.closest("a[href]");
      if (!shouldHandleLink(link, event)) return;
      showPageLoader();
    },
    true
  );

  document.addEventListener(
    "submit",
    (event) => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement)) return;
      if (form.hasAttribute("data-skip-page-loader")) return;

      const target = (form.getAttribute("target") || "").trim();
      if (target && target.toLowerCase() !== "_self") return;

      showPageLoader("Memproses...");
    },
    true
  );

  window.showPageLoader = showPageLoader;
  window.hidePageLoader = hidePageLoader;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      ensureOverlay();
      hidePageLoader();
    });
  } else {
    ensureOverlay();
    hidePageLoader();
  }

  window.addEventListener("pageshow", () => {
    hideTimer = window.setTimeout(hidePageLoader, 0);
  });

  window.addEventListener("load", hidePageLoader);
})();
