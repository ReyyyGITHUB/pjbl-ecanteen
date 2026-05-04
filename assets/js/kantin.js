(() => {
  const choices = Array.from(document.querySelectorAll("[data-kantin-choice]"));
  if (choices.length === 0) return;

  for (const choice of choices) {
    choice.addEventListener("click", () => {
      choices.forEach((item) => item.classList.remove("is-selected"));
      choice.classList.add("is-selected");
    });
  }
})();

(() => {
  const input = document.querySelector("[data-kantin-search-input]");
  const results = document.querySelector("[data-kantin-search-results]");
  const notice = document.querySelector("[data-kantin-search-notice]");
  const noticeText = document.querySelector("[data-kantin-search-notice-text]");
  const noticeClose = document.querySelector("[data-kantin-search-notice-close]");
  const form = document.querySelector(".kantin-search");

  if (!input || !results || !notice || !noticeText || !noticeClose || !form) return;

  let activeRequest = 0;
  let debounceTimer = null;
  let noticeTimer = null;
  let noticeHideTimer = null;

  const hideResults = () => {
    results.hidden = true;
    results.innerHTML = "";
    input.setAttribute("aria-expanded", "false");
  };

  const hideNotice = () => {
    window.clearTimeout(noticeTimer);
    window.clearTimeout(noticeHideTimer);

    if (notice.hidden) return;

    notice.classList.remove("is-visible");
    notice.classList.add("is-hiding");
    input.closest(".kantin-search-field")?.classList.remove("is-invalid");

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
    input.closest(".kantin-search-field")?.classList.add("is-invalid");

    window.clearTimeout(noticeTimer);
    noticeTimer = window.setTimeout(() => {
      hideNotice();
    }, 1500);
  };

  const showMessage = (message) => {
    results.hidden = false;
    results.replaceChildren();

    const messageNode = document.createElement("div");
    messageNode.className = "kantin-search-empty";
    messageNode.textContent = message;
    results.appendChild(messageNode);
    input.setAttribute("aria-expanded", "true");
  };

  const renderResults = (items) => {
    if (!items.length) {
      showMessage("Menu tidak ditemukan.");
      return;
    }

    results.replaceChildren();

    for (const item of items) {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "kantin-search-result-item";
      button.dataset.menuName = item.nama_menu;
      button.setAttribute("role", "option");
      button.textContent = item.nama_menu;
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
      showMessage("Pencarian sedang bermasalah.");
    }
  };

  input.addEventListener("input", () => {
    const keyword = input.value.trim();

    window.clearTimeout(debounceTimer);

    if (keyword.length === 0) {
      hideNotice();
      hideResults();
      return;
    }

    hideNotice();

    debounceTimer = window.setTimeout(() => {
      fetchResults(keyword);
    }, 180);
  });

  results.addEventListener("click", (event) => {
    const target = event.target.closest(".kantin-search-result-item");
    if (!target) return;

    input.value = target.dataset.menuName || "";
    hideResults();
    input.focus();
  });

  document.addEventListener("click", (event) => {
    if (form.contains(event.target) || results.contains(event.target)) return;
    hideResults();
  });

  input.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      hideResults();
      hideNotice();
    }
  });

  noticeClose.addEventListener("click", () => {
    hideNotice();
    input.focus();
  });

  form.addEventListener("submit", (event) => {
    const keyword = input.value.trim();

    if (keyword.length === 0) {
      event.preventDefault();
      hideResults();
      showNotice("Pesanan belum terisi!");
      input.focus();
      return;
    }

    hideNotice();
  });
})();
