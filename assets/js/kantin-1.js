(() => {
  const menuToggle = document.querySelector("[data-mobile-menu-toggle]");
  const mobileNav = document.getElementById("kantin-mobile-nav");

  if (menuToggle && mobileNav) {
    menuToggle.addEventListener("click", () => {
      const expanded = menuToggle.getAttribute("aria-expanded") === "true";
      menuToggle.setAttribute("aria-expanded", expanded ? "false" : "true");
      mobileNav.hidden = expanded;
    });
  }
})();

(() => {
  const buttons = Array.from(document.querySelectorAll("[data-add-to-cart]"));
  const orderEmpty = document.querySelector("[data-order-empty]");
  const orderItems = document.querySelector("[data-order-items]");
  const orderTotal = document.querySelector("[data-order-total]");
  const orderSubmit = document.querySelector("[data-order-submit]");

  if (!buttons.length || !orderEmpty || !orderItems || !orderTotal || !orderSubmit) return;

  const cart = new Map();

  const formatRupiah = (amount) =>
    new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      maximumFractionDigits: 0,
    }).format(amount);

  const updateSummary = () => {
    const items = [...cart.values()];
    const total = items.reduce((sum, item) => sum + item.price * item.qty, 0);

    orderItems.innerHTML = "";

    if (!items.length) {
      orderEmpty.hidden = false;
      orderItems.hidden = true;
      orderSubmit.disabled = true;
      orderSubmit.classList.remove("is-ready");
      orderTotal.textContent = "Rp 0";
      return;
    }

    orderEmpty.hidden = true;
    orderItems.hidden = false;
    orderSubmit.disabled = false;
    orderSubmit.classList.add("is-ready");
    orderTotal.textContent = formatRupiah(total);

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
      orderItems.appendChild(row);
    }
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
    button.addEventListener("click", () => {
      const name = button.dataset.menuName;
      const image = button.dataset.menuImage;
      const price = Number(button.dataset.menuPrice || 0);
      if (!name || !image || !price) return;

      const existing = cart.get(name);
      if (existing) {
        existing.qty += 1;
      } else {
        cart.set(name, { name, image, price, qty: 1 });
      }

      updateSummary();
    });
  }

  orderItems.addEventListener("click", (event) => {
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
})();
