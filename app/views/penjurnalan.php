<div class="w-full">
    <h1 class="text-lg font-semibold text-slate-800">Penjurnalan</h1>
</div>

<?php
use App\Helpers\FormatHelper;
$journals = $this->journalBE->getJournals();

$per_first_day   = $journals['per_first_day'] ?? 0;
$per_tenth_day   = $journals['per_tenth_day'] ?? 0;
$late_fee_result = $journals['late_fee_amount'] ?? 0;
$paid_late_fee   = $journals['paid_late_fee'] ?? 0;
?>

<form method="get" class="grid md:grid-cols-3 gap-4">
    <div>
        <label for="level-filter" class="text-xs font-semibold uppercase">jenjang</label>
        <select name="level-filter" id="level-filter"
            class="block w-full px-3 py-2 text-sm text-slate-800 bg-white rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
            <option value="" selected disabled>Pilih Jenjang</option>
        </select>
    </div>
    <div>
        <label for="grade-filter" class="text-xs font-semibold uppercase">tingkat</label>
        <select name="grade-filter" id="grade-filter"
            class="block w-full px-3 py-2 text-sm text-slate-800 bg-white rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
            <option value="" selected disabled>Pilih Tingkat</option>
        </select>

    </div>
    <div>
        <label for="section-filter" class="text-xs font-semibold uppercase">kelas</label>
        <select name="section-filter" id="section-filter"
            class="block w-full px-3 py-2 text-sm text-slate-800 bg-white rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
            <option value="" selected disabled>Pilih Kelas</option>
        </select>
    </div>
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
    <div>
        <label for="month-filter" class="text-xs font-semibold uppercase">bulan</label>
        <select name="month-filter" id="month-filter"
            class="block w-full px-3 py-2 text-sm text-slate-800 bg-white rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
            <option value="" selected disabled>Pilih Bulan</option>
        </select>
    </div>
    <div class="md:col-span-3">
        <label for="search" class="block text-sm text-gray-700">Search</label>
        <input type="text" id="search" name="search" value="<?= $_GET['search'] ?? null?>"
            class="block w-full px-3 py-2 text-sm text-slate-800 bg-white rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
    </div>
    <div class="md:col-span-3">
        <button type="submit"
            class="cursor-pointer px-4 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-700">Filter</button>
        <button type="button" id="export-btn"
            class="cursor-pointer px-4 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Export</button>
        <button type="button" id="reset-filter"
            class="cursor-pointer px-4 py-1 text-sm bg-red-500 text-white rounded hover:bg-red-700">Reset Filter</button>
    </div>
</form>

<div class="grid md:grid-cols-2 gap-4">
    <div class="mt-8 relative flex flex-col gap-2 justify-center sm:rounded-lg">
        <table class="text-sm text-left w-full md:w-100">
            <thead class="border-b border-slate-400">
                <tr>
                    <th colspan="2"  class="uppercase text-center px-6 py-4 bg-white text-slate-700 whitespace-nowrap">Penerbitan Uang Sekolah</th>
                </tr>
            </thead>
            <tbody class="border-b border-slate-400">
                <tr class="border-b w-[100%] border-slate-200">
                    <th scope="row" class="px-6 py-4 bg-white text-slate-700 whitespace-nowrap">
                        PIUT UANG SEKOLAH
                    </th>
                    <th scope="row" class="px-6 py-4 font-medium bg-white text-slate-700 whitespace-nowrap font-semibold">
                        <?= FormatHelper::formatRupiah($per_first_day)?>
                    </th>
                </tr>
                <tr class="border-b w-[100%] border-slate-200">
                    <th scope="row" class="text-right px-6 py-4 font-medium bg-white text-slate-700 whitespace-nowrap font-semibold">
                        PENERIMAAN - UANG SEKOLAH
                    </th>
                    <th scope="row" class="px-6 py-4 font-medium bg-white text-slate-700 whitespace-nowrap font-semibold">
                        <?= FormatHelper::formatRupiah($per_first_day)?>
                    </th>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="mt-8 relative flex flex-col gap-2 justify-center sm:rounded-lg">
        <table class="text-sm text-left w-full md:w-100">
            <thead class="border-b border-slate-400">
                <tr>
                    <th colspan="2"  class="uppercase text-center px-6 py-4 bg-white text-slate-700 whitespace-nowrap">Pelunasan Uang Sekolah</th>
                </tr>
            </thead>
            <tbody class="border-b border-slate-400">
                <tr class="border-b w-[100%] border-slate-200">
                    <th scope="row" class="px-6 py-4 bg-white text-slate-700 whitespace-nowrap">
                        BANK BNI
                    </th>
                    <th scope="row" class="px-6 py-4 font-medium bg-white text-slate-700 whitespace-nowrap font-semibold">
                        <?= FormatHelper::formatRupiah($per_tenth_day)?>
                    </th>
                </tr>
                <tr class="border-b w-[100%] border-slate-200">
                    <th scope="row" class="text-right px-6 py-4 font-medium bg-white text-slate-700 whitespace-nowrap font-semibold">
                        PIUT. UANG SEKOLAH
                    </th>
                    <th scope="row" class="px-6 py-4 font-medium bg-white text-slate-700 whitespace-nowrap font-semibold">
                        <?= FormatHelper::formatRupiah($per_tenth_day)?>
                    </th>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="mt-8 relative flex flex-col gap-2 justify-center sm:rounded-lg">
        <table class="text-sm text-left w-full md:w-100">
            <thead class="border-b border-slate-400">
                <tr>
                    <th colspan="2"  class="uppercase text-center px-6 py-4 bg-white text-slate-700 whitespace-nowrap">Penerbitan Denda</th>
                </tr>
            </thead>
            <tbody class="border-b border-slate-400">
                <tr class="border-b w-[100%] border-slate-200">
                    <th scope="row" class="px-6 py-4 bg-white text-slate-700 whitespace-nowrap">
                        PIUT. DENDA
                    </th>
                    <th scope="row" class="px-6 py-4 font-medium bg-white text-slate-700 whitespace-nowrap font-semibold">
                        <?= FormatHelper::formatRupiah($late_fee_result)?>
                    </th>
                </tr>
                <tr class="border-b w-[100%] border-slate-200">
                    <th scope="row" class="text-right px-6 py-4 font-medium bg-white text-slate-700 whitespace-nowrap font-semibold">
                        PENERIMAAN - UANG DENDA
                    </th>
                    <th scope="row" class="px-6 py-4 font-medium bg-white text-slate-700 whitespace-nowrap font-semibold">
                        <?= FormatHelper::formatRupiah($late_fee_result)?>
                    </th>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="mt-8 relative flex flex-col gap-2 justify-center sm:rounded-lg">
        <table class="text-sm text-left w-full md:w-100">
        <thead class="border-b border-slate-400">
                <tr>
                    <th colspan="2" class="uppercase text-center px-6 py-4 bg-white text-slate-700 whitespace-nowrap">Pembayaran Denda Lunas</th>
                </tr>
            </thead>
            <tbody class="border-b border-slate-400">
                <tr class="border-b w-[100%] border-slate-200">
                    <th scope="row" class="px-6 py-4 bg-white text-slate-700 whitespace-nowrap">
                        BANK BNI
                    </th>
                    <th scope="row" class="px-6 py-4 font-medium bg-white text-slate-700 whitespace-nowrap font-semibold">
                        <?= FormatHelper::formatRupiah($paid_late_fee)?>
                    </th>
                </tr>
                <tr class="border-b w-[100%] border-slate-200">
                    <th scope="row" class="text-right px-6 py-4 font-medium bg-white text-slate-700 whitespace-nowrap font-semibold">
                        PIUT. DENDA
                    </th>
                    <th scope="row" class="px-6 py-4 font-medium bg-white text-slate-700 whitespace-nowrap font-semibold">
                        <?= FormatHelper::formatRupiah($paid_late_fee)?>
                    </th>
                </tr>
            </tbody>
        </table>
    </div>

</div>


<script src="/js/pages/penjurnalan.js"></script>
