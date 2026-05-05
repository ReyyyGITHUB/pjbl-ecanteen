(() => {
  const paymentScreen = document.querySelector("[data-payment-screen]");
  const successScreen = document.querySelector("[data-success-screen]");
  const form = document.querySelector("[data-payment-form]");
  const input = document.querySelector("[data-proof-input]");
  const dropzone = document.querySelector("[data-proof-dropzone]");
  const label = document.querySelector("[data-proof-label]");
  const error = document.querySelector("[data-proof-error]");
  const downloadButton = document.querySelector("[data-download-proof]");
  const totalText = document.querySelector("[data-payment-total]");
  const countdownText = document.querySelector("[data-payment-countdown]");
  const qrisCode = document.querySelector("[data-qris-code]");
  const qrisGenerated = document.querySelector("[data-qris-generated]");
  const qrisFallback = document.querySelector("[data-qris-fallback]");
  const qrisStatus = document.querySelector("[data-qris-status]");
  const cancelPayment = document.querySelector("[data-cancel-payment]");

  if (
    !paymentScreen ||
    !successScreen ||
    !form ||
    !input ||
    !dropzone ||
    !label ||
    !error ||
    !downloadButton ||
    !totalText ||
    !countdownText ||
    !qrisCode ||
    !qrisGenerated ||
    !qrisFallback ||
    !qrisStatus ||
    !cancelPayment
  ) {
    return;
  }

  const DRAFT_KEY = "ecanteenCheckoutDraft";
  const PAYMENT_KEY = "ecanteenPaymentQris";
  const PAYMENT_DEADLINE_KEY = "ecanteenPaymentQrisDeadline";
  const QRIS_STATIC_CODE_KEY = "ecanteenQrisStaticCode";
  let selectedProof = null;
  let proofObjectUrl = "";
  let countdownTimer = 0;
  let currentTotal = 0;

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
    totalText.textContent = formatRupiah(total);
  };

  const getPaymentDeadline = () => {
    const existing = Number(sessionStorage.getItem(PAYMENT_DEADLINE_KEY) || 0);
    if (existing > Date.now()) return existing;

    const nextDeadline = Date.now() + 60 * 60 * 1000;
    sessionStorage.setItem(PAYMENT_DEADLINE_KEY, String(nextDeadline));
    return nextDeadline;
  };

  const updateCountdown = () => {
    const deadline = getPaymentDeadline();
    const remainingSeconds = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
    const minutes = String(Math.floor(remainingSeconds / 60)).padStart(2, "0");
    const seconds = String(remainingSeconds % 60).padStart(2, "0");

    countdownText.textContent = `${minutes}:${seconds}`;

    if (remainingSeconds === 0) {
      window.clearInterval(countdownTimer);
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

  const renderDynamicQRIS = async () => {
    if (!currentTotal) {
      setQrisStatus("Nominal belum tersedia. QRIS statis ditampilkan sementara.", true);
      qrisGenerated.hidden = true;
      qrisFallback.hidden = false;
      return;
    }

    try {
      const staticCode =
        sessionStorage.getItem(QRIS_STATIC_CODE_KEY) ||
        (await decodeStaticQRISFromImage(qrisCode.dataset.staticQrisSrc || qrisFallback.src));

      sessionStorage.setItem(QRIS_STATIC_CODE_KEY, staticCode);
      const dynamicQRIS = convertToDynamicQRIS(staticCode, currentTotal);

      if (!drawDynamicQRIS(dynamicQRIS)) {
        setQrisStatus("QRIS dinamis belum bisa dibuat. QRIS statis ditampilkan sementara.", true);
        return;
      }

      qrisStatus.hidden = true;
    } catch (error) {
      qrisGenerated.hidden = true;
      qrisFallback.hidden = false;
      setQrisStatus("QRIS dinamis belum bisa dibuat. QRIS statis ditampilkan sementara.", true);
    }
  };

  const clearProofObjectUrl = () => {
    if (proofObjectUrl) {
      URL.revokeObjectURL(proofObjectUrl);
      proofObjectUrl = "";
    }
  };

  const setError = (message) => {
    error.textContent = message;
    error.hidden = false;
  };

  const clearError = () => {
    error.hidden = true;
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

  input.addEventListener("change", () => {
    setProof(input.files?.[0]);
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

  dropzone.addEventListener("drop", (event) => {
    setProof(event.dataTransfer?.files?.[0]);
  });

  form.addEventListener("submit", (event) => {
    event.preventDefault();

    if (!selectedProof) {
      setError("Pilih gambar bukti pembayaran terlebih dahulu.");
      dropzone.focus();
      return;
    }

    clearProofObjectUrl();
    proofObjectUrl = URL.createObjectURL(selectedProof);

    sessionStorage.setItem(
      PAYMENT_KEY,
      JSON.stringify({
        status: "confirmed_frontend_only",
        proofFileName: selectedProof.name,
        proofFileType: selectedProof.type,
        proofFileSize: selectedProof.size,
        confirmedAt: new Date().toISOString(),
      })
    );

    window.clearInterval(countdownTimer);
    paymentScreen.hidden = true;
    successScreen.hidden = false;
    downloadButton.focus();
  });

  downloadButton.addEventListener("click", () => {
    if (!proofObjectUrl || !selectedProof) return;

    const link = document.createElement("a");
    link.href = proofObjectUrl;
    link.download = selectedProof.name || "bukti-pembayaran.png";
    document.body.appendChild(link);
    link.click();
    link.remove();
  });

  cancelPayment.addEventListener("click", (event) => {
    const shouldCancel = window.confirm("Apakah Anda ingin membatalkan pesanan?");

    if (!shouldCancel) {
      event.preventDefault();
    }
  });

  window.addEventListener("beforeunload", clearProofObjectUrl);

  syncPaymentMeta();
  renderDynamicQRIS();
  updateCountdown();
  countdownTimer = window.setInterval(updateCountdown, 1000);
})();
