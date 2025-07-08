document.addEventListener("alpine:init", () => {
  Alpine.data("editFeeModal", () => ({
    show: false,
    isLoading: false,
    isSaving: false,
    billId: null,
    month: null,
    year: null,
    studentName: "",
    periodLabel: "",
    fees: {
      spp: 0,
      denda: 0,
      dynamic_fees: [],
    },

    formatRupiah(number) {
      if (isNaN(number)) return "Rp 0";
      return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
      }).format(number);
    },

    async openModal(billId, month, year) {
      if (!billId || !month || !year) return;

      this.show = true;
      this.isLoading = true;
      this.billId = billId;
      this.month = month;
      this.year = year;

      this.fees = { spp: 0, denda: 0, dynamic_fees: [] };
      this.studentName = "";
      this.periodLabel = "";

      try {
        const payload = {
          billId: this.billId,
          month: this.month,
          year: this.year,
        };
        const response = await window.api.post("/get-fee-data", payload);

        const data = response.data.data;

        this.fees.spp = data.fee_details.spp || 0;
        this.fees.denda = data.fee_details.denda || 0;
        this.fees.dynamic_fees = data.fee_details.dynamic_fees || [];
        this.studentName = data.siswa;

        const monthName = new Date(this.year, this.month - 1).toLocaleString(
          "id-ID",
          { month: "long" }
        );
        this.periodLabel = `${monthName} ${this.year}`;
      } catch (error) {
        console.error("Gagal memuat rincian tagihan:", error);
        window.showToast("Gagal memuat data rincian tagihan.", "error");
        this.closeModal();
      } finally {
        this.isLoading = false;
      }
    },

    closeModal() {
      this.show = false;
      this.billId = null;
      this.month = null;
      this.year = null;
    },

    async saveFees() {
      if (this.isSaving) return;

      this.isSaving = true;

      try {
        const payload = {
          billId: this.billId,
          month: this.month,
          year: this.year,
          lateFee: this.fees.denda,
        };

        const res = await window.api.post("/update-fee-data", payload);
        console.info(res);
        window.showToast("Denda berhasil diperbarui.", "success");
        this.closeModal();
        // location.reload();
      } catch (error) {
        console.error("Gagal menyimpan denda:", error);
        const errorMessage =
          error.response?.data?.message ||
          "Terjadi kesalahan saat menyimpan data.";
        window.showToast(errorMessage, "error");
      } finally {
        this.isSaving = false;
      }
    },
  }));
});
