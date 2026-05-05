(() => {
  const paymentScreen = document.querySelector("[data-payment-screen]");
  const successScreen = document.querySelector("[data-success-screen]");
  const form = document.querySelector("[data-payment-form]");
  const input = document.querySelector("[data-proof-input]");
  const dropzone = document.querySelector("[data-proof-dropzone]");
  const label = document.querySelector("[data-proof-label]");
  const error = document.querySelector("[data-proof-error]");
  const downloadButton = document.querySelector("[data-download-proof]");

  if (!paymentScreen || !successScreen || !form || !input || !dropzone || !label || !error || !downloadButton) {
    return;
  }

  const PAYMENT_KEY = "ecanteenPaymentQris";
  let selectedProof = null;
  let proofObjectUrl = "";

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

  window.addEventListener("beforeunload", clearProofObjectUrl);
})();
