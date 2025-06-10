<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nusput Billing - <?= ucfirst(htmlspecialchars($page ?? 'Page')) ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="manifest" href="/manifest.json">
    <link rel="shortcut icon" href="/assets/img/nusaputera.png" type="image/x-icon">
    <link href="/css/flowbite.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    <script defer src="/js/alphine.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        .sidebar-item-icon-only {
            justify-content: center;
        }

        .sidebar-item-icon-only span {
            display: none;
        }

        .main-content-shifted {
            transition: margin-left 0.3s ease-in-out;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        body>.flex.flex-col {
            min-height: 100%;
        }
    </style>
</head>

<body class="bg-slate-200">
    <div class="w-screen min-h-screen flex flex-col">
        <header x-data="{ mobileMenuOpen: false }"
            class="h-14 shadow bg-white flex items-center justify-between px-4 flex-shrink-0 relative z-50">
            <div class="flex items-center gap-4">
                <button @click="$dispatch('toggle-sidebar')"
                    class="md:hidden text-gray-600 hover:text-sky-700 focus:outline-none focus:text-sky-700">
                    <i class="ti ti-layout-sidebar-left-expand text-2xl"></i>
                </button>
                <div id="logo" class="flex gap-2 items-center">
                    <img src="/assets/img/nusaputera.png" alt="Logo Nusaputera" class="h-8">
                    <h4 class="font-semibold">Sistem Pembayaran</h4>
                </div>
            </div>
            <nav class="hidden md:flex gap-4 items-center">
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === USER_ROLE_STUDENT): ?>
                <a href="/dashboard" class="block text-gray-700 hover:text-sky-600 transition-colors">Dashboard</a>
                <?php elseif(isset($_SESSION['role'])): ?>
                <a href="/students" class="block text-gray-700 hover:text-sky-600 transition-colors">Siswa</a>
                <a href="/pembayaran" class="block text-gray-700 hover:text-sky-600 transition-colors">Pembayaran</a>
                <a href="/tagihan" class="block text-gray-700 hover:text-sky-600 transition-colors">Tagihan</a>
                <a href="/rekap" class="block text-gray-700 hover:text-sky-600 transition-colors">Rekap</a>
                <a href="/penjurnalan" class="block text-gray-700 hover:text-sky-600 transition-colors">Penjurnalan</a>
                <?php endif; ?>
                <a href="/api/logout"
                    class="block font-sm px-3 py-1.5 text-white bg-red-600 rounded-md border hover:bg-red-800 transition-all">Logout</a>
            </nav>
            <div class="md:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="text-gray-600 hover:text-sky-700 focus:outline-none focus:text-sky-700">
                    <i class="ti ti-dots-vertical text-2xl"></i>
                </button>
            </div>
            <div x-show="mobileMenuOpen" @click.away="mobileMenuOpen = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                class="md:hidden absolute top-full right-0 mt-2 w-56 origin-top-right bg-white shadow-xl rounded-md z-40 border border-gray-200">
                <nav class="flex flex-col p-2 space-y-1">
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === USER_ROLE_STUDENT): ?>
                    <a href="/dashboard"
                        class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-sky-50 hover:text-sky-700">Dashboard</a>
                    <?php elseif(isset($_SESSION['role'])): ?>
                    <a href="/students"
                        class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-sky-50 hover:text-sky-700">Siswa</a>
                    <a href="/pembayaran"
                        class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-sky-50 hover:text-sky-700">Pembayaran</a>
                    <a href="/tagihan"
                        class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-sky-50 hover:text-sky-700">Tagihan</a>
                    <a href="/rekap"
                        class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-sky-50 hover:text-sky-700">Rekap</a>
                    <a href="/penjurnalan"
                        class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-sky-50 hover:text-sky-700">Penjurnalan</a>
                    <?php endif; ?>
                    <hr class="my-1">
                    <a href="/api/logout"
                        class="block w-full text-left mt-1 px-3 py-2 text-red-600 hover:bg-red-50 rounded-md">Logout</a>
                </nav>
            </div>
        </header>
        <script src="/js/app.js"></script>
        <?php
        if (isset($page)) {
            $this->loadView($page, $data ?? []);
        } else {
            echo '<div class="flex-grow p-6">Halaman tidak ditemukan atau tidak dikonfigurasi dengan benar.</div>';
        }
        ?>
    </div>
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
            if (type == 'error') {
                notyf.error(message);
            } else {
                notyf.success(message);
            }
        }
    </script>
</body>

</html>
