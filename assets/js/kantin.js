(() => {
  const userMenu = document.querySelector("[data-kantin-user-menu]");
  const userToggle = document.querySelector("[data-kantin-user-toggle]");
  const userPanel = document.querySelector("#kantin-user-menu");

  if (userMenu && userToggle && userPanel) {
    const closeMenu = () => {
      userMenu.classList.remove("is-open");
      userPanel.hidden = true;
      userToggle.setAttribute("aria-expanded", "false");
    };

    const openMenu = () => {
      userMenu.classList.add("is-open");
      userPanel.hidden = false;
      userToggle.setAttribute("aria-expanded", "true");
    };

    userToggle.addEventListener("click", (event) => {
      event.stopPropagation();
      if (userPanel.hidden) {
        openMenu();
      } else {
        closeMenu();
      }
    });

    document.addEventListener("click", (event) => {
      if (userMenu.contains(event.target)) return;
      closeMenu();
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") closeMenu();
    });
  }
})();

(() => {
  const choices = Array.from(document.querySelectorAll("[data-kantin-choice]"));
  if (choices.length === 0) return;

  for (const choice of choices) {
    choice.addEventListener("click", () => {
      choices.forEach((item) => item.classList.remove("is-selected"));
      choice.classList.add("is-selected");

      const target = choice.dataset.kantinTarget;
      if (target) {
        window.location.href = target;
      }
    });
  }
})();

(() => {
  const input = document.querySelector("[data-kantin-search-input]");
  const results = document.querySelector("[data-kantin-search-results]");
  const searchField = document.querySelector(".kantin-search-field");
  const notice = document.querySelector("[data-kantin-search-notice]");
  const noticeText = document.querySelector("[data-kantin-search-notice-text]");
  const noticeClose = document.querySelector("[data-kantin-search-notice-close]");
  const form = document.querySelector(".kantin-search");

  if (!input || !results || !searchField || !notice || !noticeText || !noticeClose || !form) return;

  let activeRequest = 0;
  let debounceTimer = null;
  let noticeTimer = null;
  let noticeHideTimer = null;
  let selectedMenuName = "";
  let selectedTargetUrl = "";

  const formatRupiah = (value) =>
    new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(Number(value || 0));

  const hideResults = () => {
    results.hidden = true;
    results.replaceChildren();
    input.setAttribute("aria-expanded", "false");
  };

  const renderResults = (items) => {
    if (!items.length) {
      hideResults();
      showNotice("Menu tidak ditemukan.");
      return;
    }

    hideNotice();
    results.replaceChildren();

    for (const item of items) {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "kantin-search-result-item";
      button.dataset.menuName = item.nama_menu;
      button.dataset.targetUrl = item.target_url || "kantin-1";

      const image = document.createElement("img");
      image.className = "kantin-search-result-media";
      image.src = item.gambar_url || "assets/img/kantin-1/menu-ayam.png";
      image.alt = item.nama_kantin || "";

      const copy = document.createElement("span");
      copy.className = "kantin-search-result-copy";

      const title = document.createElement("span");
      title.className = "kantin-search-result-main";
      title.textContent = item.nama_menu || "Menu";

      const meta = document.createElement("span");
      meta.className = "kantin-search-result-meta";
      meta.textContent = `${item.nama_kantin || "Kantin"} • ${formatRupiah(item.harga)} • Stok ${item.sisa_stock || 0}`;

      copy.append(title, meta);
      button.append(image, copy);
      results.appendChild(button);
    }

    results.hidden = false;
    input.setAttribute("aria-expanded", "true");
  };

  const fetchResults = async (keyword) => {
    activeRequest += 1;
    const requestId = activeRequest;
    const query = keyword ? `q=${encodeURIComponent(keyword)}` : "mode=recommend";

    try {
      const response = await fetch(`api/search-menu.php?${query}`, {
        headers: {
          Accept: "application/json",
        },
      });

      if (!response.ok) {
        throw new Error("Request gagal.");
      }

      const payload = await response.json();
      if (requestId !== activeRequest) return;

      renderResults(Array.isArray(payload.data) ? payload.data : []);
    } catch (error) {
      if (requestId !== activeRequest) return;
      hideResults();
      showNotice("Pencarian bermasalah.");
    }
  };

  const hideNotice = () => {
    window.clearTimeout(noticeTimer);
    window.clearTimeout(noticeHideTimer);

    if (notice.hidden) return;

    notice.classList.remove("is-visible");
    notice.classList.add("is-hiding");
    searchField.classList.remove("is-invalid");

    noticeHideTimer = window.setTimeout(() => {
      notice.hidden = true;
      notice.classList.remove("is-hiding");
      noticeText.textContent = "";
    }, 220);
  };

  const showNotice = (message) => {
    window.clearTimeout(noticeHideTimer);
    notice.classList.remove("is-hiding");
    notice.hidden = false;
    noticeText.textContent = message;
    notice.classList.add("is-visible");
    searchField.classList.add("is-invalid");

    window.clearTimeout(noticeTimer);
    noticeTimer = window.setTimeout(() => {
      hideNotice();
    }, 1500);
  };

  input.addEventListener("input", () => {
    const keyword = input.value.trim();
    if (keyword !== selectedMenuName) {
      selectedMenuName = "";
      selectedTargetUrl = "";
    }

    window.clearTimeout(debounceTimer);

    if (keyword.length === 0) {
      hideNotice();
      fetchResults("");
      return;
    }

    debounceTimer = window.setTimeout(() => {
      fetchResults(keyword);
    }, 180);
  });

  input.addEventListener("focus", () => {
    if (input.value.trim().length === 0) fetchResults("");
  });

  input.addEventListener("click", () => {
    if (input.value.trim().length === 0) fetchResults("");
  });

  input.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      hideNotice();
      hideResults();
    }
  });

  noticeClose.addEventListener("click", () => {
    hideNotice();
    input.focus();
  });

  results.addEventListener("click", (event) => {
    const target = event.target.closest(".kantin-search-result-item");
    if (!target) return;

    selectedMenuName = target.dataset.menuName || "";
    selectedTargetUrl = target.dataset.targetUrl || "kantin-1";
    input.value = selectedMenuName;
    hideResults();
    window.location.href = selectedTargetUrl;
  });

  document.addEventListener("click", (event) => {
    if (form.contains(event.target) || results.contains(event.target)) return;
    hideResults();
  });

  form.addEventListener("submit", (event) => {
    const keyword = input.value.trim();

    if (keyword.length === 0) {
      event.preventDefault();
      showNotice("Pilih menu dari rekomendasi dulu.");
      input.focus();
      fetchResults("");
      return;
    }

    if (!selectedMenuName || keyword !== selectedMenuName) {
      event.preventDefault();
      showNotice("Pilih menu dari hasil pencarian dulu.");
      input.focus();
      return;
    }

    event.preventDefault();
    hideResults();
    hideNotice();
    window.location.href = selectedTargetUrl || "kantin-1";
  });
})();
