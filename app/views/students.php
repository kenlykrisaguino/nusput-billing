<main x-data="{
    isMobile: window.innerWidth < 768,
    sidebarOpen: true,
    init() {
        this.sidebarOpen = !this.isMobile;
        window.addEventListener('resize', () => {
            const stillMobile = window.innerWidth < 768;
            if (this.isMobile && !stillMobile) {
                this.sidebarOpen = true;
            } else if (!this.isMobile && stillMobile && this.sidebarOpen) {
                this.sidebarOpen = false;
            }
            this.isMobile = stillMobile;
        });
    }
}"
    @toggle-sidebar.window="if(isMobile) sidebarOpen = !sidebarOpen; else sidebarOpen = !sidebarOpen"
    class="flex flex-grow overflow-hidden h-full">

    <div x-show="sidebarOpen && isMobile" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-30 md:hidden"
        x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    </div>

    <div x-show="sidebarOpen || !isMobile"
        :class="{
            '-translate-x-full': !sidebarOpen && isMobile,
            'translate-x-0 h-full': sidebarOpen && isMobile,
            'lg:w-1/6 md:w-2/6 sm:w-1/2': sidebarOpen && !isMobile,
            'md:w-20': !sidebarOpen && !isMobile,
            'w-3/4 sm:w-2/3': isMobile && sidebarOpen,
            'w-0 p-0': !sidebarOpen && isMobile
        }"
        class="bg-sky-600 z-40 transition-all duration-300 ease-in-out flex flex-col
               fixed md:relative top-0 left-0
               md:flex-shrink-0"
        :style="(!sidebarOpen && isMobile) ? 'padding: 0;' : 'padding: 1rem;'">

        <button x-show="sidebarOpen || !isMobile" @click="sidebarOpen = !sidebarOpen"
            :class="{
                'absolute top-3 right-3': sidebarOpen,
                'self-center mb-3': !sidebarOpen && !isMobile,
                'hidden': !sidebarOpen && isMobile
            }"
            class="bg-sky-700 hover:bg-sky-800 text-white rounded-full w-8 h-8 flex items-center justify-center focus:outline-none shadow-md cursor-pointer">
            <i x-show="sidebarOpen" class="ti ti-chevron-left text-base"></i>
            <i x-show="!sidebarOpen && !isMobile" class="ti ti-chevron-right text-base"></i>
        </button>

        <div x-show="sidebarOpen || (!sidebarOpen && !isMobile)" class="overflow-y-auto flex-grow pt-10 md:pt-0">
            <div x-show="sidebarOpen" class="flex items-center mb-6 mt-1"
                :class="{ 'mr-6': sidebarOpen && !isMobile, 'mr-10': sidebarOpen && isMobile }">
                <h3 class="text-white font-semibold transition-opacity duration-200">Quick Access</h3>
            </div>
            <div :class="sidebarOpen ? '' : 'mt-2'" class="flex flex-col gap-2 text-slate-50">
                <div x-show="sidebarOpen" class="flex gap-2 items-center mb-2">
                    <h4 class="text-xs uppercase">student actions</h4>
                    <div class="flex-1">
                        <hr class="text-white">
                    </div>
                </div>
                <div x-show="!sidebarOpen && !isMobile" class="flex justify-center mb-2"><i
                        class="ti ti-users text-white text-xl"></i></div>
                <div @click="document.getElementById('create-student-modal').classList.remove('hidden')"
                    :class="{ 'sidebar-item-icon-only': !sidebarOpen && !isMobile }"
                    class="flex gap-2 items-center cursor-pointer hover:text-slate-200 transition-colors py-1"
                    :title="(sidebarOpen || isMobile) ? '' : 'Tambah Siswa'"><i
                        class="ti ti-user-plus text-lg"></i><span x-show="sidebarOpen || isMobile"
                        :class="{ 'hover:translate-x-2': sidebarOpen && !isMobile }"
                        class="transition-transform duration-150">Tambah Siswa</span></div>
                <div @click="document.getElementById('update-students-modal').classList.remove('hidden')"
                    :class="{ 'sidebar-item-icon-only': !sidebarOpen && !isMobile }"
                    class="flex gap-2 items-center cursor-pointer hover:text-slate-200 transition-colors py-1"
                    :title="(sidebarOpen || isMobile) ? '' : 'Update Semua Siswa'"><i
                        class="ti ti-refresh-dot text-lg"></i><span x-show="sidebarOpen || isMobile"
                        :class="{ 'hover:translate-x-2': sidebarOpen && !isMobile }"
                        class="transition-transform duration-150">Update Semua Siswa</span></div>
                <a href="/exports/students" :class="{ 'sidebar-item-icon-only': !sidebarOpen && !isMobile }"
                    class="flex gap-2 items-center cursor-pointer hover:text-slate-200 transition-colors py-1"
                    :title="(sidebarOpen || isMobile) ? '' : 'Export Siswa'"><i
                        class="ti ti-file-export text-lg"></i><span x-show="sidebarOpen || isMobile"
                        :class="{ 'hover:translate-x-2': sidebarOpen && !isMobile }"
                        class="transition-transform duration-150">Export Siswa</span></a>
                <div x-show="sidebarOpen" class="flex gap-2 items-center mb-2 mt-4">
                    <h4 class="text-xs uppercase">class actions</h4>
                    <div class="flex-1">
                        <hr class="text-white">
                    </div>
                </div>
                <div x-show="!sidebarOpen && !isMobile" class="flex justify-center mb-2 mt-4"><i
                        class="ti ti-school text-white text-xl"></i></div>
                <div @click="$dispatch('open-create-level-modal')"
                    :class="{ 'sidebar-item-icon-only': !sidebarOpen && !isMobile }"
                    class="flex gap-2 items-center cursor-pointer hover:text-slate-200 transition-colors py-1"
                    :title="(sidebarOpen || isMobile) ? '' : 'Tambah Jenjang'"><i
                        class="ti ti-stairs-up text-lg"></i><span x-show="sidebarOpen || isMobile"
                        :class="{ 'hover:translate-x-2': sidebarOpen && !isMobile }"
                        class="transition-transform duration-150">Tambah Jenjang</span></div>
                <div @click="$dispatch('open-create-grade-modal')"
                    :class="{ 'sidebar-item-icon-only': !sidebarOpen && !isMobile }"
                    class="flex gap-2 items-center cursor-pointer hover:text-slate-200 transition-colors py-1"
                    :title="(sidebarOpen || isMobile) ? '' : 'Tambah Tingkat'"><i
                        class="ti ti-layers-subtract text-lg"></i><span x-show="sidebarOpen || isMobile"
                        :class="{ 'hover:translate-x-2': sidebarOpen && !isMobile }"
                        class="transition-transform duration-150">Tambah Tingkat</span></div>
                <div @click="$dispatch('open-create-class-modal')"
                    :class="{ 'sidebar-item-icon-only': !sidebarOpen && !isMobile }"
                    class="flex gap-2 items-center cursor-pointer hover:text-slate-200 transition-colors py-1"
                    :title="(sidebarOpen || isMobile) ? '' : 'Tambah Kelas'"><i
                        class="ti ti-chalkboard text-lg"></i><span x-show="sidebarOpen || isMobile"
                        :class="{ 'hover:translate-x-2': sidebarOpen && !isMobile }"
                        class="transition-transform duration-150">Tambah Kelas</span></div>
            </div>
        </div>
    </div>

    <div class="flex-grow bg-slate-200 overflow-y-auto main-content-shifted"
        :class="{
            'md:pl-4': sidebarOpen && !isMobile,
            'md:pl-8': !sidebarOpen && !isMobile,
            'pl-0': isMobile
        }">
        <div class="px-6 md:px-10 py-6 flex-1">
            <div class="w-full flex gap-4 flex-col">
                <section id="students">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-xl text-slate-700">Student Management</h3>
                        <div class="flex items-center gap-2">
                            <button @click="document.getElementById('filter-student-modal').classList.remove('hidden')"
                                title="Filter Siswa"
                                class="text-slate-500 hover:text-sky-600 transition-colors p-1.5 rounded-md hover:bg-sky-100">
                                <i class="ti ti-filter text-xl"></i>
                            </button>
                            <button @click="document.getElementById('create-student-modal').classList.remove('hidden')"
                                class="px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 transition-colors text-sm font-medium flex items-center gap-2">
                                <i class="ti ti-user-plus"></i>
                                Tambah Siswa
                            </button>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg mt-4 relative overflow-x-auto shadow">
                        <table id="student-table" class="w-full text-sm text-left rtl:text-right text-gray-700">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3">Actions</th>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Class</th>
                                    <th class="px-4 py-3">Virtual Account</th>
                                    <th class="px-4 py-3">Monthly Bills</th>
                                    <th class="px-4 py-3">Last Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php use App\Helpers\FormatHelper;
                                $students = $data['students'] ?? ($app->StudentBE()->getStudents() ?? []); ?>
                                <?php if (count($students) > 0) : ?><?php foreach($students as $student) :?>
                                <tr
                                    class="odd:bg-white even:bg-gray-50 border-b dark:border-gray-600 hover:bg-gray-100">
                                    <td class="px-4 py-2 flex gap-2 items-center">
                                        <button
                                            onclick="pages.students.openEditStudentModal('<?= htmlspecialchars($student['id'] ?? '') ?>')"
                                            title="Edit Siswa" class="text-sky-600 hover:text-sky-800"><i
                                                class="ti ti-pencil"></i></button>
                                        <button
                                            onclick="pages.students.handleDeleteStudent('<?= htmlspecialchars($student['id'] ?? '') ?>', '<?= htmlspecialchars(addslashes($student['nama'] ?? '')) ?>')"
                                            title="Hapus Siswa" class="text-red-500 hover:text-red-700"><i
                                                class="ti ti-trash"></i></button>
                                    </td>
                                    <th scope="row" class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">
                                        <?= htmlspecialchars($student['nama'] ?? '') ?><div
                                            class="text-xs text-slate-500 font-normal">
                                            <?= htmlspecialchars($student['nis'] ?? '') ?></div>
                                    </th>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <?= htmlspecialchars($student['jenjang'] ?? '') ?>
                                        <?= htmlspecialchars($student['tingkat'] ?? '') ?>
                                        <?= htmlspecialchars($student['kelas'] ?? '') ?></td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <?= htmlspecialchars($student['va'] ?? '') ?></td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <?= FormatHelper::formatRupiah($student['spp'] ?? 0) ?></td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <?= htmlspecialchars($student['latest_payment'] ?? '-') ?></td>
                                </tr><?php endforeach;?><?php else :?><tr>
                                    <td class="px-4 py-2 text-center text-gray-500" colspan="6">Belum ada data
                                        siswa.</td>
                                </tr><?php endif;?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section id="classes" class="mt-8">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-xl text-slate-700">Tariff Management</h3>
                        <div class="flex items-center gap-2">
                            <button @click="$dispatch('open-create-tariff-modal')"
                                class="px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 transition-colors text-sm font-medium flex items-center gap-2">
                                <i class="ti ti-receipt-2"></i>
                                Tambah Tarif
                            </button>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg mt-4 relative overflow-x-auto shadow">
                        <table id="class-table" class="w-full text-sm text-left rtl:text-right text-gray-700">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3">Actions</th>
                                    <th class="px-4 py-3">Jenjang</th>
                                    <th class="px-4 py-3">Tingkat</th>
                                    <th class="px-4 py-3">Kelas</th>
                                    <th class="px-4 py-3">Nominal Tarif SPP</th>
                                    <th class="px-4 py-3">Digunakan oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $tariffs = $app->ClassBE()->getTariffList() ?? []; ?>
                                <?php if (count($tariffs) > 0) : ?><?php foreach($tariffs as $tariff) :?>
                                <tr
                                    class="odd:bg-white even:bg-gray-50 border-b dark:border-gray-600 hover:bg-gray-100">
                                    <td class="px-4 py-2 flex gap-2 items-center">
                                        <button type="button"
                                            x-on:click="$dispatch('open-edit-tariff-modal', { tariffId: '<?= htmlspecialchars($class['id'] ?? '') ?>' })"
                                            class="text-sky-600 hover:text-sky-800 cursor-pointer edit-tariff-btn"
                                            title="Edit Tarif">
                                            <i class="ti ti-pencil"></i>
                                        </button>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <?= htmlspecialchars($tariff['jenjang'] ?? '-') ?></th>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <?= htmlspecialchars($tariff['tingkat'] ?? '-') ?></td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <?= htmlspecialchars($tariff['kelas'] ?? 'Semua Kelas') ?></td>
                                    <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">
                                        <?= \App\Helpers\FormatHelper::formatRupiah($tariff['nominal'] ?? 0) ?>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <?= htmlspecialchars($tariff['jumlah_siswa'] ?? 0) ?> siswa</td>
                                </tr>
                                <?php endforeach;?><?php else :?>
                                <tr>
                                    <td class="px-4 py-2 text-center text-gray-500" colspan="6">Belum ada data
                                        tarif.</td>
                                </tr>
                                <?php endif;?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/modals/student-create.php'; ?>
<?php include_once __DIR__ . '/modals/student-update-bulk.php'; ?>
<?php include_once __DIR__ . '/modals/student-filter.php'; ?>
<?php include_once __DIR__ . '/modals/student-edit.php'; ?>
<?php include_once __DIR__ . '/modals/level-create.php'; ?>
<?php include_once __DIR__ . '/modals/grade-create.php'; ?>
<?php include_once __DIR__ . '/modals/class-create.php'; ?>
<?php include_once __DIR__ . '/modals/tariff-create.php'; ?>

<div id="filter-class-modal"
    class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-[100]">
    <div
        class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Filter Kelas</h3><button
                onclick="document.getElementById('filter-class-modal').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600"><i class="ti ti-x text-xl"></i></button>
        </div>
        <p>Formulir filter kelas...</p>
        <div class="flex justify-end mt-6"><button
                onclick="document.getElementById('filter-class-modal').classList.add('hidden')"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 mr-2">Tutup</button><button
                class="px-4 py-2 bg-sky-600 text-white rounded hover:bg-sky-700">Terapkan Filter</button></div>
    </div>
</div>

<script src="/js/flowbite.min.js"></script>
<script src="/js/datatables.js"></script>
<script src="/js/pages/students.js"></script>
<script type="module">
    const DataTable = window.simpleDatatables.DataTable;
    const studentTableElement = document.getElementById("student-table");
    if (studentTableElement) {
        new DataTable(studentTableElement, {
            paging: true,
            perPage: 10,
            perPageSelect: [5, 10, 15, 20, 25, 50],
            searchable: true,
            sortable: true,
            filter: true,
            labels: {
                placeholder: "Cari siswa...",
                perPage: "{select} siswa per halaman",
                noRows: "Tidak ada data siswa ditemukan",
                info: "Menampilkan {start} sampai {end} dari {rows} siswa"
            }
        });
    }
    const classTableElement = document.getElementById("class-table");
    if (classTableElement) {
        new DataTable(classTableElement, {
            paging: true,
            perPage: 5,
            perPageSelect: [5, 10, 15, 20, 25],
            searchable: true,
            sortable: true,
            filter: true,
            labels: {
                placeholder: "Cari kelas...",
                perPage: "{select} kelas per halaman",
                noRows: "Tidak ada data kelas ditemukan",
                info: "Menampilkan {start} sampai {end} dari {rows} kelas"
            }
        });
    }
</script>
