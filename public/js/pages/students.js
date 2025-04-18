async function getClasses(level = "", grade = "", section = "") {
  const url = `/api/filter-classes?level=${level}&grade=${grade}&section=${section}`;

  console.log(url);

  try {
    const response = await fetch(url, {
      method: "GET",
      headers: {
        Accept: "application/json",
      },
    });

    console.log("Response Status:", response.status, response.statusText);
    console.log(response)
    if (!response.ok) {
      try {
        errorBody = await response.text();
        console.error("Error response body:", errorBody);
      } catch (e) {
        console.error("Could not read error response body:", e);
      }
      throw new Error(
        `HTTP error! Status: ${response.status} ${response.statusText}. Body: ${errorBody}`
      );
    }
    const data = await response.json();
    return data;
  } catch (error) {
    console.error("Failed to fetch classes:", error);
    throw error;
  }
}

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

function showEditTab(id, element) {
  const tabs = document.querySelectorAll(".tab-content");
  const buttons = document.querySelectorAll(".tab-button");

  tabs.forEach((tab) => tab.classList.add("hidden"));
  buttons.forEach((btn) => btn.classList.remove("border-blue-600"));
  buttons.forEach((btn) => btn.classList.add("border-transparent"));

  document.getElementById(`${id}-tab`).classList.remove("hidden");
  element.classList.remove("border-transparent");
  element.classList.add("border-blue-600");
}

function editStudent(va) {
  document.getElementById("edit-student").classList.remove("hidden");
}

async function submitStudent(event) {
  event.preventDefault();

  const form = event.target;
  const fileInput = document.getElementById("bulk-students");
  const submitButton = form.querySelector('button[type="submit"]');

  submitButton.disabled = true;
  submitButton.textContent = "Processing...";
  submitButton.classList.remove("cursor-pointer");
  submitButton.classList.add("cursor-progress");

  let url;
  let formData;
  let isBulkUpload = false;

  if (fileInput.files && fileInput.files.length > 0) {
    isBulkUpload = true;
    url = "/api/upload-students-bulk";
    formData = new FormData();
    formData.append("bulk-students", fileInput.files[0]);
  } else {
    url = "/api/upload-student";
    formData = new FormData(form);

    const requiredFields = [
      "nis",
      "name",
      "parent_phone",
      "level",
      "grade",
      "section",
    ];
    let missingField = false;
    for (const fieldName of requiredFields) {
      if (!formData.get(fieldName)) {
        const fieldElement = form.querySelector(`[name="${fieldName}"]`);
        const label =
          fieldElement.previousElementSibling?.textContent || fieldName;
        missingField = true;
        break;
      }
    }
    if (missingField) {
      submitButton.disabled = false;
      submitButton.textContent = "Tambah";
      submitButton.classList.add("cursor-pointer");
      submitButton.classList.remove("cursor-progress");
      return;
    }
  }
}
