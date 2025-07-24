<div id="create-reduction-modal" 
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-[100]"
     x-data="createReductionModal()"
     @open-create-reduction-modal.window="openModal()">
     
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 py-6 px-8 border w-11/12 md:w-1/2 lg:w-1/3 max-w-lg shadow-xl rounded-lg bg-white">
        
        <div class="flex justify-between items-center pb-3 border-b border-gray-200 mb-6">
            <h3 class="text-xl font-semibold text-gray-700">Tambah Peringanan</h3>
            <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="ti ti-x text-2xl"></i>
            </button>
        </div>

        <form @submit.prevent="saveReduction()">
            <div class="space-y-4">
                <div>
                    <label for="create_reduction_va" class="block text-sm font-medium text-gray-700 mb-1">Nomor VA</label>
                    <input type="text" id="create_reduction_va" x-model="formData.va" required placeholder="Contoh: 9881105XXXXXXXXXX"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                </div>
                <div>
                    <label for="create_reduction_nominal" class="block text-sm font-medium text-gray-700 mb-1">Nominal Keringanan</label>
                    <input type="number" id="create_reduction_nominal" x-model.number="formData.nominal" required placeholder="Nominal Keringanan"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                </div>
                <div>
                    <label for="create_reduction_bulan" class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                    <input type="number" id="create_reduction_bulan" x-model.number="formData.bulan" required placeholder="Input Bulan"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Bulan 1 = Januari ; 12 = Desember</p>
                </div>
                <div>
                    <label for="create_reduction_tahun" class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                    <input type="number" id="create_reduction_tahun" x-model.number="formData.tahun" required placeholder="Tahun 20XX"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Tahun 2024, 2025, dst...</p>
                </div>
            </div>

            <div class="pt-6 mt-6 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" @click="closeModal()"
                        class="px-6 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm font-medium">
                    Batal
                </button>
                <button type="submit" :disabled="isSaving"
                        class="px-6 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 text-sm font-medium flex items-center justify-center w-32">
                    <span x-show="!isSaving">Tambah Peringanan</span>
                    <span x-show="isSaving">Menyimpan...</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    function createReductionModal() {
        return {
            formData: {
                va: '',
                nominal: '',
                bulan: '',
                tahun: '',
            },
            isSaving: false,

            openModal() {
                this.formData.va = '';
                this.formData.nominal = '';
                this.formData.bulan = '';
                this.formData.tahun = '';
                document.getElementById('create-reduction-modal').classList.remove('hidden');
            },

            closeModal() {
                document.getElementById('create-reduction-modal').classList.add('hidden');
            },

            async saveReduction() {
                if (this.isSaving) return;

                this.isSaving = true;

                try {
                    const response = await window.api.post('/reduction', this.formData);
                    console.info(response)
                    if (response.data && response.data.success) {
                        window.showToast(response.data.message, 'success');
                        this.closeModal();
                    }
                } catch (error) {
                    console.error('Gagal tambah keringanan:', error);
                } finally {
                    this.isSaving = false;
                }
            }
        }
    }
</script>