(function () {
  "use strict";

  // Pastikan komponen global sudah dimuat
  if (
    typeof window.api === "undefined" ||
    typeof window.showToast === "undefined"
  ) {
    console.error("pembayaran.js: Komponen global tidak ditemukan.");
    return;
  }

  const api = window.api;
  const showToast = window.showToast;

  /**
   * Menangani proses upload file dari modal impor pembayaran.
   */
  function handleImportPayment() {
    const form = document.getElementById("import-payment-form");
    if (!form) return;

    form.addEventListener("submit", async function (e) {
      e.preventDefault();

      const submitButton = this.querySelector('button[type="submit"]');
      const fileInput = document.getElementById("import-payments-file");
      const progressContainer = document.getElementById(
        "import-payment-progress"
      );
      const progressBar = document.getElementById("import-payment-bar");
      const resultContainer = document.getElementById("import-payment-result");

      if (fileInput.files.length === 0) {
        showToast("Silakan pilih file untuk diunggah.", "warning");
        return;
      }

      submitButton.disabled = true;
      submitButton.innerHTML =
        '<i class="ti ti-loader-2 animate-spin"></i> Memproses...';
      progressContainer.classList.remove("hidden");
      progressBar.style.width = "0%";
      resultContainer.innerHTML = "";

      const formData = new FormData(this);

      try {
        const response = await api.post("/import-payment", formData, {
          headers: { "Content-Type": "multipart/form-data" },
          onUploadProgress: (progressEvent) => {
            const percentCompleted = Math.round(
              (progressEvent.loaded * 100) / progressEvent.total
            );
            progressBar.style.width = `${percentCompleted}%`;
          },
        });

        if (response.data && response.data.success) {
          showToast(
            response.data.message || "File pembayaran berhasil diproses.",
            "success"
          );
          resultContainer.innerHTML = `<div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg">${response.data.message}</div>`;

          setTimeout(() => {
            document
              .getElementById("import-payment-modal")
              .classList.add("hidden");
            window.location.reload();
          }, 2500);
        }
      } catch (error) {
        const errorMessage =
          error.response?.data?.message || "Gagal memproses file.";
        resultContainer.innerHTML = `<div class="p-4 text-sm text-red-700 bg-red-100 rounded-lg"><strong>Error:</strong> ${errorMessage}</div>`;

        // Jika ada detail error (misal: dari validasi jumlah)
        if (error.response?.data?.errors) {
          let errorList = '<ul class="mt-2 list-disc list-inside">';
          error.response.data.errors.forEach((err) => {
            errorList += `<li>NIS: ${err[0]}, VA: ${err[1]}, Error: ${err[3]}</li>`;
          });
          errorList += "</ul>";
          resultContainer.querySelector(".p-4").innerHTML += errorList;
        }
      } finally {
        submitButton.disabled = false;
        submitButton.innerHTML =
          '<i class="ti ti-upload"></i> Proses File Pembayaran';
      }
    });
  }

  /**
   * Menampilkan nama file yang dipilih di input.
   */
  function handleFileNameDisplay() {
    const fileInput = document.getElementById("import-payments-file");
    const fileNameDisplay = document.getElementById("import-payment-file-name");
    if (!fileInput || !fileNameDisplay) return;

    fileInput.addEventListener("change", () => {
      fileNameDisplay.textContent =
        fileInput.files.length > 0
          ? fileInput.files[0].name
          : "Belum ada file dipilih.";
    });
  }

  async function notifyOpenBills() {
    console.log("A")
    try {
      const response = await api.get("/notify-bills?type=1"); 
      showToast(
        response.data.message ||
          "Berhasil mengirimkan notifikasi tagihan terbuka",
        "success"
      );
    } catch (error) {
      console.error("Gagal mengirim notifikasi tagihan terbuka:", error);
      showToast(
        error.response?.data?.message ||
          "Gagal mengirimkan notifikasi tagihan terbuka.",
        "error"
      );
    }
  }

  async function notifyCloseBills() {
    try {
      const response = await api.get("/notify-bills?type=2");
      showToast(
        response.data.message ||
          "Berhasil mengirimkan notifikasi tagihan tertutup",
        "success"
      );
    } catch (error) {
      console.error("Gagal mengirim notifikasi tagihan tertutup:", error);
      showToast(
        error.response?.data?.message ||
          "Gagal mengirimkan notifikasi tagihan tertutup.",
        "error"
      );
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    handleImportPayment();
    handleFileNameDisplay();
  });

  window.pages = window.pages || {};
  window.pages.pembayaran = {
    notifyOpenBills,
    notifyCloseBills,
  };
})();
