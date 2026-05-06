(() => {
  const form = document.querySelector("[data-hero-search-form]");
  const input = document.querySelector("[data-hero-search-input]");
  const results = document.querySelector("[data-hero-search-results]");

  if (!form || !input || !results) return;

  let activeRequest = 0;
  let debounceTimer = null;

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

  const createResultItem = (item) => {
    const button = document.createElement("button");
    button.type = "button";
    button.className = "hero-search-result-item";
    button.dataset.targetUrl = item.target_url || "kantin-1";

    const image = document.createElement("img");
    image.className = "hero-search-result-media";
    image.src = item.gambar_url || "assets/img/kantin-1/menu-ayam.png";
    image.alt = item.nama_kantin || "";

    const copy = document.createElement("span");
    copy.className = "hero-search-result-copy";

    const title = document.createElement("span");
    title.className = "hero-search-result-main";
    title.textContent = item.nama_menu || "Menu";

    const meta = document.createElement("span");
    meta.className = "hero-search-result-meta";
    meta.textContent = `${item.nama_kantin || "Kantin"} • ${formatRupiah(item.harga)} • Stok ${item.sisa_stock || 0}`;

    copy.append(title, meta);
    button.append(image, copy);

    return button;
  };

  const renderResults = (items) => {
    results.replaceChildren();

    if (!items.length) {
      hideResults();
      return;
    }

    for (const item of items) {
      results.appendChild(createResultItem(item));
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
        headers: { Accept: "application/json" },
      });

      if (!response.ok) throw new Error("Request gagal.");

      const payload = await response.json();
      if (requestId !== activeRequest) return;

      renderResults(Array.isArray(payload.data) ? payload.data : []);
    } catch (error) {
      if (requestId !== activeRequest) return;
      hideResults();
    }
  };

  const syncResults = () => {
    window.clearTimeout(debounceTimer);
    const keyword = input.value.trim();
    debounceTimer = window.setTimeout(() => fetchResults(keyword), keyword ? 180 : 0);
  };

  input.addEventListener("focus", syncResults);
  input.addEventListener("click", syncResults);
  input.addEventListener("input", syncResults);

  input.addEventListener("keydown", (event) => {
    if (event.key === "Escape") hideResults();
  });

  results.addEventListener("click", (event) => {
    const target = event.target.closest(".hero-search-result-item");
    if (!target) return;

    window.location.href = target.dataset.targetUrl || "kantin-1";
  });

  document.addEventListener("click", (event) => {
    if (form.contains(event.target)) return;
    hideResults();
  });

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const firstResult = results.querySelector(".hero-search-result-item");

    if (firstResult) {
      window.location.href = firstResult.dataset.targetUrl || "kantin-1";
      return;
    }

    window.location.href = "kantin";
  });
})();
