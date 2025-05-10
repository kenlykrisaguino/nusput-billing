document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("modal-lupa-password");
  const forgotPasswordForm = document.getElementById("forgot-password-form");
  const modalErrorMessage = document.getElementById("modal-error-message");
  const lupaPasswordLink = document.getElementById("lupa-password-link");
  const modalCloseButton = document.getElementById("modal-close-button");
  const modalSubmitButton = document.getElementById("modal-submit-button");

  const initialStepDiv = document.getElementById("initial");
  const otpVerificationDiv = document.getElementById("otp-verification");
  const passwordInput1Div = document.getElementById("password-input-1");
  const passwordInput2Div = document.getElementById("password-input-2");

  const virtualAccountInput = document.getElementById("virtual-account");
  const otpInput = document.getElementById("otp");
  const newPasswordInput = document.getElementById("new-password");
  const passwordConfirmationInput = document.getElementById(
    "password-confirmation"
  );

  let currentStep = 1;

  const API_SEND_OTP = "/api/send-otp";
  const API_VERIFY_OTP = "/api/verify-otp";
  const API_RESET_PASSWORD = "/api/update-password";

  function updateModalUI() {
    initialStepDiv.style.display = "none";
    otpVerificationDiv.style.display = "none";
    passwordInput1Div.style.display = "none";
    passwordInput2Div.style.display = "none";

    modalErrorMessage.textContent = "";
    modalErrorMessage.classList.remove("show");

    switch (currentStep) {
      case 1:
        initialStepDiv.style.display = "block";
        modalSubmitButton.textContent = "Kirim OTP";
        virtualAccountInput.readOnly = false;
        break;
      case 2:
        initialStepDiv.style.display = "block";
        virtualAccountInput.readOnly = true;
        otpVerificationDiv.style.display = "block";
        modalSubmitButton.textContent = "Verifikasi OTP";
        break;
      case 3:
        passwordInput1Div.style.display = "block";
        passwordInput2Div.style.display = "block";
        modalSubmitButton.textContent = "Reset Password";
        break;
      default:
        console.error("Invalid modal step:", currentStep);
        resetModal();
        modal.classList.add("hidden");
        break;
    }
  }

  function resetModal() {
    currentStep = 1;
    virtualAccountInput.value = "";
    otpInput.value = "";
    newPasswordInput.value = "";
    passwordConfirmationInput.value = "";
    virtualAccountInput.readOnly = false;
    updateModalUI();
  }

  if (lupaPasswordLink) {
    lupaPasswordLink.addEventListener("click", () => {
      resetModal();
      modal.classList.remove("hidden");
    });
  } else {
    console.error(
      "'lupa-password-link' element not found in forgot_password_modal.js!"
    );
  }

  if (modalCloseButton) {
    modalCloseButton.addEventListener("click", () => {
      resetModal();
      modal.classList.add("hidden");
    });
  } else {
    console.error(
      "'modal-close-button' element not found in forgot_password_modal.js!"
    );
  }

  if (forgotPasswordForm && modalSubmitButton) {
    forgotPasswordForm.addEventListener("submit", async (event) => {
      event.preventDefault();

      modalErrorMessage.textContent = "";
      modalErrorMessage.classList.remove("show");

      const originalButtonText = modalSubmitButton.textContent;
      modalSubmitButton.disabled = true;
      modalSubmitButton.textContent = "Memproses...";

      try {
        let response;
        let dataToSend = {};

        switch (currentStep) {
          case 1:
            // Step 1: Send OTP
            const contactStep1 = virtualAccountInput.value.trim();
            if (!contactStep1) {
              throw new Error("Virtual Account tidak boleh kosong.");
            }

            dataToSend = { virtual_account: contactStep1 };
            try{
                response = await axios.post(API_SEND_OTP, dataToSend);
                if (response.data && response.data.success) {
                  currentStep++;
                  updateModalUI();
                } else {
                  throw new Error(
                    response.data.message ||
                      "Gagal mengirim OTP. Silahkan coba lagi."
                  );
                }
            } catch (error){
                const errorMessage = error.response?.data?.message ||
                      "Gagal mengirim OTP. Silahkan coba lagi."
                throw new Error(errorMessage);
            }
            break;

          case 2:
            // Step 2: Verify OTP
            const contactStep2 = virtualAccountInput.value.trim();
            const otp = otpInput.value.trim();
            if (!contactStep2 || !otp) {
              throw new Error("Kode OTP tidak boleh kosong.");
            }
            dataToSend = { virtual_account: contactStep2, otp: otp };
            try{
                response = await axios.post(API_VERIFY_OTP, dataToSend); // Use axios here
                
                console.info(response.data);
    
                if (response.data && response.data.success) {
                  currentStep++;
                  updateModalUI();
                } else {
                  throw new Error(
                    response.data.message ||
                      "Verifikasi OTP gagal. Kode OTP salah atau kedaluwarsa."
                  );
                }
                break;
            } catch (error) {
                const errorMessage =
                error.response?.data?.message || "Verifikasi OTP gagal. Kode OTP salah atau kedaluwarsa.";
                throw new Error(errorMessage);
            }

          case 3:
            // Step 3: Reset Password
            const contactStep3 = virtualAccountInput.value.trim(); // Get the contact from step 1
            const otpStep3 = otpInput.value.trim();
            const newPassword = newPasswordInput.value;
            const confirmPassword = passwordConfirmationInput.value;

            if (!contactStep3 || !newPassword || !confirmPassword) {
              throw new Error("Semua field harus diisi.");
            }
            if (newPassword !== confirmPassword) {
              throw new Error("Konfirmasi Password tidak cocok.");
            }

            dataToSend = {
              virtual_account: contactStep3,
              otp: otpStep3,
              password: newPassword,
            };
            try{
                response = await axios.post(API_RESET_PASSWORD, dataToSend); // Use axios here
                console.info(response);
                if (response.data && response.data.success) {
                  alert(
                    "Password berhasil direset. Silahkan login dengan password baru Anda."
                  );
                  resetModal();
                  modal.classList.add("hidden");
                } else {
                  throw new Error(
                    response.data.message ||
                      "Gagal mereset password. Silahkan coba lagi."
                  );
                }
            } catch (error){
                const errorMessage = error.response?.data?.message || "Gagal mereset password. Silahkan coba lagi."
                throw new Error(errorMessage);
            }
            break;

          default:
            console.error("Invalid modal step during submission:", currentStep);
            throw new Error("Status langkah tidak valid.");
        }
      } catch (error) {
        console.error("Modal Form Error:", error);

        let errorMessageText = "Terjadi kesalahan tak terduga.";
        if (error.message) {
          errorMessageText = error.message;
        } else if (
          error.response &&
          error.response.data &&
          error.response.data.message
        ) {
          errorMessageText = error.response.data.message;
        } else if (error.request) {
          errorMessageText =
            "Tidak dapat terhubung ke server. Mohon cek koneksi internet Anda.";
        }

        modalErrorMessage.textContent = errorMessageText;
        modalErrorMessage.classList.add("show");
      } finally {
        modalSubmitButton.disabled = false;
        modalSubmitButton.textContent = originalButtonText;
      }
    });
  } else {
    console.error(
      "Forgot password form elements not found in forgot_password_modal.js!"
    );
  }

  modal.classList.add("hidden");
  resetModal();
});
