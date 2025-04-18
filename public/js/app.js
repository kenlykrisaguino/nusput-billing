// --- PWA Registration ---
if ("serviceWorker" in navigator) {
  window.addEventListener("load", () => {
    navigator.serviceWorker
      .register("/service-worker.js")
      .then((registration) => {
        console.log("ServiceWorker registered: ", registration);
      })
      .catch((registrationError) => {
        console.log("ServiceWorker registration failed: ", registrationError);
      });
  });
}

// // --- Axios Example (Live Update) ---
// function updateStudentCount() {
//   axios
//     .get("/api/students") // Replace with your actual API endpoint
//     .then((response) => {
//       if (response.data.success) {
//         const studentCountElement = document.getElementById("student-count");
//         if (studentCountElement) {
//           studentCountElement.textContent = `Total Students: ${response.data.data.length}`;
//         }
//       } else {
//         console.error(response);
//       }
//     })
//     .catch((error) => {
//       console.error("Error fetching student data:", error);
//     });
// }
//