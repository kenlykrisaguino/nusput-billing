<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Tagihan Nusaputera</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="manifest" href="/manifest.json">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
</head>

<body>
    <div class="w-screen h-screen bg-sky-100">
        <div class="flex items-center justify-center h-full">
            <div class="bg-white p-8 rounded shadow-md w-96">
                <h1 class="text-xl font-semibold mb-4">Login</h1>
                <form id="login-form">
                    <div class="mb-4">
                        <label for="username" class="block text-sm text-gray-700">Username</label>
                        <input type="text" id="username" name="username"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-sm text-gray-700">Password</label>
                        <input type="password" id="password" name="password"
                            class="block w-full px-3 py-2 text-sm text-slate-800 bg-slate-200 rounded-md border-0 shadow-sm focus:outline-none focus:ring-slate-500 focus:bg-slate-100">
                    </div>
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-400 transition-colors cursor-pointer py-2.5 px-4 rounded-md text-sm text-medium text-slate-50">Login</button>
                    <div id="error-message" class="text-red-500 mt-2"></div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="./js/app.js"></script>

    <script>
        const loginForm = document.getElementById('login-form');
        const errorMessage = document.getElementById('error-message');
        const submitButton = loginForm.querySelector('button[type="submit"]');

        if (loginForm && errorMessage && submitButton) {
            loginForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                errorMessage.textContent = '';

                const originalButtonText = submitButton.textContent;
                submitButton.disabled = true;
                submitButton.textContent = 'Logging in...';

                const formData = new FormData(loginForm);

                try {
                    const response = await axios.post('/api/login', formData);

                    console.log('Login Response:', response);

                    if (response.data && response.data.success && response.data.data) {
                        const userRole = response.data.data.role;

                        if (userRole === 'ST') {
                            window.location.href = '/dashboard';
                        } else {
                            window.location.href = '/students';
                        }

                        return;

                    } else {
                        errorMessage.textContent = response.data.message ||
                            'Login failed. Please check your credentials.';
                    }

                } catch (error) {
                    console.error('Login Error:', error);

                    if (error.response && error.response.data && error.response.data.message) {
                        errorMessage.textContent = error.response.data.message;
                    } else if (error.request) {
                        errorMessage.textContent =
                        'Unable to connect to the server. Please check your network.';
                    } else {
                        errorMessage.textContent = 'An unexpected error occurred. Please try again.';
                    }
                } finally {
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                }
            });
        } else {
            console.error("Login form, error message element, or submit button not found!");
        }
    </script>
</body>

</html>
