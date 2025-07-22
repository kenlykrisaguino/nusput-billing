<div id="create-tariff-modal" 
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-[100]"
     x-data="createTariffModal()"
     @open-create-tariff-modal.window="openModal()">
     
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 py-6 px-8 border w-11/12 md:w-1/2 lg:w-1/3 max-w-lg shadow-xl rounded-lg bg-white">
        
        <div class="flex justify-between items-center pb-3 border-b border-gray-200 mb-6">
            <h3 class="text-xl font-semibold text-gray-700">Tambah Tarif Baru</h3>
            <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="ti ti-x text-2xl"></i>
            </button>
        </div>

        <form @submit.prevent="saveTariff()">
            <div class="space-y-4">
                <div>
                    <label for="tariff_jenjang_id" class="block text-sm font-medium text-gray-700 mb-1">Jenjang</label>
                    <select id="tariff_jenjang_id" x-model.number="formData.jenjang_id" @change="fetchTingkat()" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                        <option value="">Pilih Jenjang</option>
                        <template x-for="jenjang in lists.jenjang" :key="jenjang.id">
                            <option :value="jenjang.id" x-text="jenjang.nama"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label for="tariff_tingkat_id" class="block text-sm font-medium text-gray-700 mb-1">Tingkat</label>
                    <select id="tariff_tingkat_id" x-model.number="formData.tingkat_id" @change="fetchKelas()" required :disabled="lists.tingkat.length === 0"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm disabled:bg-gray-100">
                        <option value="">Pilih Tingkat</option>
                        <template x-for="tingkat in lists.tingkat" :key="tingkat.id">
                            <option :value="tingkat.id" x-text="tingkat.nama"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label for="tariff_kelas_id" class="block text-sm font-medium text-gray-700 mb-1">Kelas (Opsional)</label>
                    <select id="tariff_kelas_id" x-model.number="formData.kelas_id" :disabled="lists.kelas.length === 0"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm disabled:bg-gray-100">
                        <option value="">Berlaku untuk Semua Kelas di Tingkat ini</option>
                        <template x-for="kelas in lists.kelas" :key="kelas.id">
                            <option :value="kelas.id" x-text="kelas.nama"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Kosongkan jika tarif ini berlaku untuk semua kelas di tingkat yang dipilih.</p>
                </div>
                <div>
                    <label for="tariff_nominal" class="block text-sm font-medium text-gray-700 mb-1">Nominal Tarif SPP</label>
                    <div class="relative mt-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="text-gray-500 sm:text-sm">Rp</span>
                        </div>
                        <input type="number" id="tariff_nominal" x-model.number="formData.nominal" required placeholder="Contoh: 500000"
                               class="block w-full rounded-md border-gray-300 pl-10 px-3 py-2 shadow-sm sm:text-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                </div>
                <div>
                    <label for="tariff_year" class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                    <div class="relative mt-1">
                        <input type="number" id="tariff_year" x-model.number="formData.tahun" required placeholder="2025" type="number"
                               class="block w-full rounded-md border-gray-300 px-3 py-2 shadow-sm sm:text-sm focus:border-sky-500 focus:ring-sky-500">
                    </div>
                </div>
            </div>

            <div class="pt-6 mt-6 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" @click="closeModal()"
                        class="px-6 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm font-medium">
                    Batal
                </button>
                <button type="submit" :disabled="isSaving"
                        class="px-6 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 text-sm font-medium flex items-center justify-center w-32">
                    <span x-show="!isSaving">Simpan Tarif</span>
                    <span x-show="isSaving">Menyimpan...</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    function createTariffModal() {
        return {
            formData: { jenjang_id: '', tingkat_id: '', kelas_id: '', nominal: '' },
            lists: { jenjang: [], tingkat: [], kelas: [] },
            isSaving: false,

            async openModal() {
                this.resetForm();
                await this.fetchJenjang();
                document.getElementById('create-tariff-modal').classList.remove('hidden');
            },
            closeModal() {
                document.getElementById('create-tariff-modal').classList.add('hidden');
            },
            resetForm() {
                this.formData = { jenjang_id: '', tingkat_id: '', kelas_id: '', nominal: '' };
                this.lists = { jenjang: [], tingkat: [], kelas: [] };
            },
            async fetchJenjang() {
                try {
                    const response = await window.api.get('/jenjang');
                    if (response.data.success) this.lists.jenjang = response.data.data;
                } catch (e) { console.error('Gagal memuat jenjang:', e); }
            },
            async fetchTingkat() {
                this.formData.tingkat_id = ''; this.formData.kelas_id = '';
                this.lists.tingkat = []; this.lists.kelas = [];
                if (!this.formData.jenjang_id) return;
                try {
                    const response = await window.api.get(`/tingkat?jenjang_id=${this.formData.jenjang_id}`);
                    if (response.data.success) this.lists.tingkat = response.data.data;
                } catch (e) { console.error('Gagal memuat tingkat:', e); }
            },
            async fetchKelas() {
                this.formData.kelas_id = '';
                this.lists.kelas = [];
                if (!this.formData.tingkat_id) return;
                try {
                    const response = await window.api.get(`/kelas?tingkat_id=${this.formData.tingkat_id}`);
                    if (response.data.success) this.lists.kelas = response.data.data;
                } catch (e) { console.error('Gagal memuat kelas:', e); }
            },
            async saveTariff() {
                if (this.isSaving) return;
                this.isSaving = true;
                try {
                    const payload = { ...this.formData };
                    if (payload.kelas_id === '' || payload.kelas_id === 0) {
                        payload.kelas_id = null;
                    }
                    const response = await window.api.post('/tariff-create', payload);
                    console.log(response)
                    if (response.data.success) {
                        window.showToast(response.data.message || 'Tarif berhasil disimpan.', 'success');
                        this.closeModal();
                        window.location.reload(); 
                    }
                } catch (error) {
                    console.error('Gagal tambah tarif:', error);
                } finally {
                    this.isSaving = false;
                }
            }
        }
    }
</script>