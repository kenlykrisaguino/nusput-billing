document.addEventListener("alpine:init", () => {
  Alpine.data("editTariffModal", () => ({
    show: false,
    isLoading: true,
    isSaving: false,

    formData: {},

    async openModal(tariffId) {
      if (!tariffId) return;
      this.show = true;
      this.isLoading = true;

      document.getElementById("edit-tariff-modal").style.display = "block";
      const tariffYear = document.getElementById("tariff-year");

      try {
        const response = await window.api.get(`/tariff-detail/${tariffId}`);
        if (response.data.success) {
          const tahun = response?.data?.data?.tahun || new Date().getFullYear();
          tariffYear.textContent = tahun;

          this.formData = response.data.data;
        } else {
          throw new Error(response.data.message);
        }
      } catch (e) {
        console.error("Gagal memuat data tarif:", e);
        window.showToast("Gagal memuat data tarif.", "error");
        this.closeModal();
      } finally {
        this.isLoading = false;
      }
    },

    closeModal() {
      this.show = false;
      document.getElementById("edit-tariff-modal").style.display = "none";
      this.formData = {};
    },

    async saveTariff() {
      if (this.isSaving) return;
      this.isSaving = true;

      try {
        const payload = {
          id: this.formData.id,
          nominal: this.formData.nominal,
        };

        const response = await window.api.post(
          `/tariff-update/${payload.id}`,
          payload
        );
        if (response.data.success) {
          window.showToast("Tarif berhasil diupdate.", "success");
          this.closeModal();

          if (
            window.pages &&
            typeof window.pages.students.loadClassesData === "function"
          ) {
            window.pages.students.loadClassesData();
          } else {
            window.location.reload();
          }
        }
      } catch (error) {
        console.error("Gagal update tarif:", error);
      } finally {
        this.isSaving = false;
      }
    },
  }));
});
