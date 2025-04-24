<div class="w-full">
    <form action="" method="get" class="flex justify-between w-full mb-2">
        <h1 class="text-lg font-semibold text-slate-800">Rekap</h1>
        <div class="flex gap-2">
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
                    <input type="search" id="search" name="search" value="<?= $_GET['search'] ?? '' ?>"
                        class="block w-full px-2 py-1 ps-7 text-xs rounded-md text-blue-900 border border-blue-700 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Search Recap" />
                    <button type="submit"
                        class="text-white absolute end-0.5 bottom-0.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-md text-xs px-2 py-1">Search</button>
                </div>
            </div>
            <div onclick="document.getElementById('filter-recap').classList.remove('hidden')"
                class="px-2 py-1 text-xs rounded-md border border-blue-700 text-blue-500 hover:text-blue-800 cursor-pointer font-semibold">
                Filter</div>
            <div id="filter-recap" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex justify-center items-center">
                <div class="bg-white w-[90%] max-w-md p-4 rounded-lg shadow-lg relative max-h-[90vh] overflow-y-auto">
                    <h3 class="text-sm font-bold mb-2 text-slate-700">Filter Rekap</h3>

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
                    <div class="mb-2">
                        <label for="year-filter" class="text-xs font-semibold uppercase">tahun ajaran</label>
                        <select name="year-filter" id="year-filter"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option value="" selected disabled>Pilih Tahun Ajaran</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="semester-filter" class="text-xs font-semibold uppercase">semester</label>
                        <select name="semester-filter" id="semester-filter"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option value="" selected disabled>Pilih Semester</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="month-filter" class="text-xs font-semibold uppercase">bulan</label>
                        <select name="month-filter" id="month-filter"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                            <option value="" selected disabled>Pilih Bulan</option>
                        </select>
                    </div>
                    <button type="submit"
                        class="cursor-pointer mt-4 px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                        Tambah
                    </button>

                    <button onclick="closeModal('filter-recap')" type="button"
                        class="cursor-pointer mt-4 px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </form>

    <hr class="mb-2">

    <?php
    
    use App\Helpers\FormatHelper;
    
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
                        <?= FormatHelper::formatRupiah($recap['penerimaan']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap bg-red-50 text-red-700">
                        <?= FormatHelper::formatRupiah($recap['tunggakan']) ?>
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
<script src="/js/pages/rekap.js"></script>
