document.addEventListener("alpine:init", () => {
  Alpine.data("editStudentModal", () => ({
    isOpen: false,
    isLoading: true,
    student: {},
    newFeeCounter: 0,
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
        monthSelect.add(new Option(name, i + 1));
      });
      const currentYear = new Date().getFullYear();
      for (let y = currentYear + 1; y >= currentYear - 2; y--) {
        yearSelect.add(new Option(y, y));
      }
      this.a.feePeriod.month = new Date().getMonth() + 1;
      this.a.feePeriod.year = currentYear;
    },

    processFees(feesFromServer) {
      if (
        !feesFromServer ||
        !Array.isArray(feesFromServer) ||
        feesFromServer.length === 0
      )
        return [];
      return feesFromServer.map((fee) => {
        const categoryMatch = this.a.feeCategoryList.find(
          (cat) => cat.id === fee.kategori
        );
        return {
          ...fee,
          kategori: categoryMatch ? categoryMatch.id : "",
          nominal: Number(fee.nominal),
        };
      });
    },

    async openModal(studentId) {
      if (!studentId) return;
      this.resetState();
      this.newFeeCounter = 0;
      this.isOpen = true;
      this.isLoading = true;
      try {
        const [studentRes, jenjangRes, feeCatRes, additionalFeesRes] =
          await Promise.all([
            window.api.get(`/student-detail/${studentId}`),
            window.api.get("/jenjang"),
            window.api.get("/fee-categories"),
            window.api.get(
              `/student-fees/${studentId}?month=${this.a.feePeriod.month}&year=${this.a.feePeriod.year}`
            ),
          ]);

        this.a.jenjangList = jenjangRes.data.data;
        this.a.feeCategoryList = feeCatRes.data.data;

        const finalProcessedFees = this.processFees(
          additionalFeesRes.data.data
        );

        // LANGKAH 1: Bangun rak kosong. Render semua baris tapi dengan 'kategori' kosong.
        this.a.additionalFees = finalProcessedFees.map((fee) => ({
          ...fee,
          kategori: "",
        }));

        // LANGKAH 2: Tunggu render selesai. Gunakan setTimeout sebagai jaminan terkuat.
        setTimeout(() => {
          // LANGKAH 3: Letakkan buku di rak. Perbarui state dengan data 'kategori' yang benar.
          this.a.additionalFees = finalProcessedFees;
        }, 0);

        // Lanjutkan sisa logika seperti biasa...
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
      } catch (error) {
        console.error("Gagal memuat data edit siswa:", error);
        this.closeModal();
      } finally {
        this.isLoading = false;
      }
    },

    closeModal() {
      this.isOpen = false;
    },

    resetState() {
      this.student = {};
      this.a.tingkatList = [];
      this.a.kelasList = [];
      this.a.additionalFees = [];
      this.a.feePeriod.month = new Date().getMonth() + 1;
      this.a.feePeriod.year = new Date().getFullYear();
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
      try {
        const res = await window.api.get(
          `/student-fees/${this.student.id}?month=${this.a.feePeriod.month}&year=${this.a.feePeriod.year}`
        );
        const finalProcessedFees = this.processFees(res.data.data);

        // Terapkan logika yang sama di sini
        this.a.additionalFees = finalProcessedFees.map((fee) => ({
          ...fee,
          kategori: "",
        }));
        setTimeout(() => {
          this.a.additionalFees = finalProcessedFees;
        }, 0);
      } catch (error) {
        console.error("Gagal memuat biaya tambahan:", error);
        this.a.additionalFees = [];
      }
    },

    addFee() {
      const tempId = `new_${this.newFeeCounter++}`;
      this.a.additionalFees.push({
        id: tempId,
        kategori: "",
        nominal: "",
        keterangan: "",
      });
    },

    removeFee(index) {
      this.a.additionalFees.splice(index, 1);
    },

    async saveChanges() {
      const payloadFees = this.a.additionalFees
        .filter(
          (fee) => fee.kategori && fee.nominal !== "" && !isNaN(fee.nominal)
        )
        .map((fee) => {
          if (typeof fee.id === "string" && fee.id.startsWith("new_")) {
            return {
              id: null,
              kategori: fee.kategori,
              nominal: fee.nominal,
              keterangan: fee.keterangan,
            };
          }
          return fee;
        });

      try {
        await Promise.all([
          window.api.post(`/student-update/${this.student.id}`, this.student),
          window.api.post(`/student-fees-update/${this.student.id}`, {
            fees: payloadFees,
            period: this.a.feePeriod,
          }),
        ]);

        window.showToast(
          "Data siswa dan biaya tambahan berhasil diupdate.",
          "success"
        );
        this.closeModal();
        window.dispatchEvent(new CustomEvent("data-updated"));
      } catch (error) {
        console.error("Gagal menyimpan perubahan:", error);
        const errorMessage =
          error.response?.data?.message ||
          "Terjadi kesalahan saat menyimpan data.";
        window.showToast(errorMessage, "error");
      }
    },
  }));
});
