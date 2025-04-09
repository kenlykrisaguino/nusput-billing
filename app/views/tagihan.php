<div class="w-full">
    <div class="flex justify-between w-full mb-2">
        <h1 class="text-lg font-semibold text-slate-800">Tagihan</h1>
        <div class="flex gap-2">
            <div class="px-2 py-1 text-xs rounded-md border border-blue-700 text-blue-500 hover:text-blue-800 cursor-pointer font-semibold">Export</div>
            <form action="" method="get">
                <label for="search" class="mb-2 text-xs font-medium text-blue-900 sr-only">Search</label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-2 h-2 text-blue-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    <input type="search" id="search" name="search" value="<?= $_GET['search'] ?? '' ?>" class="block w-full px-2 py-1 ps-7 text-xs rounded-md text-blue-900 border border-blue-700 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Search Bills" />
                    <button type="submit" class="text-white absolute end-0.5 bottom-0.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-md text-xs px-2 py-1">Search</button>
                </div>
            </form>
            <div class="px-2 py-1 text-xs rounded-md border border-blue-700 text-blue-500 hover:text-blue-800 cursor-pointer font-semibold">Filter</div>
        </div>
    </div>
    
    <hr class="mb-2">

    <?php

    use App\Helpers\FormatHelper;
    use App\Helpers\Call;

    $data = $this->getBills();
    $bills = $data['data'];
    $months = Call::monthNameSemester($data['semester']);

    $status = [
        BILL_STATUS_PAID => [
            "display" => "Paid",
            "class" => "text-green-700 font-semibold"
        ],
        BILL_STATUS_LATE => [
            "display" => "Late",
            "class" => "text-amber-700"
        ],
        BILL_STATUS_ACTIVE => [
            "display" => "Active",
            "class" => "text-blue-700"
        ],
        BILL_STATUS_UNPAID => [
            "display" => "Unpaid",
            "class" => "text-red-700 font-semibold"
        ],
        BILL_STATUS_INACTIVE => [
            "display" => "Inavtive",
            "class" => "text-slate-400"
        ],
        BILL_STATUS_DISABLED => [
            "display" => "Disabled",
            "class" => "text-slate-200"
        ]
    ]
    ?>

<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Siswa
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Kelas
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Tagihan
                    </th>
                    <th scope="col" class="px-6 py-3 bg-emerald-50">
                        Penerimaan
                    </th>
                    <th scope="col" class="px-6 py-3 bg-red-50">
                        Tunggakan
                    </th>
                    <?php foreach($months as $num => $month) :?>
                        <th scope="col" class="px-6 py-3">
                            <?= $month ?>
                        </th>
                    <?php endforeach;?>
                </tr>
            </thead>
            <tbody>
                <?php if (count($bills) > 0) : ?>
                    <?php foreach($bills as $bill) :?>
                    <tr class="odd:bg-white even:bg-gray-50 border-b border-gray-200">
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            <?= $bill['name'] ?>
                            <div class="text-xs text-blue-500"><?= $bill['virtual_account'] ?></div>
                        </th>
                        <td class="px-6 py-4">
                            <?= $bill['class_name'] ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?= FormatHelper::formatRupiah($bill['tagihan'])?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap bg-emerald-50 text-emerald-700">
                            <?= FormatHelper::formatRupiah($bill['penerimaan'])?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap bg-red-50 text-red-700">
                            <?= FormatHelper::formatRupiah($bill['tunggakan'])?>
                        </td>
                        <?php foreach($months as $month) :
                            $modalId = 'modal_' . $bill['virtual_account'] . '_' . $month;
                            $detail = json_decode($bill["Detail$month"], true);
                        ?>
                            <td class="px-6 py-4 whitespace-nowrap <?= Call::statusColor($bill["Status$month"]) ?>">
                                <button type="button"
                                    onclick="document.getElementById('<?= $modalId ?>').classList.remove('hidden')"
                                    class="cursor-pointer">
                                    <?= FormatHelper::formatRupiah($bill[$month])?>
                                </button>

                                <div id="<?= $modalId ?>" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex justify-center items-center">
                                    <div class="bg-white w-[90%] max-w-md p-4 rounded-lg shadow-lg relative">
                                        <h3 class="text-sm font-bold mb-2 text-slate-700">Detail Tagihan - <?= $month ?></h3>

                                        <?php if ($detail && is_array($detail)) : ?>
                                            <ul class="text-xs text-slate-700 space-y-1 max-h-64 overflow-y-auto mb-3">
                                                <li class="flex justify-between border-b border-slate-600 pb-1">
                                                    <span class="font-semibold">Nama</span>
                                                    <span><?= htmlspecialchars($detail['name']) ?></span>
                                                </li>
                                                <li class="flex justify-between border-b border-slate-600 pb-1">
                                                    <span class="font-semibold">Kelas</span>
                                                    <span><?= htmlspecialchars($detail['class']) ?></span>
                                                </li>
                                                <li class="flex justify-between border-b border-slate-600 pb-1">
                                                    <span class="font-semibold">Virtual Account</span>
                                                    <span><?= htmlspecialchars($detail['virtual_account']) ?></span>
                                                </li>
                                                <li class="flex justify-between border-b pb-1 border-slate-600">
                                                    <span class="font-semibold">Periode</span>
                                                    <span><?= htmlspecialchars($detail['billing_month']) ?></span>
                                                </li>
                                                <li class="flex justify-between border-b pb-1 border-slate-600">
                                                    <span class="font-semibold">Jatuh Tempo</span>
                                                    <span><?= date('d M Y', strtotime($detail['due_date'])) ?></span>
                                                </li>
                                                <?php if (!empty($detail['payment_date'])) : ?>
                                                <li class="flex justify-between border-b pb-1 border-slate-600">
                                                    <span class="font-semibold">Tanggal Bayar</span>
                                                    <span><?= date('d M Y', strtotime($detail['payment_date'])) ?></span>
                                                </li>
                                                <?php endif; ?>
                                                <?php 
                                                $billStatus = trim($detail['status'], "\"\\")
                                                ?>
                                                <li class="flex justify-between border-b pb-1 border-slate-600">
                                                    <span class="font-semibold">Status</span>
                                                    <span class="<?=$status[$billStatus]['class']?>"><?= $status[$billStatus]['display'] ?></span>
                                                </li>

                                                <li class="pt-2 text-slate-700 font-semibold">Rincian Tagihan:</li>
                                                <?php foreach ($detail['items'] as $item) : ?>
                                                    <li class="flex justify-between pb-1 ps-2">
                                                        <span><?= htmlspecialchars($item['item_name']) ?></span>
                                                        <span><?= FormatHelper::formatRupiah($item['amount']) ?></span>
                                                    </li>
                                                <?php endforeach; ?>

                                                <li class="flex justify-between font-bold pt-2 border-t mt-2">
                                                    <span>Total</span>
                                                    <span><?= FormatHelper::formatRupiah($detail['total']) ?></span>
                                                </li>
                                            </ul>
                                        <?php else : ?>
                                            <div class="text-sm text-slate-500 italic">Detail tidak tersedia</div>
                                        <?php endif; ?>

                                        <button onclick="document.getElementById('<?= $modalId ?>').classList.add('hidden')" class="mt-4 px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                                            Tutup
                                        </button>
                                    </div>
                                </div>

                            </td>
                        <?php endforeach;?>

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
</div>
