<div id="import-payment-modal"
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-[100]">
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 py-6 px-8 border w-11/12 md:w-2/3 lg:w-3/4 shadow-xl rounded-lg bg-white">
        
        <div class="flex justify-between items-center pb-3 border-b border-gray-200 mb-6">
            <h3 class="text-xl font-semibold text-gray-700">Impor Data Pembayaran</h3>
            <button onclick="document.getElementById('import-payment-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="ti ti-x text-2xl"></i>
            </button>
        </div>

        <div class="space-y-6">
            <div>
                <a href="/format?type=payment" download
                   class="mt-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="ti ti-file-download mr-2 -ml-1"></i>
                    Unduh Template Excel (.xlsx)
                </a>
            </div>

            <form id="import-payment-form" enctype="multipart/form-data" class="space-y-4 pt-4 border-t">
                 <div>
                    <label for="import-payments-file" class="block text-sm font-medium text-gray-700 mb-1">Pilih File Laporan Pembayaran (.xlsx)</label>
                    <input type="file" name="import-payments" id="import-payments-file" required accept=".xlsx"
                           class="block w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                                  file:text-sm file:font-semibold file:bg-sky-50 file:text-sky-700
                                  hover:file:bg-sky-100 cursor-pointer border border-gray-300 rounded-md p-1"/>
                    <p id="import-payment-file-name" class="mt-1 text-xs text-gray-500">Belum ada file dipilih.</p>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('import-payment-modal').classList.add('hidden')"
                            class="px-6 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-colors text-sm font-medium flex items-center gap-2">
                        <i class="ti ti-upload"></i>
                        Proses File Pembayaran
                    </button>
                </div>
            </form>
            
            <div id="import-payment-progress" class="hidden mt-4">
                <p class="text-sm text-sky-600">Mengupload dan memproses file... Ini mungkin butuh waktu.</p>
                <div class="w-full bg-gray-200 rounded-full h-2.5 mt-1">
                    <div id="import-payment-bar" class="bg-sky-600 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
            </div>
            <div id="import-payment-result" class="mt-4 text-sm"></div>

        </div>
    </div>
</div>