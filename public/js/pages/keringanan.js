(function () {
  "use strict";

  // Memeriksa ketersediaan komponen global.
  if (
    typeof window.api === "undefined" ||
    typeof window.showToast === "undefined"
  ) {
    console.error(
      "keringanan.js: Komponen global tidak ditemukan. Pastikan app.js dimuat."
    );
    return;
  }

  // --- KONSTANTA ---
  const API_URL = {
    CREATE_REDUCTION: "/reduction",
  };

  const EL_IDS = {
    STUDENT_TABLE: "student-table",
    FILTER_MODAL: "filter-student-modal",
    FILTER_FORM: "filter-student-form",
    FILTER_JENJANG: "filter_jenjang",
    FILTER_TINGKAT: "filter_tingkat",
    FILTER_KELAS: "filter_kelas",
    APPLY_FILTER_BTN: "apply-filter-btn",
    RESET_FILTER_BTN: "reset-filter-btn",
  };

  // Shortcut untuk helper dan fungsi global.
  const { formatRupiah, escapeHtml, escapeJsString } = window.helpers;
  const showToast = window.showToast;
  const showConfirmationDialog = window.showConfirmationDialog;
  const api = window.api;

  /**
   * Menjalankan semua inisialisasi setelah halaman selesai dimuat.
   */
  document.addEventListener("DOMContentLoaded", () => {

  })

  /**
   * Mengekspos fungsi yang perlu dipanggil dari luar (misal: dari HTML `onclick`).
   */
  window.pages = window.pages || {};
  window.pages.students = {
    openEditStudentModal,
    handleDeleteStudent,
    loadStudentsData,
    openEditClassModal,
  };
})();
