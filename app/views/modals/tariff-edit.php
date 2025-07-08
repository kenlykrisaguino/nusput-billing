<div id="edit-tariff-modal"
     x-data="editTariffModal"
     x-show="openModal"
     @open-edit-tariff-modal.window="openModal($event.detail.tariffId)"
     @keydown.escape.window="closeModal()"
     x-transition.opacity
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-[100]">
     
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 py-6 px-8 border w-11/12 md:w-1/2 lg:w-1/3 max-w-lg shadow-xl rounded-lg bg-white">
        
        <div class="flex justify-between items-center pb-3 border-b border-gray-200 mb-6">
            <h3 class="text-xl font-semibold text-gray-700">Edit Tarif SPP (<span id="tariff-year"></span>)</h3>
            <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="ti ti-x text-2xl"></i>
            </button>
        </div>

        <form @submit.prevent="saveTariff()">
                <input type="hidden" x-model="formData.id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kelas</label>
                        <div class="mt-1 p-3 bg-gray-100 border border-gray-200 rounded-md">
                            <p class="text-gray-900 font-semibold">
                                <span x-text="formData.jenjang || 'N/A'"></span> - 
                                <span x-text="formData.tingkat || 'N/A'"></span>
                            </p>
                            <p class="text-sm text-gray-600">
                                <span x-text="formData.kelas || 'Semua Kelas di Tingkat Ini'"></span>
                            </p>
                        </div>
                    </div>
                    
                    <div>
                        <label for="edit_tariff_nominal" class="block text-sm font-medium text-gray-700">Nominal Tarif SPP Baru</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">Rp</span>
                            </div>
                            <input type="number" id="edit_tariff_nominal" x-model.number="formData.nominal" required placeholder="500000"
                                   class="block w-full rounded-md border-gray-300 pl-10 px-3 py-2 shadow-sm sm:text-sm focus:border-sky-500 focus:ring-sky-500">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-200 gap-3">
                    <button type="button" @click="closeModal()" class="px-5 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors text-sm font-medium">Batal</button>
                    <button type="submit" :disabled="isSaving" class="px-5 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-colors text-sm font-medium flex items-center gap-2">
                        <span x-show="!isSaving">Update Tarif</span>
                        <span x-show="isSaving">Menyimpan...</span>
                    </button>
                </div>
        </form>
    </div>
</div>

<script src="/js/components/editTariffModal.js"></script>