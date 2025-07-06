(function () {
  "use strict";

  // Memeriksa ketersediaan komponen global.
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
    GET_STUDENTS: "/students-list",
    DELETE_STUDENT: "/students-delete",
    GET_JENJANG: "/jenjang",
    GET_TINGKAT: "/tingkat",
    GET_KELAS: "/kelas",
  };

  const EL_IDS = {
    BILL_TABLE: "bill-table",
    FILTER_MODAL: "filter-bill-modal",
    FILTER_FORM: "filter-bill-form",
    FILTER_TAHUN: "filter-tahun",
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
   * Menangani klik pada tombol "Buat Tagihan Tahunan".
   * Menampilkan dialog konfirmasi sebelum mengirim request ke API.
   */
  function handleCreateBills() {
    const dialogOptions = {
      title: "Buat Tagihan Baru?",
      text: "Sistem akan membuat tagihan SPP untuk semua siswa aktif selama 1 tahun. Aksi ini tidak dapat diurungkan. Lanjutkan?",
      icon: "warning",
      confirmButtonText: "Ya, Buat Tagihan",
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
    };

    showConfirmationDialog(dialogOptions, (result) => {
      if (result.isConfirmed) {
        // Tampilkan notifikasi loading yang tidak bisa ditutup
        const loadingToast = notyf.open({
          type: "info",
          message: "Sedang memproses... Ini mungkin butuh beberapa saat.",
          dismissible: false,
          duration: 0, // Tidak hilang otomatis
        });

        api
          .post("/create-bills")
          .then((response) => {
            notyf.dismiss(loadingToast); // Tutup notifikasi loading
            if (response.data && response.data.success) {
              showToast(
                response.data.message || "Tagihan berhasil dibuat.",
                "success"
              );
              // Reload halaman setelah 2 detik untuk melihat hasilnya
              setTimeout(() => window.location.reload(), 2000);
            }
          })
          .catch((error) => {
            notyf.dismiss(loadingToast); // Tutup notifikasi loading saat error
            // Pesan error dari API sudah ditangani oleh interceptor di app.js
            console.error("Gagal membuat tagihan:", error);
          });
      }
    });
  }

  /**
   * Menangani klik pada tombol "Cek Tagihan Bulanan".
   * Menampilkan dialog konfirmasi sebelum mengirim request ke API.
   */
  function handleCheckBills() {
    const dialogOptions = {
      title: "Cek Tagihan Bulanan?",
      text: "Sistem akan mengecek tagihan SPP untuk semua siswa aktif. Aksi ini tidak dapat diurungkan. Lanjutkan?",
      icon: "warning",
      confirmButtonText: "Ya, Cek Tagihan",
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
    };

    showConfirmationDialog(dialogOptions, (result) => {
      if (result.isConfirmed) {
        // Tampilkan notifikasi loading yang tidak bisa ditutup
        const loadingToast = notyf.open({
          type: "info",
          message: "Sedang memproses... Ini mungkin butuh beberapa saat.",
          dismissible: false,
          duration: 0, // Tidak hilang otomatis
        });

        api
          .post("/check-bills")
          .then((response) => {
            notyf.dismiss(loadingToast); // Tutup notifikasi loading
            if (response.data && response.data.success) {
              showToast(
                response.data.message || "Tagihan berhasil dicek.",
                "success"
              );
              // Reload halaman setelah 2 detik untuk melihat hasilnya
              setTimeout(() => window.location.reload(), 2000);
            }
          })
          .catch((error) => {
            notyf.dismiss(loadingToast); // Tutup notifikasi loading saat error
            // Pesan error dari API sudah ditangani oleh interceptor di app.js
            console.error("Gagal membuat tagihan:", error);
          });
      }
    });
  }

  function openBillDetails(id, month, year) {
    window.dispatchEvent(
      new CustomEvent("open-edit-fee", {
        detail: {
          id: id,
          month: month,
          year: year,
        },
      })
    );
  }

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
    // Cari tombol "Buat Tagihan" dan pasang event listener.
    // Pastikan tombol di tagihan.php memiliki id="create-bill-btn"
    const createBillButton = document.getElementById("create-bill-btn");
    if (createBillButton) {
      createBillButton.addEventListener("click", handleCreateBills);
    }
    const checkBillButton = document.getElementById("check-bill-btn");
    if (checkBillButton) {
      checkBillButton.addEventListener("click", handleCheckBills);
    }

    const filterModal = document.getElementById(EL_IDS.FILTER_MODAL);
    if (!filterModal) return;

    const selectTahun = document.getElementById(EL_IDS.FILTER_TAHUN);
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

  /**
   * Mengekspos fungsi yang mungkin perlu dipanggil dari luar (jika ada).
   */
  window.pages = window.pages || {};
  window.pages.tagihan = {
    openBillDetails,
  };
})();
