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
  try {
    const response = await fetch(url, {
      method: "GET",
      headers: { Accept: "application/json" },
    });
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
  defaultOptionValue = "",
  doSort = true,
  selectedValue = null
) {
  const firstOptionBeforeClear =
    keepDefault && selectElement.options[0]
      ? selectElement.options[0].cloneNode(true)
      : null;

  selectElement.innerHTML = "";
  let hasDefaultPlaceholder = false;

  if (firstOptionBeforeClear) {
    selectElement.appendChild(firstOptionBeforeClear);
    firstOptionBeforeClear.value = defaultOptionValue;
    firstOptionBeforeClear.textContent = defaultOptionText;
    firstOptionBeforeClear.disabled = true;
    firstOptionBeforeClear.selected = true;
    hasDefaultPlaceholder = true;
  } else if (defaultOptionText) {
    const placeholderOption = document.createElement("option");
    placeholderOption.textContent = defaultOptionText;
    placeholderOption.value = defaultOptionValue;
    placeholderOption.selected = true;
    placeholderOption.disabled = true;
    selectElement.appendChild(placeholderOption);
    hasDefaultPlaceholder = true;
  }

  if (options && options.length > 0) {
    if (doSort) {
      try {
        options.sort((a, b) => {
          const textA = typeof a === "object" ? a.text || "" : String(a);
          const textB = typeof b === "object" ? b.text || "" : String(b);
          return String(textA).localeCompare(String(textB));
        });
      } catch (e) {
        console.warn("Could not sort options:", options, e);
      }
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
  }

  if (selectedValue !== null) {
    const LCaseSelectedValue = String(selectedValue).toLowerCase();
    const optionToSelect = Array.from(selectElement.options).find(
      (opt) => String(opt.value).toLowerCase() === LCaseSelectedValue
    );
    if (optionToSelect) {
      selectElement.value = optionToSelect.value;
    } else if (hasDefaultPlaceholder) {
      selectElement.selectedIndex = 0;
    }
  } else if (hasDefaultPlaceholder) {
    selectElement.selectedIndex = 0;
  }

  if (options.length === 0) {
    selectElement.disabled = hasDefaultPlaceholder;
  } else {
    selectElement.disabled = false;
  }
}

function resetAndDisableDropdown(
  selectElement,
  defaultOptionText,
  useNoClassText = "-- Tidak Ada Kelas --",
  selectedValue = null
) {
  if (!selectElement) return;
  let placeholderText = defaultOptionText;
  if (
    selectElement.name === "edit-section" ||
    selectElement.name === "section" ||
    selectElement.name === "section-filter"
  ) {
    placeholderText = useNoClassText;
  }
  populateDropdown(
    selectElement,
    [],
    placeholderText,
    true,
    "",
    true,
    selectedValue
  );
  selectElement.disabled = true;
}

async function populateLevels(
  selectElement,
  gradeElement,
  sectionElement,
  selectedLevelValue = null
) {
  if (!selectElement) return;
  populateDropdown(
    selectElement,
    [],
    "Memuat Jenjang...",
    true,
    "",
    true,
    null
  );
  selectElement.disabled = true;

  resetAndDisableDropdown(gradeElement, "Pilih Tingkat");
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
    populateDropdown(
      selectElement,
      levelOptions,
      "Pilih Jenjang",
      true,
      "",
      true,
      selectedLevelValue
    );
  } catch (error) {
    console.error("Failed to populate levels:", error);
    populateDropdown(
      selectElement,
      [],
      "Gagal Memuat Jenjang",
      true,
      "",
      true,
      selectedLevelValue
    );
    selectElement.disabled = true;
  }
}

async function populateGrades(
  selectedLevelId,
  gradeElement,
  sectionElement,
  selectedGradeValue = null
) {
  if (!gradeElement) return;
  populateDropdown(gradeElement, [], "Memuat Tingkat...", true, "", true, null);
  gradeElement.disabled = true;
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
    populateDropdown(
      gradeElement,
      gradeOptions,
      "Pilih Tingkat",
      true,
      "",
      true,
      selectedGradeValue
    );
  } catch (error) {
    console.error(
      `Failed to populate grades for level ID ${selectedLevelId}:`,
      error
    );
    populateDropdown(
      gradeElement,
      [],
      "Gagal Memuat Tingkat",
      true,
      "",
      true,
      selectedGradeValue
    );
    gradeElement.disabled = true;
  }
}

async function populateSections(
  selectedLevelId,
  selectedGradeId,
  sectionElement,
  selectedSectionValue = null
) {
  if (!sectionElement) return;
  populateDropdown(sectionElement, [], "Memuat Kelas...", true, "", true, null);
  sectionElement.disabled = true;

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
    const defaultText =
      sectionOptions.length > 0 ? "Pilih Kelas" : "-- Tidak Ada Kelas --";
    populateDropdown(
      sectionElement,
      sectionOptions,
      defaultText,
      true,
      "",
      true,
      selectedSectionValue
    );
  } catch (error) {
    console.error(
      `Failed to populate sections for ${selectedLevelId}-${selectedGradeId}:`,
      error
    );
    populateDropdown(
      sectionElement,
      [],
      "Gagal Memuat Kelas",
      true,
      "",
      true,
      selectedSectionValue
    );
    sectionElement.disabled = true;
  }
}

const ALL_MONTHS = [
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
];

function updateMonthOptions(
  semesterSelect,
  monthSelect,
  selectedMonthValue = null
) {
  if (!monthSelect || !semesterSelect) return;
  const selectedSemester = semesterSelect.value;
  let M_options = [];

  if (selectedSemester === "GENAP") {
    M_options = ALL_MONTHS.filter((m) => m.value >= 1 && m.value <= 6);
  } else if (selectedSemester === "GASAL") {
    M_options = ALL_MONTHS.filter((m) => m.value >= 7 && m.value <= 12);
  } else {
    M_options = [];
  }
  populateDropdown(
    monthSelect,
    M_options,
    "Pilih Bulan",
    true,
    "",
    false,
    selectedMonthValue
  );
  monthSelect.disabled = M_options.length === 0;
}

document.addEventListener("DOMContentLoaded", async () => {
  console.log("DOM Loaded! Initializing script...");

  const urlParams = new URLSearchParams(window.location.search);
  const initialLevel = urlParams.get("level-filter") || null;
  const initialGrade = urlParams.get("grade-filter") || null;
  const initialSection = urlParams.get("section-filter") || null;
  const initialYear = urlParams.get("year-filter") || null;
  const initialSemester = urlParams.get("semester-filter")
    ? urlParams.get("semester-filter").toUpperCase()
    : null;
  const initialMonth = urlParams.get("month-filter") || null;

  const levelSelectFilter = document.querySelector("#level-filter");
  const gradeSelectFilter = document.querySelector("#grade-filter");
  const sectionSelectFilter = document.querySelector("#section-filter");
  const yearSelect = document.querySelector("#year-filter");
  const semesterSelect = document.querySelector("#semester-filter");
  const monthSelect = document.querySelector("#month-filter");
  const searchInput = document.querySelector("#search");
  const exportButton = document.querySelector("#export-btn");
  const resetButton = document.querySelector("#reset-filter");

  if (levelSelectFilter && gradeSelectFilter && sectionSelectFilter) {
    await populateLevels(
      levelSelectFilter,
      gradeSelectFilter,
      sectionSelectFilter,
      initialLevel
    );
    if (initialLevel && levelSelectFilter.value === initialLevel) {
      await populateGrades(
        initialLevel,
        gradeSelectFilter,
        sectionSelectFilter,
        initialGrade
      );
      if (initialGrade && gradeSelectFilter.value === initialGrade) {
        await populateSections(
          initialLevel,
          initialGrade,
          sectionSelectFilter,
          initialSection
        );
      }
    }
  } else {
    if (levelSelectFilter)
      populateDropdown(
        levelSelectFilter,
        [],
        "Pilih Jenjang",
        true,
        "",
        true,
        initialLevel
      );
    if (gradeSelectFilter)
      resetAndDisableDropdown(gradeSelectFilter, "Pilih Tingkat");
    if (sectionSelectFilter)
      resetAndDisableDropdown(
        sectionSelectFilter,
        "Pilih Kelas",
        "-- Tidak Ada Kelas --"
      );
  }

  if (yearSelect) {
    const currentYear = new Date().getFullYear();
    const yearOptions = [];
    for (let y = currentYear + 1; y >= currentYear - 5; y--) {
      yearOptions.push({ value: `${y}/${y + 1}`, text: `${y}/${y + 1}` });
    }
    populateDropdown(
      yearSelect,
      yearOptions,
      "Pilih Tahun",
      true,
      "",
      true,
      initialYear
    );
  }

  if (semesterSelect) {
    populateDropdown(
      semesterSelect,
      [
        { value: "GASAL", text: "Gasal" },
        { value: "GENAP", text: "Genap" },
      ],
      "Pilih Semester",
      true,
      "",
      true,
      initialSemester
    );
  }

  if (monthSelect && semesterSelect) {
    updateMonthOptions(semesterSelect, monthSelect, initialMonth);
  }

  if (levelSelectFilter) {
    levelSelectFilter.addEventListener("change", async (event) => {
      await populateGrades(
        event.target.value,
        gradeSelectFilter,
        sectionSelectFilter
      );
      resetAndDisableDropdown(
        sectionSelectFilter,
        "Pilih Kelas",
        "-- Tidak Ada Kelas --"
      );
    });
  }

  if (gradeSelectFilter) {
    gradeSelectFilter.addEventListener("change", async (event) => {
      const selectedLevelId = levelSelectFilter
        ? levelSelectFilter.value
        : null;
      await populateSections(
        selectedLevelId,
        event.target.value,
        sectionSelectFilter
      );
    });
  }

  if (semesterSelect && monthSelect) {
    semesterSelect.addEventListener("change", () => {
      updateMonthOptions(semesterSelect, monthSelect);
    });
  }

  if (searchInput && urlParams.has("search")) {
    searchInput.value = urlParams.get("search");
  }

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

  if (resetButton) {
    resetButton.addEventListener("click", async () => {
      console.log("Resetting filters...");

      if (levelSelectFilter) {
        await populateLevels(
          levelSelectFilter,
          gradeSelectFilter,
          sectionSelectFilter,
          null
        );
      } else {
        if (gradeSelectFilter)
          resetAndDisableDropdown(gradeSelectFilter, "Pilih Tingkat");
        if (sectionSelectFilter)
          resetAndDisableDropdown(
            sectionSelectFilter,
            "Pilih Kelas",
            "-- Tidak Ada Kelas --"
          );
      }

      if (yearSelect) {
        const currentYear = new Date().getFullYear();
        const yearOptions = [];
        for (let y = currentYear + 1; y >= currentYear - 5; y--) {
          yearOptions.push({ value: `${y}/${y + 1}`, text: `${y}/${y + 1}` });
        }
        populateDropdown(
          yearSelect,
          yearOptions,
          "Pilih Tahun",
          true,
          "",
          true,
          null
        );
      }

      if (semesterSelect) {
        populateDropdown(
          semesterSelect,
          [
            { value: "GASAL", text: "Gasal" },
            { value: "GENAP", text: "Genap" },
          ],
          "Pilih Semester",
          true,
          "",
          true,
          null
        );
      }

      if (monthSelect && semesterSelect) {
        updateMonthOptions(semesterSelect, monthSelect, null);
      }

      if (searchInput) {
        searchInput.value = "";
      }

      if (window.history.replaceState) {
        const cleanUrl =
          window.location.protocol +
          "//" +
          window.location.host +
          window.location.pathname;
        window.history.replaceState({ path: cleanUrl }, "", cleanUrl);
      }

      console.log("Filters have been reset.");
    });
  }
});
