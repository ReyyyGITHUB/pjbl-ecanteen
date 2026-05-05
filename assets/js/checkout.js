(() => {
  const cartList = document.querySelector("[data-checkout-cart-list]");
  const emptyState = document.querySelector("[data-checkout-empty]");
  const totalRow = document.querySelector("[data-checkout-cart-total-row]");
  const totalText = document.querySelector("[data-checkout-total]");
  const form = document.querySelector("[data-checkout-form]");
  const submitButton = document.querySelector("[data-checkout-submit]");
  const noteField = document.querySelector("[data-checkout-note]");
  const pickupOptions = Array.from(document.querySelectorAll("[data-pickup-option]"));
  const paymentOptions = Array.from(document.querySelectorAll("[data-payment-option]"));

  if (!cartList || !emptyState || !totalRow || !totalText || !form || !submitButton || !noteField) {
    return;
  }

  const CART_KEY = "ecanteenCheckoutCart";
  const DRAFT_KEY = "ecanteenCheckoutDraft";
  let selectedPickupTime = "Istirahat 1 (09:00 - 09:15)";
  let checkoutCart = { kantinId: 1, kantinName: "Kantin Mak E", items: [] };

  const formatRupiah = (amount) => `Rp ${new Intl.NumberFormat("id-ID").format(amount)}`;

  const normalizeItem = (item, index) => {
    const price = Number(item?.price || 0);
    const stock = Math.max(1, Number(item?.stock || 999));
    const qty = Math.min(stock, Math.max(1, Number(item?.qty || 1)));

    return {
      id: String(item?.id || item?.name || index),
      name: String(item?.name || "Menu"),
      image: String(item?.image || "assets/img/kantin-1/menu-ayam.png"),
      price,
      qty,
      stock,
    };
  };

  const readCart = () => {
    try {
      const parsed = JSON.parse(sessionStorage.getItem(CART_KEY) || "{}");
      const items = Array.isArray(parsed.items) ? parsed.items.map(normalizeItem).filter((item) => item.price > 0) : [];

      checkoutCart = {
        kantinId: Number(parsed.kantinId || 1),
        kantinName: String(parsed.kantinName || "Kantin Mak E"),
        items,
      };
    } catch (error) {
      checkoutCart = { kantinId: 1, kantinName: "Kantin Mak E", items: [] };
    }
  };

  const saveCart = () => {
    sessionStorage.setItem(CART_KEY, JSON.stringify(checkoutCart));
  };

  const getTotal = () => checkoutCart.items.reduce((sum, item) => sum + item.price * item.qty, 0);

  const renderEmptyState = () => {
    cartList.replaceChildren();
    cartList.hidden = true;
    totalRow.hidden = true;
    emptyState.hidden = false;
    totalText.textContent = "Rp 0";
    submitButton.disabled = true;
    submitButton.textContent = "Pesan Sekarang, Rp 0";
  };

  const renderItems = () => {
    const total = getTotal();
    cartList.hidden = false;
    totalRow.hidden = false;
    emptyState.hidden = true;
    submitButton.disabled = false;
    totalText.textContent = formatRupiah(total);
    submitButton.textContent = `Pesan Sekarang, ${formatRupiah(total)}`;

    cartList.replaceChildren(
      ...checkoutCart.items.map((item) => {
        const row = document.createElement("article");
        row.className = "checkout-cart-item";
        row.dataset.itemId = item.id;

        const main = document.createElement("div");
        main.className = "checkout-cart-item-main";

        const image = document.createElement("img");
        image.src = item.image;
        image.alt = item.name;

        const copy = document.createElement("div");
        copy.className = "checkout-cart-item-copy";

        const name = document.createElement("strong");
        name.textContent = item.name;

        const price = document.createElement("span");
        price.textContent = formatRupiah(item.price);

        copy.append(name, price);
        main.append(image, copy);

        const actions = document.createElement("div");
        actions.className = "checkout-cart-item-actions";

        const qty = document.createElement("div");
        qty.className = "checkout-qty";

        const decrease = document.createElement("button");
        decrease.type = "button";
        decrease.dataset.checkoutAction = "decrease";
        decrease.dataset.itemId = item.id;
        decrease.ariaLabel = `Kurangi ${item.name}`;
        decrease.textContent = "-";

        const quantity = document.createElement("strong");
        quantity.textContent = String(item.qty);

        const increase = document.createElement("button");
        increase.type = "button";
        increase.dataset.checkoutAction = "increase";
        increase.dataset.itemId = item.id;
        increase.ariaLabel = `Tambah ${item.name}`;
        increase.textContent = "+";
        increase.disabled = item.qty >= item.stock;

        qty.append(decrease, quantity, increase);

        const remove = document.createElement("button");
        remove.type = "button";
        remove.className = "checkout-remove";
        remove.dataset.checkoutAction = "remove";
        remove.dataset.itemId = item.id;
        remove.ariaLabel = `Hapus ${item.name}`;

        const removeIcon = document.createElement("img");
        removeIcon.src = "assets/img/kantin-1/icon-trash-red.svg";
        removeIcon.alt = "";
        remove.append(removeIcon);

        actions.append(qty, remove);
        row.append(main, actions);
        return row;
      })
    );
  };

  const render = () => {
    if (!checkoutCart.items.length) {
      renderEmptyState();
      saveCart();
      return;
    }

    renderItems();
    saveCart();
  };

  const updateQuantity = (id, delta) => {
    const item = checkoutCart.items.find((entry) => entry.id === id);
    if (!item) return;

    const nextQty = item.qty + delta;
    if (nextQty < 1) {
      checkoutCart.items = checkoutCart.items.filter((entry) => entry.id !== id);
      render();
      return;
    }

    item.qty = Math.min(item.stock, nextQty);
    render();
  };

  cartList.addEventListener("click", (event) => {
    const actionButton = event.target.closest("[data-checkout-action]");
    if (!actionButton) return;

    const action = actionButton.dataset.checkoutAction;
    const id = actionButton.dataset.itemId;
    if (!action || !id) return;

    if (action === "remove") {
      checkoutCart.items = checkoutCart.items.filter((item) => item.id !== id);
      render();
      return;
    }

    updateQuantity(id, action === "increase" ? 1 : -1);
  });

  for (const option of pickupOptions) {
    option.addEventListener("click", () => {
      selectedPickupTime = option.dataset.pickupValue || selectedPickupTime;

      for (const current of pickupOptions) {
        const isActive = current === option;
        current.classList.toggle("is-active", isActive);
        current.setAttribute("aria-pressed", String(isActive));
      }
    });
  }

  for (const option of paymentOptions) {
    option.addEventListener("click", () => {
      const radio = option.querySelector('input[type="radio"]');
      if (radio) {
        radio.checked = true;
      }

      for (const current of paymentOptions) {
        current.classList.toggle("is-selected", current === option);
      }
    });
  }

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    if (!checkoutCart.items.length) return;

    const draft = {
      pickupTime: selectedPickupTime,
      note: noteField.value.trim(),
      paymentMethod: "qris",
      total: getTotal(),
      items: checkoutCart.items,
    };

    sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
    window.alert("Frontend only: checkout belum terhubung ke backend.");
  });

  readCart();
  render();
})();
