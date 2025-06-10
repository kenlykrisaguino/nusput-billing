<div class="w-1/6 bg-sky-600 flex-shrink-0 p-4">
    <h3 class="text-white font-semibold mb-6">Quick Access</h3>
    <div class="flex flex-col gap-2 text-slate-50">
        <div class="flex gap-2 items-center mb-2">
            <h4 class="text-xs uppercase">rekap</h4>
            <div class="flex-1">
                <hr class="text-white">
            </div>
        </div>
        <div onclick="document.getElementById('create-student').classList.remove('hidden')"
            class="cursor-pointer hover:text-slate-200 hover:translate-x-2 transition-colors">
        </div>
    </div>
</div>
<div class="w-5/6 px-10 py-6 overflow-y-auto">
    
    <?php
    
    use App\Helpers\FormatHelper;
    
    $recaps = $this->recapBE->getRecaps();
    ?>

    <section id="recaps">
        <h3 class="font-semibold">Transaction Recap</h3>
        <div class="bg-slate-50 p-4 rounded-xl mt-2 relative overflow-x-auto shadow-md sm:rounded-lg">
                <table id="recap-table" class="w-full text-sm text-left rtl:text-right text-gray-500">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">
                                <span class="flex items-center font-medium text-sm">
                                    Siswa
                                    <svg class="w-4 h-4 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                        width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
                                    </svg>
                                </span>
                            </th>
                            <th class="px-4 py-2">
                                <span class="flex items-center font-medium text-sm">
                                    Kelas
                                    <svg class="w-4 h-4 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                        width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
                                    </svg>
                                </span>
                            </th>
                            <th class="px-4 py-2">
                                <span class="flex items-center font-medium text-sm">
                                    Kontak Orang Tua
                                    <svg class="w-4 h-4 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                        width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
                                    </svg>
                                </span>
                            </th>
                            <th class="px-4 py-2">
                                <span class="flex items-center font-medium text-sm text-emerald-500">
                                    Penerimaan
                                    <svg class="w-4 h-4 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                        width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
                                    </svg>
                                </span>
                            </th>
                            <th class="px-4 py-2">
                                <span class="flex items-center font-medium text-sm text-red-500">
                                    Denda
                                    <svg class="w-4 h-4 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                        width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4" />
                                    </svg>
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recaps) > 0) : ?>
                        <?php foreach($recaps as $recap) :?>
                        <tr class="odd:bg-white even:bg-gray-50 border-b border-gray-200">
                            <th scope="row" class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">
                                <?= htmlspecialchars($recap['name']) ?>
                                <div class="text-xs text-blue-500"><?= htmlspecialchars($recap['virtual_account']) ?></div>
                            </th>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <?= $recap['class_name'] ?>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <?= $recap['parent_phone'] ?>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <?= FormatHelper::formatRupiah($recap['penerimaan']) ?>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <?= FormatHelper::formatRupiah($recap['tunggakan']) ?>
                            </td>
                        </tr>
                        <?php endforeach;?>
                        <?php else :?>
                        <tr>
                            <td class="px-4 py-2 text-center" colspan="3">
                                Data rekap kosong.
                            </td>
                        </tr>
                        <?php endif;?>
                    </tbody>
                </table>
            </div>
    </section>
</div>

<script src="/js/flowbite.min.js"></script>
<script src="/js/datatables.js"></script>
<script type="module">
    const DataTable = window.simpleDatatables.DataTable;

    const recapTable = document.getElementById("recap-table");
    if (recapTable) {
        const dataTable = new DataTable(recapTable, {
            paging: true,
            perPage: 5,
            perPageSelect: [5, 10, 15, 20, 25],
            searchable: true,
            sortable: true
        });
    }
</script>
<script src="/js/pages/rekap.js"></script>
