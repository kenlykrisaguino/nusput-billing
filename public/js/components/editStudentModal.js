document.addEventListener("alpine:init", () => {
  Alpine.data("editStudentModal", () => ({
    show: false,
    isLoading: true,
    student: {},
    a: {
      jenjangList: [],
      tingkatList: [],
      kelasList: [],
      feeCategoryList: [],
      additionalFees: [],
      feePeriod: {
        month: new Date().getMonth() + 1,
        year: new Date().getFullYear(),
      },
    },

    init() {
      const monthSelect = document.getElementById("edit_fee_month");
      const yearSelect = document.getElementById("edit_fee_year");
      const months = [
        "Januari",
        "Februari",
        "Maret",
        "April",
        "Mei",
        "Juni",
        "Juli",
        "Agustus",
        "September",
        "Oktober",
        "November",
        "Desember",
      ];

      months.forEach((name, i) => {
        const monthNum = i + 1;
        const opt = new Option(name, monthNum);
        if (monthNum === this.a.feePeriod.month) opt.selected = true;
        monthSelect.add(opt);
      });

      const currentYear = this.a.feePeriod.year;
      for (let y = currentYear + 1; y >= currentYear - 2; y--) {
        const opt = new Option(y, y);
        if (y === currentYear) opt.selected = true;
        yearSelect.add(opt);
      }
    },

    async openModal(studentId) {
      if (!studentId) return;

      this.show = true;
      this.isLoading = true;
      this.student = {};

      try {
        const [studentRes, jenjangRes, feeCatRes] = await Promise.all([
          window.api.get(`/student-detail/${studentId}`),
          window.api.get("/jenjang"),
          window.api.get("/fee-categories"),
        ]);

        this.a.jenjangList = jenjangRes.data.data;
        this.a.feeCategoryList = feeCatRes.data.data;

        const studentData = studentRes.data.data;
        const originalTingkatId = Number(studentData.tingkat_id);
        const originalKelasId = studentData.kelas_id
          ? Number(studentData.kelas_id)
          : "";

        this.student = {
          ...studentData,
          jenjang_id: Number(studentData.jenjang_id),
          tingkat_id: "",
          kelas_id: "",
        };

        await this.fetchTingkat(true);

        this.student.tingkat_id = originalTingkatId;
        await this.$nextTick();

        await this.fetchKelas(true);

        this.student.kelas_id = originalKelasId;

        await this.fetchAdditionalFees();
        document.getElementById("edit-student-modal").style.display = "block";
      } catch (error) {
        console.error("Gagal memuat data edit siswa:", error);
        window.showToast("Gagal memuat data untuk diedit.", "error");
        this.closeModal();
      } finally {
        this.isLoading = false;
      }
    },

    closeModal() {
      document.getElementById("edit-student-modal").style.display = "none";
      this.show = false;
    },

    async fetchTingkat(isInitialLoad = false) {
      if (!isInitialLoad) {
        this.student.tingkat_id = "";
        this.student.kelas_id = "";
      }

      this.a.kelasList = [];
      this.a.tingkatList = [];
      if (!this.student.jenjang_id) return;

      const res = await window.api.get(
        `/tingkat?jenjang_id=${this.student.jenjang_id}`
      );
      this.a.tingkatList = res.data.data;
    },

    async fetchKelas(isInitialLoad = false) {
      if (!isInitialLoad) {
        this.student.kelas_id = "";
      }

      this.a.kelasList = [];
      if (!this.student.tingkat_id) return;

      const res = await window.api.get(
        `/kelas?tingkat_id=${this.student.tingkat_id}`
      );
      this.a.kelasList = res.data.data;
    },

    async fetchAdditionalFees() {
      if (!this.student.id) return;
      const res = await window.api.get(
        `/student-fees/${this.student.id}?month=${this.a.feePeriod.month}&year=${this.a.feePeriod.year}`
      );
      this.a.additionalFees = res.data.data || [];
    },

    addFee() {
      this.a.additionalFees.push({
        id: null,
        kategori: "",
        nominal: "",
        keterangan: "",
      });
    },

    removeFee(index) {
      this.a.additionalFees.splice(index, 1);
    },

    async saveStudentData() {
      try {
        await window.api.post(
          `/student-update/${this.student.id}`,
          this.student
        );
        window.showToast("Data siswa berhasil diupdate.", "success");
        this.closeModal();
        window.pages.students.loadStudentsData();
      } catch (error) {
        console.error("Gagal update data siswa:", error);
      }
    },

    async saveAdditionalFees() {
      try {
        await window.api.post(`/student-fees-update/${this.student.id}`, {
          fees: this.a.additionalFees,
          period: this.a.feePeriod,
        });
        window.showToast("Biaya tambahan berhasil disimpan.", "success");
        this.fetchAdditionalFees();
      } catch (error) {
        console.error("Gagal menyimpan biaya tambahan:", error);
      }
    },
  }));
});
