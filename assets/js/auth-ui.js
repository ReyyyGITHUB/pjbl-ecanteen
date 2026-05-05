(() => {
  const root = document.documentElement;
  root.classList.add("js");

  const toggles = Array.from(document.querySelectorAll("[data-toggle-password]"));
  for (const btn of toggles) {
    btn.addEventListener("click", () => {
      const inputId = btn.getAttribute("aria-controls");
      if (!inputId) return;
      const input = document.getElementById(inputId);
      if (!(input instanceof HTMLInputElement)) return;

      const nextType = input.type === "password" ? "text" : "password";
      input.type = nextType;
      btn.setAttribute("aria-pressed", nextType === "text" ? "true" : "false");

      const label = nextType === "text" ? "Sembunyikan password" : "Tampilkan password";
      btn.setAttribute("aria-label", label);
    });
  }

  const forms = Array.from(document.querySelectorAll("form.auth-form"));
  const phoneInputs = Array.from(document.querySelectorAll("[data-phone-local]"));

  const normalizeLocalPhone = (input) => {
    const digits = input.value.replace(/\D+/g, "").replace(/^0+/, "").slice(0, 13);
    input.value = digits;
  };

  for (const input of phoneInputs) {
    input.addEventListener("input", () => {
      normalizeLocalPhone(input);
    });

    input.addEventListener("paste", () => {
      window.requestAnimationFrame(() => normalizeLocalPhone(input));
    });
  }

  for (const form of forms) {
    form.addEventListener(
      "invalid",
      (e) => {
        const el = e.target;
        if (!(el instanceof HTMLElement)) return;
        el.classList.add("field-input-invalid");
      },
      true
    );

    form.addEventListener(
      "input",
      (e) => {
        const el = e.target;
        if (!(el instanceof HTMLElement)) return;
        el.classList.remove("field-input-invalid");
      },
      true
    );
  }
})();
