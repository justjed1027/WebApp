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
    
    // Initialize pagination
    initializePagination();
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

// Handle connect buttons with AJAX to avoid page reload
function initializeConnectButtons() {
    const connectButtons = document.querySelectorAll('.btn-connect');
    connectButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const form = this.closest('form');
            if (!form) return;
            
            const receiverId = form.querySelector('input[name="receiver_id"]').value;
            const connectionActions = this.closest('.connection-actions');
            
            // Show loading state
            this.innerHTML = '...';
            this.disabled = true;
            
            // Send AJAX request
            fetch('send_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `receiver_id=${encodeURIComponent(receiverId)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI to show request sent
                    connectionActions.innerHTML = '<span class="status-badge pending">Request Sent</span>';
                } else {
                    // Handle error
                    this.innerHTML = 'Connect';
                    this.disabled = false;
                    const errorMsg = data.message === 'already_connected' ? 'Already connected with this user.' :
                                   data.message === 'pending_request' ? 'Request already pending.' :
                                   'Failed to send connection request.';
                    alert(errorMsg);
                }
            })
            .catch(error => {
                console.error('Connection request failed:', error);
                this.innerHTML = 'Connect';
                this.disabled = false;
                alert('Failed to send connection request. Please try again.');
            });
        });
    });
}

// Search functionality - now handled by form submission to search across all pages
function initializeSearch() {
    const searchInput = document.getElementById('studentSearch');
    const clearBtn = document.getElementById('clearSearch');
    const studentsGrid = document.getElementById('studentsGrid');
    let currentSearchQuery = '';
    let currentSearchPage = 1;
    
    if (!searchInput) return;
    
    // Show/hide clear button based on input
    function toggleClearButton() {
        if (clearBtn) {
            clearBtn.style.display = searchInput.value.trim() ? 'flex' : 'none';
        }
    }
    
    // Initial state
    toggleClearButton();
    
    // Live search with debounce
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        toggleClearButton();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        // Debounce search by 400ms
        searchTimeout = setTimeout(() => {
            if (searchTerm === '') {
                // If search is empty, reload page without search parameter
                const url = new URL(window.location.href);
                url.searchParams.delete('search');
                url.searchParams.delete('page');
                window.location.href = url.toString();
            } else if (searchTerm.length >= 2) {
                // Perform AJAX search across all users
                currentSearchQuery = searchTerm;
                currentSearchPage = 1;
                performSearch(searchTerm, 1);
            }
        }, 400);
    });
    
    // Clear button functionality
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            toggleClearButton();
            
            // Reload page without search
            const url = new URL(window.location.href);
            url.searchParams.delete('search');
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    }
    
    // Perform AJAX search
    function performSearch(query, page = 1) {
        // Show loading state
        studentsGrid.innerHTML = '<div class="search-loading">Searching...</div>';
        
        fetch(`search_students.php?q=${encodeURIComponent(query)}&page=${page}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Search error:', data.error);
                    return;
                }
                
                displaySearchResults(data, query);
            })
            .catch(error => {
                console.error('Search failed:', error);
                studentsGrid.innerHTML = '<div class="search-error">Search failed. Please try again.</div>';
            });
    }
    
    // Display search results
    function displaySearchResults(data, query) {
        const { students, total, totalPages, currentPage } = data;
        
        // Update the count badge
        updateCountBadge(total);
        
        // Update or create pagination controls
        updateSearchPagination(totalPages, currentPage, query);
        
        if (students.length === 0) {
            studentsGrid.innerHTML = `
                <div class="search-empty-state" style="display: block; grid-column: 1 / -1;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                    </svg>
                    <p>No students found</p>
                    <p class="empty-state-subtitle">No matches for "${escapeHtml(query)}"</p>
                </div>
            `;
            return;
        }
        
        // Build HTML for all matching students
        let html = '';
        students.forEach(student => {
            const fullName = ((student.user_firstname || '') + ' ' + (student.user_lastname || '')).trim();
            const displayName = fullName || 'Student';
            const dataName = (student.user_username + ' ' + fullName).toLowerCase();
            
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
                    <form action="send_request.php" method="POST" style="display: inline;">
                        <input type="hidden" name="receiver_id" value="${student.user_id}">
                        <button type="submit" class="btn-connect">Connect</button>
                    </form>
                `;
            }
            
            html += `
                <div class="student-card" data-name="${escapeHtml(dataName)}">
                    <a href="../profile/profile.php?user_id=${student.user_id}" style="text-decoration:none;color:inherit;display:block;">
                        <div class="connection-header" style="cursor:pointer;">
                            <div class="user-avatar" style="transition:background 0.2s;" onmouseover="this.style.background='#d9dcdf'" onmouseout="this.style.background=''"></div>
                            <div class="user-info">
                                <h4 class="user-name" style="transition:color 0.2s;" onmouseover="this.style.color='#551A8B'" onmouseout="this.style.color=''">${escapeHtml(student.user_username)}</h4>
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
        
        // Re-initialize connect buttons
        initializeConnectButtons();
    }
    
    // Update count badge
    function updateCountBadge(count) {
        const headerRow = document.querySelector('.students-header-row h3');
        if (headerRow) {
            let badge = headerRow.querySelector('.count-badge');
            if (badge) {
                badge.textContent = count;
            } else {
                badge = document.createElement('span');
                badge.className = 'count-badge';
                badge.textContent = count;
                headerRow.appendChild(badge);
            }
        }
    }
    
    // Update search pagination
    function updateSearchPagination(totalPages, currentPage, query) {
        let paginationContainer = document.querySelector('.pagination-controls');
        
        if (totalPages <= 1) {
            // Hide pagination if only 1 page or less
            if (paginationContainer) {
                paginationContainer.style.display = 'none';
            }
            return;
        }
        
        // Show pagination
        if (paginationContainer) {
            paginationContainer.style.display = 'flex';
        } else {
            // Create pagination container if it doesn't exist
            const headerRow = document.querySelector('.students-header-row');
            if (headerRow) {
                paginationContainer = document.createElement('div');
                paginationContainer.className = 'pagination-controls';
                headerRow.appendChild(paginationContainer);
            } else {
                return;
            }
        }
        
        // Build pagination HTML
        let paginationHtml = `
            <button class="pagination-btn" id="searchPrevPage" ${currentPage <= 1 ? 'disabled' : ''}>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
                </svg>
            </button>
            
            <div class="page-numbers">
        `;
        
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            paginationHtml += `<button class="page-number search-page-btn" data-page="1">1</button>`;
            if (startPage > 2) {
                paginationHtml += `<span class="page-ellipsis">...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? ' active' : '';
            paginationHtml += `<button class="page-number search-page-btn${activeClass}" data-page="${i}">${i}</button>`;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHtml += `<span class="page-ellipsis">...</span>`;
            }
            paginationHtml += `<button class="page-number search-page-btn" data-page="${totalPages}">${totalPages}</button>`;
        }
        
        paginationHtml += `
            </div>
            
            <button class="pagination-btn" id="searchNextPage" ${currentPage >= totalPages ? 'disabled' : ''}>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/>
                </svg>
            </button>
            
            <div class="page-jump">
                <input type="number" id="searchPageInput" min="1" max="${totalPages}" placeholder="${currentPage}" class="page-input" title="Enter page number">
            </div>
        `;
        
        paginationContainer.innerHTML = paginationHtml;
        
        // Add event listeners for search pagination
        const pageButtons = paginationContainer.querySelectorAll('.search-page-btn');
        pageButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const page = parseInt(this.getAttribute('data-page'));
                performSearch(query, page);
            });
        });
        
        const prevBtn = document.getElementById('searchPrevPage');
        if (prevBtn && !prevBtn.disabled) {
            prevBtn.addEventListener('click', () => performSearch(query, currentPage - 1));
        }
        
        const nextBtn = document.getElementById('searchNextPage');
        if (nextBtn && !nextBtn.disabled) {
            nextBtn.addEventListener('click', () => performSearch(query, currentPage + 1));
        }
        
        const pageInput = document.getElementById('searchPageInput');
        if (pageInput) {
            pageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const page = parseInt(this.value);
                    if (page && page >= 1 && page <= totalPages) {
                        performSearch(query, page);
                    }
                }
            });
        }
    }
    
    // Helper to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
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

// Pagination functionality
function initializePagination() {
    const pageNumbers = document.querySelectorAll('.page-number');
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    const pageInput = document.getElementById('pageInput');
    
    // Handle page number clicks
    pageNumbers.forEach(btn => {
        btn.addEventListener('click', function() {
            const page = this.getAttribute('data-page');
            navigateToPage(page);
        });
    });
    
    // Handle previous button
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            if (!this.disabled) {
                const page = this.getAttribute('data-page');
                navigateToPage(page);
            }
        });
    }
    
    // Handle next button
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            if (!this.disabled) {
                const page = this.getAttribute('data-page');
                navigateToPage(page);
            }
        });
    }
    
    // Handle jump to page via input
    if (pageInput) {
        // Allow Enter key to jump
        pageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const page = parseInt(this.value);
                const maxPage = parseInt(this.getAttribute('max'));
                
                if (page && page >= 1 && page <= maxPage) {
                    navigateToPage(page);
                } else {
                    this.value = '';
                    const currentPage = this.getAttribute('placeholder');
                    this.setAttribute('placeholder', 'Invalid');
                    setTimeout(() => {
                        this.setAttribute('placeholder', currentPage);
                    }, 2000);
                }
            }
        });
        
        // Clear on focus
        pageInput.addEventListener('focus', function() {
            this.value = '';
        });
        
        // Restore placeholder on blur if empty
        pageInput.addEventListener('blur', function() {
            if (!this.value) {
                this.value = '';
            }
        });
    }
}

function navigateToPage(page) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    window.location.href = url.toString();
}