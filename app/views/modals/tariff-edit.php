<div id="edit-tariff-modal"
     x-data="editTariffModal"
     x-show="show"
     @open-edit-tariff-modal.window="openModal($event.detail.tariffId)"
     @keydown.escape.window="closeModal()"
     style="display: none;"
     x-transition.opacity
     class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-[100]">
     
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 py-6 px-8 border w-11/12 md:w-1/2 lg:w-1/3 max-w-lg shadow-xl rounded-lg bg-white">
        
        <div class="flex justify-between items-center pb-3 border-b border-gray-200 mb-6">
            <h3 class="text-xl font-semibold text-gray-700">Edit Tarif</h3>
            <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="ti ti-x text-2xl"></i>
            </button>
        </div>

        <template x-if="isLoading">
            <p class="text-center text-gray-500 py-10">Memuat data tarif...</p>
        </template>

        <template x-if="!isLoading">
            <form @submit.prevent="saveTariff()">
                <input type="hidden" x-model="formData.id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenjang</label>
                        <select x-model.number="formData.jenjang_id" @change="fetchTingkat()" required class="form-input">
                            <option value="">Pilih Jenjang</option>
                            <template x-for="jenjang in lists.jenjang" :key="jenjang.id">
                                <option :value="jenjang.id" x-text="jenjang.nama"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tingkat</label>
                        <select x-model.number="formData.tingkat_id" @change="fetchKelas()" required :disabled="lists.tingkat.length === 0" class="form-input disabled:bg-gray-100">
                            <option value="">Pilih Tingkat</option>
                            <template x-for="tingkat in lists.tingkat" :key="tingkat.id">
                                <option :value="tingkat.id" x-text="tingkat.nama"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelas (Opsional)</label>
                        <select x-model.number="formData.kelas_id" :disabled="lists.kelas.length === 0" class="form-input disabled:bg-gray-100">
                            <option value="">Berlaku untuk Semua Kelas</option>
                            <template x-for="kelas in lists.kelas" :key="kelas.id">
                                <option :value="kelas.id" x-text="kelas.nama"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nominal Tarif SPP</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            <input type="number" x-model.number="formData.nominal" required placeholder="500000"
                                   class="block w-full rounded-md border-gray-300 pl-10 px-3 py-2 shadow-sm sm:text-sm focus:border-sky-500 focus:ring-sky-500">
                        </div>
                    </div>
                </div>

                <div class="pt-6 mt-6 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" @click="closeModal()" class="btn-secondary px-6">Batal</button>
                    <button type="submit" :disabled="isSaving" class="btn-primary flex items-center justify-center w-36">
                        <span x-show="!isSaving">Update Tarif</span>
                        <span x-show="isSaving">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </template>
    </div>
</div>

<script>
    function editTariffModal() {
        return {
            show: false, 
            
            formData: {},
            lists: { jenjang: [], tingkat: [], kelas: [] },
            isSaving: false,
            isLoading: true,

            async openModal(tariffId) {
                if(!tariffId) return;

                this.show = true; 
                this.isLoading = true;

                try {
                    const [tariffRes, jenjangRes] = await Promise.all([
                        window.api.get(`/tariff-detail/${tariffId}`),
                        window.api.get('/jenjang')
                    ]);

                    this.formData = tariffRes.data.data;
                    this.lists.jenjang = jenjangRes.data.data;
                    
                    await this.$nextTick();
                    await this.fetchTingkat(true);
                    
                    await this.$nextTick();
                    await this.fetchKelas(true);

                } catch (e) {
                    console.error('Gagal memuat data tarif:', e);
                    window.showToast('Gagal memuat data tarif.', 'error');
                    this.closeModal();
                } finally {
                    this.isLoading = false;
                }
            },

            closeModal() {
                this.show = false; 
            },

            async fetchTingkat(isInitialLoad = false) {
                if (!isInitialLoad) this.formData.tingkat_id = '';
                this.lists.tingkat = [];
                if (!this.formData.jenjang_id) return;
                const response = await window.api.get(`/tingkat?jenjang_id=${this.formData.jenjang_id}`);
                if (response.data.success) this.lists.tingkat = response.data.data;
            },

            async fetchKelas(isInitialLoad = false) {
                if (!isInitialLoad) this.formData.kelas_id = '';
                this.lists.kelas = [];
                if (!this.formData.tingkat_id) return;
                const response = await window.api.get(`/kelas?tingkat_id=${this.formData.tingkat_id}`);
                if (response.data.success) this.lists.kelas = response.data.data;
            },

            async saveTariff() {
                if (this.isSaving) return;
                this.isSaving = true;
                try {
                    const payload = { ...this.formData };
                    if (payload.kelas_id === '' || payload.kelas_id === 0) {
                        payload.kelas_id = null;
                    }
                    const response = await window.api.post(`/tariff-update/${payload.id}`, payload);
                    if (response.data.success) {
                        window.showToast('Tarif berhasil diupdate.', 'success');
                        this.closeModal();
                        if (window.pages && typeof window.pages.students.loadClassesData === 'function') {
                            window.pages.students.loadClassesData();
                        } else {
                            window.location.reload(); 
                        }
                    }
                } catch (error) {
                    console.error('Gagal update tarif:', error);
                } finally {
                    this.isSaving = false;
                }
            }
        }
    }
</script>