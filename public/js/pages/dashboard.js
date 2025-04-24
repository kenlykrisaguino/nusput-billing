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

document.addEventListener("DOMContentLoaded", () => {
  const yearSelect = document.querySelector("#year-filter");
  const semesterSelect = document.querySelector("#semester-filter");
  const yearOptions = [];
  const currentYear = new Date().getFullYear();

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
});
