(() => {
  const paymentScreen = document.querySelector("[data-payment-screen]");
  const successScreen = document.querySelector("[data-success-screen]");
  const form = document.querySelector("[data-payment-form]");
  const input = document.querySelector("[data-proof-input]");
  const dropzone = document.querySelector("[data-proof-dropzone]");
  const label = document.querySelector("[data-proof-label]");
  const error = document.querySelector("[data-proof-error]");
  const agreementInput = document.querySelector("[data-payment-agreement]");
  const agreementError = document.querySelector("[data-agreement-error]");
  const submitButton = document.querySelector(".payment-qris-submit");
  const totalText = document.querySelector("[data-payment-total]");
  const countdownText = document.querySelector("[data-payment-countdown]");
  const qrisCode = document.querySelector("[data-qris-code]");
  const qrisGenerated = document.querySelector("[data-qris-generated]");
  const qrisFallback = document.querySelector("[data-qris-fallback]");
  const qrisStatus = document.querySelector("[data-qris-status]");
  const qrisRefresh = document.querySelector("[data-qris-refresh]");
  const cancelPayment = document.querySelector("[data-cancel-payment]");
  const cancelModal = document.querySelector("[data-cancel-modal]");
  const cancelClose = document.querySelector("[data-cancel-close]");
  const cancelConfirm = document.querySelector("[data-cancel-confirm]");

  if (
    !paymentScreen ||
    !successScreen ||
    !form ||
    !input ||
    !dropzone ||
    !label ||
    !error ||
    !agreementInput ||
    !agreementError ||
    !submitButton ||
    !totalText ||
    !countdownText ||
    !qrisCode ||
    !qrisGenerated ||
    !qrisFallback ||
    !qrisStatus ||
    !qrisRefresh ||
    !cancelPayment ||
    !cancelModal ||
    !cancelClose ||
    !cancelConfirm
  ) {
    return;
  }

  const DRAFT_KEY = "ecanteenCheckoutDraft";
  const PAYMENT_KEY = "ecanteenPaymentQris";
  const PAYMENT_DEADLINE_KEY = "ecanteenPaymentQrisDeadline";
  const PAYMENT_TIMER_SIGNATURE_KEY = "ecanteenPaymentQrisTimerSignature";
  const QRIS_STATIC_CODE_KEY = "ecanteenQrisStaticCode";
  const QRIS_VALIDITY_MS = 5 * 60 * 1000;
  const basePath = window.location.pathname.replace(/\/payment-qris\/?$/, "");
  let selectedProof = null;
  let proofObjectUrl = "";
  let countdownTimer = 0;
  let currentTotal = 0;
  let currentTimerSignature = "";
  let qrisExpired = false;
  let pendingSuccessVisible = false;

  const formatRupiah = (amount) => `Rp ${new Intl.NumberFormat("id-ID").format(amount)}`;

  const readDraft = () => {
    try {
      return JSON.parse(sessionStorage.getItem(DRAFT_KEY) || "{}");
    } catch (error) {
      return {};
    }
  };

  const syncPaymentMeta = () => {
    const draft = readDraft();
    const total = Number(draft.total || 0);

    currentTotal = Math.max(0, Math.round(total));
    currentTimerSignature = JSON.stringify({
      total: currentTotal,
      paymentMethod: draft.paymentMethod || "qris",
      items: Array.isArray(draft.items)
        ? draft.items.map((item) => ({
            id: String(item?.id || item?.name || ""),
            qty: Number(item?.qty || 0),
            price: Number(item?.price || 0),
          }))
        : [],
    });
    totalText.textContent = formatRupiah(total);
  };

  const createPaymentDeadline = () => {
    const nextDeadline = Date.now() + QRIS_VALIDITY_MS;

    sessionStorage.setItem(PAYMENT_DEADLINE_KEY, String(nextDeadline));
    return nextDeadline;
  };

  const getPaymentDeadline = () => {
    const existing = Number(sessionStorage.getItem(PAYMENT_DEADLINE_KEY) || 0);
    const existingSignature = sessionStorage.getItem(PAYMENT_TIMER_SIGNATURE_KEY) || "";
    const now = Date.now();

    if (existingSignature === currentTimerSignature && existing > now && existing - now <= QRIS_VALIDITY_MS) {
      return existing;
    }

    sessionStorage.setItem(PAYMENT_TIMER_SIGNATURE_KEY, currentTimerSignature);
    return createPaymentDeadline();
  };

  const setQrisExpiredState = () => {
    qrisExpired = true;
    qrisCode.classList.add("is-expired");
    qrisRefresh.hidden = false;
    setQrisStatus("Waktu QRIS habis. Refresh QRIS untuk membuat kode baru.", true);
  };

  const updateCountdown = () => {
    const deadline = getPaymentDeadline();
    const remainingSeconds = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
    const minutes = String(Math.floor(remainingSeconds / 60)).padStart(2, "0");
    const seconds = String(remainingSeconds % 60).padStart(2, "0");

    countdownText.textContent = `${minutes}:${seconds}`;

    if (remainingSeconds === 0) {
      window.clearInterval(countdownTimer);
      setQrisExpiredState();
    }
  };

  const convertCRC16 = (value) => {
    let crc = 0xffff;

    for (let index = 0; index < value.length; index += 1) {
      crc ^= value.charCodeAt(index) << 8;

      for (let bit = 0; bit < 8; bit += 1) {
        crc = crc & 0x8000 ? (crc << 1) ^ 0x1021 : crc << 1;
      }
    }

    return (crc & 0xffff).toString(16).toUpperCase().padStart(4, "0");
  };

  const convertToDynamicQRIS = (qris, nominal) => {
    const cleanNominal = String(Math.max(0, Math.round(Number(nominal) || 0)));
    const qrisWithoutCrc = qris.slice(0, -4).replace("010211", "010212");
    const [prefix, suffix] = qrisWithoutCrc.split("5802ID");

    if (!prefix || !suffix || cleanNominal === "0") {
      return "";
    }

    const nominalData = `54${String(cleanNominal.length).padStart(2, "0")}${cleanNominal}`;
    const payloadWithoutCrc = `${prefix}${nominalData}5802ID${suffix}`;

    return `${payloadWithoutCrc}${convertCRC16(payloadWithoutCrc)}`;
  };

  const setQrisStatus = (message, isError = false) => {
    qrisStatus.textContent = message;
    qrisStatus.hidden = false;
    qrisStatus.classList.toggle("is-error", isError);
  };

  const drawDynamicQRIS = (dynamicQRIS) => {
    qrisGenerated.replaceChildren();

    if (!dynamicQRIS || typeof QRCode === "undefined") {
      qrisGenerated.hidden = true;
      qrisFallback.hidden = false;
      return false;
    }

    qrisFallback.hidden = true;
    qrisGenerated.hidden = false;

    new QRCode(qrisGenerated, {
      correctLevel: QRCode.CorrectLevel.M,
      text: dynamicQRIS,
      width: 256,
      height: 256,
    });

    return true;
  };

  const decodeStaticQRISFromImage = (imageUrl) =>
    new Promise((resolve, reject) => {
      if (typeof jsQR === "undefined") {
        reject(new Error("Library pembaca QR belum tersedia."));
        return;
      }

      const image = new Image();
      image.onload = () => {
        const canvas = document.createElement("canvas");
        canvas.width = image.naturalWidth || image.width;
        canvas.height = image.naturalHeight || image.height;

        const context = canvas.getContext("2d", { willReadFrequently: true });
        if (!context) {
          reject(new Error("Browser tidak bisa membaca canvas QRIS."));
          return;
        }

        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        const result = jsQR(imageData.data, imageData.width, imageData.height);

        if (!result?.data) {
          reject(new Error("QRIS statis tidak bisa dibaca dari gambar."));
          return;
        }

        resolve(result.data);
      };
      image.onerror = () => reject(new Error("Gambar QRIS statis gagal dimuat."));
      image.src = imageUrl;
    });

  const renderDynamicQRIS = async ({ resetTimer = false } = {}) => {
    if (!currentTotal) {
      setQrisStatus("Nominal belum tersedia. QRIS statis ditampilkan sementara.", true);
      qrisGenerated.hidden = true;
      qrisFallback.hidden = false;
      qrisRefresh.hidden = true;
      return;
    }

    try {
      if (resetTimer) {
        sessionStorage.setItem(PAYMENT_TIMER_SIGNATURE_KEY, currentTimerSignature);
        createPaymentDeadline();
      }

      const staticCode =
        sessionStorage.getItem(QRIS_STATIC_CODE_KEY) ||
        (await decodeStaticQRISFromImage(qrisCode.dataset.staticQrisSrc || qrisFallback.src));

      sessionStorage.setItem(QRIS_STATIC_CODE_KEY, staticCode);
      const dynamicQRIS = convertToDynamicQRIS(staticCode, currentTotal);

      if (!drawDynamicQRIS(dynamicQRIS)) {
        setQrisStatus("QRIS dinamis belum bisa dibuat. QRIS statis ditampilkan sementara.", true);
        return;
      }

      qrisExpired = false;
      qrisCode.classList.remove("is-expired");
      qrisRefresh.hidden = true;
      qrisStatus.hidden = true;
    } catch (error) {
      qrisGenerated.hidden = true;
      qrisFallback.hidden = false;
      qrisRefresh.hidden = true;
      setQrisStatus("QRIS dinamis belum bisa dibuat. QRIS statis ditampilkan sementara.", true);
    }
  };

  const clearProofObjectUrl = () => {
    if (proofObjectUrl) {
      URL.revokeObjectURL(proofObjectUrl);
      proofObjectUrl = "";
    }
  };

  const showPendingSuccessScreen = () => {
    if (pendingSuccessVisible) return;
    pendingSuccessVisible = true;
    paymentScreen.hidden = true;
    successScreen.hidden = false;
    window.scrollTo(0, 0);
  };

  const hidePendingSuccessScreen = () => {
    if (!pendingSuccessVisible) return;
    pendingSuccessVisible = false;
    successScreen.hidden = true;
    paymentScreen.hidden = false;
  };

  const setError = (message) => {
    error.textContent = message;
    error.hidden = false;
  };

  const clearError = () => {
    error.hidden = true;
  };

  const setAgreementError = () => {
    agreementError.hidden = false;
  };

  const clearAgreementError = () => {
    agreementError.hidden = true;
  };

  const setProof = (file) => {
    if (!file) return;

    if (!file.type.startsWith("image/")) {
      selectedProof = null;
      input.value = "";
      label.textContent = "Click or drop image";
      dropzone.classList.remove("has-file");
      setError("File harus berupa gambar bukti pembayaran.");
      return;
    }

    selectedProof = file;
    label.textContent = file.name;
    dropzone.classList.add("has-file");
    clearError();
  };

  const setSubmitLoading = (isLoading) => {
    submitButton.disabled = isLoading;
    submitButton.classList.toggle("is-loading", isLoading);
    submitButton.textContent = isLoading ? "Menyimpan pesanan..." : "Sudah Membayar";
  };

  const submitPaymentProof = async (draft = readDraft()) => {
    const formData = new FormData();

    formData.append("proof", selectedProof);
    formData.append(
      "draft",
      JSON.stringify({
        ...draft,
        paymentMethod: "qris",
      })
    );

    const response = await fetch(`${basePath}/api/confirm-qris-payment.php`, {
      method: "POST",
      body: formData,
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok || !data.ok) {
      throw new Error(data.message || "Pembayaran gagal disimpan.");
    }

    return data;
  };

  input.addEventListener("change", () => {
    setProof(input.files?.[0]);
  });

  agreementInput.addEventListener("change", clearAgreementError);

  dropzone.addEventListener("keydown", (event) => {
    if (event.key !== "Enter" && event.key !== " ") return;
    event.preventDefault();
    input.click();
  });

  for (const eventName of ["dragenter", "dragover"]) {
    dropzone.addEventListener(eventName, (event) => {
      event.preventDefault();
      dropzone.classList.add("is-dragging");
    });
  }

  for (const eventName of ["dragleave", "drop"]) {
    dropzone.addEventListener(eventName, (event) => {
      event.preventDefault();
      dropzone.classList.remove("is-dragging");
    });
  }

  dropzone.addEventListener("drop", (event) => {
    setProof(event.dataTransfer?.files?.[0]);
  });

  form.addEventListener("submit", async (event) => {
    event.preventDefault();

    if (qrisExpired) {
      setQrisStatus("QRIS sudah kedaluwarsa. Refresh QRIS sebelum mengunggah bukti pembayaran.", true);
      qrisRefresh.hidden = false;
      qrisRefresh.focus();
      return;
    }

    if (!selectedProof) {
      setError("Pilih gambar bukti pembayaran terlebih dahulu.");
      dropzone.focus();
      return;
    }

    if (!agreementInput.checked) {
      setAgreementError();
      agreementInput.focus();
      return;
    }

    clearProofObjectUrl();
    proofObjectUrl = URL.createObjectURL(selectedProof);
    setSubmitLoading(true);
    showPendingSuccessScreen();

    try {
      const draft = readDraft();
      const result = await submitPaymentProof(draft);
      sessionStorage.setItem(
        PAYMENT_KEY,
        JSON.stringify({
          status: "confirmed",
          proofFileName: selectedProof.name,
          proofFileType: selectedProof.type,
          proofFileSize: selectedProof.size,
          confirmedAt: new Date().toISOString(),
          pickupTime: draft.pickupTime || "",
          items: Array.isArray(draft.items)
            ? draft.items.map((item) => ({
                id: String(item?.id || item?.name || ""),
                name: String(item?.name || "Menu"),
                price: Number(item?.price || 0),
                qty: Number(item?.qty || 0),
              }))
            : [],
          ...result,
        })
      );

      sessionStorage.removeItem(DRAFT_KEY);
      sessionStorage.removeItem(PAYMENT_DEADLINE_KEY);
      sessionStorage.removeItem(PAYMENT_TIMER_SIGNATURE_KEY);
      window.clearInterval(countdownTimer);
      window.location.href = `${basePath}/payment-success?kode=${encodeURIComponent(result.order_code || "")}`;
    } catch (submitError) {
      hidePendingSuccessScreen();
      setError(submitError.message || "Pembayaran gagal disimpan.");
      dropzone.focus();
      setSubmitLoading(false);
    }
  });

  qrisRefresh.addEventListener("click", () => {
    window.clearInterval(countdownTimer);
    syncPaymentMeta();
    renderDynamicQRIS({ resetTimer: true });
    updateCountdown();
    countdownTimer = window.setInterval(updateCountdown, 1000);
  });

  const closeCancelModal = () => {
    cancelModal.hidden = true;
    cancelPayment.focus();
  };

  cancelPayment.addEventListener("click", (event) => {
    event.preventDefault();
    cancelModal.hidden = false;
    cancelClose.focus();
  });

  cancelClose.addEventListener("click", closeCancelModal);

  cancelModal.addEventListener("click", (event) => {
    if (event.target === cancelModal) {
      closeCancelModal();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !cancelModal.hidden) {
      closeCancelModal();
    }
  });

  window.addEventListener("beforeunload", clearProofObjectUrl);

  syncPaymentMeta();
  renderDynamicQRIS();
  updateCountdown();
  countdownTimer = window.setInterval(updateCountdown, 1000);
})();
