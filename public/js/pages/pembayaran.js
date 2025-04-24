function closeModal(id) {
  const modal = document.getElementById(id);

  modal.classList.add("hidden");

  const inputs = modal.querySelectorAll("input");
  const selects = modal.querySelectorAll("select");
  const textareas = modal.querySelectorAll("textarea");

  inputs.forEach((input) => {
    if (input.type === "file") {
      input.value = null;
    } else {
      input.value = "";
    }
  });

  selects.forEach((select) => {
    select.selectedIndex = 0;
  });

  textareas.forEach((textarea) => {
    textarea.value = "";
  });
}

function populateDropdown(
  selectElement,
  options,
  defaultOptionText,
  keepDefault = false,
  defaultValue = ""
) {
  const defaultOption =
    keepDefault && selectElement.options[0] ? selectElement.options[0] : null;
  selectElement.innerHTML = "";
  let hasDefault = false;
  if (defaultOption) {
    selectElement.appendChild(defaultOption);
    defaultOption.value = defaultValue;
    defaultOption.selected = true;
    defaultOption.disabled = true;
    defaultOption.textContent = defaultOptionText;
    hasDefault = true;
  } else if (defaultOptionText) {
    const firstOption = document.createElement("option");
    firstOption.textContent = defaultOptionText;
    firstOption.value = defaultValue;
    firstOption.selected = true;
    firstOption.disabled = true;
    selectElement.appendChild(firstOption);
    hasDefault = true;
  }
  if (options && options.length > 0) {
    try {
      options.sort((a, b) => {
        const textA = typeof a === "object" ? a.text || "" : a;
        const textB = typeof b === "object" ? b.text || "" : b;
        return String(textA).localeCompare(String(textB));
      });
    } catch (e) {
      console.warn("Could not sort options:", options, e);
    }
    options.forEach((opt) => {
      const optionElement = document.createElement("option");
      if (typeof opt === "object" && opt !== null && opt.value !== undefined) {
        optionElement.value = opt.value;
        optionElement.textContent =
          opt.text !== undefined ? opt.text : opt.value;
      } else {
        optionElement.value = opt;
        optionElement.textContent = opt;
      }
      selectElement.appendChild(optionElement);
    });
    selectElement.disabled = false;
  } else {
    selectElement.disabled = hasDefault && options.length === 0;
  }
}

async function importPaymentXLSX(event) {
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
    console.error("No File Input Found!");
    exit();
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

  const yearSelect = document.querySelector("#year-filter");
  const monthSelect = document.querySelector("#month-filter");
  const currentYear = new Date().getFullYear();
  const yearOptions = [];
  for (let y = currentYear + 1; y >= currentYear - 5; y--) {
    yearOptions.push({ value: `${y}/${y + 1}`, text: `${y}/${y + 1}` });
  }
  populateDropdown(yearSelect, yearOptions, "Pilih Tahun", true);

  populateDropdown(
    monthSelect,
    [
      { value: 1, text: "Januari" },
      { value: 2, text: "Februari" },
      { value: 3, text: "Maret" },
      { value: 4, text: "April" },
      { value: 5, text: "Mei" },
      { value: 6, text: "Juni" },
      { value: 7, text: "Juli" },
      { value: 8, text: "Agustus" },
      { value: 9, text: "September" },
      { value: 10, text: "Oktober" },
      { value: 11, text: "November" },
      { value: 12, text: "Desember" },
    ],
    "Pilih Bulan",
    true
  );
});
