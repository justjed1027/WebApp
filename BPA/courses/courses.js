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

// Event Modal and Pagination Functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeEventModal();
    initializeEventPagination();
});

function initializeEventModal() {
    const modal = document.getElementById('eventModal');
    const modalClose = document.getElementById('modalClose');
    const modalOverlay = document.getElementById('modalOverlay');
    const btnExpandDescription = document.getElementById('btnExpandDescription');
    const btnRegister = document.getElementById('btnRegisterEvent');
    const viewDetailsButtons = document.querySelectorAll('.course-view-details');
    
    if (!modal) return;
    
    let currentEventId = null;
    
    // View Details buttons
    viewDetailsButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            openEventModal(eventId);
        });
    });
    
    // Close modal
    if (modalClose) {
        modalClose.addEventListener('click', closeModal);
    }
    
    if (modalOverlay) {
        modalOverlay.addEventListener('click', closeModal);
    }
    
    // Expand description
    if (btnExpandDescription) {
        btnExpandDescription.addEventListener('click', function() {
            const descText = document.getElementById('modalDescription');
            descText.classList.toggle('expanded');
            btnExpandDescription.textContent = descText.classList.contains('expanded') ? 'Read less' : 'Read more';
        });
    }
    
    // Register button
    if (btnRegister) {
        btnRegister.addEventListener('click', async function() {
            if (!currentEventId) return;
            
            btnRegister.disabled = true;
            btnRegister.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="M11.251.068a.5.5 0 0 1 .227.58L9.677 6.5H13a.5.5 0 0 1 .364.843l-8 8.5a.5.5 0 0 1-.842-.49L6.323 9.5H3a.5.5 0 0 1-.364-.843l8-8.5a.5.5 0 0 1 .615-.09z"/></svg> Registering...';
            
            try {
                const response = await fetch('../events/register_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ eventId: currentEventId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Success - close modal and reload page to update event list
                    closeModal();
                    location.reload();
                } else {
                    alert(data.message || 'Failed to register for event');
                    btnRegister.disabled = false;
                    btnRegister.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/> </svg> Register for Event';
                }
            } catch (error) {
                console.error('Registration error:', error);
                alert('Failed to register for event. Please try again.');
                btnRegister.disabled = false;
                btnRegister.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/></svg> Register for Event';
            }
        });
    }
    
    // Escape key closes modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.hasAttribute('hidden')) {
            closeModal();
        }
    });
    
    function openEventModal(eventId) {
        // Find event in courseEventsData
        if (typeof courseEventsData === 'undefined') {
            console.error('Course events data not available');
            return;
        }
        
        const event = courseEventsData.find(e => e.events_id == eventId);
        if (!event) {
            console.error('Event not found:', eventId);
            return;
        }
        
        currentEventId = eventId;
        populateModal(event);
        modal.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        modal.setAttribute('hidden', '');
        document.body.style.overflow = '';
        currentEventId = null;
        const descText = document.getElementById('modalDescription');
        if (descText) {
            descText.classList.remove('expanded');
        }
        if (btnExpandDescription) {
            btnExpandDescription.textContent = 'Read more';
        }
        if (btnRegister) {
            btnRegister.disabled = false;
            btnRegister.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/></svg> Register for Event';
        }
    }
    
    function populateModal(event) {
        // Get event image
        const imageUrl = getEventImage(event);
        const modalImage = document.getElementById('modalImage');
        const modalTitle = document.getElementById('modalTitle');
        
        if (modalImage) {
            modalImage.src = imageUrl;
            modalImage.alt = event.events_title || 'Event';
        }
        
        if (modalTitle) {
            modalTitle.textContent = event.events_title || 'Event';
        }
        
        // Format date
        const eventDate = new Date(event.events_date + 'T00:00:00');
        const dateStr = eventDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const timeRange = getTimeRange(event.events_start, event.events_end);
        
        const modalDate = document.getElementById('modalDate');
        if (modalDate) {
            modalDate.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                </svg>
                <span>${dateStr}</span>
            `;
        }
        
        const modalTime = document.getElementById('modalTime');
        if (modalTime) {
            modalTime.innerHTML = timeRange ? `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
                </svg>
                <span>${timeRange}</span>
            ` : '';
        }
        
        const modalLocation = document.getElementById('modalLocation');
        if (modalLocation) {
            modalLocation.innerHTML = event.events_location ? `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/>
                </svg>
                <span>${event.events_location}</span>
            ` : '';
        }
        
        // Participants
        const capacity = event.events_capacity || 0;
        const count = event.registration_count || 0;
        const participantWord = count === 1 ? 'participant' : 'participants';
        const participantText = capacity > 0 ? `${count}/${capacity} ${participantWord}` : `${count} ${participantWord}`;
        
        const modalParticipants = document.getElementById('modalParticipants');
        if (modalParticipants) {
            modalParticipants.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                </svg>
                <span>${participantText}</span>
            `;
        }
        
        // Tags
        const subjects = event.subjects ? event.subjects.split(',').map(s => s.trim()) : [];
        const modalTags = document.getElementById('modalTags');
        if (modalTags) {
            modalTags.innerHTML = subjects.map(subject => 
                `<span class="event-tag">#${subject.toLowerCase().replace(/\s+/g, '')}</span>`
            ).join('');
        }
        
        const modalDescription = document.getElementById('modalDescription');
        if (modalDescription) {
            modalDescription.textContent = event.events_description || 'No description available.';
        }
        
        // Event details
        const modalCategory = document.getElementById('modalCategory');
        if (modalCategory) {
            modalCategory.textContent = subjects[0] || 'General';
        }
        
        // Host profile (actual user who created the event)
        const modalCreatorAvatar = document.getElementById('modalCreatorAvatar');
        const modalCreator = document.getElementById('modalCreator');
        const modalCreatorRole = document.getElementById('modalCreatorRole');

        if (modalCreator) {
            const hostName = (event.user_firstname || event.user_lastname)
                ? `${event.user_firstname || ''} ${event.user_lastname || ''}`.trim()
                : (event.user_username || 'Event Host');
            modalCreator.textContent = hostName;
            modalCreator.href = `../profile/profile.php?user_id=${event.host_user_id}`;
            modalCreator.style.cursor = 'pointer';
        }
        if (modalCreatorAvatar && event.profile_filepath) {
            modalCreatorAvatar.src = event.profile_filepath;
        }
        if (modalCreatorRole) {
            modalCreatorRole.textContent = event.user_username || '';
        }
        
        const capacityText = event.events_capacity ? `${event.events_capacity} spots` : 'Unlimited';
        const modalCapacity = document.getElementById('modalCapacity');
        if (modalCapacity) {
            modalCapacity.textContent = capacityText;
        }
        
        let deadlineStr = 'Open';
        if (event.events_deadline) {
            const deadlineDate = new Date(event.events_deadline + 'T00:00:00');
            const deadlineDateStr = deadlineDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            deadlineStr = `Open until ${deadlineDateStr}`;
        }
        const modalRegistration = document.getElementById('modalRegistration');
        if (modalRegistration) {
            modalRegistration.textContent = deadlineStr;
        }
    }
    
    function getEventImage(event) {
        if (event.events_img) return event.events_img;
        const subject = (event.subjects || '').toLowerCase();
        if (subject.includes('computer') || subject.includes('programming')) {
            return 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=1000&h=280&fit=crop';
        } else if (subject.includes('data') || subject.includes('science')) {
            return 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1000&h=280&fit=crop';
        } else if (subject.includes('design') || subject.includes('ui') || subject.includes('ux')) {
            return 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=1000&h=280&fit=crop';
        }
        return 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1000&h=280&fit=crop';
    }
    
    function formatTime(timeStr) {
        if (!timeStr) return '';
        if (timeStr.includes(':') && !timeStr.includes('-')) {
            const [hours, minutes] = timeStr.split(':');
            const hour = parseInt(hours);
            const minute = parseInt(minutes);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minute.toString().padStart(2, '0')} ${ampm}`;
        }
        const date = new Date(timeStr);
        if (isNaN(date.getTime())) return '';
        return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    }
    
    function getTimeRange(startTime, endTime) {
        if (!startTime && !endTime) return '';
        if (startTime && endTime) {
            const start = formatTime(startTime);
            const end = formatTime(endTime);
            return `${start} - ${end}`;
        }
        return formatTime(startTime || endTime);
    }
}

function initializeEventPagination() {
    const eventsList = document.getElementById('eventsList');
    const prevBtn = document.getElementById('eventsPrevPage');
    const nextBtn = document.getElementById('eventsNextPage');
    const pageNumbers = document.getElementById('eventsPageNumbers');
    
    if (!eventsList) return;
    
    const eventsPerPage = 3;
    let currentPage = 1;
    const eventItems = eventsList.querySelectorAll('.event-item');
    const totalEvents = eventItems.length;
    const totalPages = Math.ceil(totalEvents / eventsPerPage);
    
    function showPage(page) {
        currentPage = page;
        
        // Hide all events
        eventItems.forEach((item, index) => {
            const startIndex = (page - 1) * eventsPerPage;
            const endIndex = startIndex + eventsPerPage;
            
            if (index >= startIndex && index < endIndex) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
        
        // Update pagination buttons
        if (prevBtn) {
            prevBtn.disabled = page <= 1;
            prevBtn.setAttribute('data-page', page - 1);
        }
        
        if (nextBtn) {
            nextBtn.disabled = page >= totalPages;
            nextBtn.setAttribute('data-page', page + 1);
        }
        
        // Update page number buttons
        if (pageNumbers) {
            const pageNumButtons = pageNumbers.querySelectorAll('.page-number');
            pageNumButtons.forEach(btn => {
                const btnPage = parseInt(btn.getAttribute('data-page'));
                if (btnPage === page) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }
        
        // Re-attach event listeners to visible View Details buttons
        const visibleButtons = eventsList.querySelectorAll('.event-item:not([style*="display: none"]) .course-view-details');
        visibleButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const eventId = this.getAttribute('data-event-id');
                const modal = document.getElementById('eventModal');
                if (modal && typeof courseEventsData !== 'undefined') {
                    const event = courseEventsData.find(e => e.events_id == eventId);
                    if (event) {
                        // Trigger the modal open (reuse the existing function)
                        this.click();
                    }
                }
            });
        });
    }
    
    // Initial page display
    showPage(1);
    
    // Previous button
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            if (!this.disabled) {
                const page = parseInt(this.getAttribute('data-page'));
                showPage(page);
            }
        });
    }
    
    // Next button
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            if (!this.disabled) {
                const page = parseInt(this.getAttribute('data-page'));
                showPage(page);
            }
        });
    }
    
    // Page number buttons
    if (pageNumbers) {
        const pageNumButtons = pageNumbers.querySelectorAll('.page-number');
        pageNumButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const page = parseInt(this.getAttribute('data-page'));
                showPage(page);
            });
        });
    }
}
