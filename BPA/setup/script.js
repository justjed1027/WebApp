document.addEventListener("DOMContentLoaded", () => {
  const skillInput = document.getElementById("skillInput");
  const skillsBox = document.querySelector(".skills-box");

  skillInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter" && skillInput.value.trim() !== "") {
      e.preventDefault();
      const skillTag = document.createElement("span");
      skillTag.classList.add("skill-tag");
      skillTag.textContent = skillInput.value.trim() + " ✕";

      // Remove skill on click
      skillTag.addEventListener("click", () => {
        skillsBox.removeChild(skillTag);
      });

      skillsBox.insertBefore(skillTag, skillInput);
      skillInput.value = "";
    }
  });

  // Handle form submit
  document.getElementById("profileForm").addEventListener("submit", (e) => {
    e.preventDefault();
    alert("Profile setup complete!");
  });
});
 