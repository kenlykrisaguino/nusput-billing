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
    GET_JOURNAL: "/journal-data",
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
    FILTER_BULAN: "filter-bulan",
    FILTER_JENJANG: "filter-jenjang",
    FILTER_TINGKAT: "filter-tingkat",
    FILTER_KELAS: "filter-kelas",
    APPLY_FILTER_BTN: "apply-filter-btn",
    RESET_FILTER_BTN: "reset-filter-btn",
  };

  const EL_CLASSES = {
    PENERBITAN_UANG_SEKOLAH: "penerbitan-uang-sekolah",
    PELUNASAN_UANG_SEKOLAH: "pelunasan-uang-sekolah",
    PENERBITAN_DENDA: "penerbitan-denda",
    DENDA_LUNAS: "denda-lunas",
  };

  const { formatRupiah, escapeHtml, escapeJsString } = window.helpers;
  // Shortcut untuk fungsi global.
  const api = window.api;
  const showToast = window.showToast;
  const showConfirmationDialog = window.showConfirmationDialog;

  let currentFilters = {};

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

  /**
   * Mengosongkan form filter dan memuat ulang data.
   */
  function resetFiltersAndReload() {
    const currentPath = window.location.pathname;
    window.location.href = `${currentPath}`;
  }

  /**
   * Menjalankan semua inisialisasi setelah halaman selesai dimuat.
   */
  document.addEventListener("DOMContentLoaded", () => {
    const updateExportLinks = () => {
      const exportLinks = document.querySelectorAll(".export-journal-link");
      const currentQueryString = window.location.search; // Contoh: "?filter-tahun=2023"

      exportLinks.forEach((link) => {
        const baseHref = link.href.split("?")[0];
        link.href = `${baseHref}${currentQueryString}`;
      });
    };

    updateExportLinks();

    const filterModal = document.getElementById(EL_IDS.FILTER_MODAL);
    if (!filterModal) return;

    const selectSiswa = document.getElementById(EL_IDS.FILTER_SISWA);
    const selectTahun = document.getElementById(EL_IDS.FILTER_TAHUN);
    const selectBulan = document.getElementById(EL_IDS.FILTER_BULAN);
    const selectJenjang = document.getElementById(EL_IDS.FILTER_JENJANG);
    const selectTingkat = document.getElementById(EL_IDS.FILTER_TINGKAT);
    const selectKelas = document.getElementById(EL_IDS.FILTER_KELAS);

    const populateSelect = (select, data, placeholder) => {
      select.innerHTML = `<option value="">${placeholder}</option>`;
      data.forEach((item) => {
        const option = document.createElement("option");
        option.value = item.id;
        option.textContent = item.nama;
        select.appendChild(option);
      });
      select.disabled = false;
      select.classList.remove("bg-gray-100");
    };

    const loadFilterSiswa = async () => {
      try {
        const response = await api.get(API_URL.GET_SISWA);
        if (response.data.success)
          populateSelect(selectSiswa, response.data.data, "Semua Siswa");
      } catch (error) {
        console.error("Gagal memuat siswa untuk filter:", error);
      }
    };

    const loadFilterTahun = () => {
      const tahun = () => {
        const currentYear = new Date().getFullYear();
        const years = [];
        for (let y = currentYear; y >= 2000; y--) {
          years.push({ id: y, nama: y.toString() });
        }
        return years;
      };
      populateSelect(selectTahun, tahun(), "Default: Tahun Ini");
    };
    const loadFilterBulan = () => {
      const bulan = () => {
        const months = [];
        months.push({ id: 1, nama: "Januari" });
        months.push({ id: 2, nama: "Februari" });
        months.push({ id: 3, nama: "Maret" });
        months.push({ id: 4, nama: "April" });
        months.push({ id: 5, nama: "Mei" });
        months.push({ id: 6, nama: "Juni" });
        months.push({ id: 7, nama: "Juli" });
        months.push({ id: 8, nama: "Agustus" });
        months.push({ id: 9, nama: "September" });
        months.push({ id: 10, nama: "Oktober" });
        months.push({ id: 11, nama: "November" });
        months.push({ id: 12, nama: "Desember" });

        return months;
      };
      populateSelect(selectBulan, bulan(), "Semua Bulan");
    };

    const loadFilterJenjang = async () => {
      try {
        const response = await api.get(API_URL.GET_JENJANG);
        if (response.data.success)
          populateSelect(selectJenjang, response.data.data, "Semua Jenjang");
      } catch (error) {
        console.error("Gagal memuat jenjang untuk filter:", error);
      }
    };

    selectJenjang.addEventListener("change", async () => {
      selectTingkat.innerHTML = '<option value="">Semua Tingkat</option>';
      selectTingkat.disabled = true;
      selectKelas.innerHTML = '<option value="">Semua Kelas</option>';
      selectKelas.disabled = true;

      if (selectJenjang.value) {
        const response = await api.get(
          `${API_URL.GET_TINGKAT}?jenjang_id=${selectJenjang.value}`
        );
        if (response.data.success)
          populateSelect(selectTingkat, response.data.data, "Semua Tingkat");
      }
    });

    selectTingkat.addEventListener("change", async () => {
      selectKelas.innerHTML = '<option value="">Semua Kelas</option>';
      selectKelas.disabled = true;

      if (selectTingkat.value) {
        const response = await api.get(
          `${API_URL.GET_KELAS}?tingkat_id=${selectTingkat.value}`
        );
        if (response.data.success)
          populateSelect(selectKelas, response.data.data, "Semua Kelas");
      }
    });

    const observer = new MutationObserver(() => {
      if (!filterModal.classList.contains("hidden")) {
        loadFilterJenjang();
        loadFilterTahun();
        loadFilterBulan();
        loadFilterSiswa();
      }
    });
    observer.observe(filterModal, {
      attributes: true,
      attributeFilter: ["class"],
    });

    document
      .getElementById(EL_IDS.APPLY_FILTER_BTN)
      .addEventListener("click", applyFiltersAndReload);
    document
      .getElementById(EL_IDS.RESET_FILTER_BTN)
      .addEventListener("click", resetFiltersAndReload);
  });

  window.pages = window.pages || {};
  window.pages.penjurnalan = {};
})();
