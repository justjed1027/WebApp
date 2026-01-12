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
    
    // Initialize students modal functionality
    initializeStudentsModal();
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

// Students Modal Functionality
function initializeStudentsModal() {
    const browseLearners = document.getElementById('browseLearners');
    const findMentors = document.getElementById('findMentors');
    const modal = document.getElementById('studentsModal');
    const closeModal = document.getElementById('closeModal');
    const modalOverlay = modal ? modal.querySelector('.modal-overlay') : null;
    
    if (!modal || (!browseLearners && !findMentors)) return;
    
    let currentSubjectId = null;
    let currentType = null;
    let currentPage = 1;
    
    // Browse Learners button
    if (browseLearners) {
        browseLearners.addEventListener('click', function() {
            currentSubjectId = this.getAttribute('data-subject-id');
            currentType = 'learning';
            currentPage = 1;
            openStudentsModal('Students Learning This Topic', currentSubjectId, currentType, currentPage);
        });
    }
    
    // Find Mentors button
    if (findMentors) {
        findMentors.addEventListener('click', function() {
            currentSubjectId = this.getAttribute('data-subject-id');
            currentType = 'fluent';
            currentPage = 1;
            openStudentsModal('Students Fluent in This Topic', currentSubjectId, currentType, currentPage);
        });
    }
    
    // Close modal
    if (closeModal) {
        closeModal.addEventListener('click', closeStudentsModal);
    }
    
    if (modalOverlay) {
        modalOverlay.addEventListener('click', closeStudentsModal);
    }
    
    // Escape key closes modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeStudentsModal();
        }
    });
    
    function openStudentsModal(title, subjectId, type, page) {
        const modalTitle = document.getElementById('modalTitle');
        const studentsGrid = document.getElementById('modalStudentsGrid');
        
        if (modalTitle) modalTitle.textContent = title;
        if (studentsGrid) studentsGrid.innerHTML = '<div class="search-loading">Loading students...</div>';
        
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Fetch students
        fetchSubjectStudents(subjectId, type, page);
    }
    
    function closeStudentsModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
    
    function fetchSubjectStudents(subjectId, type, page) {
        fetch(`get_subject_students.php?subject_id=${subjectId}&type=${type}&page=${page}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    return;
                }
                
                displayModalStudents(data, subjectId, type);
            })
            .catch(error => {
                console.error('Fetch failed:', error);
                const studentsGrid = document.getElementById('modalStudentsGrid');
                if (studentsGrid) {
                    studentsGrid.innerHTML = '<div class="search-error">Failed to load students. Please try again.</div>';
                }
            });
    }
    
    function displayModalStudents(data, subjectId, type) {
        const { students, total, totalPages, currentPage } = data;
        const studentsGrid = document.getElementById('modalStudentsGrid');
        const countDiv = document.getElementById('modalCount');
        const paginationDiv = document.getElementById('modalPagination');
        
        // Update count
        if (countDiv) {
            countDiv.textContent = `${total} student${total !== 1 ? 's' : ''} found`;
        }
        
        // Display students
        if (students.length === 0) {
            studentsGrid.innerHTML = `
                <div class="search-empty-state" style="display: block; grid-column: 1 / -1;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>
                    </svg>
                    <p>No students found</p>
                    <p class="empty-state-subtitle">Be the first to join!</p>
                </div>
            `;
            paginationDiv.style.display = 'none';
            return;
        }
        
        // Build students HTML
        let html = '';
        students.forEach(student => {
            const fullName = ((student.user_firstname || '') + ' ' + (student.user_lastname || '')).trim();
            const displayName = fullName || 'Student';
            
            let actionButton = '';
            if (student.connection_status === 'accepted') {
                actionButton = `
                    <span class="status-badge connected">Connected</span>
                    <a href="../dms/dms.php?user_id=${student.user_id}" class="btn-message">Message</a>
                `;
            } else if (student.connection_status === 'pending_sent') {
                actionButton = '<span class="status-badge pending">Request Sent</span>';
            } else if (student.connection_status === 'pending_received') {
                actionButton = '<span class="status-badge pending">Pending Response</span>';
            } else {
                actionButton = `
                    <form action="../connections/send_request.php" method="POST" style="display: inline;">
                        <input type="hidden" name="receiver_id" value="${student.user_id}">
                        <button type="submit" class="btn-connect">Connect</button>
                    </form>
                `;
            }
            
            html += `
                <div class="student-card">
                    <a href="../profile/profile.php?user_id=${student.user_id}" style="text-decoration:none;color:inherit;display:block;">
                        <div class="connection-header" style="cursor:pointer;">
                            <div class="user-avatar"></div>
                            <div class="user-info">
                                <h4 class="user-name">${escapeHtml(student.user_username)}</h4>
                                <p class="user-details">${escapeHtml(displayName)}</p>
                            </div>
                        </div>
                    </a>
                    <div class="connection-actions">
                        ${actionButton}
                    </div>
                </div>
            `;
        });
        
        studentsGrid.innerHTML = html;
        
        // Handle pagination
        if (totalPages > 1) {
            displayModalPagination(totalPages, currentPage, subjectId, type);
        } else {
            paginationDiv.style.display = 'none';
        }
    }
    
    function displayModalPagination(totalPages, currentPage, subjectId, type) {
        const paginationDiv = document.getElementById('modalPagination');
        paginationDiv.style.display = 'flex';
        
        let html = `
            <button class="pagination-btn" ${currentPage <= 1 ? 'disabled' : ''} data-page="${currentPage - 1}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
                </svg>
            </button>
            <div class="page-numbers">
        `;
        
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            html += `<button class="page-number" data-page="1">1</button>`;
            if (startPage > 2) {
                html += `<span class="page-ellipsis">...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="page-number${i === currentPage ? ' active' : ''}" data-page="${i}">${i}</button>`;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<span class="page-ellipsis">...</span>`;
            }
            html += `<button class="page-number" data-page="${totalPages}">${totalPages}</button>`;
        }
        
        html += `
            </div>
            <button class="pagination-btn" ${currentPage >= totalPages ? 'disabled' : ''} data-page="${currentPage + 1}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/>
                </svg>
            </button>
        `;
        
        paginationDiv.innerHTML = html;
        
        // Add event listeners
        paginationDiv.querySelectorAll('.page-number, .pagination-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.disabled) return;
                const page = parseInt(this.getAttribute('data-page'));
                fetchSubjectStudents(subjectId, type, page);
            });
        });
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
