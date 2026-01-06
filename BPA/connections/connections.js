// Connections Page JavaScript
// Extends shared functionality from sidecontent.js

document.addEventListener('DOMContentLoaded', function() {
  // Initialize theme toggle
  initializeThemeToggle();
  
  // Initialize search functionality
  const searchInput = document.getElementById('connections-search');
  if (searchInput) {
    searchInput.addEventListener('input', handleConnectionsSearch);
  }

  // Initialize Find Students button
  const findStudentsBtn = document.querySelector('.find-students-btn');
  if (findStudentsBtn) {
    findStudentsBtn.addEventListener('click', handleFindStudents);
  }

  // Initialize connection action buttons
  initializeConnectionActions();
});

function initializeThemeToggle() {
  const themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', function() {
      document.body.classList.toggle('light-mode');
      
      // Save theme preference
      const isLight = document.body.classList.contains('light-mode');
      localStorage.setItem('theme', isLight ? 'light' : 'dark');
    });
    
    // Apply saved theme
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
      document.body.classList.add('light-mode');
    }
  }
}

function handleConnectionsSearch(event) {
  const query = event.target.value.toLowerCase();
  
  // Search through connection cards
  const connectionCards = document.querySelectorAll('.connection-card, .recommendation-card');
  connectionCards.forEach(card => {
    const name = card.querySelector('.user-name');
    if (name) {
      const nameText = name.textContent.toLowerCase();
      const shouldShow = nameText.includes(query) || query === '';
      card.style.display = shouldShow ? '' : 'none';
    }
  });

  // If no results found, show message
  updateSearchResults(query);
}

function updateSearchResults(query) {
  if (query && document.querySelectorAll('.connection-card:not([style*="display: none"]), .recommendation-card:not([style*="display: none"])').length === 0) {
    console.log('No connections found for:', query);
  }
}

function handleFindStudents() {
  // Focus on the search input and update placeholder
  const searchInput = document.getElementById('connections-search');
  if (searchInput) {
    searchInput.focus();
    searchInput.placeholder = 'Start typing to find students...';
  }
}

function initializeConnectionActions() {
  // Add loading states to connection buttons
  const actionButtons = document.querySelectorAll('.btn-accept, .btn-decline, .btn-connect');
  
  actionButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      // Add loading state
      const originalText = this.innerHTML;
      this.innerHTML = '...';
      this.disabled = true;
      
      // Re-enable after form submission (fallback)
      setTimeout(() => {
        this.innerHTML = originalText;
        this.disabled = false;
      }, 3000);
    });
  });

  // Legacy button interactions
  document.querySelectorAll('.btn-connect').forEach(btn => {
    btn.addEventListener('click', function() {
      btn.textContent = 'Connected';
      btn.disabled = true;
      btn.style.background = '#e5e7eb';
      btn.style.color = '#888';
      btn.style.cursor = 'default';
    });
  });

  document.querySelectorAll('.side-follow').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      btn.textContent = 'Following';
      btn.style.color = '#888';
      btn.style.pointerEvents = 'none';
    });
  });
}

// Export functions for potential external use
window.ConnectionsPage = {
  handleConnectionsSearch,
  handleFindStudents
};