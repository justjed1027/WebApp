// Course Data
const courses = {
  general: [
    { title: "Mathematics", desc: "Algebra, Geometry, Calculus, and more.", img: "https://picsum.photos/400/200?random=1", key: "math" },
    { title: "English", desc: "Grammar, literature, and writing skills.", img: "https://picsum.photos/400/200?random=2", key: "english" },
    { title: "Science", desc: "Physics, Chemistry, and Biology concepts.", img: "https://picsum.photos/400/200?random=3", key: "science" },
    { title: "Data Science", desc: "Data analysis, AI, and ML foundations.", img: "https://picsum.photos/400/200?random=4", key: "data" },
    { title: "Design", desc: "UI/UX and creative design thinking.", img: "https://picsum.photos/400/200?random=5", key: "design" }
  ],
  math: [
    { title: "Algebra Basics", desc: "Variables, equations, and linear functions.", img: "https://picsum.photos/400/200?random=10" },
    { title: "Geometry", desc: "Shapes, angles, theorems, and proofs.", img: "https://picsum.photos/400/200?random=11" },
    { title: "Calculus", desc: "Limits, derivatives, and integrals.", img: "https://picsum.photos/400/200?random=12" }
  ],
  english: [
    { title: "Grammar Essentials", desc: "Master sentence structure and tenses.", img: "https://picsum.photos/400/200?random=13" },
    { title: "Literature Studies", desc: "Explore classic and modern literature.", img: "https://picsum.photos/400/200?random=14" },
    { title: "Creative Writing", desc: "Develop storytelling and essay skills.", img: "https://picsum.photos/400/200?random=15" }
  ],
  science: [
    { title: "Physics", desc: "Newton’s laws, energy, and motion.", img: "https://picsum.photos/400/200?random=16" },
    { title: "Chemistry", desc: "Atoms, reactions, and periodic table.", img: "https://picsum.photos/400/200?random=17" },
    { title: "Biology", desc: "Cells, genetics, and ecosystems.", img: "https://picsum.photos/400/200?random=18" }
  ],
  data: [
    { title: "Data Analysis", desc: "Work with spreadsheets and SQL.", img: "https://picsum.photos/400/200?random=19" },
    { title: "Machine Learning", desc: "Intro to supervised learning models.", img: "https://picsum.photos/400/200?random=20" },
    { title: "AI Foundations", desc: "Basics of neural networks and NLP.", img: "https://picsum.photos/400/200?random=21" }
  ],
  design: [
    { title: "UI Design", desc: "Typography, colors, and layout principles.", img: "https://picsum.photos/400/200?random=22" },
    { title: "UX Research", desc: "User testing and design thinking.", img: "https://picsum.photos/400/200?random=23" },
    { title: "Prototyping", desc: "Wireframes and interactive mockups.", img: "https://picsum.photos/400/200?random=24" }
  ]
};

// Elements
const courseContainer = document.getElementById("course-container");
const pageTitle = document.getElementById("page-title");
const backBtn = document.getElementById("back-btn");

// Render function
function renderCourses(list, title, isSub = false) {
  courseContainer.innerHTML = ""; // clear
  pageTitle.textContent = title;
  backBtn.style.display = isSub ? "inline-block" : "none";

  list.forEach(course => {
    const card = document.createElement("div");
    card.className = "card";
    card.innerHTML = `
      <img src="${course.img}" alt="${course.title}">
      <div class="card-content">
        <h2>${course.title}</h2>
        <p>${course.desc}</p>
        ${isSub ? `<a href="#" class="btn">View Resources</a>` : `<button class="btn" data-key="${course.key}">View Courses</button>`}
      </div>
    `;
    courseContainer.appendChild(card);
  });

  if (!isSub) {
    document.querySelectorAll(".btn[data-key]").forEach(btn => {
      btn.addEventListener("click", e => {
        const key = e.target.getAttribute("data-key");
        renderCourses(courses[key], courseTitle(key), true);
      });
    });
  }
}

// Convert key to title
function courseTitle(key) {
  switch (key) {
    case "math": return "Mathematics Courses";
    case "english": return "English Courses";
    case "science": return "Science Courses";
    case "data": return "Data Science Courses";
    case "design": return "Design Courses";
    default: return "Courses";
  }
}

// Back button handler
backBtn.addEventListener("click", () => renderCourses(courses.general, "General Courses"));

// Initial render
renderCourses(courses.general, "General Courses");
