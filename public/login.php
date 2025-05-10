<!DOCTYPE html>
<title>Login Sistem Keuangan Nusaputera</title>
<link rel="stylesheet" href="/css/style.css">
<link rel="shortcut icon" href="/assets/img/nusaputera.png" type="image/x-icon">
<link rel="manifest" href="/manifest.json">
<link href="./css/bootstrap.min.css" rel="stylesheet"><br><br>
<script src="https://unpkg.com/@tailwindcss/browser@4"></script>
<style>
    body {
        background-color: #F5F7FA;
        background-image: linear-gradient(135deg, rgba(0, 115, 230, 0.08) 25%, transparent 25%),
            linear-gradient(225deg, rgba(255, 215, 0, 0.08) 25%, transparent 25%);
        background-size: 50px 50px;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .login {
        background: white;
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        width: 100%;
        max-width: 400px;
        text-align: center;
        transform: translateY(0);
        transition: transform 0.3s ease-in-out;
    }

    .login:hover {
        transform: translateY(-5px);
    }

    .form-control {
        padding-left: 40px;
    }

    .input-group-text {
        background: none;
        border-right: none;
    }

    .btn-primary {
        background-color: #4A90E2;
        border: none;
        transition: 0.3s;
    }

    .btn-primary:hover {
        background-color: #357ABD;
    }

    .logo {
        width: 100px;
        margin-bottom: 15px;
    }

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
        width: 100%;
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

<body>
    <div class="login flex flex-col items-center">
        <img src="./assets/img/nusaputera.png" alt="Logo Sekolah" class="logo">
        <h3 class="text-primary fw-bold">Sistem Keuangan Sekolah Nasional Nusaputera</h3>
        <p class="text-muted">Silakan login untuk melanjutkan</p>
        <div id="error-message"></div>

        <form role="form" id="login-form" action="" method="post"><br>
            <div class="mb-3 input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" id="username" name="username" class="form-control" placeholder="Username"
                    required>
            </div>
            <div class="mb-3 input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" id="login-password" name="password" class="form-control" placeholder="Password"
                    required>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100" value="login">Login</button>
        </form>
        <footer class="text-center mt-4 text-muted small">
            <button type="button" id="lupa-password-link"
                class="cursor-pointer text-slate-400 hover:text-blue-500 text-xs p-0 m-0 border-none bg-transparent">Lupa
                Password</button>
        </footer>
    </div>
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
                    <small class="text-slate-400 text-xs italic">Jika tidak mendapatkan pesan, silahkan hubungi
                        admin <a class="hover:text-blue-500 transition-colors" href="https://wa.me/6281329171920"
                            target="_blank">0813-2917-1920</a></small>
                </div>
                <div class="mt-4" id="password-input-1">
                    <label for="new-password" class="block text-sm text-gray-700">Password Baru</label>
                    <input type="password" id="new-password" name="new-password"
                        class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                </div>
                <div class="mt-4" id="password-input-2">
                    <label for="password-confirmation" class="block text-sm text-gray-700">Konfirmasi Password
                        Baru</label>
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
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="./js/app.js"></script>
    <script src="./js/pages/login.js"></script>
    <script src="./js/pages/forgot_password_modal.js"></script>
</body>
