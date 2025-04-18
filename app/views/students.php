<div class="w-full">
    <div class="flex justify-between w-full mb-2">
        <h1 class="text-lg font-semibold text-slate-800">List Siswa</h1>
        <div class="flex gap-2">
            <div onclick="document.getElementById('create-student').classList.remove('hidden')"
                class="px-2 py-1 text-xs rounded-md border border-blue-700 text-blue-500 hover:text-blue-800 cursor-pointer font-semibold">
                Tambah Siswa</div>
            <a href="/exports/students"
                class="px-2 py-1 text-xs rounded-md border border-blue-700 text-blue-500 hover:text-blue-800 cursor-pointer font-semibold">
                Export</a>
            <form action="" method="get">
                <label for="search" class="mb-2 text-xs font-medium text-blue-900 sr-only">Search</label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-2 h-2 text-blue-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                        </svg>
                    </div>
                    <input type="search" id="search" name="search" value="<?= $_GET['search'] ?? '' ?>"
                        class="block w-full px-2 py-1 ps-7 text-xs rounded-md text-blue-900 border border-blue-700 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Search Students" />
                    <button type="submit"
                        class="text-white absolute end-0.5 bottom-0.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-md text-xs px-2 py-1">Search</button>
                </div>
            </form>
            <div
                class="px-2 py-1 text-xs rounded-md border border-blue-700 text-blue-500 hover:text-blue-800 cursor-pointer font-semibold">
                Filter</div>
        </div>
    </div>

    <hr class="mb-2">

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Aksi
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Siswa
                    </th>
                    <th scope="col" class="px-6 py-3">
                        SPP
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Jenjang
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Kontak
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Kontak Orang Tua
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Virtual Account
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Pembayaran Terakhir
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php
                
                use App\Helpers\FormatHelper;
                
                $students = $this->getStudents();
                ?>
                <?php if (count($students) > 0) : ?>
                <?php foreach($students as $student) :?>
                <tr class="odd:bg-white even:bg-gray-50 border-b border-gray-200">
                    <td class="px-6 py-4 flex gap-2">
                        <i class="fa-solid fa-pencil cursor-pointer hover:text-blue-300 transition-colors"
                            onclick="editStudent('<?= $student['virtual_account'] ?>')"></i>
                    </td>
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                        <?= $student['name'] ?>
                        <div class="text-xs text-blue-500"><?= $student['nis'] ?></div>
                    </th>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?= FormatHelper::formatRupiah($student['monthly_fee']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?= $student['level'] ?> <?= $student['grade'] ?> <?= $student['section'] ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= isset($student['phone']) ? $student['phone'] : $student['email'] ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= $student['parent_phone'] ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= $student['virtual_account'] ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= $student['latest_payment'] ?? '-' ?>
                    </td>
                </tr>
                <?php endforeach;?>
                <?php else :?>
                <tr>
                    <td class="px-6 py-4 text-center" colspan="9">
                        Data siswa kosong.
                    </td>
                </tr>
                <?php endif;?>
            </tbody>
        </table>
    </div>

    <script>
        const editStudent = (va) => {
            document.getElementById('edit-student').classList.remove('hidden')

        }

        const submitEditStudent = (va) => {
            console.log(va);
        }

        const showEditTab = (id, element) => {
            const tabs = document.querySelectorAll('.tab-content')
            const buttons = document.querySelectorAll('.tab-button')

            tabs.forEach(tab => tab.classList.add('hidden'))
            buttons.forEach(btn => btn.classList.remove('border-blue-600'));
            buttons.forEach(btn => btn.classList.add('border-transparent'));

            document.getElementById(`${id}-tab`).classList.remove('hidden');
            element.classList.remove('border-transparent');
            element.classList.add('border-blue-600');
        }

        const closeModal = (id) => {
            const modal = document.getElementById(id)

            modal.classList.add('hidden')

            const inputs = modal.querySelectorAll('input')
            const selects = modal.querySelectorAll('select')
            const textareas = modal.querySelectorAll('textarea')

            inputs.forEach(input => {
                if (input.type === 'file') {
                    input.value = null
                } else {
                    input.value = ''
                }
            })

            selects.forEach(select => {
                select.selectedIndex = 0
            })

            textareas.forEach(textarea => {
                textarea.value = ''
            })
        }
    </script>

    <script>
        const studentForm = document.getElementById('create-student-form');
        studentForm.addEventListener('submit', handleStudentSubmit);

        async function handleStudentSubmit(event) {
            event.preventDefault(); 

            const form = event.target;
            const messageDiv = document.getElementById('student-form-message');
            const fileInput = document.getElementById('bulk-students');
            const submitButton = form.querySelector('button[type="submit"]');

            messageDiv.textContent = '';
            messageDiv.className = 'mt-3 text-xs';
            submitButton.disabled = true;
            submitButton.textContent = 'Processing...';

            let url;
            let formData;
            let isBulkUpload = false;

            if (fileInput.files && fileInput.files.length > 0) {
                isBulkUpload = true;
                url = '/api/upload-students-bulk'; 
                formData = new FormData();
                formData.append('bulk-students', fileInput.files[0]); 
                console.log('Attempting Bulk Upload...');

            } else {
                isBulkUpload = false;
                url = '/api/upload-student'; 
                formData = new FormData(form);
                console.log('Attempting Single Student Upload...');

                const requiredFields = ['nis', 'name', 'parent_phone', 'level', 'grade', 'section'];
                let missingField = false;
                for (const fieldName of requiredFields) {
                    if (!formData.get(fieldName)) { 
                        const fieldElement = form.querySelector(`[name="${fieldName}"]`);
                        const label = fieldElement.previousElementSibling?.textContent || fieldName;
                        messageDiv.textContent = `Error: Field "${label.replace('*','').trim()}" is required.`;
                        messageDiv.classList.add('text-red-600');
                        missingField = true;
                        break;
                    }
                }
                if (missingField) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Tambah';
                    return;
                }
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData, 
                    // headers: { 'Content-Type': 'application/json' },
                    // body: JSON.stringify(Object.fromEntries(formData.entries()))
                });

                const result = await response.json();

                if (response.ok) {
                    messageDiv.textContent = result.message || (isBulkUpload ? 'Bulk upload successful!' :
                        'Student added successfully!');
                    messageDiv.classList.add('text-green-600');
                    form.reset(); 
                    setTimeout(() => {
                        if (typeof closeModal === 'function') {
                            closeModal('create-student');
                        }
                    }, 1500); 
                } else {
                    messageDiv.textContent =
                        `Error: ${result.message || 'An error occurred.'} (Status: ${response.status})`;
                    messageDiv.classList.add('text-red-600');
                }

            } catch (error) {
                console.error('Submission Error:', error);
                messageDiv.textContent =
                    `Error: ${isBulkUpload ? 'Bulk' : 'Single'} submission failed. Check console for details.`;
                messageDiv.classList.add('text-red-600');
            } finally {
                if (!messageDiv.classList.contains('text-green-600')) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Tambah';
                }
            }
        }
    </script>

    <div id="create-student" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex justify-center items-center">
        <form method="post" id="create-student-form" class="bg-white w-[90%] max-w-md p-4 rounded-lg shadow-lg relative">
            <h3 class="text-sm font-bold mb-2 text-slate-700">Tambah Siswa</h3>

            <div class="border-b border-slate-600 pb-2 mb-4">
                <label for="bulk-students" class="mb-1 block text-xs font-medium text-slate-700">Upload file</label>
                <input name="bulk-students" id="bulk-students" type="file" accept=".xlsx"
                    class="mt-2 block w-full text-xs file:mr-4 file:rounded-md file:border-0 file:bg-blue-500 file:py-1 file:px-2 file:text-xs file:font-medium file:text-white hover:file:bg-blue-700 focus:outline-none disabled:pointer-events-none disabled:opacity-60" />
                <small class="text-slate-400 text-xs italic">Format Excel Tambah Siswa dapat diunduh <a
                        class="text-blue-400 hover:text-blue-500" href="/format?type=student">disini</a></small>
            </div>

            <div class="grid grid-cols-6 gap-2 mb-8">
                <div class="col-span-2">
                    <label for="nis" class="text-xs font-semibold uppercase">nis <span
                            class="text-red-600 font-bold">*</span></label>
                    <input type="text" name="nis" id="nis"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="col-span-4">
                    <label for="name" class="text-xs font-semibold uppercase">nama <span
                            class="text-red-600 font-bold">*</span></label>
                    <input type="text" name="name" id="name"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="col-span-6">
                    <label for="dob" class="text-xs font-semibold uppercase">tanggal lahir</label>
                    <input type="date" name="dob" id="dob"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="col-span-3">
                    <label for="phone_number" class="text-xs font-semibold uppercase">nomor telepon</label>
                    <input type="phone" name="phone_number" id="phone_number"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="col-span-3">
                    <label for="email_address" class="text-xs font-semibold uppercase">alamat email</label>
                    <input type="email" name="email_address" id="email_address"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="col-span-6">
                    <label for="parent_phone" class="text-xs font-semibold uppercase">telepon orang tua <span
                            class="text-red-600 font-bold">*</span></label>
                    <input type="phone" name="parent_phone" id="parent_phone"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="col-span-6">
                    <label for="address" class="text-xs font-semibold uppercase">alamat rumah</label>
                    <textarea name="address" id="address"
                        class="resize-none block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100"></textarea>
                </div>
                <div class="col-span-2">
                    <label for="level" class="text-xs font-semibold uppercase">jenjang <span
                            class="text-red-600 font-bold">*</span></label>
                    <select name="level" id="level"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                        <option selected disabled>Pilih Jenjang</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label for="grade" class="text-xs font-semibold uppercase">tingkat <span
                            class="text-red-600 font-bold">*</span></label>
                    <select name="grade" id="grade"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                        <option selected disabled>Pilih Tingkat</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label for="section" class="text-xs font-semibold uppercase">kelas <span
                            class="text-red-600 font-bold">*</span></label>
                    <select name="section" id="section"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                        <option selected disabled>Pilih Kelas</option>
                    </select>
                </div>
            </div>

            <button type="submit"
                class="mt-4 px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                Tambah
            </button>

            <button onclick="closeModal('create-student')" type="button"
                class="mt-4 px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                Batal
            </button>

        </form>
    </div>

    <div id="edit-student" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex justify-center items-center">
        <form id="create-student-form" class="bg-white w-[90%] max-w-md p-4 rounded-lg shadow-lg relative">
            <div class="flex justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-700">Edit Siswa</h3>
                <div id="tabs" class="flex gap-4">
                    <button type="button"
                        class="cursor-pointer uppercase tab-button px-2 py-1 text-xs font-semibold text-slate-600 border-b-2 border-blue-600"
                        onclick="showEditTab('information', this)">Informasi</button>
                    <button type="button"
                        class="cursor-pointer uppercase tab-button px-2 py-1 text-xs font-semibold text-slate-600 border-b-2 border-transparent"
                        onclick="showEditTab('bill', this)">Kelas dan Tagihan</button>
                </div>
            </div>
            <div id="information-tab" class="tab-content grid grid-cols-6 gap-2 mb-8">
                <div class="col-span-2">
                    <label for="edit-nis" class="text-xs font-semibold uppercase">nis <span
                            class="text-red-600 font-bold">*</span></label>
                    <input type="text" name="edit-nis" id="edit-nis"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="col-span-4">
                    <label for="edit-name" class="text-xs font-semibold uppercase">nama <span
                            class="text-red-600 font-bold">*</span></label>
                    <input type="text" name="edit-name" id="edit-name"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="col-span-6">
                    <label for="edit-dob" class="text-xs font-semibold uppercase">tanggal lahir</label>
                    <input type="date" name="edit-dob" id="edit-dob"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="col-span-3">
                    <label for="edit-phone-number" class="text-xs font-semibold uppercase">nomor telepon</label>
                    <input type="phone" name="edit-phone-number" id="edit-phone-number"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="col-span-3">
                    <label for="edit-email-address" class="text-xs font-semibold uppercase">alamat email</label>
                    <input type="email" name="edit-email-address" id="edit-email-address"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="col-span-6">
                    <label for="edit-parent-phone" class="text-xs font-semibold uppercase">telepon orang tua <span
                            class="text-red-600 font-bold">*</span></label>
                    <input type="phone" name="edit-parent-phone" id="edit-parent-phone"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="col-span-6">
                    <label for="edit-address" class="text-xs font-semibold uppercase">alamat rumah</label>
                    <textarea name="edit-address" id="edit-address"
                        class="resize-none block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100"></textarea>
                </div>
            </div>
            <div id="bill-tab" class="tab-content mb-8 hidden">
                <div class="grid grid-cols-6 gap-2 pb-4 border-b border-slate-500">
                    <div class="col-span-2">
                        <label for="edit-level" class="text-xs font-semibold uppercase">jenjang <span
                                class="text-red-600 font-bold">*</span></label>
                        <select name="edit-level" id="edit-level"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option selected disabled>Pilih Jenjang</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label for="edit-grade" class="text-xs font-semibold uppercase">tingkat <span
                                class="text-red-600 font-bold">*</span></label>
                        <select name="edit-grade" id="edit-grade"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option selected disabled>Pilih Tingkat</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label for="edit-section" class="text-xs font-semibold uppercase">kelas <span
                                class="text-red-600 font-bold">*</span></label>
                        <select name="edit-section" id="edit-section"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option selected disabled>Pilih Kelas</option>
                        </select>
                    </div>
                    <div class="col-span-6">
                        <label for="edit-monthly-fee" class="text-xs font-semibold uppercase">spp bulanan <span
                                class="text-red-600 font-bold">*</span></label>
                        <input type="phone" name="edit-monthly-fee" id="edit-monthly-fee"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                    </div>
                </div>
                <div class="grid grid-cols-6 gap-2 pt-2">
                    <div class="col-span-2">
                        <label for="edit-academic-year" class="text-xs font-semibold uppercase">tahun ajaran</label>
                        <select name="edit-academic-year" id="edit-academic-year"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option selected disabled>Pilih tahun</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label for="edit-semester" class="text-xs font-semibold uppercase">semester</label>
                        <select name="edit-semester" id="edit-semester"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option selected disabled>Pilih Semseter</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label for="edit-month" class="text-xs font-semibold uppercase">bulan</label>
                        <select name="edit-month" id="edit-month"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option selected disabled>Pilih Bulan</option>
                        </select>
                    </div>
                    <div class="col-span-6 flex justify-between">
                        <h4 class="text-xs uppercase">Biaya Tambahan</h4>
                        <button type="button"
                            class="cursor-pointer text-slate-500 hover:text-blue-500 transition-colors">
                            <i class="fa-regular fa-square-plus text-xs"></i>
                        </button>
                    </div>
                </div>

            </div>

            <button onclick="submitEditStudent(this)"
                class="mt-4 px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                Edit
            </button>

            <div onclick="closeModal('edit-student')"
                class="mt-4 inline cursor-pointer px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                Batal
            </div>

        </form>
    </div>

</div>

<script>
    const submitSiswa = (this) => {
        closeModal('create-student')
    }

    const downloadExport = () => {}
</script>
