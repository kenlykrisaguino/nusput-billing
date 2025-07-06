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
                    <h4 class="text-xs uppercase">journal actions</h4>
                    <div class="flex-1">
                        <hr class="text-white">
                    </div>
                </div>
                <a href="/exports/journals"
                    :class="{ 'sidebar-item-icon-only': !sidebarOpen && !isMobile }"
                    class="flex block gap-2 items-center cursor-pointer hover:text-slate-200 transition-colors py-1"
                    :title="(sidebarOpen || isMobile) ? '' : 'Notify Open Bills'"><i
                        class="ti ti-file-export text-lg"></i><span x-show="sidebarOpen || isMobile"
                        :class="{ 'hover:translate-x-2': sidebarOpen && !isMobile }"
                        class="transition-transform duration-150">Export Jurnal</span></a>
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
                <section id="journal">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-xl text-slate-700">Penjurnalan</h3>
                        <div class="flex items-center gap-2">
                            <button @click="document.getElementById('filter-journal-modal').classList.remove('hidden')"
                                title="Filter Penjurnalan"
                                class="text-slate-500 hover:text-sky-600 transition-colors p-1.5 rounded-md hover:bg-sky-100">
                                <i class="ti ti-filter text-xl"></i>
                            </button>
                            <a href="/exports/journals"
                                class="px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 transition-colors text-sm font-medium flex items-center gap-2">
                                <i class="ti ti-file-export"></i>
                                Export Jurnal
                            </a>
                        </div>
                    </div>
                    <?php use App\Helpers\FormatHelper;
                        $data = ($app->JournalBE()->getJournals() ?? []); 
                    
                    ?>
                    <div class="bg-white p-4 rounded-lg mt-4 relative overflow-x-auto shadow">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div class="bg-sky-50 shadow-lg rounded-lg p-4 w-full space-y-4 border border-sky-100">
                                <div class="flex items-center gap-2 text-sky-700">
                                    <i class="ti ti-file-invoice text-lg"></i>
                                    <p class="text-sm font-semibold">Penerbitan Uang Sekolah</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-white rounded-md p-3 shadow-sm border-l-4 border-sky-500">
                                        <div class="text-xs font-medium text-sky-800">Piutang - Uang Sekolah (Debit)
                                        </div>
                                        <div class="text-md font-semibold text-gray-800 penerbitan-uang-sekolah"><?= FormatHelper::formatRupiah($data['piutang'])?></div>
                                    </div>

                                    <div class="bg-white rounded-md p-3 shadow-sm border-r-4 border-sky-500 text-right">
                                        <div class="text-xs font-medium text-sky-800">Penerimaan - Uang Sekolah (Kredit)
                                        </div>
                                        <div class="text-md font-semibold text-gray-800 penerbitan-uang-sekolah"><?= FormatHelper::formatRupiah($data['piutang'])?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-amber-50 shadow-lg rounded-lg p-4 w-full space-y-4 border border-amber-100">
                                <div class="flex items-center gap-2 text-amber-700">
                                    <i class="ti ti-circle-check text-lg"></i>
                                    <p class="text-sm font-semibold">Pelunasan Uang Sekolah</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-white rounded-md p-3 shadow-sm border-l-4 border-amber-500">
                                        <div class="text-xs font-medium text-amber-800">Bank BNI (Debit)
                                        </div>
                                        <div class="text-md font-semibold text-gray-800 pelunasan-uang-sekolah"><?= FormatHelper::formatRupiah($data['pelunasan'])?></div>
                                    </div>

                                    <div class="bg-white rounded-md p-3 shadow-sm border-r-4 border-amber-500 text-right">
                                        <div class="text-xs font-medium text-amber-800">Piutang - Uang Sekolah (Kredit)
                                        </div>
                                        <div class="text-md font-semibold text-gray-800 pelunasan-uang-sekolah"><?= FormatHelper::formatRupiah($data['pelunasan'])?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-red-50 shadow-lg rounded-xl p-4 w-full space-y-4 border border-red-100">
                                <div class="flex items-center gap-2 text-red-700">
                                    <i class="ti ti-file-alert text-xl"></i>
                                    <p class="text-sm font-semibold">Penerbitan Denda</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-white rounded-md p-3 shadow-sm border-l-4 border-red-500">
                                        <div class="text-xs font-medium text-red-800">Piutang - Uang Denda (Debit)
                                        </div>
                                        <div class="text-md font-semibold text-gray-800 penerbitan-denda"><?= FormatHelper::formatRupiah($data['hutang'])?></div>
                                    </div>

                                    <div class="bg-white rounded-md p-3 shadow-sm border-r-4 border-red-500 text-right">
                                        <div class="text-xs font-medium text-red-800">Penerimaan - Uang Denda (Kredit)
                                        </div>
                                        <div class="text-md font-semibold text-gray-800 penerbitan-denda"><?= FormatHelper::formatRupiah($data['hutang'])?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-emerald-50 shadow-lg rounded-xl p-4 w-full space-y-4 border border-emerald-100">
                                <div class="flex items-center gap-2 text-emerald-700">
                                    <i class="ti ti-file-alert text-xl"></i>
                                    <p class="text-sm font-semibold">Denda Lunas</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-white rounded-md p-3 shadow-sm border-l-4 border-emerald-500">
                                        <div class="text-xs font-medium text-emerald-800">Bank BNI (Debit)
                                        </div>
                                        <div class="text-md font-semibold text-gray-800 denda-lunas"><?= FormatHelper::formatRupiah($data['hutang_terbayar'])?></div>
                                    </div>

                                    <div class="bg-white rounded-md p-3 shadow-sm border-r-4 border-emerald-500 text-right">
                                        <div class="text-xs font-medium text-emerald-800">Piutang - Denda (Kredit)
                                        </div>
                                        <div class="text-md font-semibold text-gray-800 denda-lunas"><?= FormatHelper::formatRupiah($data['hutang_terbayar'])?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <section id="recap">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-xl text-slate-700">Rekap Keuangan</h3>
                        <div class="flex items-center gap-2">
                            <button @click="document.getElementById('filter-journal-modal').classList.remove('hidden')"
                                title="Filter Rekap"
                                class="text-slate-500 hover:text-sky-600 transition-colors p-1.5 rounded-md hover:bg-sky-100">
                                <i class="ti ti-filter text-xl"></i>
                            </button>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg mt-4 relative overflow-x-auto shadow">
                        <table id="recap-table" class="w-full text-sm text-left rtl:text-right text-gray-700">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Class</th>
                                    <th class="px-4 py-3 text-green-500">Penerimaan</th>
                                    <th class="px-4 py-3 text-red-500">Denda</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $payments = $data['payments'] ?? ($app->RecapBE()->getRecaps() ?? []); ?>
                                <?php if (count($payments) > 0) : ?>
                                <?php foreach($payments as $payment) :?>
                                <tr
                                    class="odd:bg-white even:bg-gray-50 border-b dark:border-gray-600 hover:bg-gray-100">
                                    <th scope="row" class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">
                                        <?= htmlspecialchars($payment['nama'] ?? '') ?><div
                                            class="text-xs text-slate-500 font-normal">
                                            <?= htmlspecialchars($payment['va'] ?? '') ?></div>
                                    </th>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <?= htmlspecialchars($payment['jenjang'] ?? '') ?>
                                        <?= htmlspecialchars($payment['tingkat'] ?? '') ?>
                                        <?= htmlspecialchars($payment['kelas'] ?? '') ?></td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <?= FormatHelper::formatRupiah($payment['penerimaan'] ?? 0) ?></td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <?= FormatHelper::formatRupiah($payment['denda'] ?? 0) ?> </td>
                                </tr>
                                <?php endforeach;?>
                                <?php else :?>
                                <tr>
                                    <td colspan="5" class="px-4 py-2 text-center text-gray-500" colspan="6">Tidak
                                        ada data ditemukan</td>
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

<?php include_once __DIR__ . '/modals/laporan-filter.php'; ?>

<script src="/js/pages/penjurnalan.js"></script>
<script src="/js/pages/rekap.js"></script>

<script src="/js/flowbite.min.js"></script>
<script src="/js/datatables.js"></script>

<script type="module">
    const DataTable = window.simpleDatatables.DataTable;
    const recapTableElement = document.getElementById("recap-table");
    if (recapTableElement) {
        new DataTable(recapTableElement, {
            paging: true,
            perPage: 10,
            perPageSelect: [5, 10, 15, 20, 25, 50],
            searchable: true,
            sortable: true,
            filter: true,
            labels: {
                placeholder: "Cari siswa...",
                perPage: "rekap per halaman",
                noRows: "Tidak ada data ditemukan",
                info: "Menampilkan {start} sampai {end} dari {rows} pembayran"
            }
        });
    }
</script>
