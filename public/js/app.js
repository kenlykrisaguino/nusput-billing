(function () {
  "use strict";

  if (typeof window === "undefined") {
    console.log(
      "App.js: Not in a browser environment, skipping initializations."
    );
    return;
  }

  // --- 1. Notyf Initialization ---
  const notyf = new Notyf({
    duration: 4000,
    position: { x: "left", y: "bottom" },
    ripple: true,
    dismissible: true,
    types: [
      {
        type: "success",
        backgroundColor: "#28a745",
        icon: { className: "ti ti-circle-check", tagName: "i", text: "" },
      },
      {
        type: "error",
        backgroundColor: "#dc3545",
        icon: { className: "ti ti-alert-circle", tagName: "i", text: "" },
      },
      {
        type: "warning",
        backgroundColor: "#ffc107",
        icon: { className: "ti ti-alert-triangle", tagName: "i", text: "" },
        className: "notyf__toast--warning text-dark",
      },
      {
        type: "info",
        backgroundColor: "#17a2b8",
        icon: { className: "ti ti-info-circle", tagName: "i", text: "" },
      },
    ],
  });

  window.showToast = function (message, type = "info") {
    const validTypes = ["success", "error", "warning", "info"];
    if (
      !validTypes.includes(type) &&
      !notyf.options.types.find((t) => t.type === type)
    ) {
      notyf.open({ type: "info", message: message });
      return;
    }
    notyf.open({ type: type, message: message });
  };

  // --- 2. Axios Setup ---
  const apiClient = window.axios.create({
    baseURL: "/api",
    timeout: 20000,
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
      Accept: "application/json",
    },
  });

  apiClient.interceptors.request.use(
    (config) => {
      const authToken = localStorage.getItem("userAuthToken");
      if (authToken) {
        config.headers.Authorization = `Bearer ${authToken}`;
      }
      return config;
    },
    (error) => {
      return Promise.reject(error);
    }
  );

  apiClient.interceptors.response.use(
    (response) => {
      return response;
    },
    (error) => {
      console.error("API Call Failed:", error);
      let errorMessage = "Terjadi kesalahan yang tidak diketahui.";
      if (error.response) {
        const status = error.response.status;
        const data = error.response.data;
        errorMessage = data?.message || error.message;
        switch (status) {
          case 400:
            errorMessage = data?.message || "Permintaan tidak valid.";
            break;
          case 401:
            errorMessage =
              data?.message ||
              "Akses ditolak. Sesi Anda mungkin telah berakhir.";
            break;
          case 403:
            errorMessage =
              data?.message || "Anda tidak memiliki izin untuk tindakan ini.";
            break;
          case 404:
            errorMessage =
              data?.message ||
              `Endpoint API tidak ditemukan: ${error.config.url}`;
            break;
          case 422:
            errorMessage = data?.message || "Data yang dikirim tidak valid.";
            if (data && data.errors) {
              errorMessage = Object.values(data.errors).flat().join("\n");
            }
            break;
          case 500:
          case 502:
          case 503:
          case 504:
            errorMessage = data?.message || "Server sedang mengalami masalah.";
            break;
        }
        window.showToast(errorMessage, "error");
      } else if (error.request) {
        window.showToast("Tidak dapat terhubung ke server.", "error");
      } else {
        window.showToast(
          error.message || "Terjadi kesalahan saat memproses permintaan.",
          "error"
        );
      }
      return Promise.reject(error);
    }
  );
  window.api = apiClient;

  // --- 3. SweetAlert2 Confirmation Dialog ---
  window.showConfirmationDialog = function (options, onConfirm, onCancel) {
    if (typeof Swal === "undefined") {
      console.error("SweetAlert2 (Swal) is not loaded.");
      if (confirm(`${options.title}\n${options.text}\n\nLanjutkan?`)) {
        if (typeof onConfirm === "function") onConfirm({ isConfirmed: true });
      } else {
        if (typeof onCancel === "function") onCancel();
      }
      return;
    }
    const swalOptions = {
      title: options.title || "Anda yakin?",
      text: options.text || "Tindakan ini mungkin tidak dapat diurungkan!",
      icon: options.icon || "warning",
      showCancelButton: true,
      confirmButtonText: options.confirmButtonText || "Ya, lanjutkan!",
      cancelButtonText: options.cancelButtonText || "Batal",
      confirmButtonColor: options.confirmButtonColor || "#3085d6",
      cancelButtonColor: options.cancelButtonColor || "#d33",
      reverseButtons: true,
    };
    if (
      options.icon === "warning" &&
      (options.confirmButtonText?.toLowerCase().includes("hapus") ||
        options.title?.toLowerCase().includes("hapus"))
    ) {
      swalOptions.confirmButtonColor = "#d33";
      swalOptions.cancelButtonColor = "#3085d6";
    }
    Swal.fire(swalOptions).then((result) => {
      if (result.isConfirmed) {
        if (typeof onConfirm === "function") onConfirm(result);
      } else if (result.dismiss === Swal.DismissReason.cancel) {
        if (typeof onCancel === "function") onCancel(result);
      }
    });
  };

  // --- 4. Helper Functions ---
  window.helpers = {
    formatRupiah: function (angka) {
      if (isNaN(angka) || angka === null) return "Rp 0";
      return "Rp " + Number(angka).toLocaleString("id-ID");
    },
    escapeHtml: function (unsafe) {
      if (unsafe === null || typeof unsafe === "undefined") return "";
      return unsafe
        .toString()
        .replace(/&/g, "&")
        .replace(/</g, "<")
        .replace(/>/g, ">")
        .replace(/"/g, '"')
        .replace(/'/g, "'");
    },
    escapeJsString: function (str) {
      if (str === null || typeof str === "undefined") {
        return "";
      }
      return String(str)
        .replace(/'/g, "\\'")
        .replace(/"/g, '\\"')
        .replace(/\n/g, "\\n")
        .replace(/\r/g, "\\r");
    },
    debounce: function (func, delay) {
      let timeout;
      return function (...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), delay);
      };
    },
    genericDelete: function (options) {
      const dialogOpts = {
        title: options.deleteTitle || `Hapus ${options.itemName}?`,
        text:
          options.deleteText ||
          `Data ${options.itemName} akan dihapus permanen.`,
        icon: "warning",
        confirmButtonText:
          options.confirmText || `Ya, Hapus ${options.itemName}`,
      };
      window.showConfirmationDialog(
        dialogOpts,
        () => {
          window.showToast(`Menghapus ${options.itemName}...`, "info");
          window.api
            .delete(`${options.apiUrl}/${options.itemId}`)
            .then((response) => {
              if (response.data && response.data.success) {
                window.showToast(
                  response.data.message ||
                    `${options.itemName} berhasil dihapus.`,
                  "success"
                );
                if (typeof options.onSuccessCallback === "function") {
                  options.onSuccessCallback(response.data);
                }
              }
            })
            .catch((error) => {});
        },
        () => {
          window.showToast(
            `Penghapusan ${options.itemName} dibatalkan.`,
            "info"
          );
        }
      );
    },
  };

  console.log(
    "App.js initialized: Notyf, Axios, SweetAlert2 confirmation, and Helpers are ready."
  );

  // --- PWA Registration ---
  if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
      navigator.serviceWorker
        .register("/service-worker.js")
        .then((registration) => {
          console.log("ServiceWorker registered: ", registration);
        })
        .catch((registrationError) => {
          console.log("ServiceWorker registration failed: ", registrationError);
        });
    });
  } else {
    console.log("ServiceWorker not supported in this browser.");
  }
})();
