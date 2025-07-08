<div id="create-class-modal" 
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-[100]"
     x-data="createClassModal()"
     @open-create-class-modal.window="openModal()">
     
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 py-6 px-8 border w-11/12 md:w-1/2 lg:w-1/3 max-w-lg shadow-xl rounded-lg bg-white">
        
        <div class="flex justify-between items-center pb-3 border-b border-gray-200 mb-6">
            <h3 class="text-xl font-semibold text-gray-700">Tambah Kelas Baru</h3>
            <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="ti ti-x text-2xl"></i>
            </button>
        </div>

        <form @submit.prevent="saveClass()">
            <div class="space-y-4">
                <div>
                    <label for="create_class_level_id" class="block text-sm font-medium text-gray-700 mb-1">Pilih Jenjang</label>
                    <select id="create_class_level_id" x-model.number="selection.jenjang_id" @change="fetchTingkat()" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                        <option value="">Memuat jenjang...</option>
                        <template x-for="jenjang in lists.jenjang" :key="jenjang.id">
                            <option :value="jenjang.id" x-text="jenjang.nama"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label for="create_class_grade_id" class="block text-sm font-medium text-gray-700 mb-1">Pilih Tingkat Induk</label>
                    <select id="create_class_grade_id" x-model.number="selection.tingkat_id" required :disabled="lists.tingkat.length === 0"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm disabled:bg-gray-100">
                        <option value="">Pilih Jenjang terlebih dahulu</option>
                        <template x-for="tingkat in lists.tingkat" :key="tingkat.id">
                            <option :value="tingkat.id" x-text="tingkat.nama"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label for="create_class_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Kelas</label>
                    <input type="text" id="create_class_name" x-model="formData.nama" required placeholder="Contoh: A, B, IPA 1, IPS 2"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                </div>
            </div>

            <div class="pt-6 mt-6 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" @click="closeModal()"
                        class="px-6 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm font-medium">
                    Batal
                </button>
                <button type="submit" :disabled="isSaving"
                        class="px-6 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 text-sm font-medium flex items-center justify-center w-32">
                    <span x-show="!isSaving">Simpan Kelas</span>
                    <span x-show="isSaving">Menyimpan...</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    function createClassModal() {
        return {
            formData: { nama: '' },
            selection: { jenjang_id: '', tingkat_id: '' },
            lists: { jenjang: [], tingkat: [] },
            isSaving: false,

            async openModal() {
                this.formData.nama = '';
                this.selection.jenjang_id = '';
                this.selection.tingkat_id = '';
                this.lists.tingkat = [];
                await this.fetchJenjang();
                document.getElementById('create-class-modal').classList.remove('hidden');
            },

            closeModal() {
                document.getElementById('create-class-modal').classList.add('hidden');
            },

            async fetchJenjang() {
                try {
                    const response = await window.api.get('/jenjang');
                    if (response.data.success) this.lists.jenjang = response.data.data;
                } catch (error) { console.error('Gagal memuat jenjang:', error); }
            },

            async fetchTingkat() {
                this.selection.tingkat_id = '';
                this.lists.tingkat = [];
                if (!this.selection.jenjang_id) return;
                try {
                    const response = await window.api.get(`/tingkat?jenjang_id=${this.selection.jenjang_id}`);
                    if (response.data.success) this.lists.tingkat = response.data.data;
                } catch (error) { console.error('Gagal memuat tingkat:', error); }
            },

            async saveClass() {
                if (this.isSaving) return;
                this.isSaving = true;

                const payload = { ...this.formData, tingkat_id: this.selection.tingkat_id };
                
                try {
                    const response = await window.api.post('/class-create', payload);
                    if (response.data && response.data.success) {
                        window.showToast(response.data.message, 'success');
                        this.closeModal();
                    }
                } catch (error) {
                    console.error('Gagal tambah kelas:', error);
                } finally {
                    this.isSaving = false;
                }
            }
        }
    }
</script>