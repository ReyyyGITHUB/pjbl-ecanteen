(() => {
  const menuToggle = document.querySelector("[data-mobile-menu-toggle]");
  const mobileNav = document.getElementById("kantin-mobile-nav");

  if (menuToggle && mobileNav) {
    const closeMenu = () => {
      menuToggle.setAttribute("aria-expanded", "false");
      mobileNav.hidden = true;
      mobileNav.classList.remove("is-open");
    };

    menuToggle.addEventListener("click", () => {
      const expanded = menuToggle.getAttribute("aria-expanded") === "true";
      if (expanded) {
        closeMenu();
        return;
      }

      menuToggle.setAttribute("aria-expanded", "true");
      mobileNav.hidden = false;
      mobileNav.classList.add("is-open");
    });

    mobileNav.addEventListener("click", (event) => {
      if (event.target.closest("a")) {
        closeMenu();
      }
    });
  }
})();

(() => {
  const buttons = Array.from(document.querySelectorAll("[data-add-to-cart]"));
  const menuCards = Array.from(document.querySelectorAll(".kantin-menu-card"));
  const orderContent = document.querySelector("[data-order-content]");
  const orderTotal = document.querySelector("[data-order-total]");
  const orderSubmit = document.querySelector("[data-order-submit]");
  const mobileCartbar = document.querySelector("[data-mobile-cartbar]");
  const mobileCartbarItems = document.querySelector("[data-mobile-cartbar-items]");
  const mobileCartbarTotal = document.querySelector("[data-mobile-cartbar-total]");
  const mobileSheet = document.querySelector("[data-mobile-sheet]");
  const mobileOrderContent = document.querySelector("[data-mobile-order-content]");
  const mobileOrderTotal = document.querySelector("[data-mobile-order-total]");
  const mobileOrderSubmit = document.querySelector("[data-mobile-order-submit]");
  const mobileSheetCloseButtons = Array.from(document.querySelectorAll("[data-mobile-sheet-close]"));
  const mobileViewport = window.matchMedia("(max-width: 1180px)");

  if (
    !buttons.length ||
    !orderContent ||
    !orderTotal ||
    !orderSubmit ||
    !mobileCartbar ||
    !mobileCartbarItems ||
    !mobileCartbarTotal ||
    !mobileSheet ||
    !mobileOrderContent ||
    !mobileOrderTotal ||
    !mobileOrderSubmit
  ) {
    return;
  }

  const cart = new Map();
  let mobileSheetHideTimer = 0;
  const checkoutCartKey = "ecanteenCheckoutCart";

  const formatRupiah = (amount) =>
    new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(amount);

  const setMobileSheetState = (open) => {
    window.clearTimeout(mobileSheetHideTimer);

    if (open) {
      mobileSheet.hidden = false;
      mobileCartbar.setAttribute("aria-expanded", "true");
      document.body.classList.add("is-mobile-sheet-open");
      window.requestAnimationFrame(() => {
        mobileSheet.classList.add("is-open");
      });
      return;
    }

    mobileSheet.classList.remove("is-open");
    mobileCartbar.setAttribute("aria-expanded", "false");
    document.body.classList.remove("is-mobile-sheet-open");
    mobileSheetHideTimer = window.setTimeout(() => {
      if (!mobileSheet.classList.contains("is-open")) {
        mobileSheet.hidden = true;
      }
    }, 220);
  };

  const addItemToCart = (source, trigger = null) => {
    const id = source.dataset.menuId || source.dataset.menuName;
    const name = source.dataset.menuName;
    const image = source.dataset.menuImage;
    const price = Number(source.dataset.menuPrice || 0);
    const stock = Number(source.dataset.menuStock || 0);
    const isDisabled = source.matches(":disabled") || source.classList.contains("is-disabled-card");

    if (!name || !image || !price || stock < 1 || isDisabled) return;

    const existing = cart.get(name);
    if (existing) {
      if (existing.qty >= existing.stock) return;
      existing.qty += 1;
    } else {
      cart.set(name, { id, name, image, price, qty: 1, stock });
    }

    updateSummary();
  };

  const renderDesktopEmptyState = () => {
    orderContent.innerHTML = `
      <div class="kantin-order-empty">
        <img src="assets/img/kantin-1/icon-cart-outline.svg" alt="">
        <strong>Ringkasan jajanmu masih kosong!</strong>
        <p>Yuk, tambahkan menu favoritmu dulu.</p>
      </div>
    `;
  };

  const renderMobileEmptyState = () => {
    mobileOrderContent.innerHTML = `
      <div class="kantin-mobile-order-empty">
        <img src="assets/img/kantin-1/icon-cart-outline.svg" alt="">
        <strong>Ringkasan jajanmu masih kosong!</strong>
        <p>Yuk, tambahkan menu favoritmu dulu.</p>
      </div>
    `;
  };

  const renderDesktopItems = (items) => {
    const list = document.createElement("div");
    list.className = "kantin-order-items";

    for (const item of items) {
      const row = document.createElement("article");
      row.className = "kantin-order-item";
      row.innerHTML = `
        <img src="${item.image}" alt="${item.name}">
        <div class="kantin-order-item-copy">
          <strong>${item.name}</strong>
          <span>${formatRupiah(item.price)} x ${item.qty}</span>
        </div>
        <div class="kantin-order-item-actions">
          <button type="button" data-cart-action="decrease" data-menu-name="${item.name}" aria-label="Kurangi ${item.name}">-</button>
          <strong>${item.qty}</strong>
          <button type="button" data-cart-action="increase" data-menu-name="${item.name}" aria-label="Tambah ${item.name}" ${item.qty >= item.stock ? "disabled" : ""}>+</button>
        </div>
      `;
      list.appendChild(row);
    }

    orderContent.replaceChildren(list);
  };

  const renderMobileItems = (items) => {
    const list = document.createElement("div");
    list.className = "kantin-mobile-order-items";

    for (const item of items) {
      const row = document.createElement("article");
      row.className = "kantin-mobile-order-item";
      row.innerHTML = `
        <div class="kantin-mobile-order-item-copy">
          <strong>${item.name}</strong>
          <span>${formatRupiah(item.price)}${item.qty > 1 ? ` x ${item.qty}` : ""}</span>
        </div>
        <button type="button" class="kantin-mobile-order-remove" data-cart-action="remove" data-menu-name="${item.name}" aria-label="Hapus ${item.name}">
          <img src="assets/img/kantin-1/icon-trash-red.svg" alt="">
        </button>
      `;
      list.appendChild(row);
    }

    mobileOrderContent.replaceChildren(list);
  };

  const updateSummary = () => {
    const items = [...cart.values()];
    const total = items.reduce((sum, item) => sum + item.price * item.qty, 0);
    const itemCount = items.reduce((sum, item) => sum + item.qty, 0);

    if (!items.length) {
      renderDesktopEmptyState();
      renderMobileEmptyState();
      orderSubmit.disabled = true;
      mobileOrderSubmit.disabled = true;
      orderSubmit.classList.remove("is-ready");
      mobileOrderSubmit.classList.remove("is-ready");
      orderTotal.textContent = "Rp 0";
      mobileOrderTotal.textContent = "Rp 0";
      mobileCartbar.hidden = true;
      mobileCartbarItems.textContent = "0 Items";
      mobileCartbarTotal.textContent = "0";
      setMobileSheetState(false);
      return;
    }

    orderSubmit.disabled = false;
    mobileOrderSubmit.disabled = false;
    orderSubmit.classList.add("is-ready");
    mobileOrderSubmit.classList.add("is-ready");
    orderTotal.textContent = formatRupiah(total);
    mobileOrderTotal.textContent = formatRupiah(total);
    mobileCartbar.hidden = false;
    mobileCartbarItems.textContent = `${itemCount} Items`;
    mobileCartbarTotal.textContent = new Intl.NumberFormat("id-ID").format(total);
    renderDesktopItems(items);
    renderMobileItems(items);
  };

  const adjustCart = (name, delta) => {
    const item = cart.get(name);
    if (!item) return;

    if (delta > 0 && item.qty >= item.stock) return;
    item.qty += delta;
    if (item.qty <= 0) {
      cart.delete(name);
    }

    updateSummary();
  };

  for (const button of buttons) {
    button.addEventListener("click", (event) => {
      event.stopPropagation();
      const card = button.closest("[data-menu-card]");
      addItemToCart(card ?? button, button);
    });
  }

  for (const card of menuCards) {
    card.addEventListener("click", () => {
      addItemToCart(card);
    });
  }

  const handleCartAction = (event) => {
    const actionButton = event.target.closest("[data-cart-action]");
    if (!actionButton) return;

    const name = actionButton.dataset.menuName;
    const action = actionButton.dataset.cartAction;
    if (!name || !action) return;

    if (action === "remove") {
      cart.delete(name);
      updateSummary();
      return;
    }

    adjustCart(name, action === "increase" ? 1 : -1);
  };

  orderContent.addEventListener("click", handleCartAction);
  mobileOrderContent.addEventListener("click", handleCartAction);

  const handleCheckout = (button) => {
    if (button.disabled) return;
    const payload = {
      kantinId: 1,
      kantinName: "Kantin Mak E",
      items: [...cart.values()].map((item) => ({
        id: String(item.id || item.name),
        name: item.name,
        image: item.image,
        price: item.price,
        qty: item.qty,
        stock: item.stock,
      })),
    };

    sessionStorage.setItem(checkoutCartKey, JSON.stringify(payload));
    window.location.href = "checkout";
  };

  orderSubmit.addEventListener("click", () => handleCheckout(orderSubmit));
  mobileOrderSubmit.addEventListener("click", () => handleCheckout(mobileOrderSubmit));

  mobileCartbar.addEventListener("click", () => {
    if (orderSubmit.disabled || !mobileViewport.matches) return;
    setMobileSheetState(true);
  });

  for (const closeButton of mobileSheetCloseButtons) {
    closeButton.addEventListener("click", () => {
      setMobileSheetState(false);
    });
  }

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && mobileSheet.classList.contains("is-open")) {
      setMobileSheetState(false);
    }
  });

  const handleViewportChange = (event) => {
    if (!event.matches) {
      setMobileSheetState(false);
    }
  };

  if (typeof mobileViewport.addEventListener === "function") {
    mobileViewport.addEventListener("change", handleViewportChange);
  } else if (typeof mobileViewport.addListener === "function") {
    mobileViewport.addListener(handleViewportChange);
  }

  renderDesktopEmptyState();
  renderMobileEmptyState();
})();
