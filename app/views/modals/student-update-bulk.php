<div id="update-students-modal"
     class="hidden fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-[100]">
    <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 py-6 px-8 border w-11/12 md:w-4/6 lg:w-3/6 max-w-xl shadow-xl rounded-lg bg-white">
        
        <div class="flex justify-between items-center pb-3 border-b border-gray-200 mb-6">
            <h3 class="text-xl font-semibold text-gray-700">Sinkronisasi Siswa Massal</h3>
            <button onclick="document.getElementById('update-students-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="ti ti-x text-2xl"></i>
            </button>
        </div>

        <div class="space-y-6">
            <div class="p-4 bg-amber-50 border-l-4 border-amber-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="ti ti-alert-triangle text-amber-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-amber-700">
                            <strong>Peringatan:</strong> Tindakan ini akan menimpa seluruh data siswa. Siswa yang tidak ada di file akan dinonaktifkan.
                        </p>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="font-medium text-gray-800 mb-2">Langkah 1: Ekspor Data Terbaru</h4>
                <a href="/exports/students" download
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="ti ti-file-export mr-2 -ml-1"></i>
                    Ekspor Data Siswa Saat Ini (.xlsx)
                </a>
            </div>

            <form id="bulk-update-student-form" enctype="multipart/form-data" class="space-y-4 pt-4 border-t">
                 <div>
                    <h4 class="font-medium text-gray-800 mb-2">Langkah 2: Upload File</h4>
                    <label for="bulk_update_xlsx_file" class="block text-sm font-medium text-gray-700 mb-1">Pilih File Excel (.xlsx) yang Sudah Diedit</label>
                    <input type="file" name="bulk_update_xlsx_file" id="bulk_update_xlsx_file" required accept=".xlsx"
                           class="block w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                                  file:text-sm file:font-semibold file:bg-sky-50 file:text-sky-700
                                  hover:file:bg-sky-100 cursor-pointer border border-gray-300 rounded-md p-1"/>
                    <p id="bulk-update-file-name-display" class="mt-1 text-xs text-gray-500">Belum ada file dipilih.</p>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('update-students-modal').classList.add('hidden')"
                            class="px-6 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors text-sm font-medium flex items-center gap-2">
                        <i class="ti ti-refresh-dot"></i>
                        Proses dan Sinkronkan
                    </button>
                </div>
            </form>
            
            <div id="bulk-update-progress" class="hidden mt-4">
                <p class="text-sm text-sky-600">Memproses file...</p>
                <div class="w-full bg-gray-200 rounded-full h-2.5 mt-1">
                    <div id="bulk-update-bar" class="bg-sky-600 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
            </div>
            <div id="bulk-update-result" class="mt-4 text-sm"></div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('update-students-modal');
    if (!modal) return;

    const bulkForm = document.getElementById('bulk-update-student-form');
    const fileInput = document.getElementById('bulk_update_xlsx_file');
    const fileNameDisplay = document.getElementById('bulk-update-file-name-display');
    const progressContainer = document.getElementById('bulk-update-progress');
    const progressBar = document.getElementById('bulk-update-bar');
    const resultContainer = document.getElementById('bulk-update-result');

    const observer = new MutationObserver((mutationsList) => {
        for (const mutation of mutationsList) {
            if (mutation.attributeName === 'class' && !modal.classList.contains('hidden')) {
                bulkForm.reset();
                fileNameDisplay.textContent = 'Belum ada file dipilih.';
                progressContainer.classList.add('hidden');
                resultContainer.innerHTML = '';
            }
        }
    });
    observer.observe(modal, { attributes: true });

    fileInput.addEventListener('change', () => {
        fileNameDisplay.textContent = fileInput.files.length > 0 
            ? fileInput.files[0].name 
            : 'Belum ada file dipilih.';
    });

    bulkForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.textContent = 'Memproses...';
        submitButton.disabled = true;
        
        progressContainer.classList.remove('hidden');
        progressBar.style.width = '0%';
        resultContainer.innerHTML = '';
        
        const formData = new FormData(this);

        try {
            const response = await window.api.post('/update-students-bulk', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
                responseType: 'blob',
                onUploadProgress: (progressEvent) => {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    progressBar.style.width = `${percentCompleted}%`;
                }
            });

            if (response.headers['content-type'].includes('spreadsheetml')) {
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                const filename = response.headers['content-disposition'].split('filename=')[1].replace(/"/g, '');
                link.setAttribute('download', filename || 'import_errors.xlsx');
                document.body.appendChild(link);
                link.click();
                link.remove();

                window.showToast('Beberapa data tidak valid. Silakan periksa file error yang diunduh.', 'warning');
                resultContainer.innerHTML = `<div class="p-4 text-sm text-amber-700 bg-amber-100 rounded-lg">Ditemukan error. File berisi detail kesalahan telah diunduh secara otomatis.</div>`;

            } else {
                const jsonText = await response.data.text();
                const jsonResponse = JSON.parse(jsonText);
                
                if (jsonResponse.success) {
                     const summary = jsonResponse.data;
                     const message = `Sinkronisasi Selesai: ${summary.created} dibuat, ${summary.updated} diupdate, ${summary.deleted} dinonaktifkan.`;
                    window.showToast(message, 'success');
                    resultContainer.innerHTML = `<div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg">${message}</div>`;
                    
                    setTimeout(() => {
                        modal.classList.add('hidden');
                        if (window.pages && typeof window.pages.students.loadStudentsData === 'function') {
                            window.pages.students.loadStudentsData(); // Refresh tabel
                        }
                    }, 3000);
                } else {
                    throw new Error(jsonResponse.message || 'Terjadi kesalahan.');
                }
            }
        } catch (error) {
            let errorMessage = 'Gagal memproses file.';
            if (error.response && error.response.data) {
                try {
                    const errorJsonText = await error.response.data.text();
                    const errorJson = JSON.parse(errorJsonText);
                    errorMessage = errorJson.message || errorMessage;
                } catch (e) { /* biarkan pesan default */ }
            } else if(error.message) {
                errorMessage = error.message;
            }
            
            window.showToast(errorMessage, 'error');
            resultContainer.innerHTML = `<div class="p-4 text-sm text-red-700 bg-red-100 rounded-lg">${errorMessage}</div>`;
        } finally {
            submitButton.textContent = originalText;
            submitButton.disabled = false;
        }
    });
});
</script>