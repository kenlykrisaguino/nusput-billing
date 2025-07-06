(function () {
  "use strict";

  // Pastikan komponen global sudah dimuat
  if (
    typeof window.api === "undefined" ||
    typeof window.showToast === "undefined" ||
    typeof window.showConfirmationDialog === "undefined"
  ) {
    console.error(
      "tagihan.js: Komponen global tidak ditemukan. Pastikan app.js dimuat."
    );
    return;
  }

  // --- KONSTANTA ---
  const API_URL = {
    GET_JOURNAL: "/students-list",
    GET_SISWA: "/filter-siswa",
    GET_JENJANG: "/jenjang",
    GET_TINGKAT: "/tingkat",
    GET_KELAS: "/kelas",
  };

  const EL_IDS = {
    FILTER_MODAL: "filter-journal-modal",
    FILTER_FORM: "filter-journal-form",
    FILTER_SISWA: "filter-siswa",
    FILTER_TAHUN: "filter-tahun",
    FILTER_SEMESTER: "filter-semester",
    FILTER_BULAN: "filter-bulan",
    FILTER_JENJANG: "filter-jenjang",
    FILTER_TINGKAT: "filter-tingkat",
    FILTER_KELAS: "filter-kelas",
    APPLY_FILTER_BTN: "apply-filter-btn",
    RESET_FILTER_BTN: "reset-filter-btn",
  };

  const { formatRupiah, escapeHtml, escapeJsString } = window.helpers;
  // Shortcut untuk fungsi global.
  const api = window.api;
  const showToast = window.showToast;
  const showConfirmationDialog = window.showConfirmationDialog;

  let currentFilters = {};

  // Notyf.js instance mungkin tidak diekspos secara global, tapi kita bisa akses dari window jika ada.
  // Jika tidak, kita buat toast yang tidak bisa di-dismiss secara manual.
  const notyf = window.notyf || {
    open: (opts) => showToast(opts.message, opts.type),
    dismiss: () => {},
  };

  /**
   * Mengambil nilai dari form filter dan memuat ulang data.
   */
  function applyFiltersAndReload() {
    const filterForm = document.getElementById(EL_IDS.FILTER_FORM);
    const formData = new FormData(filterForm);
    const params = new URLSearchParams();

    for (const [key, value] of formData.entries()) {
      if (value) params.append(key, value);
    }

    currentFilters = Object.fromEntries(params.entries());
    document.getElementById(EL_IDS.FILTER_MODAL).classList.add("hidden");

    const currentPath = window.location.pathname;
    window.location.href = `${currentPath}?${params.toString()}`;
  }

  document.addEventListener("DOMContentLoaded", () => {
    handleImportPayment();
    handleFileNameDisplay();
  });

  window.pages = window.pages || {};
  window.pages.penjurnalan = {};
})();
