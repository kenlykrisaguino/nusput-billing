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

document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM Loaded! Initializing script...");
  const levelSelectFilter = document.querySelector("#level-filter");
  const gradeSelectFilter = document.querySelector("#grade-filter");
  const sectionSelectFilter = document.querySelector("#section-filter");

  if (levelSelectFilter && gradeSelectFilter && sectionSelectFilter) {
    populateLevels(levelSelectFilter, gradeSelectFilter, sectionSelectFilter);
    levelSelectFilter.addEventListener("change", (event) => {
      populateGrades(
        event.target.value,
        levelSelectFilter,
        gradeSelectFilter,
        sectionSelectFilter
      );
    });
    gradeSelectFilter.addEventListener("change", (event) => {
      const selectedLevelId = levelSelectFilter.value;
      populateSections(
        selectedLevelId,
        event.target.value,
        gradeSelectFilter,
        sectionSelectFilter
      );
    });
  }
  const yearSelect = document.querySelector("#year-filter");
  const semesterSelect = document.querySelector("#semester-filter");
  const monthSelect = document.querySelector("#month-filter");
  const searchInput = document.querySelector("#search");
  const exportButton = document.querySelector("#export-btn");
  const currentYear = new Date().getFullYear();
  const yearOptions = [];
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

  if (exportButton) {
    exportButton.addEventListener("click", () => {
      const params = new URLSearchParams();
      if (levelSelectFilter && levelSelectFilter.value) {
        params.append("level-filter", levelSelectFilter.value);
      }
      if (gradeSelectFilter && gradeSelectFilter.value) {
        params.append("grade-filter", gradeSelectFilter.value);
      }
      if (sectionSelectFilter && sectionSelectFilter.value) {
        params.append("section-filter", sectionSelectFilter.value);
      }
      if (yearSelect && yearSelect.value) {
        params.append("year-filter", yearSelect.value);
      }
      if (semesterSelect && semesterSelect.value) {
        params.append("semester-filter", semesterSelect.value);
      }
      if (monthSelect && monthSelect.value) {
        params.append("month-filter", monthSelect.value);
      }
      if (searchInput && searchInput.value) {
        params.append("search", searchInput.value);
      }

      const exportUrl = `/exports/journals?${params.toString()}`;
      console.log("Exporting with URL:", exportUrl);
      window.location.href = exportUrl; 
    });
  }
});
