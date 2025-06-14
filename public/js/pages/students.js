(function () {
  "use strict";

  // Memeriksa ketersediaan komponen global.
  if (
    typeof window.api === "undefined" ||
    typeof window.showToast === "undefined"
  ) {
    console.error(
      "students.js: Komponen global tidak ditemukan. Pastikan app.js dimuat."
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

  // Variabel untuk menyimpan kondisi filter yang sedang aktif.
  let currentFilters = {};

  /**
   * Menggambar ulang baris-baris data di dalam tabel siswa.
   */
  function renderStudentsToTable(students) {
    const tbody = document.querySelector(`#${EL_IDS.STUDENT_TABLE} tbody`);
    if (!tbody) return;

    tbody.innerHTML = "";
    if (!students || students.length === 0) {
      tbody.innerHTML =
        '<tr><td class="px-4 py-2 text-center text-gray-500" colspan="6">Tidak ada data siswa ditemukan.</td></tr>';
      return;
    }

    students.forEach((student) => {
      const studentNameEscaped = escapeJsString(student.nama);
      const row = `
        <tr class="odd:bg-white even:bg-gray-50 border-b hover:bg-gray-100">
          <td class="px-4 py-2 flex gap-2 items-center">
            <button onclick="pages.students.openEditStudentModal('${escapeJsString(
              student.id
            )}')" title="Edit Siswa" class="text-sky-600 hover:text-sky-800"><i class="ti ti-pencil"></i></button>
            <button onclick="pages.students.handleDeleteStudent('${escapeJsString(
              student.id
            )}', '${studentNameEscaped}')" title="Hapus Siswa" class="text-red-500 hover:text-red-700"><i class="ti ti-trash"></i></button>
          </td>
          <th scope="row" class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">
            ${escapeHtml(
              student.nama
            )}<div class="text-xs text-slate-500 font-normal">${escapeHtml(
        student.nis
      )}</div>
          </th>
          <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(
            student.jenjang || ""
          )} ${escapeHtml(student.tingkat || "")} ${escapeHtml(
        student.kelas || ""
      )}</td>
          <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(
            student.va || ""
          )}</td>
          <td class="px-4 py-2 whitespace-nowrap">${formatRupiah(
            student.spp || 0
          )}</td>
          <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(
            student.latest_payment || "-"
          )}</td>
          <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(
            student.updated_at || "-"
          )}</td>
        </tr>
      `;
      tbody.insertAdjacentHTML("beforeend", row);
    });
  }

  /**
   * Mengambil data siswa dari API dengan menyertakan filter yang aktif.
   */
  function loadStudentsData() {
    showToast("Memuat data siswa...", "info");

    const params = new URLSearchParams(currentFilters).toString();
    const apiUrl = `${API_URL.GET_STUDENTS}?${params}`;

    api
      .get(apiUrl)
      .then((response) => {
        if (response.data && response.data.success) {
          renderStudentsToTable(response.data.data);
          showToast("Data berhasil dimuat.", "success");
        } else {
          renderStudentsToTable([]);
        }
      })
      .catch((error) => {
        console.error("Error memuat data siswa:", error);
        renderStudentsToTable([]);
      });
  }

  /**
   * Membuka modal edit siswa (placeholder).
   */
  function openEditStudentModal(studentId) {
    window.dispatchEvent(
      new CustomEvent("open-edit-student", {
        detail: { studentId: studentId },
      })
    );
  }

  /**
   * Menangani proses soft-delete (menonaktifkan) siswa dengan konfirmasi.
   */
  function handleDeleteStudent(studentId, studentName) {
    showConfirmationDialog(
      {
        title: `Nonaktifkan Siswa "${studentName}"?`,
        text: "Data siswa ini akan dinonaktifkan.",
        icon: "warning",
        confirmButtonText: "Ya, Nonaktifkan",
      },
      (result) => {
        if (result.isConfirmed) {
          api
            .delete(`${API_URL.DELETE_STUDENT}/${studentId}`)
            .then((response) => {
              if (response.data && response.data.success) {
                showToast(
                  response.data.message || "Siswa berhasil dinonaktifkan.",
                  "success"
                );
                loadStudentsData();
              }
            });
        }
      }
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
    loadStudentsData();
    document.getElementById(EL_IDS.FILTER_MODAL).classList.add("hidden");
  }

  /**
   * Mengosongkan form filter dan memuat ulang data.
   */
  function resetFiltersAndReload() {
    document.getElementById(EL_IDS.FILTER_FORM).reset();
    currentFilters = {};
    loadStudentsData();
    document.getElementById(EL_IDS.FILTER_MODAL).classList.add("hidden");
  }

  /**
   * Menjalankan semua inisialisasi setelah halaman selesai dimuat.
   */
  document.addEventListener("DOMContentLoaded", () => {
    // Muat data tabel utama.
    if (document.getElementById(EL_IDS.STUDENT_TABLE)) {
      loadStudentsData();
    }

    // Inisialisasi logika untuk modal filter.
    const filterModal = document.getElementById(EL_IDS.FILTER_MODAL);
    if (!filterModal) return;

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
      if (!filterModal.classList.contains("hidden")) loadFilterJenjang();
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

  function openEditClassModal(tariffId) {
    if (!tariffId) return;

    window.dispatchEvent(
      new CustomEvent("open-edit-tariff-modal", {
        detail: { tariffId: tariffId },
      })
    );
  }

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
