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

  if (
    !buttons.length ||
    !orderContent ||
    !orderTotal ||
    !orderSubmit ||
    !mobileCartbar ||
    !mobileCartbarItems ||
    !mobileCartbarTotal
  ) {
    return;
  }

  const cart = new Map();

  const formatRupiah = (amount) =>
    new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(amount);

  const addItemToCart = (source, trigger = null) => {
    const name = source.dataset.menuName;
    const image = source.dataset.menuImage;
    const price = Number(source.dataset.menuPrice || 0);
    const stock = Number(source.dataset.menuStock || 0);
    const isDisabled = source.matches(":disabled") || source.classList.contains("is-disabled-card");

    if (!name || !image || !price || stock < 1 || isDisabled) return;

    const existing = cart.get(name);
    if (existing) {
      existing.qty += 1;
    } else {
      cart.set(name, { name, image, price, qty: 1 });
    }

    updateSummary();
  };

  const renderEmptyState = () => {
    orderContent.innerHTML = `
      <div class="kantin-order-empty">
        <img src="assets/img/kantin-1/icon-cart-outline.svg" alt="">
        <strong>Pesananmu masih kosong!</strong>
        <p>Yuk, tambahkan pesananmu!</p>
      </div>
    `;
  };

  const updateSummary = () => {
    const items = [...cart.values()];
    const total = items.reduce((sum, item) => sum + item.price * item.qty, 0);
    const itemCount = items.reduce((sum, item) => sum + item.qty, 0);

    if (!items.length) {
      renderEmptyState();
      orderSubmit.disabled = true;
      orderSubmit.classList.remove("is-ready");
      orderTotal.textContent = "Rp 0";
      mobileCartbar.hidden = true;
      mobileCartbarItems.textContent = "0 Items";
      mobileCartbarTotal.textContent = "0";
      return;
    }

    orderSubmit.disabled = false;
    orderSubmit.classList.add("is-ready");
    orderTotal.textContent = formatRupiah(total);
    mobileCartbar.hidden = false;
    mobileCartbarItems.textContent = `${itemCount} Items`;
    mobileCartbarTotal.textContent = new Intl.NumberFormat("id-ID").format(total);

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
          <button type="button" data-cart-action="increase" data-menu-name="${item.name}" aria-label="Tambah ${item.name}">+</button>
        </div>
      `;
      list.appendChild(row);
    }

    orderContent.replaceChildren(list);
  };

  const adjustCart = (name, delta) => {
    const item = cart.get(name);
    if (!item) return;

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

  orderContent.addEventListener("click", (event) => {
    const actionButton = event.target.closest("[data-cart-action]");
    if (!actionButton) return;

    const name = actionButton.dataset.menuName;
    const action = actionButton.dataset.cartAction;
    if (!name || !action) return;

    adjustCart(name, action === "increase" ? 1 : -1);
  });

  orderSubmit.addEventListener("click", () => {
    if (orderSubmit.disabled) return;
    window.alert("Frontend only: alur checkout belum dihubungkan ke backend.");
  });

  mobileCartbar.addEventListener("click", () => {
    if (orderSubmit.disabled) return;
    window.alert("Frontend only: alur checkout belum dihubungkan ke backend.");
  });

  renderEmptyState();
})();
