// Find Students Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme toggle
    initializeThemeToggle();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize sidebar toggle
    initializeSidebarToggle();

    // Ensure connect buttons submit correctly with loading state
    initializeConnectButtons();
});

// Theme toggle functionality
function initializeThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    
    // Check for saved theme preference or default to dark mode
    const currentTheme = localStorage.getItem('theme') || 'dark';
    
    // Apply the current theme
    if (currentTheme === 'light') {
        body.classList.add('light-mode');
    } else {
        body.classList.remove('light-mode');
    }
    
    // Theme toggle event listener
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            body.classList.toggle('light-mode');
            
            // Save the current theme preference
            const isLight = body.classList.contains('light-mode');
            localStorage.setItem('theme', isLight ? 'light' : 'dark');
        });
    }
}

// Ensure connect buttons still submit after showing loading state
function initializeConnectButtons() {
    const connectButtons = document.querySelectorAll('.btn-connect');
    connectButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const originalText = this.innerHTML;
            const form = this.closest('form');

            this.innerHTML = '...';
            this.disabled = true;

            if (form) {
                e.preventDefault();
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit(this);
                } else {
                    form.submit();
                }
            }

            // Fallback restore in case navigation doesn't occur
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 4000);
        });
    });
}

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('studentSearch');
    const studentsGrid = document.getElementById('studentsGrid');
    
    if (!searchInput || !studentsGrid) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const studentCards = studentsGrid.querySelectorAll('.student-card');
        
        studentCards.forEach(card => {
            const studentName = card.getAttribute('data-name');
            
            if (studentName && studentName.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Check if any students are visible
        const visibleCards = Array.from(studentCards).filter(card => card.style.display !== 'none');
        
        // Show/hide empty state based on search results
        let emptyState = studentsGrid.parentElement.querySelector('.search-empty-state');
        
        if (visibleCards.length === 0 && searchTerm !== '') {
            if (!emptyState) {
                emptyState = document.createElement('div');
                emptyState.className = 'search-empty-state empty-state';
                emptyState.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                    </svg>
                    <p>No students found</p>
                    <p class="empty-state-subtitle">Try adjusting your search terms</p>
                `;
                studentsGrid.parentElement.appendChild(emptyState);
            }
            emptyState.style.display = 'block';
            studentsGrid.style.display = 'none';
        } else {
            if (emptyState) {
                emptyState.style.display = 'none';
            }
            studentsGrid.style.display = 'grid';
        }
    });
}

// Sidebar toggle functionality
function initializeSidebarToggle() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('sidebar-collapsed');
        });
    }
}

// Connect button handling with loading states
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-connect')) {
        e.target.disabled = true;
        e.target.textContent = 'Sending...';
        
        // Re-enable after form submission (in case of errors)
        setTimeout(() => {
            if (!e.target.closest('form').submitted) {
                e.target.disabled = false;
                e.target.textContent = 'Connect';
            }
        }, 3000);
    }
});

// Form submission handling
document.addEventListener('submit', function(e) {
    if (e.target.querySelector('.btn-connect')) {
        e.target.submitted = true;
    }
});