

// Get open buttons
const openStudentPopupBtn = document.getElementById("openStudentPopupBtn");
const openFacultyPopupBtn = document.getElementById("openFacultyPopupBtn");
const openGuestPopupBtn = document.getElementById("openGuestPopupBtn");

// Get popup overlays
const studentPopup = document.getElementById("studentPopup");
const facultyPopup = document.getElementById("facultyPopup");
const guestPopup = document.getElementById("guestPopup");

// Get close buttons
const closeStudentPopupBtn = document.getElementById("closeStudentPopupBtn");
const closeFacultyPopupBtn = document.getElementById("closeFacultyPopupBtn");
const closeGuestPopupBtn = document.getElementById("closeGuestPopupBtn");

// Function to open popup
function openPopup(popup) {
  popup.style.display = "flex";
}

// Function to close popup
function closePopup(popup) {
  popup.style.display = "none";
}

// Open popup
openStudentPopupBtn.addEventListener("click", () => openPopup(studentPopup));
openFacultyPopupBtn.addEventListener("click", () => openPopup(facultyPopup));
openGuestPopupBtn.addEventListener("click", () => openPopup(guestPopup));

// Close popup
closeStudentPopupBtn.addEventListener("click", () => closePopup(studentPopup));
closeFacultyPopupBtn.addEventListener("click", () => closePopup(facultyPopup));
closeGuestPopupBtn.addEventListener("click", () => closePopup(guestPopup));

//Button vissibilty
document.addEventListener("DOMContentLoaded", () => {
  const tabButtons = document.querySelectorAll(".tab-button");
  const popupButtons = document.querySelectorAll(".popup-button");

  if (!tabButtons.length || !popupButtons.length) return;

  function updatePopupVisibility(activeTab) {
    popupButtons.forEach((popupBtn) => {
      if (popupBtn.dataset.target === activeTab) {
        popupBtn.style.display = "inline-block";
      } else {
        popupBtn.style.display = "none";
      }
    });
  }

  // initial visibility
  const initialTab = document.querySelector(".tab-button.active")?.dataset?.target;
  if (initialTab) updatePopupVisibility(initialTab);

  // handle tab clicks
  tabButtons.forEach((tabBtn) => {
    tabBtn.addEventListener("click", () => {
      updatePopupVisibility(tabBtn.dataset.target);
    });
  });
});
