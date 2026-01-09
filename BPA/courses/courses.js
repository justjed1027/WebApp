// Theme Toggle (matches calendar functionality)
document.addEventListener('DOMContentLoaded', function() {
    // Theme initialization - use same system as calendar
    const themeToggleSidebar = document.getElementById('themeToggle');
    const body = document.body;
    const savedTheme = localStorage.getItem('theme');
    
    // Apply saved theme on load
    if (savedTheme === 'light') {
        body.classList.add('light-mode');
    }
    
    // Theme toggle function
    function toggleTheme() {
        body.classList.toggle('light-mode');
        localStorage.setItem('theme', body.classList.contains('light-mode') ? 'light' : 'dark');
    }
    
    // Add event listener to sidebar toggle
    if (themeToggleSidebar) {
        themeToggleSidebar.addEventListener('click', toggleTheme);
    }

    // Initialize search functionality for all pages
    initializeSearchFunctionality();
    
    // Initialize course list filtering and sorting if on course-list page
    initializeCourseList();
    
    // Initialize course detail tabs if on course-detail page
    initializeCourseTabs();
});

// Universal Search Functionality
function initializeSearchFunctionality() {
    const searchInput = document.getElementById('search-input');
    
    if (!searchInput) return;
    
    // Detect which page we're on
    const courseGroups = document.querySelectorAll('.course-group-card');
    const courseCards = document.querySelectorAll('.course-list-card');
    
    // If on main courses page, search course groups
    if (courseGroups.length > 0 && courseCards.length === 0) {
        searchInput.addEventListener('input', function() {
            searchCourses(this.value);
        });
    }
    // If on course-list page, the filterAndSort function will handle search via event listener
    // No need to add duplicate listener as initializeCourseList() already handles it
}

// Course List Page - Filtering and Sorting
function initializeCourseList() {
    const levelFilter = document.getElementById('level-filter');
    const sortSelect = document.getElementById('sort-select');
    const searchInput = document.getElementById('search-input');
    const courseCards = document.querySelectorAll('.course-list-card');
    
    if (!courseCards.length) return;
    
    function filterAndSort() {
        const levelValue = levelFilter ? levelFilter.value : 'all';
        // If no sort control exists, do not sort; preserve original DOM order
        const sortValue = sortSelect ? sortSelect.value : null;
        const searchValue = searchInput ? searchInput.value.toLowerCase() : '';
        
        // Convert NodeList to array for sorting
        const cardsArray = Array.from(courseCards);
        
        // Filter courses
        cardsArray.forEach(card => {
            const level = card.dataset.level;
            const title = card.querySelector('h3').textContent.toLowerCase();
            const instructorEl = card.querySelector('.course-instructor');
            const instructor = instructorEl ? instructorEl.textContent.toLowerCase() : '';
            
            const matchesLevel = levelValue === 'all' || level === levelValue;
            const matchesSearch = searchValue === '' || 
                                 title.includes(searchValue) || 
                                 instructor.includes(searchValue);
            
            card.style.display = (matchesLevel && matchesSearch) ? 'flex' : 'none';
        });
        
        // Sort visible courses only if a sort control exists
        const visibleCards = cardsArray.filter(card => card.style.display !== 'none');
        const container = document.querySelector('.courses-list-grid');
        
        if (container && sortValue) {
            visibleCards.sort((a, b) => {
                switch(sortValue) {
                    case 'popular':
                        const studentsA = parseInt(a.dataset.students);
                        const studentsB = parseInt(b.dataset.students);
                        return studentsB - studentsA;
                    
                    case 'rating':
                        const ratingA = parseFloat(a.dataset.rating);
                        const ratingB = parseFloat(b.dataset.rating);
                        return ratingB - ratingA;
                    
                    case 'duration':
                        const durationA = parseInt(a.dataset.duration);
                        const durationB = parseInt(b.dataset.duration);
                        return durationA - durationB;
                    
                    case 'name':
                        const nameA = a.querySelector('h3').textContent;
                        const nameB = b.querySelector('h3').textContent;
                        return nameA.localeCompare(nameB);
                    
                    default:
                        return 0;
                }
            });
            
            // Re-append sorted cards
            visibleCards.forEach(card => container.appendChild(card));
        }
        
        // Update count
        const countDisplay = document.querySelector('.course-count-display span');
        if (countDisplay) {
            countDisplay.textContent = visibleCards.length;
        }
    }
    
    // Add event listeners
    if (levelFilter) {
        levelFilter.addEventListener('change', filterAndSort);
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', filterAndSort);
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', filterAndSort);
    }
}

// Course Detail Page - Tab Switching
function initializeCourseTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    if (!tabButtons.length || !tabContents.length) return;
    
    // Set initial active tab content
    if (tabContents.length > 0) {
        tabContents[0].classList.add('active');
    }
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.dataset.tab;
            
            // Remove active class from all tabs and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            button.classList.add('active');
            const targetContent = document.getElementById(targetTab);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });
}

// Sidebar navigation active state
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        const link = item.querySelector('a');
        if (link && link.getAttribute('href') === currentPage) {
            item.classList.add('active');
        }
    });
});

// Search functionality for main courses page (if needed)
function searchCourses(query) {
    const courseGroups = document.querySelectorAll('.course-group-card');
    const lowerQuery = query.toLowerCase();
    
    courseGroups.forEach(group => {
        const title = group.querySelector('h3').textContent.toLowerCase();
        const description = group.querySelector('p').textContent.toLowerCase();
        
        if (title.includes(lowerQuery) || description.includes(lowerQuery)) {
            group.style.display = 'flex';
        } else {
            group.style.display = 'none';
        }
    });
}

// Smooth scroll for internal links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
