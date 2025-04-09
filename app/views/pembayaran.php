<div class="w-full">
    <div class="flex justify-between w-full mb-2">
        <h1 class="text-lg font-semibold text-slate-800">Pembayaran</h1>
        <div class="flex gap-2">
            <div class="px-2 py-1 text-xs rounded-md border border-blue-700 text-blue-500 hover:text-blue-800 cursor-pointer font-semibold">Import Pembayaran</div>
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
</div>
