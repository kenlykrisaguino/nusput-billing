document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const loginErrorMessage = document.getElementById('error-message');
    const loginSubmitButton = loginForm ? loginForm.querySelector('button[type="submit"]') : null;

    if (loginForm && loginErrorMessage && loginSubmitButton) {
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            loginErrorMessage.textContent = '';
            loginErrorMessage.classList.remove('show');

            const originalButtonText = loginSubmitButton.textContent;
            loginSubmitButton.disabled = true;
            loginSubmitButton.textContent = 'Logging in...';

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

                } else {
                    loginErrorMessage.textContent = response.data.message ||
                        'Login failed. Please check your credentials.';
                    loginErrorMessage.classList.add('show');
                }

            } catch (error) {
                console.error('Login Error:', error);

                if (error.response && error.response.data && error.response.data.message) {
                    loginErrorMessage.textContent = error.response.data.message;
                } else if (error.request) {
                    loginErrorMessage.textContent =
                        'Unable to connect to the server. Please check your network.';
                } else {
                    loginErrorMessage.textContent = 'An unexpected error occurred. Please try again.';
                }
                loginErrorMessage.classList.add('show');
            } finally {
                loginSubmitButton.disabled = false;
                loginSubmitButton.textContent = originalButtonText;
            }
        });
    } else {
        console.error("Login form elements not found in login.js!");
    }
});