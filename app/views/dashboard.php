<?php

use App\Helpers\FormatHelper;

$data = $this->studentBE->studentPage();

$dashboard = $data['dashboard'];
$payments = $data['payments'];

$status = [
    BILL_STATUS_PAID => [
        'display' => 'Paid',
        'class' => 'text-green-700 font-semibold',
    ],
    BILL_STATUS_LATE => [
        'display' => 'Late',
        'class' => 'text-amber-700',
    ],
    BILL_STATUS_ACTIVE => [
        'display' => 'Active',
        'class' => 'text-blue-700',
    ],
    BILL_STATUS_UNPAID => [
        'display' => 'Unpaid',
        'class' => 'text-red-700 font-semibold',
    ],
    BILL_STATUS_INACTIVE => [
        'display' => 'Inavtive',
        'class' => 'text-slate-400',
    ],
    BILL_STATUS_DISABLED => [
        'display' => 'Disabled',
        'class' => 'text-slate-200',
    ],
];
?>

<div class="w-full grid grid-cols-1 sm:grid-cols-2 gap-y-2 md:grid-cols-3 lg:grid-cols-4">
    <div>
        <div class="text-xs text-blue-500 font-bold uppercase">NIS</div>
        <div class="text-lg text-slate-800 font-medium"><?= $dashboard['nis'] ?? '-' ?></div>
    </div>
    <div>
        <div class="text-xs text-blue-500 font-bold uppercase">Nama</div>
        <div class="text-lg text-slate-800 font-medium"><?= $dashboard['name'] ?? '-' ?></div>
    </div>
    <div>
        <div class="text-xs text-blue-500 font-bold uppercase">Kontak Orang Tua</div>
        <div class="text-lg text-slate-800 font-medium"><?= $dashboard['parent_phone'] ?? '-' ?></div>
    </div>
    <div>
        <div class="text-xs text-blue-500 font-bold uppercase">Virtual Account</div>
        <div class="text-lg text-slate-800 font-medium"><?= $dashboard['virtual_account'] ?? '-' ?></div>
    </div>
    <div>
        <div class="text-xs text-blue-500 font-bold uppercase">Kelas</div>
        <div class="text-lg text-slate-800 font-medium"><?= $dashboard['class_name'] ?? '-' ?></div>
    </div>
    <div>
        <div class="text-xs text-blue-500 font-bold uppercase">SPP</div>
        <div class="text-lg text-slate-800 font-medium">
            <?= FormatHelper::formatRupiah($dashboard['monthly_fee'] ?? 0 )?>
        </div>
    </div>
    <div>
        <div class="text-xs text-blue-500 font-bold uppercase">Pembayaran Terakhir</div>
        <div class="text-lg text-slate-800 font-medium"><?= $dashboard['last_payment'] ?? '-' ?></div>
    </div>
    <div>
        <div class="text-xs text-blue-500 font-bold uppercase">Total Tagihan</div>
        <div class="text-lg text-slate-800 font-medium">
            <?= FormatHelper::formatRupiah($dashboard['total_bills'] ?? 0) ?>
        </div>
    </div>
</div>
<hr class="h-0.25 text-sky-200 my-4" />
<form method="get" class="grid sm:grid-cols-2 gap-2 mb-4">
    <div>
        <label for="year-filter" class="text-xs font-semibold uppercase">tahun ajaran</label>
        <select name="year-filter" id="year-filter"
            class="block w-full px-3 py-2 text-sm text-slate-800 bg-white rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
            <option value="" selected disabled>Pilih Tahun Ajaran</option>
        </select>
    </div>
    <div>
        <label for="semester-filter" class="text-xs font-semibold uppercase">semester</label>
        <select name="semester-filter" id="semester-filter"
            class="block w-full px-3 py-2 text-sm text-slate-800 bg-white rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
            <option value="" selected disabled>Pilih Semester</option>
        </select>
    </div>
    <div class="md:col-span-2">
        <button type="submit"
            class="cursor-pointer px-4 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-700">Filter</button>
    </div>
</form>

<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3">
                    Bulan
                </th>
                <th scope="col" class="px-6 py-3">
                    Tagihan
                </th>
                <th scope="col" class="px-6 py-3">
                    Tanggal Pembayaran
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($payments) > 0) : ?>
            <?php foreach($payments as $id => $payment) :?>
            <?php
            $modalId = 'modal_' . $id;
            $detail = json_decode($payment['detail'], true);
            ?>
            <tr onclick="document.getElementById('<?= $modalId ?>').classList.remove('hidden')"
                class="<?= $status[$payment['trx_status']]['class'] ?> bg-white cursor-pointer hover:bg-slate-200 transition-all border-b border-gray-200">
                <th scope="row" class="px-6 py-4 font-medium whitespace-nowrap flex flex-col gap-2">
                    <div><?= $payment['month'] ?></div>
                </th>
                <td class="px-6 py-4 whitespace-nowrap">
                    <?= FormatHelper::formatRupiah($payment['bills']) ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <?= $payment['paid_at'] ?>
                </td>
            </tr>
            <div id="<?= $modalId ?>"
                class="fixed inset-0 z-50 hidden bg-slate-900/50 flex justify-center items-center">
                <div class="bg-white w-[90%] max-w-md p-4 rounded-lg shadow-lg relative">
                    <div class="flex justify-between items-center mb-2 ">
                        <h3 class="text-sm font-bold text-slate-700">Detail Tagihan <?= $payment['month'] ?></h3>
                        <?php if($payment['paid_at'] != ""):?>
                            <?php 
                            $url = $_SERVER['HTTP_HOST'];
                            $code = $this->paymentBE->generateInvoiceURL($payment['user_id'], $payment['bill_id']);    
                            $full = "http://$url/invoice/$code";
                            ?>
                            <a href="<?= $full?>" target="_blank"
                                class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                                <i class="fa-regular fa-file-lines"></i> Buka Invoice
                            </a>
                        <?php endif?>
                    </div>

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
                            <span class="font-semibold">Tahun Ajaran</span>
                            <span><?= htmlspecialchars($detail['academic_year']) ?></span>
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
                        $billStatus = trim($detail['status'], "\"\\");
                        ?>
                        <li class="flex justify-between border-b pb-1 border-slate-600">
                            <span class="font-semibold">Status</span>
                            <span
                                class="<?= $status[$billStatus]['class'] ?>"><?= $status[$billStatus]['display'] ?></span>
                        </li>

                        <li class="pt-2 text-slate-700 font-semibold">Rincian Tagihan:</li>
                        <?php foreach ($detail['items'] as $item) : ?>
                        <?php
                        $item_name = htmlspecialchars($item['item_name']);
                        
                        if ($item_name == 'monthly_fee') {
                            $item_name = 'Tagihan Bulanan';
                        } elseif ($item_name == 'late_fee') {
                            $item_name = 'Biaya Keterlambatan';
                        }
                        ?>
                        <li class="flex justify-between pb-1 ps-2">
                            <span><?= $item_name ?></span>
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

                    <button onclick="document.getElementById('<?= $modalId ?>').classList.add('hidden')"
                        class="mt-4 px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                        Tutup
                    </button>
                </div>
            </div>
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

<script src="/js/pages/dashboard.js"></script>
