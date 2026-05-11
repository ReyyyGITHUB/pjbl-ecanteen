(() => {
  const root = document.querySelector("[data-payment-root]");
  if (!root) return;

  const runDemoPayment = () => {
    const paymentScreen = document.querySelector("[data-payment-screen]");
    const successScreen = document.querySelector("[data-success-screen]");
    const form = document.querySelector("[data-payment-form]");
    const input = document.querySelector("[data-proof-input]");
    const dropzone = document.querySelector("[data-proof-dropzone]");
    const preview = document.querySelector("[data-proof-preview]");
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
      !cancelClose
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
    let previewObjectUrl = "";
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

    const setQrisStatus = (message, isError = false) => {
      qrisStatus.textContent = message;
      qrisStatus.hidden = false;
      qrisStatus.classList.toggle("is-error", isError);
    };

    const updateCountdown = () => {
      const deadline = getPaymentDeadline();
      const remainingSeconds = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
      countdownText.textContent = `${String(Math.floor(remainingSeconds / 60)).padStart(2, "0")}:${String(remainingSeconds % 60).padStart(2, "0")}`;
      if (remainingSeconds === 0) {
        window.clearInterval(countdownTimer);
        qrisExpired = true;
        qrisCode.classList.add("is-expired");
        qrisRefresh.hidden = false;
        setQrisStatus("Waktu QRIS habis. Refresh QRIS untuk membuat kode baru.", true);
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
      if (!prefix || !suffix || cleanNominal === "0") return "";
      const nominalData = `54${String(cleanNominal.length).padStart(2, "0")}${cleanNominal}`;
      const payloadWithoutCrc = `${prefix}${nominalData}5802ID${suffix}`;
      return `${payloadWithoutCrc}${convertCRC16(payloadWithoutCrc)}`;
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

    const resetPreview = () => {
      if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
      previewObjectUrl = "";
      if (preview) {
        preview.src = "";
        preview.hidden = true;
      }
      dropzone.classList.remove("has-preview");
      label.textContent = "Click or drop image";
    };

    const showPreview = (file) => {
      if (!preview) return;
      if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
      previewObjectUrl = URL.createObjectURL(file);
      preview.src = previewObjectUrl;
      preview.hidden = false;
      dropzone.classList.add("has-preview");
      label.textContent = file.name;
    };

    const setProof = (file) => {
      if (!file) return;
      if (!file.type.startsWith("image/")) {
        selectedProof = null;
        input.value = "";
        dropzone.classList.remove("has-file");
        resetPreview();
        error.textContent = "File harus berupa gambar bukti pembayaran.";
        error.hidden = false;
        return;
      }
      selectedProof = file;
      dropzone.classList.add("has-file");
      showPreview(file);
      error.hidden = true;
    };

    const setSubmitLoading = (isLoading) => {
      submitButton.disabled = isLoading;
      submitButton.classList.toggle("is-loading", isLoading);
      submitButton.textContent = isLoading ? "Menyimpan pesanan..." : "Sudah Membayar";
    };

    const submitPaymentProof = async (draft = readDraft()) => {
      const formData = new FormData();
      formData.append("proof", selectedProof);
      formData.append("draft", JSON.stringify({ ...draft, paymentMethod: "qris" }));
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

    input.addEventListener("change", () => setProof(input.files?.[0]));
    agreementInput.addEventListener("change", () => {
      agreementError.hidden = true;
    });
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
    dropzone.addEventListener("drop", (event) => setProof(event.dataTransfer?.files?.[0]));

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      if (qrisExpired) {
        setQrisStatus("QRIS sudah kedaluwarsa. Refresh QRIS sebelum mengunggah bukti pembayaran.", true);
        qrisRefresh.hidden = false;
        qrisRefresh.focus();
        return;
      }
      if (!selectedProof) {
        error.textContent = "Pilih gambar bukti pembayaran terlebih dahulu.";
        error.hidden = false;
        dropzone.focus();
        return;
      }
      if (!agreementInput.checked) {
        agreementError.hidden = false;
        agreementInput.focus();
        return;
      }

      setSubmitLoading(true);
      paymentScreen.hidden = true;
      successScreen.hidden = false;
      pendingSuccessVisible = true;
      window.scrollTo(0, 0);

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
        if (pendingSuccessVisible) {
          pendingSuccessVisible = false;
          successScreen.hidden = true;
          paymentScreen.hidden = false;
        }
        error.textContent = submitError.message || "Pembayaran gagal disimpan.";
        error.hidden = false;
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
      if (event.target === cancelModal) closeCancelModal();
    });
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && !cancelModal.hidden) closeCancelModal();
    });
    window.addEventListener("beforeunload", () => {
      if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
    });

    syncPaymentMeta();
    renderDynamicQRIS();
    updateCountdown();
    countdownTimer = window.setInterval(updateCountdown, 1000);
  };

  if (root.dataset.demoMode === "true") {
    runDemoPayment();
    return;
  }

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
  const louvinStatus = document.querySelector("[data-louvin-status]");
  const louvinOrderCode = document.querySelector("[data-louvin-order-code]");
  const louvinFee = document.querySelector("[data-louvin-fee]");
  const louvinNet = document.querySelector("[data-louvin-net]");
  const louvinExpired = document.querySelector("[data-louvin-expired]");
  const checkNow = document.querySelector("[data-check-payment-now]");
  const successLink = document.querySelector("[data-success-link]");

  if (
    !root ||
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
    !cancelConfirm ||
    !louvinStatus ||
    !louvinOrderCode ||
    !louvinFee ||
    !louvinNet ||
    !louvinExpired ||
    !checkNow ||
    !successLink
  ) {
    return;
  }

  const DRAFT_KEY = "ecanteenCheckoutDraft";
  const PAYMENT_KEY = "ecanteenPaymentQris";
  const LOUVIN_KEY = "ecanteenLouvinPayment";
  const createPaymentApi = root.dataset.createPaymentApi || "api/create-louvin-payment.php";
  const checkPaymentApi = root.dataset.checkPaymentApi || "api/check-louvin-payment.php";
  const successUrl = root.dataset.successUrl || "payment-success";
  const basePath = window.location.pathname.replace(/\/payment-qris\/?$/, "");
  const pollIntervalMs = 3000;
  let pollTimer = 0;
  let countdownTimer = 0;
  let activePayment = null;

  const formatRupiah = (amount) => `Rp ${new Intl.NumberFormat("id-ID").format(Number(amount) || 0)}`;

  const readJsonStorage = (key) => {
    try {
      const parsed = JSON.parse(sessionStorage.getItem(key) || "null");
      return parsed && typeof parsed === "object" ? parsed : null;
    } catch (error) {
      return null;
    }
  };

  const writeJsonStorage = (key, value) => {
    sessionStorage.setItem(key, JSON.stringify(value));
  };

  const readDraft = () => readJsonStorage(DRAFT_KEY) || {};

  const setStatus = (message, isError = false) => {
    qrisStatus.textContent = message;
    qrisStatus.hidden = false;
    qrisStatus.classList.toggle("is-error", isError);
    louvinStatus.textContent = message;
    louvinStatus.classList.toggle("is-error", isError);
  };

  const drawQR = (qrString) => {
    qrisGenerated.replaceChildren();

    if (!qrString || typeof QRCode === "undefined") {
      qrisGenerated.hidden = true;
      qrisFallback.hidden = false;
      setStatus("QRIS belum bisa dibuat. Library QR belum siap.", true);
      return false;
    }

    qrisFallback.hidden = true;
    qrisGenerated.hidden = false;
    qrisCode.classList.remove("is-expired");

    new QRCode(qrisGenerated, {
      correctLevel: QRCode.CorrectLevel.M,
      text: qrString,
      width: 256,
      height: 256,
    });

    qrisStatus.hidden = true;
    return true;
  };

  const syncMeta = (payment) => {
    const total = Number(payment?.total || payment?.payment?.total_payment || 0);
    totalText.textContent = formatRupiah(total);
    louvinOrderCode.textContent = String(payment?.order_code || "-");
    louvinFee.textContent = formatRupiah(payment?.fee || 0);
    louvinNet.textContent = formatRupiah(payment?.net_amount || 0);

    const expiredAt = payment?.expired_at ? new Date(payment.expired_at) : null;
    louvinExpired.textContent = expiredAt && !Number.isNaN(expiredAt.getTime())
      ? new Intl.DateTimeFormat("id-ID", { hour: "2-digit", minute: "2-digit", day: "2-digit", month: "2-digit" }).format(expiredAt)
      : "-";

    successLink.href = payment?.order_code
      ? `${successUrl}?kode=${encodeURIComponent(payment.order_code)}`
      : successUrl;
  };

  const updateCountdown = () => {
    const expiredAt = activePayment?.expired_at ? new Date(activePayment.expired_at).getTime() : 0;
    if (!expiredAt) {
      countdownText.textContent = "--:--";
      return;
    }

    const remainingSeconds = Math.max(0, Math.floor((expiredAt - Date.now()) / 1000));
    const minutes = String(Math.floor(remainingSeconds / 60)).padStart(2, "0");
    const seconds = String(remainingSeconds % 60).padStart(2, "0");
    countdownText.textContent = `${minutes}:${seconds}`;

    if (remainingSeconds === 0) {
      window.clearInterval(countdownTimer);
      window.clearInterval(pollTimer);
      qrisCode.classList.add("is-expired");
      qrisRefresh.hidden = false;
      setStatus("QRIS kedaluwarsa. Kembali ke checkout lalu buat pembayaran baru.", true);
    }
  };

  const createLouvinPayment = async () => {
    const draft = readDraft();
    if (!Array.isArray(draft.items) || draft.items.length < 1) {
      throw new Error("Data checkout tidak ditemukan. Kembali ke checkout lalu coba lagi.");
    }

    const response = await fetch(createPaymentApi, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        ...draft,
        paymentMethod: "qris",
      }),
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok || !data.ok) {
      throw new Error(data.message || "Transaksi Louvin gagal dibuat.");
    }

    return data;
  };

  const checkPaymentStatus = async () => {
    if (!activePayment?.order_code && !activePayment?.transaction_id) return null;

    checkNow.disabled = true;
    checkNow.textContent = "Mengecek...";

    try {
      const url = new URL(checkPaymentApi, window.location.origin);
      url.searchParams.set("order_code", activePayment.order_code || "");
      url.searchParams.set("transaction_id", activePayment.transaction_id || "");
      const response = await fetch(url.toString(), {
        headers: { Accept: "application/json" },
      });
      const data = await response.json().catch(() => ({}));

      if (!response.ok || !data.ok) {
        throw new Error(data.message || "Status pembayaran gagal dicek.");
      }

      activePayment = {
        ...activePayment,
        status: data.status,
        total: data.total,
        net_amount: data.net_amount || activePayment.net_amount,
        fee: data.fee || activePayment.fee,
      };
      writeJsonStorage(LOUVIN_KEY, activePayment);

      if (data.confirmed || data.status === "settled" || data.payment_status === "pembayaran_dikonfirmasi") {
        const successPayload = {
          status: "confirmed",
          order_code: data.order_code || activePayment.order_code,
          transaction_id: data.transaction_id || activePayment.transaction_id,
          total: data.total || activePayment.total,
          fee: data.fee || activePayment.fee,
          net_amount: data.net_amount || activePayment.net_amount,
          confirmedAt: new Date().toISOString(),
          pickupTime: readDraft().pickupTime || "",
          items: Array.isArray(data.items) && data.items.length ? data.items : readDraft().items || [],
          wa_status: data.wa_status || "pending",
          wa_error: data.wa_error || "",
          buyer_wa_status: data.buyer_wa_status || "pending",
          buyer_wa_error: data.buyer_wa_error || "",
          manual_whatsapp_url: data.manual_whatsapp_url || "",
        };

        writeJsonStorage(PAYMENT_KEY, successPayload);
        sessionStorage.removeItem(DRAFT_KEY);
        sessionStorage.removeItem(LOUVIN_KEY);
        window.clearInterval(pollTimer);
        window.clearInterval(countdownTimer);
        setStatus("Pembayaran berhasil dikonfirmasi. Mengalihkan ke halaman sukses...");
        window.showPageLoader?.("Pembayaran berhasil...");
        window.location.href = `${successUrl}?kode=${encodeURIComponent(successPayload.order_code || "")}`;
        return data;
      }

      if (data.status === "failed" || data.payment_status === "pembayaran_ditolak") {
        window.clearInterval(pollTimer);
        window.clearInterval(countdownTimer);
        qrisCode.classList.add("is-expired");
        setStatus("Pembayaran gagal atau kedaluwarsa. Kembali ke checkout lalu coba lagi.", true);
        return data;
      }

      setStatus("Menunggu pembayaran dari e-wallet atau mobile banking...");
      return data;
    } finally {
      checkNow.disabled = false;
      checkNow.textContent = "Cek Status";
    }
  };

  const startPolling = () => {
    window.clearInterval(pollTimer);
    pollTimer = window.setInterval(checkPaymentStatus, pollIntervalMs);
  };

  const startCountdown = () => {
    window.clearInterval(countdownTimer);
    updateCountdown();
    countdownTimer = window.setInterval(updateCountdown, 1000);
  };

  const initPayment = async () => {
    setStatus("Menyiapkan invoice QRIS Louvin...");
    checkNow.disabled = true;
    const draft = readDraft();
    if (Number(draft.total || 0) > 0) {
      totalText.textContent = formatRupiah(draft.total);
    }

    const stored = readJsonStorage(LOUVIN_KEY);
    if (stored?.order_code && stored?.payment?.qr_string) {
      activePayment = stored;
    } else {
      activePayment = await createLouvinPayment();
      writeJsonStorage(LOUVIN_KEY, activePayment);
    }

    syncMeta(activePayment);
    drawQR(activePayment.payment?.qr_string || activePayment.payment?.payment_number || "");
    setStatus("Scan QRIS, lalu tunggu konfirmasi otomatis dari Louvin.");
    checkNow.disabled = false;
    successLink.hidden = true;
    startCountdown();
    startPolling();
    await checkPaymentStatus();
  };

  qrisRefresh.addEventListener("click", () => {
    sessionStorage.removeItem(LOUVIN_KEY);
    window.location.href = `${basePath}/checkout`;
  });

  checkNow.addEventListener("click", checkPaymentStatus);

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

  initPayment().catch((error) => {
    window.clearInterval(pollTimer);
    window.clearInterval(countdownTimer);
    checkNow.disabled = true;
    qrisGenerated.hidden = true;
    qrisFallback.hidden = true;
    setStatus(error.message || "Pembayaran Louvin gagal disiapkan.", true);
  });
})();
