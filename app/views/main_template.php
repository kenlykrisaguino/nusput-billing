<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nusput Billing - <?= ucfirst(htmlspecialchars($page)) ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="manifest" href="/manifest.json">
    <link rel="shortcut icon" href="/assets/img/nusaputera.png" type="image/x-icon">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://kit.fontawesome.com/64915ea633.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
</head>

<body>
    <div class="w-screen h-screen bg-sky-100">
        <div id="header" class="w-screen p-4 flex justify-center flex-col items-center gap-4">
            <div id="logo" class="flex items-center gap-4">
                <img src="/assets/img/nusaputera.png" alt="logo" class="h-12" />
                <div class="">
                    <h1 class="text-xl font-bold">Sistem Pembayaran</h1>
                    <?php
                    $user = $this->getUser();
                    ?>
                    <p class="text-xs"><?= $user['name'] ?></p>
                </div>
            </div>
            <div id="nav">
                <ul class="flex gap-6">
                    <?php if($user['role'] != USER_ROLE_STUDENT):?>
                    <li class="flex justify-center items-center flex-col">
                        <a href="students" class="text-sm font-medium text-blue-700 hover:text-blue-900">Siswa</a>
                        <span
                            class="h-1 w-1 bg-blue-400 rounded-full<?php if ($page != 'students') :?> opacity-0 <?php endif;?>"></span>
                    </li>
                    <li class="flex justify-center items-center flex-col">
                        <a href="pembayaran"
                            class="text-sm font-medium text-blue-700 hover:text-blue-900">Pembayaran</a>
                        <span
                            class="h-1 w-1 bg-blue-400 rounded-full<?php if ($page != 'pembayaran') :?> opacity-0 <?php endif;?>"></span>
                    </li>
                    <li class="flex justify-center items-center flex-col">
                        <a href="tagihan" class="text-sm font-medium text-blue-700 hover:text-blue-900">Tagihan</a>
                        <span
                            class="h-1 w-1 bg-blue-400 rounded-full<?php if ($page != 'tagihan') :?> opacity-0 <?php endif;?>"></span>
                    </li>
                    <li class="flex justify-center items-center flex-col">
                        <a href="rekap" class="text-sm font-medium text-blue-700 hover:text-blue-900">Rekap</a>
                        <span
                            class="h-1 w-1 bg-blue-400 rounded-full<?php if ($page != 'rekap') :?> opacity-0 <?php endif;?>"></span>
                    </li>
                    <li class="flex justify-center items-center flex-col">
                        <a href="penjurnalan"
                            class="text-sm font-medium text-blue-700 hover:text-blue-900">Penjurnalan</a>
                        <span
                            class="h-1 w-1 bg-blue-400 rounded-full<?php if ($page != 'penjurnalan') :?> opacity-0 <?php endif;?>"></span>
                    </li>
                    <li class="flex justify-center items-center flex-col">
                        <a href="logs" class="text-sm font-medium text-blue-700 hover:text-blue-900">Logs</a>
                        <span
                            class="h-1 w-1 bg-blue-400 rounded-full<?php if ($page != 'logs') :?> opacity-0 <?php endif;?>"></span>
                    </li>
                    <?php else: ?>
                    <li class="flex justify-center items-center flex-col">
                        <a href="dashboard" class="text-sm font-medium text-blue-700 hover:text-blue-900">Dashboard</a>
                        <span
                            class="h-1 w-1 bg-blue-400 rounded-full<?php if ($page != 'dashboard') :?> opacity-0 <?php endif;?>"></span>
                    </li>
                    <?php endif?>
                    <li class="flex justify-center items-center flex-col">
                        <a href="/api/logout" class="text-sm font-medium text-red-700 hover:text-red-900">Logout</a>
                        <span class="h-1 w-1 bg-blue-400 rounded-full opacity-0"></span>
                    </li>
                </ul>
            </div>
            <div class="w-screen px-6">
                <hr class="h-0.25 text-sky-200" />
            </div>
        </div>
        <main id="content" class="w-screen px-10">
            <?php if (isset($page)) {
                $this->loadView($page, $data ?? []);
            } ?>
        </main>
    </div>
    <?php if($user['role'] != USER_ROLE_STUDENT):?>

    <div class="fixed bottom-4 right-4">
        <button id="floatingButton"
            class="bg-blue-200 cursor-pointer text-blue-800 p-2 h-10 w-10 rounded-full shadow-lg hover:bg-blue-300 focus:outline-none">
            <i id="buttonIcon" class="fas fa-cog text-sm"></i>
        </button>
        <div id="optionsMenu" class="hidden mt-2 space-y-2 text-sm">
            <button id="checkBills" class="bg-blue-100 cursor-pointer text-blue-800 px-6 py-2 rounded-full shadow-lg hover:bg-blue-200 flex items-center">
                <i class="fas fa-check mr-2"></i>
                <span class="text-xs">Check Bills</span>
            </button>
            <button id="createBills" class="bg-blue-100 cursor-pointer text-blue-800 px-6 py-2 rounded-full shadow-lg hover:bg-blue-200 flex items-center">
                <i class="fas fa-file-invoice mr-2"></i>
                <span class="text-xs">Create Bills</span>
            </button>
            <button id="notifyOpen" class="bg-blue-100 cursor-pointer text-blue-800 px-6 py-2 rounded-full shadow-lg hover:bg-blue-200 flex items-center">
                <i class="fas fa-bell mr-2"></i>
                <span class="text-xs">Notify Open Bill</span>
            </button>
            <button id="notifyClose" class="bg-blue-100 cursor-pointer text-blue-800 px-6 py-2 rounded-full shadow-lg hover:bg-blue-200 flex items-center">
                <i class="fas fa-bell-slash mr-2"></i>
                <span class="text-xs">Notify Close Bill</span>
            </button>
        </div>
    </div>

    <script>        
        document.getElementById('floatingButton').addEventListener('click', function() {
            var optionsMenu = document.getElementById('optionsMenu');
            var buttonIcon = document.getElementById('buttonIcon');

            if (optionsMenu.classList.contains('hidden')) {
                buttonIcon.classList.remove('fa-cog');
                buttonIcon.classList.add('fa-times');
            } else {
                buttonIcon.classList.remove('fa-times');
                buttonIcon.classList.add('fa-cog');
            }

            optionsMenu.classList.toggle('hidden');
        });

        document.getElementById('checkBills').addEventListener('click', function() {
            axios.get('/api/check-bills')
                .then(response => {
                    showToast('Check Bills Success', 'success');
                })
                .catch(error => {
                    showToast('Check Bills Failed', 'error');
                });
        });

        document.getElementById('createBills').addEventListener('click', function() {
            axios.get('/api/create-bills')
                .then(response => {
                    showToast('Create Bills Success', 'success');
                })
                .catch(error => {
                    showToast('Create Bills Failed', 'error');
                });
        });

        document.getElementById('notifyOpen').addEventListener('click', function() {
            axios.get('/api/notify-bills?type=<?= FIRST_DAY?>')
                .then(response => {
                    showToast('Notify Open Bill Success', 'success');
                })
                .catch(error => {
                    showToast('Notify Open Bill Failed', 'error');
                });
        });

        document.getElementById('notifyClose').addEventListener('click', function() {
            axios.get('/api/notify-bills?type=<?= DAY_AFTER?>')
                .then(response => {
                    showToast('Notify Close Bill Success', 'success');
                })
                .catch(error => {
                    showToast('Notify Close Bill Failed', 'error');
                });
        });
    </script>
    <?php endif?>

    <script>
        var notyf = new Notyf({
          duration: 5000,
          position: {
            x: 'left',
            y: 'bottom'
          },
          ripple: true,
          dismissible: true
        });

        function showToast(message, type) {
            if(type == 'error'){
              notyf.error(message);
            } else {
              notyf.success(message);
            }
        }
    </script>

    <script src="/js/app.js"></script>
</body>

</html>