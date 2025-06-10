<div id="create-level-modal" 
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-[100]"
     x-data="createLevelModal()"
     @open-create-level-modal.window="openModal()">
     
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 py-6 px-8 border w-11/12 md:w-1/2 lg:w-1/3 max-w-lg shadow-xl rounded-lg bg-white">
        
        <div class="flex justify-between items-center pb-3 border-b border-gray-200 mb-6">
            <h3 class="text-xl font-semibold text-gray-700">Tambah Jenjang Baru</h3>
            <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="ti ti-x text-2xl"></i>
            </button>
        </div>

        <form @submit.prevent="saveLevel()">
            <div class="space-y-4">
                <div>
                    <label for="create_level_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Jenjang</label>
                    <input type="text" id="create_level_name" x-model="formData.nama" required placeholder="Contoh: SMA, SMK Perhotelan"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                </div>
                <div>
                    <label for="create_level_va_code" class="block text-sm font-medium text-gray-700 mb-1">Kode Unik VA</label>
                    <input type="number" id="create_level_va_code" x-model.number="formData.va_code" required placeholder="Contoh: 3"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Kode 1-2 digit untuk prefix Virtual Account. Harus unik.</p>
                </div>
            </div>

            <div class="pt-6 mt-6 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" @click="closeModal()"
                        class="px-6 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm font-medium">
                    Batal
                </button>
                <!-- Tombol submit sekarang menampilkan status 'isSaving' -->
                <button type="submit" :disabled="isSaving"
                        class="px-6 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 text-sm font-medium flex items-center justify-center w-32">
                    <span x-show="!isSaving">Simpan Jenjang</span>
                    <span x-show="isSaving">Menyimpan...</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    function createLevelModal() {
        return {
            formData: {
                nama: '',
                va_code: ''
            },
            isSaving: false,

            openModal() {
                this.formData.nama = '';
                this.formData.va_code = '';
                document.getElementById('create-level-modal').classList.remove('hidden');
            },

            closeModal() {
                document.getElementById('create-level-modal').classList.add('hidden');
            },

            async saveLevel() {
                if (this.isSaving) return;

                this.isSaving = true;

                try {
                    const response = await window.api.post('/level-create', this.formData);
                    
                    if (response.data && response.data.success) {
                        window.showToast(response.data.message, 'success');
                        this.closeModal();
                    }
                } catch (error) {
                    console.error('Gagal tambah jenjang:', error);
                } finally {
                    this.isSaving = false;
                }
            }
        }
    }
</script>