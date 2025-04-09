<div class="w-full">
    <div class="flex justify-between w-full mb-2">
        <h1 class="text-lg font-semibold text-slate-800">Rekap</h1>
        <div class="flex gap-2">
            <form action="" method="get">
                <label for="search" class="mb-2 text-xs font-medium text-blue-900 sr-only">Search</label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-2 h-2 text-blue-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    <input type="search" id="search" name="search" value="<?= $_GET['search'] ?? '' ?>" class="block w-full px-2 py-1 ps-7 text-xs rounded-md text-blue-900 border border-blue-700 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Search Recap" />
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

    $recaps = $this->getRecaps();
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
                        Kontak Orang Tua
                    </th>
                    <th scope="col" class="px-6 py-3 bg-emerald-50 text-emerald-700">
                        Pembayaran
                    </th>
                    <th scope="col" class="px-6 py-3 bg-red-50 text-red-700">
                        Denda
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($recaps) > 0) : ?>
                    <?php foreach($recaps as $recap) :?>
                    <tr class="odd:bg-white even:bg-gray-50 border-b border-gray-200">
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            <?= $recap['name'] ?>
                            <div class="text-xs text-blue-500"><?= $recap['virtual_account'] ?></div>
                        </th>
                        <td class="px-6 py-4">
                            <?= $recap['class_name'] ?>
                        </td>
                        <td class="px-6 py-4">
                            <?= $recap['parent_phone'] ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap bg-emerald-50 text-emerald-700">
                            <?= FormatHelper::formatRupiah($recap['penerimaan'])?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap bg-red-50 text-red-700">
                            <?= FormatHelper::formatRupiah($recap['tunggakan'])?>
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
</div>
</div>
