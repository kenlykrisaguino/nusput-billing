<?php

use App\Helpers\ApiResponse;
use App\Helpers\FormatHelper;

$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$currentUrl .= "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

$path = parse_url($currentUrl, PHP_URL_PATH);

$segments = explode('/', trim($path, '/'));

$details = $app->PaymentBE()->getPublicInvoice($segments);
if (!$details['status']) {
    echo $details['details'];
    exit;
}

array_shift($segments);
$code = implode('/', $segments);
$url = $_SERVER['HTTP_HOST'];

$meta = $details['meta'];
$details = $details['details'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/assets/img/nusaputera.png" type="image/x-icon">
    <link rel="manifest" href="/manifest.json">
    <title>Invoice <?= $meta['nama'] . ' - ' . $meta['kelas'] ?></title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
</head>

<body>
    <div class="bg-slate-100 h-screen w-screen p-8">
        <div class="flex flex-col">
            <div class="flex w-full justify-between pb-6 mb-6 border-b-2 border-slate-500">
                <div class="text-2xl">
                    <h1 class="font-bold">Invoice Pembayaran SPP</h1>
                    <p class="text-xs text-blue-500 italic">#<?= $code ?></p>
                    <a class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700" href="http://<?= $url ?>/api/export-invoice/<?=$code?>">Download Invoice</a>
                </div>
                <div class="font-semibold text-right text-slate-400">
                    <p class="text-xs">Tanggal Pembayaran Masuk Sistem</p>
                    <p class="text-sm"><?= htmlspecialchars($meta['date'] ?? '') ?></p>
                </div>
            </div>
            <div class="mb-4">
                <table>
                    <tbody>
                        <tr>
                            <td class="pr-4">Nama</td>
                            <td class="pr-4">: <?= $meta['nama'] ?></td>
                        </tr>
                        <tr>
                            <td class="pr-4">Kelas</td>
                            <td class="pr-4">: <?= $meta['kelas'] ?></td>
                        </tr>
                        <tr>
                            <td class="pr-4">Virtual Account</td>
                            <td class="pr-4">: <?= $meta['va'] ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-lefttext-slate-500">
                    <thead class="text-xs text-slate-700 uppercase bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                Nama
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Periode
                            </th>
                            <th scope="col" class="px-6 py-3 text-right">
                                Biaya
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($details as $item):?>
                        <tr class="bg-white border-b border-slate-200">
                            <?php 
                            $item_name = htmlspecialchars($item['jenis']);
                            if($item_name == "spp"){
                                $item_name = "Tagihan Bulanan";
                            } else if($item_name == "late"){
                                $item_name = "Biaya Keterlambatan";
                            }
                            ?>
                            <th scope="row" class="px-6 py-4 font-medium text-slate-900 whitespace-nowrap">
                                <?= $item_name ?>
                            </th>
                            <td class="px-6 py-4 text-center">
                                <?= htmlspecialchars($item['bulan'] . "/" . $item['tahun']) ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?= FormatHelper::formatRupiah(htmlspecialchars($item['nominal'])) ?>
                            </td>
                        </tr>
                        <?php endforeach?>
                    </tbody>
                    <tfoot>
                        <th scope="col" colspan="2"
                            class="px-6 py-3 text-xs text-slate-700 uppercase bg-slate-50 text-right">
                            Total Pembayaran
                        </th>
                        <th scope="col" class="px-6 py-3 text-md text-slate-700 bg-slate-50 text-right">
                            <?= FormatHelper::formatRupiah(htmlspecialchars($meta['total']))  ?>
                        </th>
                    </tfoot>
                </table>
            </div>
            <div class="mt-5 ">
            </div>
        </div>
    </div>

</body>

</html>
