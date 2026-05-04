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
      button.innerHTML = `
        <img class="kantin-search-result-media" src="${item.gambar_url}" alt="${item.nama_kantin}">
        <span class="kantin-search-result-main">${item.nama_menu}</span>
        <span class="kantin-search-result-meta">${item.nama_kantin}</span>
      `;
      results.appendChild(button);
    }

    results.hidden = false;
    input.setAttribute("aria-expanded", "true");
  };

  const fetchResults = async (keyword) => {
    activeRequest += 1;
    const requestId = activeRequest;

    try {
      const response = await fetch(`api/search-menu.php?q=${encodeURIComponent(keyword)}`, {
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
    }

    window.clearTimeout(debounceTimer);

    if (keyword.length === 0) {
      hideNotice();
      hideResults();
      return;
    }

    debounceTimer = window.setTimeout(() => {
      fetchResults(keyword);
    }, 180);
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
    input.value = selectedMenuName;
    hideResults();
    input.focus();
  });

  document.addEventListener("click", (event) => {
    if (form.contains(event.target) || results.contains(event.target)) return;
    hideResults();
  });

  form.addEventListener("submit", (event) => {
    const keyword = input.value.trim();

    if (keyword.length === 0) {
      event.preventDefault();
      showNotice("Pesanan belum terisi!");
      input.focus();
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
    window.location.href = "kantin-1";
  });
})();
