<div id="edit-fee-modal"
     x-show="show"
     x-data="editFeeModal"
     x-cloak
     @open-edit-fee.window="openModal($event.detail.id, $event.detail.month, $event.detail.year)"
     @keydown.escape.window="closeModal()"
     class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-[100]">

    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 py-6 px-8 border w-11/12 md:w-1/2 lg:w-1/3 shadow-xl rounded-lg bg-white">

        <div class="flex justify-between items-center pb-3 border-b border-gray-200 mb-6">
            <div>
                <h3 class="text-xl font-semibold text-gray-700">Edit Rincian Tagihan</h3>
                <p class="text-sm text-gray-500" x-text="`Siswa: ${studentName} - Periode: ${periodLabel}`"></p>
            </div>
            <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="ti ti-x text-2xl"></i>
            </button>
        </div>

        <div class="space-y-6">
            <div x-show="isLoading" class="text-center py-10">
                <i class="ti ti-loader animate-spin text-sky-500 text-4xl"></i>
                <p class="mt-2 text-sm text-gray-600">Memuat data...</p>
            </div>

            <div x-show="!isLoading" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">SPP</label>
                    <div class="mt-1 px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-500" x-text="formatRupiah(fees.spp)"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Denda</label>
                    <div class="mt-1 px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-500" x-text="formatRupiah(fees.denda)"></div>
                </div>

                <div class="pt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Biaya Lainnya</label>
                    <div class="space-y-2 max-h-40 overflow-y-auto border rounded-md p-3 bg-gray-50">
                        <template x-if="fees.dynamic_fees && fees.dynamic_fees.length === 0">
                            <p class="text-xs text-gray-400 text-center">Tidak ada biaya lainnya.</p>
                        </template>
                        <template x-for="fee in fees.dynamic_fees" :key="fee.id">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600" x-text="fee.jenis"></span>
                                <span class="font-medium text-gray-800" x-text="formatRupiah(fee.nominal)"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="!isLoading" class="pt-6 mt-6 flex justify-end gap-3 border-t">
            <button type="button" @click="closeModal()"
                    class="px-6 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium">
                Tutup
            </button>
        </div>
    </div>
</div>

<script src="/js/components/editFeeModal.js"></script>
