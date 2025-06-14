<div id="edit-student-modal" 
     x-data="editStudentModal"
     x-show="openModal"
     @open-edit-student.window="openModal($event.detail.studentId)"
     @keydown.escape.window="closeModal()"
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-[100]"
     x-transition.opacity>
    
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 py-6 px-8 border w-11/12 md:w-2/3 lg:w-4/6 max-w-4xl shadow-xl rounded-lg bg-white">
        
        <div class="flex justify-between items-center pb-3 border-b border-gray-200 mb-6">
            <h3 class="text-xl font-semibold text-gray-700">Edit Data Siswa: <span x-text="student.nama || 'Memuat...'"></span></h3>
            <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="ti ti-x text-2xl"></i>
            </button>
        </div>

        <!-- Form Utama -->
        <form @submit.prevent="saveStudentData()" class="space-y-6">
            <input type="hidden" x-model="student.id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="edit_nama_siswa" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap Siswa</label>
                    <input type="text" id="edit_nama_siswa" x-model="student.nama" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                </div>
                <div>
                    <label for="edit_nis_siswa" class="block text-sm font-medium text-gray-700 mb-1">Nomor Induk Siswa (NIS)</label>
                    <input type="text" id="edit_nis_siswa" x-model="student.nis" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="edit_jenjang_id" class="block text-sm font-medium text-gray-700 mb-1">Jenjang</label>
                    <select id="edit_jenjang_id" x-model.number="student.jenjang_id" @change="fetchTingkat()" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                        <option value="">Pilih Jenjang</option>
                        <template x-for="jenjang in a.jenjangList" :key="jenjang.id">
                            <option :value="jenjang.id" x-text="jenjang.nama"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label for="edit_tingkat_id" class="block text-sm font-medium text-gray-700 mb-1">Tingkat</label>
                    <select id="edit_tingkat_id" x-model.number="student.tingkat_id" @change="fetchKelas()" :disabled="a.tingkatList.length === 0" required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm disabled:bg-gray-100">
                        <option value="">Pilih Tingkat</option>
                        <template x-for="tingkat in a.tingkatList" :key="tingkat.id">
                            <option :value="tingkat.id" x-text="tingkat.nama"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label for="edit_kelas_id" class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
                    <select id="edit_kelas_id" x-model.number="student.kelas_id" :disabled="a.kelasList.length === 0"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm disabled:bg-gray-100">
                        <option value="">Pilih Kelas</option>
                        <template x-for="kelas in a.kelasList" :key="kelas.id">
                            <option :value="kelas.id" x-text="kelas.nama"></option>
                        </template>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="edit_no_hp_ortu" class="block text-sm font-medium text-gray-700 mb-1">No. HP Orang Tua/Wali</label>
                    <input type="tel" id="edit_no_hp_ortu" x-model="student.no_hp_ortu"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                </div>
                <div>
                    <label for="edit_spp_siswa" class="block text-sm font-medium text-gray-700 mb-1">Tarif SPP Bulanan</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">Rp</span>
                        </div>
                        <input type="number" id="edit_spp_siswa" x-model.number="student.spp" required
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                    </div>
                </div>
            </div>
            
            <div class="pt-6 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" @click="closeModal()"
                        class="px-6 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium">
                    Batal
                </button>
                <button type="submit"
                        class="px-6 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-colors text-sm font-medium">
                    Update Data Siswa
                </button>
            </div>
        </form>

        <!-- Biaya Tambahan -->
        <div class="mt-8 pt-6 border-t border-dashed">
             <div class="flex justify-between items-center mb-4">
                <h4 class="text-lg font-semibold text-gray-800">Biaya Tambahan Siswa</h4>
                <div class="flex items-center gap-2">
                    <label for="edit_fee_month" class="text-sm font-medium text-gray-700">Periode:</label>
                    <select id="edit_fee_month" x-model.number="a.feePeriod.month" @change="fetchAdditionalFees()"
                            class="block w-full px-2 py-1 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                        <!-- Opsi Bulan diisi oleh init() -->
                    </select>
                    <select id="edit_fee_year" x-model.number="a.feePeriod.year" @change="fetchAdditionalFees()"
                            class="block w-full px-2 py-1 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                        <!-- Opsi Tahun diisi oleh init() -->
                    </select>
                </div>
             </div>

             <form @submit.prevent="saveAdditionalFees()">
                 <div class="space-y-3 bg-slate-50 p-4 rounded-md border">
                     <template x-if="a.additionalFees.length === 0">
                        <p class="text-center text-gray-500 py-4">Tidak ada biaya tambahan untuk periode ini.</p>
                     </template>
                     <template x-for="(fee, index) in a.additionalFees" :key="fee.id || index">
                        <div class="grid grid-cols-12 gap-2 items-center">
                            <input type="hidden" x-model="fee.id">
                            <div class="col-span-4">
                                <select x-model="fee.kategori" required
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                                    <option value="">Pilih Kategori</option>
                                    <template x-for="cat in a.feeCategoryList" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.nama"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-span-3">
                                <input type="number" x-model.number="fee.nominal" placeholder="Nominal" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                            </div>
                            <div class="col-span-4">
                                <input type="text" x-model="fee.keterangan" placeholder="Keterangan (opsional)"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                            </div>
                            <div class="col-span-1 text-right">
                                <button type="button" @click="removeFee(index)" title="Hapus baris" class="text-red-500 hover:text-red-700 p-1">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>
                     </template>
                 </div>
                 <div class="flex justify-between items-center mt-4">
                    <button type="button" @click="addFee()"
                            class="text-sm text-sky-600 hover:text-sky-800 font-medium flex items-center gap-1">
                        <i class="ti ti-plus"></i> Tambah Baris Biaya
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
                        Simpan Biaya Tambahan
                    </button>
                 </div>
             </form>
        </div>
    </div>
</div>

<script src="/js/components/editStudentModal.js"></script>