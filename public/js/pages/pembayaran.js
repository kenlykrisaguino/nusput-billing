function closeModal (id) {
    const modal = document.getElementById(id)

    modal.classList.add('hidden')

    const inputs = modal.querySelectorAll('input')
    const selects = modal.querySelectorAll('select')
    const textareas = modal.querySelectorAll('textarea')

    inputs.forEach(input => {
        if (input.type === 'file') {
            input.value = null
        } else {
            input.value = ''
        }
    })

    selects.forEach(select => {
        select.selectedIndex = 0
    })

    textareas.forEach(textarea => {
        textarea.value = ''
    })
}

async function importPaymentXLSX(event){
  event.preventDefault();
  const form = event.target;
  const fileInput =
    form.querySelector("#bulk-payments") ||
    document.getElementById("bulk-payments");
  const submitButton = form.querySelector('button[type="submit"]');
  const originalButtonText = "Import";

  submitButton.disabled = true;
  submitButton.textContent = "Processing...";
  submitButton.classList.remove("cursor-pointer");
  submitButton.classList.add("cursor-progress");

  let url;
  let requestBody;
  let headers = { Accept: "application/json" };

  if (fileInput && fileInput.files && fileInput.files.length > 0) {
    isBulkUpload = true;
    url = "/api/import-payment";
    requestBody = new FormData();
    requestBody.append("import-payments", fileInput.files[0]);
    console.log("Preparing import payments");
  } else {
    console.error("No File Input Found!")
    exit()
  }

  try {
    console.log(`Sending request to ${url}`);
    const response = await fetch(url, {
      method: "POST",
      headers: headers,
      body: requestBody,
    });
    console.info("Response Status:", response.status);
    console.info("Response OK:", response.ok);
    let result;
    try {
      const responseText = await response.text();
      console.log("Raw Response Body:", responseText);
      if (responseText) {
        result = JSON.parse(responseText);
        console.log("Parsed JSON Response:", result);
      } else {
        result = {
          success: response.ok,
          message: response.ok ? "Success (No Content)" : "Error (No Content)",
        };
        console.log("Empty response body, assuming success based on status.");
      }
    } catch (e) {
      console.error("Failed to parse JSON response:", e);
      if (!response.ok) {
        throw new Error(
          response.statusText || `Server error ${response.status}`
        );
      } else {
        alert("Received an unexpected response format from the server.");
        result = { message: "Unexpected response format." };
      }
    }
    if (!result.success && !response.ok) {
      console.error("Server Error:", response.status, result);
      const errorMessage =
        result?.message ||
        (Array.isArray(result?.errors)
          ? result.errors.join(", ")
          : `Error ${response.status}`);
      alert(errorMessage);
    } else {
      if (
        isBulkUpload &&
        response.headers.get("Content-Type")?.includes("spreadsheetml.sheet")
      ) {
        alert(
          "Import failed. Please check the downloaded error report file for details."
        );
        const blob = await response.blob();
        const downloadUrl = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = downloadUrl;
        a.download = "import_payments_errors.xlsx";
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(downloadUrl);
        if (typeof closeModal === "function") {
          closeModal("import-payment");
        }
      } else {
        alert(
          result.message ||
            (isBulkUpload
              ? "File berhasil diproses!"
              : "Data pembayaran berhasil ditambahkan!")
        );
        if (typeof closeModal === "function") {
          closeModal("import-payment");
        }
        // location.reload();
      }
    }
  } catch (error) {
    console.error("Fetch or Processing Error:", error);
    alert(`Terjadi kesalahan: ${error.message}`);
  } finally {
    submitButton.disabled = false;
    submitButton.textContent = originalButtonText;
    submitButton.classList.add("cursor-pointer");
    submitButton.classList.remove("cursor-progress");
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const importPaymentModal = document.getElementById("import-payment");
  const importForm = importPaymentModal.querySelector("#import-payment-form");
  importForm.addEventListener("submit", importPaymentXLSX);

});
