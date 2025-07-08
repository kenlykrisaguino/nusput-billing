<div id="filter-recap-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-[100]">
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 p-6 border w-11/12 md:w-1/2 lg:w-1/3 max-w-lg shadow-xl rounded-lg bg-white">
        
        <div class="flex justify-between items-center pb-3 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-800">Filter Tagihan</h3>
            <button onclick="document.getElementById('filter-recap-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="ti ti-x text-2xl"></i>
            </button>
        </div>

        <div class="my-6">
            <form id="filter-recap-form">
                <div class="space-y-5">
                    <div>
                        <label for="filter-tahun" class="block text-sm font-medium text-gray-700">Tahun</label>
                        <select name="filter-tahun" id="filter-tahun" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                           <option value="">Memuat Tahun...</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter-jenjang" class="block text-sm font-medium text-gray-700">Jenjang</label>
                        <select name="filter-jenjang" id="filter-jenjang" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                           <option value="">Memuat Jenjang...</option>
                        </select>
                    </div>

                    <div>
                        <label for="filter-tingkat" class="block text-sm font-medium text-gray-700">Tingkat</label>
                        <select name="filter-tingkat" id="filter-tingkat" disabled class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-gray-100 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                            <option value="">Semua Tingkat</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="filter-kelas" class="block text-sm font-medium text-gray-700">Kelas</label>
                        <select name="filter-kelas" id="filter-kelas" disabled class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-gray-100 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                            <option value="">Semua Kelas</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="flex justify-end pt-4 border-t border-gray-200 gap-3">
            <button type="button" id="reset-filter-btn" class="px-5 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors text-sm font-medium">Reset</button>
            <button type="button" id="apply-filter-btn" class="px-5 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-colors text-sm font-medium flex items-center gap-2">
                <i class="ti ti-filter"></i>
                Terapkan Filter
            </button>
        </div>
    </div>
</div>