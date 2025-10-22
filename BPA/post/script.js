// ===== INITIALIZATION ON DOM LOAD =====
document.addEventListener('DOMContentLoaded', () => {
  // ===== SIDEBAR TOGGLE FUNCTIONALITY =====
  const sidebar = document.getElementById('sidebar');
  
  // Double-click anywhere to toggle sidebar
  document.addEventListener('dblclick', (e) => {
    // Prevent toggling when double-clicking on input fields or buttons
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'BUTTON' || e.target.tagName === 'A') {
      return;
    }
    
    sidebar.classList.toggle('collapsed');
    
    // Save state to localStorage
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebarCollapsed', isCollapsed);
  });

  // Restore sidebar state on page load
  const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
  if (sidebarCollapsed) {
    sidebar.classList.add('collapsed');
  }

  // ===== THEME TOGGLE FUNCTIONALITY =====
  // Note: Full theme system will be implemented later with database integration
  const themeToggle = document.getElementById('themeToggle');
  let isDarkMode = true; // Default to dark mode

  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      isDarkMode = !isDarkMode;
      
      // Toggle body class for theme (for future implementation)
      if (isDarkMode) {
        document.body.classList.remove('light-mode');
      } else {
        document.body.classList.add('light-mode');
      }
      
      // Save preference to localStorage
      localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
      
      // TODO: Later this will save to database via AJAX
      // saveThemePreference(isDarkMode ? 'dark' : 'light');
    });
  }

  // Restore theme preference on page load
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'light') {
    isDarkMode = false;
    document.body.classList.add('light-mode');
  }
});

// ===== ACTIVE NAV LINK HIGHLIGHTING =====
// Automatically highlight active nav link based on current page
document.addEventListener('DOMContentLoaded', () => {
  const currentPage = window.location.pathname.split('/').pop();
  const navLinks = document.querySelectorAll('.nav-link');
  
  navLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (href && href.includes(currentPage)) {
      // Remove active class from all links
      navLinks.forEach(l => l.classList.remove('active'));
      // Add active class to current link
      link.classList.add('active');
    }
  });
});

// ===== POSTS RENDERING =====
// Simulated posts (later will come from PHP + DB)
const posts = [
  {
    id: 1,
    user: "Alex Johnson",
    time: "2 hours ago",
    field: "Computer Science",
    content: "Does anyone have experience implementing a binary search tree in JavaScript? I'm struggling with the delete operation."
  },
  {
    id: 2,
    user: "Morgan Lee",
    time: "5 hours ago",
    field: "UX Design",
    content: "Just finished this UI design guide for mobile applications. Hope this helps everyone working on app projects this semester!"
  },
  {
    id: 3,
    user: "Sarah Chen",
    time: "8 hours ago",
    field: "Data Science",
    content: "Check out this amazing visualization I created using D3.js for analyzing student performance metrics!"
  },
  {
    id: 4,
    user: "Marcus Rodriguez",
    time: "1 day ago",
    field: "Web Development",
    content: "Anyone interested in collaborating on a React project? Looking to build a study group scheduler app."
  }
];

// Render posts
function renderPosts() {
  const container = document.getElementById("posts-container");
  container.innerHTML = "";

  posts.forEach(post => {
    const postEl = document.createElement("div");
    postEl.className = "post";

    postEl.innerHTML = `
      <div class="post-header">
        <span class="post-user">${escapeHtml(post.user)} · <small>${escapeHtml(post.field)}</small></span>
        <span class="post-time">${escapeHtml(post.time)}</span>
      </div>
      <div class="post-content">
        <p>${escapeHtml(post.content)}</p>
      </div>
    `;

    container.appendChild(postEl);
  });
}

// Helper function to escape HTML and prevent XSS
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

document.addEventListener("DOMContentLoaded", renderPosts);

// ===== SEARCH FUNCTIONALITY (PLACEHOLDER) =====
const searchInput = document.querySelector('.search-input');
if (searchInput) {
  searchInput.addEventListener('input', (e) => {
    const searchTerm = e.target.value.toLowerCase();
    // TODO: Implement search functionality
    console.log('Searching for:', searchTerm);
  });
}

// ===== SMOOTH ANIMATIONS =====
// Add entrance animations for posts
document.addEventListener('DOMContentLoaded', () => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 50);
      }
    });
  }, { threshold: 0.1 });

  setTimeout(() => {
    document.querySelectorAll('.post').forEach(post => {
      post.style.opacity = '0';
      post.style.transform = 'translateY(20px)';
      post.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
      observer.observe(post);
    });
  }, 100);
});
