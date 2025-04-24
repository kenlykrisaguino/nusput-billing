<div class="w-full">
    <div class="flex justify-between w-full mb-2">
        <h1 class="text-lg font-semibold text-slate-800">Pembayaran</h1>
        <div class="flex gap-2">
            <div class="px-2 py-1 text-xs rounded-md border border-blue-700 text-blue-500 hover:text-blue-800 cursor-pointer font-semibold" onclick="document.getElementById('import-payment').classList.remove('hidden')">Import Pembayaran</div>
            <form action="" method="get">
                <label for="search" class="mb-2 text-xs font-medium text-blue-900 sr-only">Search</label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-2 h-2 text-blue-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    <input type="search" id="search" name="search" value="<?= $_GET['search'] ?? '' ?>" class="block w-full px-2 py-1 ps-7 text-xs rounded-md text-blue-900 border border-blue-700 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Search Payment" />
                    <button type="submit" class="text-white absolute end-0.5 bottom-0.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-md text-xs px-2 py-1">Search</button>
                </div>
            </form>
            <div class="px-2 py-1 text-xs rounded-md border border-blue-700 text-blue-500 hover:text-blue-800 cursor-pointer font-semibold">Filter</div>
        </div>
    </div>
    
    <hr class="mb-2">

    <div id="import-payment" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex justify-center items-center">
        <form id="import-payment-form" method="post"
            class="bg-white w-[90%] max-w-md p-4 rounded-lg shadow-lg relative">
            <h3 class="text-sm font-bold mb-2 text-slate-700">Import Pembayaran</h3>

            <div class="pb-2">
                <label for="bulk-payments" class="mb-1 block text-xs font-medium text-slate-700">Upload file</label>
                <input name="bulk-payments" id="bulk-payments" type="file"
                    class="mt-2 block w-full text-xs file:mr-4 file:rounded-md file:border-0 file:bg-blue-500 file:py-1 file:px-2 file:text-xs file:font-medium file:text-white hover:file:bg-blue-700 focus:outline-none disabled:pointer-events-none disabled:opacity-60" />
                <small class="text-slate-400 text-xs italic">Format Excel Import Pembayaran dapat diunduh <a
                        class="text-blue-400 hover:text-blue-500" href="/format?type=payment">disini</a></small>
            </div>

            <button onclick="importPaymentXLSX(this)" type="submit"
                class="mt-4 px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                Import
            </button>

            <button onclick="closeModal('import-payment')" type="button"
                class="mt-4 px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                Batal
            </button>
            

        </form>

    </div>

    
    <?php

    use App\Helpers\FormatHelper;
    use App\Helpers\Call;

    $transactions = $this->getPayments();
    ?>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Nama
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Transaksi
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Waktu
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transactions) > 0) : ?>
                    <?php foreach($transactions as $trx) :?>
                    <tr class="odd:bg-white even:bg-gray-50 border-b border-gray-200">
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            <?= $trx['payment'] ?>
                        </th>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?= FormatHelper::formatRupiah($trx['trx_amount']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?= $trx['trx_timestamp'] ?>
                        </td>
                    </tr>
                    <?php endforeach;?>
                <?php else :?>
                    <tr>
                        <td class="px-6 py-4 text-center" colspan="9">
                            Data transaksi kosong.
                        </td>
                    </tr>
                <?php endif;?>
            </tbody>
        </table>
    </div>
</div>
<script src="/js/pages/pembayaran.js"></script>
