<div class="w-full">
    <form action="" method="get" class="flex justify-between w-full mb-2">
        <h1 class="text-lg font-semibold text-slate-800">List Siswa</h1>
        <div class="flex gap-2">
            <div onclick="document.getElementById('create-student').classList.remove('hidden')"
                class="px-2 py-1 text-xs rounded-md border border-blue-700 text-blue-500 hover:text-blue-800 cursor-pointer font-semibold">
                Tambah Siswa</div>
            <div onclick="document.getElementById('update-students').classList.remove('hidden')"
                class="px-2 py-1 text-xs rounded-md border border-blue-700 text-blue-500 hover:text-blue-800 cursor-pointer font-semibold">
                Update Siswa</div>
            <a href="/exports/students"
                class="px-2 py-1 text-xs rounded-md border border-blue-700 text-blue-500 hover:text-blue-800 cursor-pointer font-semibold">
                Export</a>
            <div>
                <label for="search" class="mb-2 text-xs font-medium text-blue-900 sr-only">Search</label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-2 h-2 text-blue-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                        </svg>
                    </div>
                    <input type="search" id="search" name="search"
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                        class="block w-full px-2 py-1 ps-7 text-xs rounded-md text-blue-900 border border-blue-700 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Search Students" />
                    <button type="submit"
                        class="text-white absolute end-0.5 bottom-0.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-md text-xs px-2 py-1">Search</button>
                </div>
            </div>
            <div onclick="document.getElementById('filter-student').classList.remove('hidden')"
                class="px-2 py-1 text-xs rounded-md border border-blue-700 text-blue-500 hover:text-blue-800 cursor-pointer font-semibold">
                Filter</div>
            <div id="filter-student" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex justify-center items-center">
                <div class="bg-white w-[90%] max-w-md p-4 rounded-lg shadow-lg relative max-h-[90vh] overflow-y-auto">
                    <h3 class="text-sm font-bold mb-2 text-slate-700">Filter Siswa</h3>
    
                    <div class="mb-2">
                        <label for="level-filter" class="text-xs font-semibold uppercase">jenjang</label>
                        <select name="level-filter" id="level-filter"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option value="" selected disabled>Pilih Jenjang</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="grade-filter" class="text-xs font-semibold uppercase">tingkat</label>
                        <select name="grade-filter" id="grade-filter"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option value="" selected disabled>Pilih Tingkat</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="section-filter" class="text-xs font-semibold uppercase">kelas</label>
                        <select name="section-filter" id="section-filter"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option value="" selected disabled>Pilih Kelas</option>
                        </select>
                    </div>
                    <button type="submit"
                        class="cursor-pointer mt-4 px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                        Filter
                    </button>
    
                    <button onclick="closeModal('filter-student')" type="button"
                        class="cursor-pointer mt-4 px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </form>

    <hr class="mb-2">

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                    <th scope="col" class="px-6 py-3">Siswa</th>
                    <th scope="col" class="px-6 py-3">SPP</th>
                    <th scope="col" class="px-6 py-3">Jenjang</th>
                    <th scope="col" class="px-6 py-3">Kontak</th>
                    <th scope="col" class="px-6 py-3">Kontak Orang Tua</th>
                    <th scope="col" class="px-6 py-3">Virtual Account</th>
                    <th scope="col" class="px-6 py-3">Pembayaran Terakhir</th>
                </tr>
            </thead>
            <tbody>
                <?php
                use App\Helpers\FormatHelper;
                $students = $this->studentBE->getStudents();
                ?>
                <?php if (count($students) > 0) : ?>
                <?php foreach($students as $student) :?>
                <tr class="odd:bg-white even:bg-gray-50 border-b border-gray-200">
                    <td class="px-6 py-4 flex gap-2">
                        <i class="fa-solid fa-pencil cursor-pointer hover:text-blue-300 transition-colors"
                            onclick="editStudent('<?= htmlspecialchars($student['id']) ?>')"></i>
                    </td>
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                        <?= htmlspecialchars($student['name']) ?>
                        <div class="text-xs text-blue-500"><?= htmlspecialchars($student['nis']) ?></div>
                    </th>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?= FormatHelper::formatRupiah($student['monthly_fee']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?= htmlspecialchars($student['level'] ?? '') ?>
                        <?= htmlspecialchars($student['grade'] ?? '') ?>
                        <?= htmlspecialchars($student['section'] ?? '') ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars(isset($student['phone']) && $student['phone'] ? $student['phone'] : $student['email'] ?? '') ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($student['parent_phone'] ?? '') ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($student['virtual_account'] ?? '') ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($student['latest_payment'] ?? '-') ?>
                    </td>
                </tr>
                <?php endforeach;?>
                <?php else :?>
                <tr>
                    <td class="px-6 py-4 text-center" colspan="8">
                        Data siswa kosong.
                    </td>
                </tr>
                <?php endif;?>
            </tbody>
        </table>
    </div>

    <div id="create-student" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex justify-center items-center">
        <form method="post" id="create-student-form"
            class="bg-white w-[90%] max-w-md p-4 rounded-lg shadow-lg relative max-h-[90vh] overflow-y-auto">
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
                    <input type="tel" name="phone" id="phone_number"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="col-span-3">
                    <label for="email_address" class="text-xs font-semibold uppercase">alamat email</label>
                    <input type="email" name="email" id="email_address"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="col-span-6">
                    <label for="parent_phone" class="text-xs font-semibold uppercase">telepon orang tua <span
                            class="text-red-600 font-bold">*</span></label>
                    <input type="tel" name="parent_phone" id="parent_phone"
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
                        <option value="" selected disabled>Pilih Jenjang</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label for="grade" class="text-xs font-semibold uppercase">tingkat <span
                            class="text-red-600 font-bold">*</span></label>
                    <select name="grade" id="grade"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                        <option value="" selected disabled>Pilih Tingkat</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label for="section" class="text-xs font-semibold uppercase">kelas</label>
                    <select name="section" id="section"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                        <option value="" selected>-- Tidak Ada Kelas --</option>
                    </select>
                </div>
            </div>

            <button type="submit"
                class="cursor-pointer mt-4 px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                Tambah
            </button>

            <button onclick="closeModal('create-student')" type="button"
                class="cursor-pointer mt-4 px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                Batal
            </button>
        </form>
    </div>

    <div id="update-students" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex justify-center items-center">
        <form method="post" id="update-student-form"
            class="bg-white w-[90%] max-w-md p-4 rounded-lg shadow-lg relative max-h-[90vh] overflow-y-auto">
            <h3 class="text-sm font-bold mb-2 text-slate-700">Update Siswa</h3>

            <div class="border-b border-slate-600 pb-2 mb-4">
                <label for="bulk-update-students" class="mb-1 block text-xs font-medium text-slate-700">Upload file</label>
                <input name="bulk-update-students" id="bulk-update-students" type="file" accept=".xlsx"
                    class="mt-2 block w-full text-xs file:mr-4 file:rounded-md file:border-0 file:bg-blue-500 file:py-1 file:px-2 file:text-xs file:font-medium file:text-white hover:file:bg-blue-700 focus:outline-none disabled:pointer-events-none disabled:opacity-60" />
                <small class="text-slate-400 text-xs italic">Data siswa saat ini dapat diunduh di <a
                        class="text-blue-400 hover:text-blue-500" href="/exports/students">disini</a></small>
            </div>

            <button type="submit"
                class="cursor-pointer mt-4 px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                Update
            </button>

            <button onclick="closeModal('update-students')" type="button"
                class="cursor-pointer mt-4 px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                Batal
            </button>
        </form>
    </div>

    <div id="edit-student" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex justify-center items-center">
        <form method="POST" id="edit-student-form"
            class="bg-white w-[90%] max-w-md p-4 rounded-lg shadow-lg relative max-h-[90vh] overflow-y-auto">
            <input type="hidden" name="user_id" id="edit-user-id">
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
                    <input type="tel" name="edit-phone-number" id="edit-phone-number"
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
                    <input type="tel" name="edit-parent-phone" id="edit-parent-phone"
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
                            <option value="" selected disabled>Pilih Jenjang</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label for="edit-grade" class="text-xs font-semibold uppercase">tingkat <span
                                class="text-red-600 font-bold">*</span></label>
                        <select name="edit-grade" id="edit-grade"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option value="" selected disabled>Pilih Tingkat</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label for="edit-section" class="text-xs font-semibold uppercase">kelas</label>
                        <select name="edit-section" id="edit-section"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option value="" selected>-- Tidak Ada Kelas --</option>
                        </select>
                    </div>
                    <div class="col-span-6">
                        <label for="edit-monthly-fee" class="text-xs font-semibold uppercase">spp bulanan <span
                                class="text-red-600 font-bold">*</span></label>
                        <input type="number" step="any" name="edit-monthly-fee" id="edit-monthly-fee"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                    </div>
                </div>
                <div class="grid grid-cols-6 gap-2 pt-2">
                    <div class="col-span-2">
                        <label for="edit-academic-year" class="text-xs font-semibold uppercase">tahun ajaran</label>
                        <select name="edit-academic-year" id="edit-academic-year"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option value="" selected disabled>Pilih tahun</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label for="edit-semester" class="text-xs font-semibold uppercase">semester</label>
                        <select name="edit-semester" id="edit-semester"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option value="" selected disabled>Pilih Semester</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label for="edit-month" class="text-xs font-semibold uppercase">bulan</label>
                        <select name="edit-month" id="edit-month"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option value="" selected disabled>Pilih Bulan</option>
                        </select>
                    </div>
                    <div class="col-span-6 flex justify-between mt-2 items-center">
                        <h4 class="text-xs uppercase font-semibold text-slate-600">Biaya Tambahan</h4>
                        <button type="button" title="Tambah Biaya Baru" onclick="addNewFeeRow()"
                            class="cursor-pointer text-blue-500 hover:text-blue-700 transition-colors">
                            <i class="fa-solid fa-plus text-sm"></i>
                        </button>
                    </div>
                    <div id="additional-fees-container"
                        class="col-span-6 mt-1 space-y-1 border-t border-slate-200 pt-2">
                        <p class="text-xs text-slate-500 italic">Pilih Tahun Ajaran, Semester, dan Bulan untuk
                            melihat/mengedit biaya tambahan.</p>
                    </div>
                </div>
            </div>

            <button onclick="submitEditStudent(this)" type="button"
                class="cursor-pointer mt-4 px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                Edit
            </button>

            <button onclick="closeModal('edit-student')" type="button"
                class="cursor-pointer mt-4 px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                Batal
            </button>
        </form>
    </div>
</div>

<script src="/js/pages/students.js"></script>
