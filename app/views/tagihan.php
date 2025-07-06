<script src="/js/components/editFeeModal.js"></script>

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
                    <h4 class="text-xs uppercase">bill actions</h4>
                    <div class="flex-1">
                        <hr class="text-white">
                    </div>
                </div>
                <a href="/exports/bills" :class="{ 'sidebar-item-icon-only': !sidebarOpen && !isMobile }"
                    class="flex gap-2 items-center cursor-pointer hover:text-slate-200 transition-colors py-1"
                    :title="(sidebarOpen || isMobile) ? '' : 'Export Tagihan'"><i
                        class="ti ti-file-export text-lg"></i><span x-show="sidebarOpen || isMobile"
                        :class="{ 'hover:translate-x-2': sidebarOpen && !isMobile }"
                        class="transition-transform duration-150">Export Tagihan</span></a>
                <div id="create-bill-btn" :class="{ 'sidebar-item-icon-only': !sidebarOpen && !isMobile }"
                    class="flex gap-2 items-center cursor-pointer hover:text-slate-200 transition-colors py-1"
                    :title="(sidebarOpen || isMobile) ? '' : 'Buat Tagihan Bulanan'">
                    <i class="ti ti-file-invoice text-lg"></i>
                    <span x-show="sidebarOpen || isMobile" :class="{ 'hover:translate-x-2': sidebarOpen && !isMobile }"
                        class="transition-transform duration-150">Buat Tagihan Tahunan</span>
                </div>
                <div id="check-bill-btn"
                    @click="document.getElementById('import-bill-modal').classList.remove('hidden')"
                    :class="{ 'sidebar-item-icon-only': !sidebarOpen && !isMobile }"
                    class="flex gap-2 items-center cursor-pointer hover:text-slate-200 transition-colors py-1"
                    :title="(sidebarOpen || isMobile) ? '' : 'Cek Tagihan Bulan'"><i
                        class="ti ti-report text-lg"></i><span x-show="sidebarOpen || isMobile"
                        :class="{ 'hover:translate-x-2': sidebarOpen && !isMobile }"
                        class="transition-transform duration-150">Cek Tagihan Bulan</span></div>
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
                <section id="bills">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-xl text-slate-700">Bill Management</h3>
                        <div class="flex items-center gap-2">
                            <button @click="document.getElementById('filter-bill-modal').classList.remove('hidden')"
                                title="Filter Tagihan"
                                class="text-slate-500 hover:text-sky-600 transition-colors p-1.5 rounded-md hover:bg-sky-100">
                                <i class="ti ti-filter text-xl"></i>
                            </button>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg mt-4 relative overflow-x-auto shadow">
                        <?php
                            use App\Helpers\Call;
                            use App\Helpers\FormatHelper;
                            $billRecaps = $app->BillBE()->getBills() ?? ['data' => [], 'months' => []];
                        ?>
                        <table id="bill-table" class="w-full text-sm text-left text-gray-700">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3 sticky left-0 bg-gray-100 z-10">Nama Siswa</th>
                                    <th class="px-4 py-3 sticky left-0 bg-gray-100 z-10">Midtrans VA</th>
                                    <th class="px-4 py-3">Kelas</th>
                                    <th class="px-4 py-3 text-green-700">Tagihan</th>
                                    <th class="px-4 py-3 text-red-600">Denda</th>
                                    <?php for ($month = 1; $month <= 12; $month++): ?>
                                    <?php if (count($billRecaps) > 0) : ?>
                                    <th
                                        class="px-4 py-3 text-center  <?= $billRecaps[0]['bulan'] ?? false == $month ? 'bg-sky-100' : '' ?>">
                                        <?= htmlspecialchars(sprintf('%02d', $month)) ?></th>
                                    <?php else: ?>
                                    <th class="px-4 py-3 text-center"><?= htmlspecialchars(sprintf('%02d', $month)) ?>
                                    </th>
                                    <?php endif; ?>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>

                                <?php if (count($billRecaps['data']) > 0) : ?>
                                    <?php foreach($billRecaps['data'] as $recap) :?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <th scope="row"
                                            class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap sticky left-0 bg-white z-10">
                                            <?= htmlspecialchars($recap['nama'] ?? '') ?>
                                            <div class="text-xs text-blue-500 font-normal">
                                                <?= htmlspecialchars($recap['virtual_account'] ?? '') ?>
                                            </div>
                                        </th>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <?= htmlspecialchars($recap['va_midtrans'] ?? '-') ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <?= htmlspecialchars($recap['jenjang'] ?? '') ?>
                                            <?= htmlspecialchars($recap['tingkat'] ?? '') ?>
                                            <?= htmlspecialchars($recap['kelas'] ?? '') ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap font-medium text-green-700">
                                            <?= FormatHelper::formatRupiah($recap['total_nominal'] + $recap['denda'] ?? 0) ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap font-semibold text-red-600">
                                            <?= FormatHelper::formatRupiah($recap['denda'] ?? 0) ?>
                                        </td>
                                        <!-- Kolom data bulan dinamis -->
                                        <?php for ($month = 1; $month <= 12; $month++): ?>
                                        <?php
                                        $bill = $app->BillBE()->getMonthBill($month, $billRecaps['year'], $recap['id']);
                                        $bgColor = '';
                                        $textColor = '';
                                        $isSameYear = Call::year() == $billRecaps['year'];

                                        if ($recap['bulan'] == $month && $isSameYear) {
                                            $bgColor = 'bg-sky-100';
                                            if ($recap['status'] === 'lunas') {
                                                $textColor = 'text-green-600';
                                                $bgColor = 'bg-green-100';
                                            } else {
                                                $textColor = 'text-blue-600';
                                            }
                                        } elseif ($recap['bulan'] > $month && $isSameYear) {
                                            $colors = $app->BillBE()->checkSingularBillStatus($recap['id'], $month, $billRecaps['year']);
                                            $bgColor = $colors['bg'];
                                            $textColor = $colors['text'];
                                        } else {
                                            $textColor = 'text-slate-300';
                                        }
                                        ?>
                                        <td
                                        onclick="pages.tagihan.openBillDetails('<?= htmlspecialchars($recap['id']) ?>', '<?= htmlspecialchars($month) ?>', '<?= htmlspecialchars($recap['tahun']) ?>')"
                                        class="px-4 py-2 text-center whitespace-nowrap <?= $bgColor ?> cursor-pointer hover:bg-blue-100 transition-colors">
                                            <span class="whitespace-nowrap <?= $textColor ?>"> <?= FormatHelper::formatRupiah($bill['sum']) ?></span>
                                        </td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php endforeach;?>
                                <?php else:?>
                                <tr>
                                    <td colspan="16" class="px-4 py-2 text-center text-gray-500">
                                        Tidak ada data tagihan ditemukan.</td>
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

<?php include_once __DIR__ . '/modals/fee-edit.php'; ?>
<?php include_once __DIR__ . '/modals/fee-filter.php'; ?>

<script src="/js/flowbite.min.js"></script>
<script src="/js/datatables.js"></script>
<script src="/js/pages/tagihan.js"></script>

<script type="module">
    const DataTable = window.simpleDatatables.DataTable;
    const billTableElement = document.getElementById("bill-table");
    if (billTableElement) {
        new DataTable(billTableElement, {
            paging: true,
            perPage: 10,
            perPageSelect: [5, 10, 15, 20, 25, 50, 1000],
            searchable: true,
            sortable: true,
            filter: true,
            labels: {
                placeholder: "Cari Tagihan...",
                perPage: "pembayaran per halaman",
                noRows: "Tidak ada data ditemukan",
                info: "Menampilkan {start} sampai {end} dari {rows} pembayran"
            }
        });
    }
</script>
