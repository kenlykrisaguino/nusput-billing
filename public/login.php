<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Tagihan Nusaputera</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="shortcut icon" href="/assets/img/nusaputera.png" type="image/x-icon">
    <link rel="manifest" href="/manifest.json">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style>
        #error-message {
            display: none;
            background-color: #fdecea;
            color: #a94442;
            border: 1px solid #ebccd1;
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            font-size: 1rem;
        }

        #error-message.show {
            display: block;
        }

        #modal-error-message {
            display: none;
            background-color: #fdecea;
            color: #a94442;
            border: 1px solid #ebccd1;
            padding: 8px;
            margin-top: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        #modal-error-message.show {
            display: block;
        }

        #otp-verification,
        #password-input-1,
        #password-input-2 {
            display: none;
        }
    </style>
</head>

<body>
    <div id="modal-lupa-password" class="fixed inset-0 z-50 hidden bg-slate-900/50 flex justify-center items-center">
        <div class="bg-white w-[90%] max-w-md p-4 rounded-lg shadow-lg relative">
            <div class="flex justify-between items-center mb-2 ">
                <h3 class="text-sm font-bold text-slate-700">Lupa Password</h3>
            </div>
            <div id="modal-error-message"></div>

            <form id="forgot-password-form" action="#" method="POST">
                <div class="mt-4" id="initial">
                    <label for="virtual-account" class="block text-sm text-gray-700">Virtual Account</label>
                    <input type="text" id="virtual-account" name="virtual-account"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="mt-4" id="otp-verification">
                    <label for="otp" class="block text-sm text-gray-700">Kode OTP</label>
                    <input type="text" id="otp" name="otp"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                    <small class="text-slate-400 text-xs italic">Jika tidak mendapatkan pesan, silahkan hubungi admin <a class="hover:text-blue-500 transition-colors" href="https://wa.me/6281329171920" target="_blank">0813-2917-1920</a></small>
                </div>
                <div class="mt-4" id="password-input-1">
                    <label for="new-password" class="block text-sm text-gray-700">Password Baru</label>
                    <input type="password" id="new-password" name="new-password"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="mt-4" id="password-input-2">
                    <label for="password-confirmation" class="block text-sm text-gray-700">Konfirmasi Password Baru</label>
                    <input type="password" id="password-confirmation" name="password-confirmation"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="flex w-full justify-between items-center mt-6">
                     <button type="button" id="modal-close-button"
                        class="px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                        Tutup
                    </button>
                    <button type="submit" id="modal-submit-button"
                        class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                        Kirim
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="w-screen h-screen bg-sky-100">
        <div class="flex items-center justify-center h-full">
            <div class="bg-white p-8 rounded shadow-md w-96">
                <h1 class="text-xl font-semibold mb-4">Login</h1>
                <div id="error-message"></div>
                <form id="login-form">
                    <div class="mb-4">
                        <label for="username" class="block text-sm text-gray-700">Username</label>
                        <input type="text" id="username" name="username"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                    </div>
                    <div class="mb-4">
                        <label for="login-password" class="block text-sm text-gray-700">Password</label>
                        <input type="password" id="login-password" name="password"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                    </div>
                    <div class="flex w-full justify-between items-center">
                        <button type="button" id="lupa-password-link" class="cursor-pointer text-slate-400 hover:text-blue-500 text-xs p-0 m-0 border-none bg-transparent">Lupa
                            Password</button>
                        <button type="submit"
                            class="bg-blue-500 hover:bg-blue-400 transition-colors cursor-pointer py-2.5 px-4 rounded-md text-sm text-medium text-slate-50">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="./js/app.js"></script> 
    <script src="./js/pages/login.js"></script>
    <script src="./js/pages/forgot_password_modal.js"></script>

</body>

</html>