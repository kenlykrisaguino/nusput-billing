<div id="create-student-modal" x-data="{ activeTab: 'bulkUpload' }"
    @modal-opened.window="if ($event.detail.modalId === 'create-student-modal' && $event.detail.initialTab) activeTab = $event.detail.initialTab; else activeTab = 'bulkUpload';"
    class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-[100]">
    <div
        class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 py-6 px-8 border w-11/12 md:w-2/3 lg:w-1/2 max-w-4xl shadow-xl rounded-lg bg-white">
        <div class="flex justify-between items-center pb-3 border-b border-gray-200 mb-6">
            <h3 class="text-xl font-semibold text-gray-700">Tambah Siswa</h3>
            <button @click="$el.closest('#create-student-modal').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="ti ti-x text-2xl"></i>
            </button>
        </div>

        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px space-x-6 sm:space-x-8" aria-label="Tabs">
                <button @click="activeTab = 'bulkUpload'"
                    :class="{ 'border-sky-500 text-sky-600': activeTab === 'bulkUpload', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'bulkUpload' }"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm focus:outline-none">
                    Upload File Excel (Bulk)
                </button>
                <button @click="activeTab = 'manualForm'"
                    :class="{ 'border-sky-500 text-sky-600': activeTab === 'manualForm', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'manualForm' }"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm focus:outline-none">
                    Isi Form Manual
                </button>

            </nav>
        </div>

        <div>
            <div x-show="activeTab === 'bulkUpload'" id="bulk-upload-section" class="space-y-6">
                <div>
                    <p class="text-sm text-gray-600 mb-2">
                        Gunakan fitur ini untuk menambahkan banyak data siswa sekaligus menggunakan file Excel (.xlsx).
                        Pastikan format file Anda sesuai dengan template yang disediakan.
                    </p>
                    <a href="/format?type=student" download
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mb-4">
                        <i class="ti ti-file-download mr-2 -ml-1"></i>
                        Unduh Template Excel
                    </a>
                </div>
                <form id="bulk-upload-student-form" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label for="student_xlsx_file" class="block text-sm font-medium text-gray-700 mb-1">Pilih File
                            Excel (.xlsx)</label>
                        <input type="file" name="student_xlsx_file" id="student_xlsx_file" required accept=".xlsx"
                            class="block w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                                      file:text-sm file:font-semibold file:bg-sky-50 file:text-sky-700
                                      hover:file:bg-sky-100 cursor-pointer border border-gray-300 rounded-md p-1" />
                        <p id="bulk-file-name-display" class="mt-1 text-xs text-gray-500">Belum ada file dipilih.</p>
                    </div>
                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="$el.closest('#create-student-modal').classList.add('hidden')"
                            class="px-6 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-6 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-colors text-sm font-medium flex items-center gap-2">
                            <i class="ti ti-upload"></i>
                            Upload File
                        </button>
                    </div>
                </form>
                <div id="bulk-upload-progress" class="hidden mt-4">
                    <p class="text-sm text-sky-600">Sedang mengupload dan memproses file...</p>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mt-1">
                        <div id="bulk-upload-bar" class="bg-sky-600 h-2.5 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
                <div id="bulk-upload-result" class="mt-4 text-sm"></div>
            </div>

            <div x-show="activeTab === 'manualForm'" id="manual-form-section">
                <form id="create-student-form-manual" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="create_nama_siswa" class="block text-sm font-medium text-gray-700 mb-1">Nama
                                Lengkap Siswa</label>
                            <input type="text" name="nama" id="create_nama_siswa" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm"
                                placeholder="Masukkan nama lengkap">
                        </div>
                        <div>
                            <label for="create_nis_siswa" class="block text-sm font-medium text-gray-700 mb-1">Nomor
                                Induk Siswa (NIS)</label>
                            <input type="text" name="nis" id="create_nis_siswa" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm"
                                placeholder="Masukkan NIS">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="create_jenjang_id"
                                class="block text-sm font-medium text-gray-700 mb-1">Jenjang</label>
                            <select name="jenjang_id" id="create_jenjang_id" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                                <option value="" disabled selected>Memuat Jenjang...</option>
                            </select>
                        </div>
                        <div>
                            <label for="create_tingkat_id"
                                class="block text-sm font-medium text-gray-700 mb-1">Tingkat</label>
                            <select name="tingkat_id" id="create_tingkat_id" required disabled
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-gray-100 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                                <option value="" disabled selected>Pilih Tingkat</option>
                            </select>
                        </div>
                        <div>
                            <label for="create_kelas_id"
                                class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
                            <select name="kelas_id" id="create_kelas_id" required disabled
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-gray-100 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm">
                                <option value="" disabled selected>Pilih Kelas</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="create_no_hp_ortu" class="block text-sm font-medium text-gray-700 mb-1">No. HP
                                Orang Tua/Wali</label>
                            <input type="tel" name="no_hp_ortu" id="create_no_hp_ortu"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm"
                                placeholder="Contoh: 081234567890">
                        </div>
                        <div>
                            <label for="create_spp_siswa" class="block text-sm font-medium text-gray-700 mb-1">Tarif
                                SPP Bulanan</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                </div>
                                <input type="number" name="spp" id="create_spp_siswa" required step="any"
                                    class="block w-full px-3 py-2 pl-10 pr-3 border border-gray-300 rounded-md focus:outline-none focus:ring-sky-500 focus:border-sky-500 sm:text-sm"
                                    placeholder="0.00">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Bisa terisi otomatis atau di-override.</p>
                        </div>
                    </div>
                    <div class="pt-6 border-t border-gray-200 flex justify-end gap-3">
                        <button type="button" @click="$el.closest('#create-student-modal').classList.add('hidden')"
                            class="px-6 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-6 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-colors text-sm font-medium">
                            Simpan Siswa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('create-student-modal');
        if (!modal) return;

        const manualForm = document.getElementById('create-student-form-manual');
        const bulkForm = document.getElementById('bulk-upload-student-form');

        const selectJenjang = document.getElementById('create_jenjang_id');
        const selectTingkat = document.getElementById('create_tingkat_id');
        const selectKelas = document.getElementById('create_kelas_id');
        const inputSpp = document.getElementById('create_spp_siswa');

        const fileInput = document.getElementById('student_xlsx_file');
        const fileNameDisplay = document.getElementById('bulk-file-name-display');
        const bulkProgress = document.getElementById('bulk-upload-progress');
        const bulkProgressBar = document.getElementById('bulk-upload-bar');
        const bulkResult = document.getElementById('bulk-upload-result');

        const resetSelect = (select, placeholder) => {
            select.innerHTML = `<option value="" disabled selected>${placeholder}</option>`;
            select.disabled = true;
            select.classList.add('bg-gray-100');
        };

        const populateSelect = (select, data, placeholder) => {
            resetSelect(select, placeholder);
            if (data && data.length > 0) {
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.nama;
                    select.appendChild(option);
                });
                select.disabled = false;
                select.classList.remove('bg-gray-100');
            }
        };

        const loadJenjang = async () => {
            try {
                const response = await window.api.get('/jenjang');
                if (response.data && response.data.success) {
                    populateSelect(selectJenjang, response.data.data, 'Pilih Jenjang');
                }
            } catch (error) {
                console.error('Gagal memuat jenjang:', error);
                window.showToast('Gagal memuat data jenjang.', 'error');
            }
        };

        const handleJenjangChange = async () => {
            const jenjangId = selectJenjang.value;
            resetSelect(selectTingkat, 'Pilih Tingkat');
            resetSelect(selectKelas, 'Pilih Kelas');
            inputSpp.value = '';
            if (!jenjangId) return;

            try {
                const response = await window.api.get(`/tingkat?jenjang_id=${jenjangId}`);
                if (response.data && response.data.success) {
                    populateSelect(selectTingkat, response.data.data, 'Pilih Tingat');
                }
            } catch (error) {
                console.error('Gagal memuat tingkat:', error);
                window.showToast('Gagal memuat data tingkat.', 'error');
            }
        };

        const handleTingkatChange = async () => {
            const tingkatId = selectTingkat.value;
            resetSelect(selectKelas, 'Pilih Kelas');
            inputSpp.value = '';
            if (!tingkatId) return;

            try {
                const response = await window.api.get(`/kelas?tingkat_id=${tingkatId}`);
                if (response.data && response.data.success) {
                    populateSelect(selectKelas, response.data.data, 'Pilih Kelas');
                    if (response.data.data.length == 0) {
                        await fetchSppTarif();
                    }
                }
            } catch (error) {
                console.error('Gagal memuat kelas:', error);
                window.showToast('Gagal memuat data kelas.', 'error');
            }
        };

        const handleKelasChange = async () => {
            await fetchSppTarif();
        };

        const fetchSppTarif = async () => {
            const jenjangId = selectJenjang.value;
            const tingkatId = selectTingkat.value;
            const kelasId = selectKelas.value;
            inputSpp.value = '';

            if (!jenjangId || !tingkatId) return;

            let apiUrl = `/spp-tarif?jenjang_id=${jenjangId}&tingkat_id=${tingkatId}`;
            if (kelasId) {
                apiUrl += `&kelas_id=${kelasId}`;
            }

            try {
                const response = await window.api.get(apiUrl);
                if (response.data && response.data.success && response.data.data.nominal) {
                    inputSpp.value = parseFloat(response.data.data.nominal).toFixed(0);
                }
            } catch (error) {
                console.error('Gagal memuat tarif SPP:', error);
                window.showToast('Gagal mengambil tarif SPP.', 'warning');
            }
        };

        const initModal = () => {
            loadJenjang();
            manualForm.reset();
            bulkForm.reset();
            resetSelect(selectTingkat, 'Pilih Tingkat');
            resetSelect(selectKelas, 'Pilih Kelas');
            fileNameDisplay.textContent = 'Belum ada file dipilih.';
            bulkProgress.classList.add('hidden');
            bulkResult.innerHTML = '';
        };

        const observer = new MutationObserver((mutationsList) => {
            for (const mutation of mutationsList) {
                if (mutation.attributeName === 'class' && !modal.classList.contains('hidden')) {
                    initModal();
                }
            }
        });

        observer.observe(modal, {
            attributes: true
        });

        selectJenjang.addEventListener('change', handleJenjangChange);
        selectTingkat.addEventListener('change', handleTingkatChange);
        selectKelas.addEventListener('change', handleKelasChange);

        manualForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = 'Menyimpan...';
            submitButton.disabled = true;

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await window.api.post('/upload-student', data);
                if (response.data && response.data.success) {
                    window.showToast(response.data.message || 'Siswa berhasil ditambahkan!',
                        'success');
                    modal.classList.add('hidden');
                    if (window.pages && typeof window.pages.students.loadStudentsData ===
                        'function') {
                        window.pages.students.loadStudentsData();
                    }
                }
            } catch (error) {
                console.error('Gagal menambahkan siswa:', error);
            } finally {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }
        });

        fileInput.addEventListener('change', () => {
            fileNameDisplay.textContent = fileInput.files.length > 0 ? fileInput.files[0].name :
                'Belum ada file dipilih.';
        });

        bulkForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = 'Mengupload...';
            submitButton.disabled = true;

            bulkProgress.classList.remove('hidden');
            bulkProgressBar.style.width = '0%';
            bulkResult.innerHTML = '';

            const formData = new FormData(this);

            try {
                const response = await window.api.post('/upload-students-bulk', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    },
                    onUploadProgress: (progressEvent) => {
                        const percentCompleted = Math.round((progressEvent.loaded *
                            100) / progressEvent.total);
                        bulkProgressBar.style.width = `${percentCompleted}%`;
                    }
                });

                if (response.data && response.data.success) {
                    window.showToast(response.data.message || 'Siswa berhasil diimpor.', 'success');
                    bulkResult.innerHTML =
                        `<div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg">${response.data.message}</div>`;
                    setTimeout(() => {
                        modal.classList.add('hidden');
                        if (window.pages && typeof window.pages.students
                            .loadStudentsData === 'function') {
                            window.pages.students.loadStudentsData();
                        }
                    }, 2000);
                }
            } catch (error) {
                const errorMessage = error.response?.data?.message || 'Gagal mengupload file.';
                bulkResult.innerHTML =
                    `<div class="p-4 text-sm text-red-700 bg-red-100 rounded-lg">${errorMessage}</div>`;
            } finally {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }
        });

    });
</script>
