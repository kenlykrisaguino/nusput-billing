<?php
$host = $_SERVER['HTTP_HOST'];
$url = "http://$host/invoice/"
?>

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
                    <h4 class="text-xs uppercase">payment actions</h4>
                    <div class="flex-1">
                        <hr class="text-white">
                    </div>
                </div>
                <div @click="document.getElementById('import-payment-modal').classList.remove('hidden')"
                    :class="{ 'sidebar-item-icon-only': !sidebarOpen && !isMobile }"
                    class="flex gap-2 items-center cursor-pointer hover:text-slate-200 transition-colors py-1"
                    :title="(sidebarOpen || isMobile) ? '' : 'Input Pembayaran'"><i
                        class="ti ti-receipt-dollar text-lg"></i><span x-show="sidebarOpen || isMobile"
                        :class="{ 'hover:translate-x-2': sidebarOpen && !isMobile }"
                        class="transition-transform duration-150">Input Pembayaran</span></div>
                <div x-show="sidebarOpen" class="flex gap-2 items-center mb-2 mt-4">
                    <h4 class="text-xs uppercase">notification actions</h4>
                    <div class="flex-1">
                        <hr class="text-white">
                    </div>
                </div>
                <div onclick="pages.pembayaran.notifyOpenBills()"
                    :class="{ 'sidebar-item-icon-only': !sidebarOpen && !isMobile }"
                    class="flex gap-2 items-center cursor-pointer hover:text-slate-200 transition-colors py-1"
                    :title="(sidebarOpen || isMobile) ? '' : 'Notify Open Bills'"><i
                        class="ti ti-bell text-lg"></i><span x-show="sidebarOpen || isMobile"
                        :class="{ 'hover:translate-x-2': sidebarOpen && !isMobile }"
                        class="transition-transform duration-150">Notify Open Bills</span></div>
                <div onclick="pages.pembayaran.notifyCloseBills()"
                    :class="{ 'sidebar-item-icon-only': !sidebarOpen && !isMobile }"
                    class="flex gap-2 items-center cursor-pointer hover:text-slate-200 transition-colors py-1"
                    :title="(sidebarOpen || isMobile) ? '' : 'Notify Close Bills'"><i
                        class="ti ti-bell-exclamation text-lg"></i><span x-show="sidebarOpen || isMobile"
                        :class="{ 'hover:translate-x-2': sidebarOpen && !isMobile }"
                        class="transition-transform duration-150">Notify Close Bills</span></div>
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
                <section id="payments">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-xl text-slate-700">Payment Management</h3>
                        <div class="flex items-center gap-2">
                            <button @click="document.getElementById('import-payment-modal').classList.remove('hidden')"
                                class="px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 transition-colors text-sm font-medium flex items-center gap-2">
                                <i class="ti ti-receipt-dollar"></i>
                                Input Pembayaran
                            </button>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg mt-4 relative overflow-x-auto shadow">
                        <table id="payment-table" class="w-full text-sm text-left rtl:text-right text-gray-700">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3">Actions</th>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Class</th>
                                    <th class="px-4 py-3">Amount</th>
                                    <th class="px-4 py-3">Transaction Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php use App\Helpers\FormatHelper;
                                $payments = $data['payments'] ?? ($app->PaymentBE()->getPayments() ?? []); ?>
                                <?php if (count($payments) > 0) : ?>
                                <?php foreach($payments as $payment) :?>
                                <tr
                                    class="odd:bg-white even:bg-gray-50 border-b dark:border-gray-600 hover:bg-gray-100">
                                    <td class="px-4 py-2 flex gap-2 items-center">
                                        <a href="<?= $url . $app->PaymentBE()->generateInvoiceUrl($payment['user_id'], $payment['id_relasi'])?>"
                                            title="Detail Pembayaran" class="text-sky-600 hover:text-sky-800"><i
                                                class="ti ti-eye"></i></a>
                                    </td>
                                    <th scope="row" class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">
                                        <?= htmlspecialchars($payment['payment'] ?? '') ?><div
                                            class="text-xs text-slate-500 font-normal">
                                            <?= htmlspecialchars($payment['virtual_account'] ?? '') ?></div>
                                    </th>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <?= htmlspecialchars($payment['jenjang'] ?? '') ?>
                                        <?= htmlspecialchars($payment['tingkat'] ?? '') ?>
                                        <?= htmlspecialchars($payment['kelas'] ?? '') ?></td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <?= FormatHelper::formatRupiah($payment['trx_amount'] ?? 0) ?></td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <?= htmlspecialchars($payment['trx_timestamp'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach;?>
                                <?php else :?>
                                <tr>
                                    <td colspan="5" class="px-4 py-2 text-center text-gray-500" colspan="6">Tidak ada data ditemukan</td>
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

<?php include_once __DIR__ . "/modals/import-payment.php"; ?>

<script src="/js/flowbite.min.js"></script>
<script src="/js/datatables.js"></script>
<script src="/js/pages/pembayaran.js"></script>

<script type="module">
    const DataTable = window.simpleDatatables.DataTable;
    const paymentTableElement = document.getElementById("payment-table");
    if (paymentTableElement) {
        new DataTable(paymentTableElement, {
            paging: true,
            perPage: 10,
            perPageSelect: [5, 10, 15, 20, 25, 50],
            searchable: true,
            sortable: true,
            filter: true,
            labels: {
                placeholder: "Cari siswa...",
                perPage: "pembayaran per halaman",
                noRows: "Tidak ada data ditemukan",
                info: "Menampilkan {start} sampai {end} dari {rows} pembayran"
            }
        });
    }
</script>