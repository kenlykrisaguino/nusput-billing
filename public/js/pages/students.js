let publicFeeCategories = [];

async function getPublicFeeCategories() {
  if (publicFeeCategories && publicFeeCategories.length > 0) {
    return publicFeeCategories;
  }
  try {
    console.log("Fetching public fee categories...");
    const response = await fetch("/api/get-fee-categories"); // Corrected endpoint?
    const result = await response.json();
    if (!response.ok || !result.success) {
      throw new Error(
        result.message || `Failed to fetch fee categories (${response.status})`
      );
    }
    publicFeeCategories = result.data || [];
    console.log("Public Fee Categories fetched:", publicFeeCategories);
    return publicFeeCategories;
  } catch (error) {
    console.error("Error fetching public fee categories:", error);
    alert("Gagal memuat kategori biaya tambahan.");
    return [];
  }
}

async function getClassesData(level = "", grade = "", section = "") {
  const levelParam = typeof level === "object" ? level?.id ?? "" : level;
  const gradeParam = typeof grade === "object" ? grade?.id ?? "" : grade;
  const sectionParam =
    typeof section === "object" ? section?.id ?? "" : section;
  const params = new URLSearchParams({
    level: levelParam,
    grade: gradeParam,
    section: sectionParam,
  });
  const url = `/api/filter-classes?${params.toString()}`;
  console.log("Fetching:", url);
  try {
    const response = await fetch(url, {
      method: "GET",
      headers: { Accept: "application/json" },
    });
    console.log(
      `Response Status for ${url}:`,
      response.status,
      response.statusText
    );
    if (!response.ok) {
      let errorBody = `Status: ${response.status} ${response.statusText}`;
      try {
        const text = await response.text();
        console.error(`Error response body for ${url}:`, text);
        errorBody = text || errorBody;
      } catch (e) {}
      throw new Error(
        `HTTP error! Failed to fetch from ${url}. Details: ${errorBody}`
      );
    }
    const contentType = response.headers.get("content-type");
    if (!contentType || !contentType.includes("application/json")) {
      console.warn(
        `Received non-JSON response from ${url}. Content-Type: ${contentType}`
      );
      throw new Error(`Expected JSON response, but received ${contentType}`);
    }
    const result = await response.json();
    console.log(`Data received from ${url}:`, result);
    if (!result || !result.success) {
      console.error(
        `API Error from ${url}:`,
        result?.message || "Unknown API error"
      );
      throw new Error(result?.message || "API request was not successful.");
    }
    return result.data || { levels: [], grades: [], sections: [], details: [] };
  } catch (error) {
    console.error(`Failed to fetch or process data from ${url}:`, error);
    throw error;
  }
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

function resetAndDisableDropdown(
  selectElement,
  defaultOptionText,
  useDefaultText = "-- Tidak Ada Kelas --"
) {
  if (
    selectElement.name === "edit-section" ||
    selectElement.name === "section"
  ) {
    populateDropdown(selectElement, [], useDefaultText, true, "");
  } else {
    populateDropdown(selectElement, [], defaultOptionText, true, "");
  }
  selectElement.disabled = true;
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.add("hidden");
  const form = modal.querySelector("form");
  if (form) {
    form.reset();
    if (form.newFeeIndex) {
      delete form.newFeeIndex;
    } // Reset counter

    if (id === "create-student") {
      resetAndDisableDropdown(modal.querySelector("#grade"), "Pilih Tingkat");
      resetAndDisableDropdown(
        modal.querySelector("#section"),
        "Pilih Kelas",
        "-- Tidak Ada Kelas --"
      );
      const levelSelectModal = modal.querySelector("#level");
      if (levelSelectModal) levelSelectModal.selectedIndex = 0;
    }
    if (id === "edit-student") {
      resetAndDisableDropdown(
        modal.querySelector("#edit-grade"),
        "Pilih Tingkat"
      );
      resetAndDisableDropdown(
        modal.querySelector("#edit-section"),
        "Pilih Kelas",
        "-- Tidak Ada Kelas --"
      );
      const editLevelSelectModal = modal.querySelector("#edit-level");
      if (editLevelSelectModal) editLevelSelectModal.selectedIndex = 0;
      const feesContainer = modal.querySelector("#additional-fees-container");
      if (feesContainer) feesContainer.innerHTML = "";
    }
  }
  const submitButton = form?.querySelector(
    'button[type="submit"], button[onclick^="submitEditStudent"]'
  );
  if (submitButton) {
    submitButton.disabled = false;
    submitButton.textContent = id === "create-student" ? "Tambah" : "Edit";
    submitButton.classList.add("cursor-pointer");
    submitButton.classList.remove("cursor-progress");
  }
}

function showEditTab(id, element) {
  const modal = element.closest(".fixed");
  if (!modal) return;
  const tabs = modal.querySelectorAll(".tab-content");
  const buttons = modal.querySelectorAll(".tab-button");
  tabs.forEach((tab) => tab.classList.add("hidden"));
  buttons.forEach((btn) => {
    btn.classList.remove("border-blue-600");
    btn.classList.add("border-transparent");
  });
  const targetTab = modal.querySelector(`#${id}-tab`);
  if (targetTab) targetTab.classList.remove("hidden");
  element.classList.remove("border-transparent");
  element.classList.add("border-blue-600");
}

async function populateLevels(selectElement, gradeElement, sectionElement) {
  if (!selectElement) return;
  resetAndDisableDropdown(selectElement, "Memuat Jenjang...");
  if (gradeElement) resetAndDisableDropdown(gradeElement, "Pilih Tingkat");
  if (sectionElement)
    resetAndDisableDropdown(
      sectionElement,
      "Pilih Kelas",
      "-- Tidak Ada Kelas --"
    );
  try {
    const responseData = await getClassesData();
    const levelOptions = responseData.levels.map((level) => ({
      value: level.id,
      text: level.name,
    }));
    populateDropdown(selectElement, levelOptions, "Pilih Jenjang");
  } catch (error) {
    console.error("Failed to populate levels:", error);
    populateDropdown(selectElement, [], "Gagal Memuat Jenjang", true);
  }
}

async function populateGrades(
  selectedLevelId,
  levelElement,
  gradeElement,
  sectionElement
) {
  if (!gradeElement) return;
  resetAndDisableDropdown(gradeElement, "Memuat Tingkat...");
  if (sectionElement)
    resetAndDisableDropdown(
      sectionElement,
      "Pilih Kelas",
      "-- Tidak Ada Kelas --"
    );
  if (!selectedLevelId) {
    resetAndDisableDropdown(gradeElement, "Pilih Tingkat");
    return;
  }
  try {
    const responseData = await getClassesData(selectedLevelId);
    const gradeOptions = responseData.grades.map((grade) => ({
      value: grade.id,
      text: grade.name,
    }));
    populateDropdown(gradeElement, gradeOptions, "Pilih Tingkat");
  } catch (error) {
    console.error(
      `Failed to populate grades for level ID ${selectedLevelId}:`,
      error
    );
    populateDropdown(gradeElement, [], "Gagal Memuat Tingkat", true);
  }
}

async function populateSections(
  selectedLevelId,
  selectedGradeId,
  gradeElement,
  sectionElement
) {
  if (!sectionElement) return;
  resetAndDisableDropdown(sectionElement, "Memuat Kelas...");
  if (!selectedLevelId || !selectedGradeId) {
    resetAndDisableDropdown(
      sectionElement,
      "Pilih Kelas",
      "-- Tidak Ada Kelas --"
    );
    return;
  }
  try {
    const responseData = await getClassesData(selectedLevelId, selectedGradeId);
    const sectionOptions = responseData.sections.map((section) => ({
      value: section.id,
      text: section.name,
    }));
    populateDropdown(sectionElement, sectionOptions, "-- Tidak Ada Kelas --");
  } catch (error) {
    console.error(
      `Failed to populate sections for ${selectedLevelId}-${selectedGradeId}:`,
      error
    );
    populateDropdown(sectionElement, [], "Gagal Memuat Kelas", true);
  }
}

function removeFeeRow(button) {
  const rowDiv = button.closest(".fee-row");
  const container = document.getElementById("additional-fees-container");
  if (rowDiv) {
    rowDiv.remove();
  }
  if (container && !container.querySelector(".fee-row")) {
    const modal = container.closest("#edit-student");
    if (modal) {
      const yearSelect = modal.querySelector("#edit-academic-year");
      const semesterSelect = modal.querySelector("#edit-semester");
      const monthSelect = modal.querySelector("#edit-month");
      if (yearSelect.value && semesterSelect.value && monthSelect.value) {
        container.innerHTML =
          '<p class="text-xs text-slate-500 italic">Tidak ada biaya tambahan untuk periode ini.</p>';
      } else {
        container.innerHTML =
          '<p class="text-xs text-slate-500 italic">Pilih Tahun Ajaran, Semester, dan Bulan untuk melihat/mengedit biaya tambahan.</p>';
      }
    }
  }
}

function addNewFeeRow() {
  const container = document.getElementById("additional-fees-container");
  if (!container) {
    console.error("Container #additional-fees-container not found.");
    return;
  }
  const modal = container.closest("#edit-student");
  if (!modal) return;
  const form = modal.querySelector("form");
  if (!form) return;
  const yearSelect = form.querySelector("#edit-academic-year");
  const semesterSelect = form.querySelector("#edit-semester");
  const monthSelect = form.querySelector("#edit-month");
  if (!yearSelect?.value || !semesterSelect?.value || !monthSelect?.value) {
    alert("Silakan pilih Tahun Ajaran, Semester, dan Bulan terlebih dahulu.");
    return;
  }
  const placeholder = container.querySelector("p.text-slate-500");
  if (placeholder) {
    container.innerHTML = "";
  }
  if (!publicFeeCategories || publicFeeCategories.length === 0) {
    alert("Kategori biaya tambahan belum dimuat. Coba lagi nanti.");
    return;
  }
  if (typeof form.newFeeIndex !== "number") {
    form.newFeeIndex = 1;
  }
  const newIndex = form.newFeeIndex++;
  const feeDiv = document.createElement("div");
  feeDiv.className = "grid grid-cols-12 gap-2 items-center fee-row new-fee-row";
  const selectContainer = document.createElement("div");
  selectContainer.className = "col-span-5";
  const categorySelect = document.createElement("select");
  categorySelect.name = `new_fee[${newIndex}][category]`; // Changed name format
  categorySelect.className =
    "block w-full px-2 py-1 text-xs text-slate-800 bg-slate-100 rounded-md border border-slate-300 shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500";
  populateDropdown(
    categorySelect,
    publicFeeCategories.map((cat) => ({ value: cat.id, text: cat.name })),
    "Pilih Kategori",
    false
  );
  selectContainer.appendChild(categorySelect);
  const inputContainer = document.createElement("div");
  inputContainer.className = "col-span-5";
  const amountInput = document.createElement("input");
  amountInput.type = "number";
  amountInput.step = "any";
  amountInput.name = `new_fee[${newIndex}][amount]`; // Changed name format
  amountInput.placeholder = "Jumlah";
  amountInput.className =
    "block w-full px-2 py-1 text-xs text-slate-800 bg-slate-100 rounded-md border border-slate-300 shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500";
  inputContainer.appendChild(amountInput);
  const removeContainer = document.createElement("div");
  removeContainer.className = "col-span-2 text-right";
  const removeButton = document.createElement("button");
  removeButton.type = "button";
  removeButton.title = "Hapus Biaya Ini";
  removeButton.className = "text-red-500 hover:text-red-700 transition-colors";
  removeButton.innerHTML = '<i class="fa-solid fa-trash-can text-xs"></i>';
  removeButton.onclick = function () {
    removeFeeRow(this);
  };
  removeContainer.appendChild(removeButton);
  feeDiv.appendChild(selectContainer);
  feeDiv.appendChild(inputContainer);
  feeDiv.appendChild(removeContainer);
  container.appendChild(feeDiv);
}

async function loadAdditionalFees(userId, year, semester, month) {
  const container = document.getElementById("additional-fees-container");
  if (!container) {
    console.error("Container #additional-fees-container not found.");
    return;
  }
  container.innerHTML =
    '<p class="text-xs text-slate-500 italic">Memuat biaya tambahan...</p>';
  if (!userId || !year || !semester || !month) {
    container.innerHTML =
      '<p class="text-xs text-slate-500 italic">Pilih Tahun Ajaran, Semester, dan Bulan untuk melihat/mengedit biaya tambahan.</p>';
    return;
  }
  const params = new URLSearchParams({
    user_id: userId,
    year: year,
    semester: semester,
    month: month,
  });
  try {
    console.log(`Fetching fees with params: ${params.toString()}`);
    const response = await fetch(`/api/get-student-fees?${params.toString()}`);
    const result = await response.json();
    console.log("Additional Fees Response:", result);
    if (!response.ok || !result.success) {
      throw new Error(
        result.message || `Failed to fetch fees (${response.status})`
      );
    }
    container.innerHTML = "";
    if (result.data && result.data.length > 0) {
      result.data.forEach((fee) => {
        const feeDiv = document.createElement("div");
        feeDiv.className =
          "grid grid-cols-12 gap-2 items-center fee-row existing-fee-row";
        const labelContainer = document.createElement("div");
        labelContainer.className = "col-span-5";
        const label = document.createElement("label");
        label.className = "text-xs text-slate-700 truncate";
        label.textContent = fee.name || "Biaya Lain";
        label.htmlFor = `edit_additional_fee[${fee.id}]`;
        label.title = fee.name || "Biaya Lain"; // Use edit_ format for existing
        labelContainer.appendChild(label);
        const inputContainer = document.createElement("div");
        inputContainer.className = "col-span-5";
        const input = document.createElement("input");
        input.type = "number";
        input.step = "any";
        input.name = `edit_additional_fee[${fee.id}]`; // Use edit_ format for existing
        input.id = `edit_additional_fee[${fee.id}]`;
        input.value = fee.amount || 0;
        input.className =
          "block w-full px-2 py-1 text-xs text-slate-800 bg-slate-100 rounded-md border border-slate-300 shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500";
        inputContainer.appendChild(input);
        const removeContainer = document.createElement("div");
        removeContainer.className = "col-span-2 text-right";
        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.title = "Hapus Biaya Ini (Akan dihapus saat disimpan)";
        removeButton.className =
          "text-red-500 hover:text-red-700 transition-colors";
        removeButton.innerHTML =
          '<i class="fa-solid fa-trash-can text-xs"></i>';
        removeButton.onclick = function () {
          removeFeeRow(this);
        };
        removeContainer.appendChild(removeButton);
        feeDiv.appendChild(labelContainer);
        feeDiv.appendChild(inputContainer);
        feeDiv.appendChild(removeContainer);
        container.appendChild(feeDiv);
      });
    } else {
      container.innerHTML =
        '<p class="text-xs text-slate-500 italic">Tidak ada biaya tambahan untuk periode ini.</p>';
    }
  } catch (error) {
    console.error("Error loading additional fees:", error);
    container.innerHTML = `<p class="text-xs text-red-500 italic">Gagal memuat biaya tambahan: ${error.message}</p>`;
  }
}

async function editStudent(userId) {
  const modal = document.getElementById("edit-student");
  if (!modal) return;
  const form = modal.querySelector("#edit-student-form");
  if (form) {
    form.reset();
    form.newFeeIndex = 1;
  } // Reset and initialize index
  const infoTabButton = modal.querySelector('button[onclick*="information"]');
  if (infoTabButton) showEditTab("information", infoTabButton);
  console.log(`Fetching data for User ID: ${userId}`);
  modal.classList.remove("hidden");
  const editLevelSelect = modal.querySelector("#edit-level");
  const editGradeSelect = modal.querySelector("#edit-grade");
  const editSectionSelect = modal.querySelector("#edit-section");
  const feesContainer = modal.querySelector("#additional-fees-container");
  resetAndDisableDropdown(editLevelSelect, "Memuat...");
  resetAndDisableDropdown(editGradeSelect, "Memuat...");
  resetAndDisableDropdown(editSectionSelect, "-- Tidak Ada Kelas --");
  if (feesContainer)
    feesContainer.innerHTML =
      '<p class="text-xs text-slate-500 italic">Pilih Tahun Ajaran, Semester, dan Bulan untuk melihat/mengedit biaya tambahan.</p>';
  await getPublicFeeCategories();
  try {
    const response = await fetch(`/api/get-student-detail?user_id=${userId}`);
    const result = await response.json();
    console.log("Student Detail Response:", result);
    if (!response.ok || !result.success) {
      throw new Error(
        result.message || `Failed to fetch student data (${response.status})`
      );
    }
    const studentData = result.data;
    if (!studentData) {
      throw new Error("No student data found in the API response.");
    }
    modal.querySelector("#edit-user-id").value = userId;
    modal.querySelector("#edit-nis").value = studentData.nis || "";
    modal.querySelector("#edit-name").value = studentData.name || "";
    modal.querySelector("#edit-dob").value = studentData.dob || "";
    modal.querySelector("#edit-phone-number").value = studentData.phone || "";
    modal.querySelector("#edit-email-address").value = studentData.email || "";
    modal.querySelector("#edit-parent-phone").value =
      studentData.parent_phone || "";
    modal.querySelector("#edit-address").value = studentData.address || "";
    modal.querySelector("#edit-monthly-fee").value =
      studentData.monthly_fee || "";
    await populateLevels(editLevelSelect, editGradeSelect, editSectionSelect);
    editLevelSelect.value = studentData.level_id || "";
    if (editLevelSelect.value) {
      await populateGrades(
        studentData.level_id,
        editLevelSelect,
        editGradeSelect,
        editSectionSelect
      );
      editGradeSelect.value = studentData.grade_id || "";
      if (editGradeSelect.value) {
        await populateSections(
          studentData.level_id,
          studentData.grade_id,
          editGradeSelect,
          editSectionSelect
        );
        editSectionSelect.value = studentData.section_id ?? "";
      } else {
        resetAndDisableDropdown(
          editSectionSelect,
          "-- Tidak Ada Kelas --",
          true
        );
      }
    } else {
      resetAndDisableDropdown(editGradeSelect, "Pilih Tingkat", true);
      resetAndDisableDropdown(editSectionSelect, "-- Tidak Ada Kelas --", true);
    }
    const yearSelect = modal.querySelector("#edit-academic-year");
    const semesterSelect = modal.querySelector("#edit-semester");
    const monthSelect = modal.querySelector("#edit-month");
    const currentYear = new Date().getFullYear();
    const yearOptions = [];
    // Ensure year value is just the starting year if backend expects that
    for (let y = currentYear + 1; y >= currentYear - 5; y--) {
      yearOptions.push({ value: `${y}/${y + 1}`, text: `${y}/${y + 1}` });
    }
    populateDropdown(yearSelect, yearOptions, "Pilih Tahun", true);
    populateDropdown(
      semesterSelect,
      [
        { value: 1, text: "Ganjil" },
        { value: 2, text: "Genap" },
      ],
      "Pilih Semester",
      true
    );
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
    const loadFeesHandler = () => {
      loadAdditionalFees(
        userId,
        yearSelect.value,
        semesterSelect.value,
        monthSelect.value
      );
    };
    yearSelect.removeEventListener("change", yearSelect._loadFeesHandler);
    semesterSelect.removeEventListener(
      "change",
      semesterSelect._loadFeesHandler
    );
    monthSelect.removeEventListener("change", monthSelect._loadFeesHandler);
    yearSelect._loadFeesHandler = loadFeesHandler;
    semesterSelect._loadFeesHandler = loadFeesHandler;
    monthSelect._loadFeesHandler = loadFeesHandler;
    yearSelect.addEventListener("change", loadFeesHandler);
    semesterSelect.addEventListener("change", loadFeesHandler);
    monthSelect.addEventListener("change", loadFeesHandler);
  } catch (error) {
    console.error(`Failed to load student data for ID ${userId}:`, error);
    alert(`Gagal memuat data siswa: ${error.message}`);
    closeModal("edit-student");
  }
}

async function submitStudent(event) {
  event.preventDefault();
  const form = event.target;
  const fileInput =
    form.querySelector("#bulk-students") ||
    document.getElementById("bulk-students");
  const submitButton = form.querySelector('button[type="submit"]');
  const originalButtonText = "Tambah";
  submitButton.disabled = true;
  submitButton.textContent = "Processing...";
  submitButton.classList.remove("cursor-pointer");
  submitButton.classList.add("cursor-progress");
  let url;
  let requestBody;
  let headers = { Accept: "application/json" };
  let isBulkUpload = false;
  if (fileInput && fileInput.files && fileInput.files.length > 0) {
    isBulkUpload = true;
    url = "/api/upload-students-bulk";
    requestBody = new FormData();
    requestBody.append("bulk-students", fileInput.files[0]);
    console.log("Preparing bulk upload (FormData)");
  } else {
    isBulkUpload = false;
    url = "/api/upload-student";
    const dataToSend = {};
    const formDataForValidation = new FormData(form);
    const alwaysRequiredFields = [
      "nis",
      "name",
      "parent_phone",
      "level",
      "grade",
    ];
    let missingField = false;
    let firstMissingFieldName = "";
    for (const fieldName of alwaysRequiredFields) {
      const value = formDataForValidation.get(fieldName);
      if (!value || (typeof value === "string" && value.trim() === "")) {
        const fieldElement = form.elements[fieldName];
        const label =
          fieldElement?.labels?.[0]?.textContent ||
          fieldElement?.previousElementSibling?.textContent ||
          fieldName;
        firstMissingFieldName = label.replace(":", "").replace("*", "").trim();
        missingField = true;
        break;
      }
      dataToSend[fieldName] = value.trim();
    }
    const optionalFields = ["dob", "phone", "email", "address", "section"];
    optionalFields.forEach((fieldName) => {
      const value = formDataForValidation.get(fieldName);
      if (value !== null && typeof value !== "undefined" && value !== "") {
        dataToSend[fieldName] = value;
      }
    });
    if (!missingField) {
      const sectionSelect = form.elements["section"];
      if (sectionSelect) {
        const hasAvailableSections =
          sectionSelect.options.length > 1 &&
          !sectionSelect.disabled &&
          sectionSelect.options[0].value !== "";
        const sectionValue = dataToSend["section"];
        if (hasAvailableSections && (!sectionValue || sectionValue === "")) {
          missingField = true;
          const label =
            sectionSelect.labels?.[0]?.textContent ||
            sectionSelect.previousElementSibling?.textContent ||
            "section";
          firstMissingFieldName = label
            .replace(":", "")
            .replace("*", "")
            .trim();
        }
      }
    }
    if (missingField) {
      alert(`Error: Field "${firstMissingFieldName}" wajib diisi.`);
      submitButton.disabled = false;
      submitButton.textContent = originalButtonText;
      submitButton.classList.add("cursor-pointer");
      submitButton.classList.remove("cursor-progress");
      return;
    }
    headers["Content-Type"] = "application/json";
    requestBody = JSON.stringify(dataToSend);
    console.info("Preparing single student upload (JSON):", dataToSend);
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
        a.download = "import_student_errors.xlsx";
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(downloadUrl);
        if (typeof closeModal === "function") {
          closeModal("create-student");
        }
      } else {
        alert(
          result.message ||
            (isBulkUpload
              ? "File berhasil diproses!"
              : "Data siswa berhasil ditambahkan!")
        );
        if (typeof closeModal === "function") {
          closeModal("create-student");
        }
        location.reload();
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

async function submitEditStudent(button) {
  const form = button.closest("form");
  if (!form) {
    console.error("Could not find form for edit button");
    return;
  }
  const url = "/api/update-student";
  const submitButton = button;
  const originalButtonText = "Edit";

  const dataToSend = {};
  const tempFormData = new FormData(form);

  dataToSend["user_id"] = parseInt(tempFormData.get("user_id"), 10);
  dataToSend["edit-nis"] = tempFormData.get("edit-nis");
  dataToSend["edit-name"] = tempFormData.get("edit-name");
  dataToSend["edit-dob"] = tempFormData.get("edit-dob");
  dataToSend["edit-phone-number"] = tempFormData.get("edit-phone-number");
  dataToSend["edit-email-address"] = tempFormData.get("edit-email-address");
  dataToSend["edit-parent-phone"] = tempFormData.get("edit-parent-phone");
  dataToSend["edit-address"] = tempFormData.get("edit-address");
  dataToSend["edit-level"] = tempFormData.get("edit-level");
  dataToSend["edit-grade"] = tempFormData.get("edit-grade");
  dataToSend["edit-section"] = tempFormData.get("edit-section");
  dataToSend["edit-monthly-fee"] = tempFormData.get("edit-monthly-fee");
  dataToSend["edit-academic-year"] = tempFormData.get("edit-academic-year");
  dataToSend["edit-semester"] = tempFormData.get("edit-semester");
  dataToSend["edit-month"] = tempFormData.get("edit-month");

  // Process new fees into the desired array structure
  dataToSend["new_fee"] = [];
  const newFeeRows = form.querySelectorAll(".new-fee-row");
  newFeeRows.forEach((row) => {
    const categorySelect = row.querySelector('select[name^="new_fee["]');
    const amountInput = row.querySelector('input[name^="new_fee["]');
    if (
      categorySelect &&
      amountInput &&
      categorySelect.value &&
      amountInput.value
    ) {
      dataToSend["new_fee"].push({
        category: parseInt(categorySelect.value, 10),
        amount: parseFloat(amountInput.value),
      });
    }
  });

  const alwaysRequiredFields = [
    "edit-nis",
    "edit-name",
    "edit-parent-phone",
    "edit-level",
    "edit-grade",
    "edit-monthly-fee",
  ];
  let missingField = false;
  let firstMissingFieldName = "";
  for (const fieldName of alwaysRequiredFields) {
    const value = dataToSend[fieldName]; // Check the constructed object
    if (!value || (typeof value === "string" && value.trim() === "")) {
      const fieldElement = form.querySelector(`[name="${fieldName}"]`);
      const label = fieldElement?.labels?.[0]?.textContent || fieldName;
      firstMissingFieldName = label.replace(":", "").replace("*", "").trim();
      missingField = true;
      break;
    }
  }
  if (missingField) {
    alert(`Error: Field "${firstMissingFieldName}" wajib diisi.`);
    return;
  }

  submitButton.disabled = true;
  submitButton.textContent = "Processing...";
  submitButton.classList.remove("cursor-pointer");
  submitButton.classList.add("cursor-progress");
  console.log(
    "Submitting Edit Form Data (JSON) to:",
    url,
    JSON.stringify(dataToSend, null, 2)
  ); 

  try {
    const response = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json", 
        Accept: "application/json",
      },
      body: JSON.stringify(dataToSend), 
    });
    const result = await response.json();
    if (!response.ok) {
      console.error("Server Error:", response.status, result);
      throw new Error(result.message || `Error ${response.status}`);
    }
    alert(result.message || "Data siswa berhasil diperbarui!");
    closeModal("edit-student");
    console.info(result)
    // location.reload();
  } catch (error) {
    console.error("Fetch Error:", error);
    alert(`Terjadi kesalahan: ${error.message}`);
  } finally {
    submitButton.disabled = false;
    submitButton.textContent = originalButtonText;
    submitButton.classList.add("cursor-pointer");
    submitButton.classList.remove("cursor-progress");
  }
}

document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM Loaded! Initializing script...");
  const createStudentModal = document.getElementById("create-student");
  const editStudentModal = document.getElementById("edit-student");
  if (createStudentModal) {
    const levelSelectCreate = createStudentModal.querySelector("#level");
    const gradeSelectCreate = createStudentModal.querySelector("#grade");
    const sectionSelectCreate = createStudentModal.querySelector("#section");
    const createForm = createStudentModal.querySelector("#create-student-form");
    if (levelSelectCreate && gradeSelectCreate && sectionSelectCreate) {
      populateLevels(levelSelectCreate, gradeSelectCreate, sectionSelectCreate);
      levelSelectCreate.addEventListener("change", (event) => {
        populateGrades(
          event.target.value,
          levelSelectCreate,
          gradeSelectCreate,
          sectionSelectCreate
        );
      });
      gradeSelectCreate.addEventListener("change", (event) => {
        const selectedLevelId = levelSelectCreate.value;
        populateSections(
          selectedLevelId,
          event.target.value,
          gradeSelectCreate,
          sectionSelectCreate
        );
      });
    }
    if (createForm) {
      createForm.addEventListener("submit", submitStudent);
    }
  }
  if (editStudentModal) {
    const levelSelectEdit = editStudentModal.querySelector("#edit-level");
    const gradeSelectEdit = editStudentModal.querySelector("#edit-grade");
    const sectionSelectEdit = editStudentModal.querySelector("#edit-section");
    if (levelSelectEdit && gradeSelectEdit && sectionSelectEdit) {
      levelSelectEdit.addEventListener("change", (event) => {
        populateGrades(
          event.target.value,
          levelSelectEdit,
          gradeSelectEdit,
          sectionSelectEdit
        );
      });
      gradeSelectEdit.addEventListener("change", (event) => {
        const selectedLevelId = levelSelectEdit.value;
        populateSections(
          selectedLevelId,
          event.target.value,
          gradeSelectEdit,
          sectionSelectEdit
        );
      });
    }
  }
});
