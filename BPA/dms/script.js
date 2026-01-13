// ===== THEME TOGGLE =====
const themeToggle = document.getElementById('themeToggle');
const body = document.body;
const savedTheme = localStorage.getItem('theme');
if (savedTheme === 'light') body.classList.add('light-mode');
if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        body.classList.toggle('light-mode');
        localStorage.setItem('theme', body.classList.contains('light-mode') ? 'light' : 'dark');
    });
}

// Global state
let currentConversationId = null;
let currentOtherUserId = null;
let currentUserId = null;

// DOM Elements
const conversationList = document.getElementById('conversationList');
const messagesContainer = document.getElementById('messagesContainer');
const chatHeader = document.getElementById('chatHeader');
const messageInput = document.getElementById('messageInput');
const messageText = document.getElementById('messageText');
const sendBtn = document.getElementById('sendBtn');
const headerName = document.getElementById('headerName');
const headerAvatar = document.getElementById('headerAvatar');

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    // Set current user ID from window global
    currentUserId = window.currentUserId;
    document.body.dataset.userId = currentUserId;
    console.log('Initialized currentUserId:', currentUserId);
    
    // Mark user as online
    updateUserStatus('online');
    
    await loadConversations();
    
    // Check if we need to start a conversation with a specific user
    if (window.startUserId) {
        await startConversationWithUser(window.startUserId);
    }
    
    // Send message on button click
    sendBtn.addEventListener('click', sendMessage);
    
    // Send message on Enter key
    messageText.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    
    // Request session button
    const requestSessionBtn = document.getElementById('requestSessionBtn');
    if (requestSessionBtn) {
        requestSessionBtn.addEventListener('click', openSessionRequestModal);
    }
    
    // Tab switching
    const tabBtns = document.querySelectorAll('.dm-tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', switchTab);
    });
    
    // Load session requests on initialization
    loadSessionRequests();
    
    // Refresh requests periodically
    setInterval(loadSessionRequests, 30000);
    
    // Modal controls for session request
    const sessionModal = document.getElementById('sessionRequestModal');
    const closeSessionModal = document.getElementById('closeSessionModal');
    const cancelSessionModal = document.getElementById('cancelSessionModal');
    const sessionRequestForm = document.getElementById('sessionRequestForm');
    
    if (closeSessionModal) {
        closeSessionModal.addEventListener('click', closeSessionRequestModal);
    }
    
    if (cancelSessionModal) {
        cancelSessionModal.addEventListener('click', closeSessionRequestModal);
    }
    
    if (sessionRequestForm) {
        sessionRequestForm.addEventListener('submit', submitSessionRequest);
    }
    
    // Modal controls for time selection
    const timeSelectionModal = document.getElementById('timeSelectionModal');
    const closeTimeModal = document.getElementById('closeTimeModal');
    const cancelTimeModal = document.getElementById('cancelTimeModal');
    const timeSelectionForm = document.getElementById('timeSelectionForm');
    
    if (closeTimeModal) {
        closeTimeModal.addEventListener('click', closeTimeSelectionModal);
    }
    
    if (cancelTimeModal) {
        cancelTimeModal.addEventListener('click', closeTimeSelectionModal);
    }
    
    if (timeSelectionForm) {
        timeSelectionForm.addEventListener('submit', handleTimeSubmit);
    }
    
    // Close modal when clicking outside
    if (timeSelectionModal) {
        timeSelectionModal.addEventListener('click', (e) => {
            if (e.target === timeSelectionModal) {
                closeTimeSelectionModal();
            }
        });
    }
    
    // Close modal when clicking outside
    if (sessionModal) {
        sessionModal.addEventListener('click', (e) => {
            if (e.target === sessionModal) {
                closeSessionRequestModal();
            }
        });
    }
    
    // Update online status every 30 seconds
    setInterval(() => {
        updateUserStatus('online');
    }, 30000);
    
    // Refresh conversation list every 15 seconds to show updated online/offline status
    setInterval(() => {
        loadConversations();
    }, 15000);
    
    // Mark offline when leaving the page
    window.addEventListener('beforeunload', () => {
        updateUserStatus('offline');
    });
});

// Load all conversations
async function loadConversations() {
    try {
        const response = await fetch('./backend/list_conversations.php');
        console.log('Response status:', response.status);

        const text = await response.text();
        console.log('Raw response text:', text);

        let data;
        try {
            data = JSON.parse(text);
        } catch (parseErr) {
            console.error('Failed to parse JSON:', parseErr);
            conversationList.innerHTML = '<div class="dm-empty-state">Error loading conversations (invalid JSON). Check console for details.</div>';
            return;
        }

        console.log('Conversations response:', data);
        console.log('Full response data:', JSON.stringify(data, null, 2));

        if (data.success) {
            renderConversations(data.conversations);
        } else {
            console.error('Error from backend:', data);
            const errorMsg = data.error || 'Unknown error';
            const debugInfo = data.debug ? `<br><small>Debug: ${JSON.stringify(data.debug)}</small>` : '';
            conversationList.innerHTML = `<div class="dm-empty-state">Error: ${errorMsg}${debugInfo}</div>`;
        }
    } catch (error) {
        console.error('Error loading conversations:', error);
        console.error('Error details:', error.message, error.stack);
        conversationList.innerHTML = '<div class="dm-empty-state">Error loading conversations. Check console for details.</div>';
    }
}

// Render conversations in sidebar
function renderConversations(conversations) {
    if (conversations.length === 0) {
        conversationList.innerHTML = '<div class="dm-empty-state">No conversations yet. Connect with other users to start chatting!</div>';
        return;
    }
    
    conversationList.innerHTML = '';
    
    conversations.forEach(conv => {
        const item = document.createElement('div');
        item.className = 'dm-list-item';
        item.dataset.conversationId = conv.conversation_id;
        item.dataset.otherUserId = conv.other_user_id;
        
        const avatarClass = `avatar${(conv.other_user_id % 4) + 1}`;
        
        const unreadBadge = conv.unread_count > 0 
            ? `<span class="dm-list-unread">${conv.unread_count}</span>` 
            : '';
        
        const timeStr = conv.last_message_time 
            ? formatTime(conv.last_message_time) 
            : 'No messages';
        
        // Format online status
        const statusClass = conv.is_online ? 'online' : 'offline';
        const statusText = conv.is_online ? '🟢 Online' : `⚫ ${formatTimeAgo(conv.last_seen)}`;
        
        item.innerHTML = `
            <div class="dm-avatar ${avatarClass}"></div>
            <div class="dm-list-info">
                <div class="dm-list-name">${escapeHtml(conv.other_user_username)} <span class="dm-list-time">${timeStr}</span> ${unreadBadge}</div>
                <div class="dm-list-preview dm-status-${statusClass}">${statusText}</div>
            </div>
        `;
        
        item.addEventListener('click', () => selectConversation(conv.conversation_id, conv.other_user_id, conv.other_user_username, conv.is_online));
        
        conversationList.appendChild(item);
    });
}

// Select a conversation and load messages
async function selectConversation(conversationId, otherUserId, otherUsername, isOnline) {
    currentConversationId = conversationId;
    currentOtherUserId = otherUserId;
    
    // Update active state in sidebar
    document.querySelectorAll('.dm-list-item').forEach(item => {
        item.classList.remove('active');
    });
    const selectedItem = document.querySelector(`[data-conversation-id="${conversationId}"]`);
    if (selectedItem) {
        selectedItem.classList.add('active');
    }
    
    // Update header with online status
    headerName.innerHTML = `<a href="../profile/profile.php?user_id=${otherUserId}" style="text-decoration:none;color:inherit;cursor:pointer;transition:color 0.2s;" onmouseover="this.style.color='#551A8B'" onmouseout="this.style.color='inherit'">${escapeHtml(otherUsername)}</a>`;
    const statusEl = chatHeader.querySelector('.dm-header-status');
    if (statusEl) {
        statusEl.textContent = isOnline ? '🟢 Online' : '⚫ Offline';
    }
    headerAvatar.className = `dm-header-avatar avatar${(otherUserId % 4) + 1}`;
    chatHeader.style.display = 'flex';
    messageInput.style.display = 'flex';
    
    // Load messages
    await loadMessages(conversationId);
    
    // Mark as read
    await markAsRead(conversationId);
}

// Load messages for a conversation
async function loadMessages(conversationId) {
    try {
        const response = await fetch(`backend/load_messages.php?conversation_id=${conversationId}`);
        const data = await response.json();
        
        if (data.success) {
            renderMessages(data.messages);
        } else {
            messagesContainer.innerHTML = '<div class="dm-empty-state">Error loading messages</div>';
        }
    } catch (error) {
        console.error('Error loading messages:', error);
        messagesContainer.innerHTML = '<div class="dm-empty-state">Error loading messages</div>';
    }
}

// Render messages in chat area
function renderMessages(messages) {
    if (messages.length === 0) {
        messagesContainer.innerHTML = '<div class="dm-empty-state">No messages yet. Start the conversation!</div>';
        return;
    }
    
    messagesContainer.innerHTML = '';
    
    messages.forEach(msg => {
        const messageDiv = document.createElement('div');
        const isMe = msg.sender_id != currentOtherUserId; // If not from other user, it's from me
        messageDiv.className = `dm-message ${isMe ? 'dm-message-me' : 'dm-message-other'}`;
        
        messageDiv.innerHTML = `
            <div class="dm-message-bubble">${escapeHtml(msg.message_text)}</div>
            <div class="dm-message-time">${formatTime(msg.sent_at)}</div>
        `;
        
        messagesContainer.appendChild(messageDiv);
    });
    
    // Scroll to bottom
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Send a message
async function sendMessage() {
    if (!currentConversationId) return;
    
    const text = messageText.value.trim();
    if (!text) return;
    
    try {
        const response = await fetch('./backend/send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                conversation_id: currentConversationId,
                message_text: text
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            messageText.value = '';
            await loadMessages(currentConversationId);
            await loadConversations(); // Refresh conversation list to update timestamps
            
            // Trigger badge update in other tabs/pages
            localStorage.setItem('dm_badge_update', Date.now().toString());
            // Trigger in same tab
            window.dispatchEvent(new Event('dm_badge_update'));
        } else {
            alert('Error sending message: ' + data.error);
        }
    } catch (error) {
        console.error('Error sending message:', error);
        alert('Error sending message');
    }
}

// Mark messages as read
async function markAsRead(conversationId) {
    try {
        await fetch('./backend/mark_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                conversation_id: conversationId
            })
        });
        
        // Refresh conversation list to clear unread badges
        await loadConversations();
        
        // Trigger badge update in other tabs/pages
        localStorage.setItem('dm_badge_update', Date.now().toString());
        // Trigger in same tab
        window.dispatchEvent(new Event('dm_badge_update'));
    } catch (error) {
        console.error('Error marking as read:', error);
    }
}

// Utility: Format timestamp
function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    // Same day - show time
    if (diff < 86400000 && date.getDate() === now.getDate()) {
        return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }
    
    // Yesterday
    const yesterday = new Date(now);
    yesterday.setDate(yesterday.getDate() - 1);
    if (date.getDate() === yesterday.getDate()) {
        return 'Yesterday';
    }
    
    // This week - show day name
    if (diff < 604800000) {
        return date.toLocaleDateString('en-US', { weekday: 'short' });
    }
    
    // Older - show date
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

// Utility: Format time ago for last_seen
function formatTimeAgo(timestamp) {
    if (!timestamp) return 'never';
    
    const date = new Date(timestamp);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`;
    
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

// Update user online/offline status
function updateUserStatus(action) {
    fetch(`backend/update_status.php?action=${action}`)
        .then(response => response.json())
        .catch(err => console.log('Status update failed:', err));
}

// Start or open conversation with a specific user (from connections page)
async function startConversationWithUser(userId) {
    try {
        console.log('Starting conversation with user:', userId);
        
        // Get user info first
        const userResponse = await fetch(`backend/get_connections.php`);
        const userData = await userResponse.json();
        
        if (!userData.success) {
            console.error('Failed to get connections');
            return;
        }
        
        const user = userData.connections.find(c => c.user_id === userId);
        if (!user) {
            console.error('User not found in connections');
            return;
        }
        
        console.log('Found user:', user);
        
        // Try to get or create conversation using start_conversation endpoint
        const response = await fetch('./backend/start_conversation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                other_user_id: userId
            })
        });
        
        const data = await response.json();
        console.log('Conversation response:', data);
        
        if (data.success) {
            // Reload conversations to show the new/existing one
            await loadConversations();
            // Open the conversation
            await selectConversation(data.conversation_id, userId, user.user_username);
            // Clear URL parameter
            window.history.replaceState({}, document.title, window.location.pathname);
        } else {
            console.error('Failed to create conversation:', data.error);
        }
    } catch (error) {
        console.error('Error starting conversation:', error);
    }
}

// Utility: Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Open session request modal
function openSessionRequestModal() {
    if (!currentOtherUserId) {
        showToast('Please select a conversation first', 'warning');
        return;
    }
    
    const sessionModal = document.getElementById('sessionRequestModal');
    if (sessionModal) {
        sessionModal.style.display = 'flex';
    }
}

// Close session request modal
function closeSessionRequestModal() {
    const sessionModal = document.getElementById('sessionRequestModal');
    if (sessionModal) {
        sessionModal.style.display = 'none';
    }
    
    // Reset form
    const form = document.getElementById('sessionRequestForm');
    if (form) {
        form.reset();
    }
}

// Submit session request with details
async function submitSessionRequest(e) {
    e.preventDefault();
    
    if (!currentOtherUserId) {
        showToast('Please select a conversation first', 'warning');
        return;
    }
    
    const areaOfHelp = document.getElementById('areaOfHelp').value;
    const description = document.getElementById('sessionDescription').value;
    const duration = document.getElementById('sessionDuration').value;
    const sessionType = document.getElementById('sessionType').value;
    
    try {
        const response = await fetch('./backend/create_session_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                recipient_user_id: currentOtherUserId,
                session_type: sessionType,
                area_of_help: areaOfHelp,
                description: description,
                duration: duration
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Session request sent successfully!', 'success', 'Your request has been sent to your connection!');
            closeSessionRequestModal();
        } else {
            showToast('Failed to send request', 'error', data.error || 'Please try again');
        }
    } catch (error) {
        console.error('Error requesting session:', error);
        showToast('Error sending request', 'error', error.message);
    }
}

// Tab switching
function switchTab(e) {
    const tabName = e.currentTarget.dataset.tab;
    
    // Update button states
    document.querySelectorAll('.dm-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    e.currentTarget.classList.add('active');
    
    // Update content visibility
    document.querySelectorAll('.dm-tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    const tabContent = document.getElementById(tabName + 'Tab');
    if (tabContent) {
        tabContent.classList.add('active');
    }
}

// Load session requests for current user
async function loadSessionRequests() {
    try {
        const response = await fetch('./backend/get_session_requests.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            renderSessionRequests(data.requests);
            
            // Update badge count
            const badge = document.getElementById('requestBadge');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        } else {
            console.error('Error loading requests:', data.error);
        }
    } catch (error) {
        console.error('Error fetching session requests:', error);
    }
}

// Render session requests in the list
function renderSessionRequests(requests) {
    const requestsList = document.getElementById('requestsList');
    
    if (!requestsList) return;
    
    if (!requests || requests.length === 0) {
        requestsList.innerHTML = '<div class="dm-empty-state">No pending requests</div>';
        return;
    }
    
    let html = '';
    
    requests.forEach(req => {
        const createdDate = new Date(req.created_at);
        const timeAgo = getTimeAgo(createdDate);
        
        // Check if request is accepted
        const isAccepted = req.status === 'accepted';
        const actionButtons = isAccepted 
            ? `<button class="btn-request btn-session" onclick="openSessionFromRequest({request_id: ${req.request_id}, requester_id: ${req.requester_id}, recipient_id: ${req.recipient_id}, requester_name: '${escapeHtml(req.requester_name)}', recipient_name: '${escapeHtml(req.recipient_name)}', area_of_help: '${escapeHtml(req.area_of_help)}', session_date: '${req.session_date}', session_start_time: '${req.session_start_time}', session_end_time: '${req.session_end_time}'})">Enter Session</button>`
            : `<button class="btn-request btn-accept" onclick="respondSessionRequest(${req.request_id}, 'accept')">Accept</button>
               <button class="btn-request btn-reject" onclick="respondSessionRequest(${req.request_id}, 'reject')">Reject</button>`;
        
        html += `
            <div class="dm-request-item ${isAccepted ? 'accepted' : ''}">
                <div class="dm-request-header">
                    <div class="dm-request-requester">${escapeHtml(req.user_username)}</div>
                    <div class="dm-request-time">${timeAgo}</div>
                </div>
                <div class="dm-request-subject">
                    <span class="dm-request-badge">${escapeHtml(req.area_of_help)}</span>
                    <span class="dm-request-badge">${escapeHtml(req.session_type)}</span>
                </div>
                <div class="dm-request-description">${escapeHtml(req.description)}</div>
                <div class="dm-request-meta">
                    <div class="dm-request-meta-item">
                        <span>⏱️ ${req.duration} ${isNaN(req.duration) ? '' : 'min'}</span>
                    </div>
                </div>
                <div class="dm-request-actions">
                    ${actionButtons}
                </div>
            </div>
        `;
    });
    
    requestsList.innerHTML = html;
}

// Respond to session request
async function respondSessionRequest(requestId, action) {
    if (action === 'accept') {
        // Show time selection modal instead of directly accepting
        openTimeSelectionModal(requestId);
    } else {
        // Directly reject without time selection
        submitSessionResponse(requestId, 'reject', null, null, null, null);
    }
}

// Open time selection modal for accepting a request
function openTimeSelectionModal(requestId) {
    const modal = document.getElementById('timeSelectionModal');
    if (modal) {
        modal.dataset.requestId = requestId;
        modal.style.display = 'flex';
        
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('sessionDate').min = today;
        document.getElementById('sessionDate').value = today;
    }
}

// Close time selection modal
function closeTimeSelectionModal() {
    const modal = document.getElementById('timeSelectionModal');
    if (modal) {
        modal.style.display = 'none';
        modal.dataset.requestId = '';
    }
    
    // Reset form
    const form = document.getElementById('timeSelectionForm');
    if (form) {
        form.reset();
    }
}

// Submit session response with time details
async function submitSessionResponse(requestId, action, sessionDate, startTime, endTime, sessionNotes) {
    try {
        const response = await fetch('./backend/respond_session_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                request_id: requestId,
                action: action,
                message: '',
                session_date: sessionDate,
                start_time: startTime,
                end_time: endTime,
                session_notes: sessionNotes
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (action === 'accept') {
                showToast('Request Accepted!', 'success', `Session scheduled for ${sessionDate}`);
            } else {
                showToast('Request Rejected', 'info', 'The request has been rejected');
            }
            loadSessionRequests();
            closeTimeSelectionModal();
        } else {
            showToast('Failed to respond', 'error', data.error || 'Please try again');
        }
    } catch (error) {
        console.error('Error responding to request:', error);
        showToast('Error responding', 'error', error.message);
    }
}

// Utility: Get time ago string
function getTimeAgo(date) {
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    
    return date.toLocaleDateString();
}

// Handle time selection form submission
function handleTimeSubmit(e) {
    e.preventDefault();
    
    const modal = document.getElementById('timeSelectionModal');
    const requestId = parseInt(modal.dataset.requestId);
    const sessionDate = document.getElementById('sessionDate').value;
    const startTime = document.getElementById('sessionStartTime').value;
    const endTime = document.getElementById('sessionEndTime').value;
    const sessionNotes = document.getElementById('sessionNotes').value;
    
    // Validate times
    if (startTime >= endTime) {
        showToast('Invalid Time', 'error', 'End time must be after start time');
        return;
    }
    
    // Submit with all the details
    submitSessionResponse(requestId, 'accept', sessionDate, startTime, endTime, sessionNotes);
}

// Toast Notification System
function showToast(title, type = 'info', message = '') {
    const container = document.getElementById('notificationContainer');
    if (!container) return;
    
    // Determine icon based on type
    let icon = 'ℹ️';
    switch(type) {
        case 'success':
            icon = '✅';
            break;
        case 'error':
            icon = '❌';
            break;
        case 'warning':
            icon = '⚠️';
            break;
        case 'info':
            icon = 'ℹ️';
            break;
    }
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${icon}</span>
        <div class="toast-content">
            <div class="toast-title">${escapeHtml(title)}</div>
            ${message ? `<div class="toast-message">${escapeHtml(message)}</div>` : ''}
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">✕</button>
    `;
    
    container.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.classList.add('removing');
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 300);
        }
    }, 5000);
}

// ===== SESSION ROOM FUNCTIONS =====
let currentSessionId = null;
let sessionMessageRefreshInterval = null;
let sessionReadyCheckInterval = null;
let currentSessionData = null;
let lastRenderedMessageIds = new Set();

// Open session from request button
async function openSessionFromRequest(session) {
    currentSessionData = session;
    
    // First, join the session
    try {
        const response = await fetch('./backend/join_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: session.request_id
            }),
            credentials: 'include'
        });
        
        let data;
        try {
            data = await response.json();
        } catch (parseError) {
            showToast('Error', 'error', 'Invalid server response');
            return;
        }
        
        if (data.success) {
            if (data.other_user_joined) {
                // Other user already in, open chat directly
                openSessionRoom(session);
            } else {
                // Show waiting room
                showWaitingRoom(session);
            }
        } else {
            showToast('Error', 'error', data.error || 'Failed to join session');
        }
    } catch (error) {
        showToast('Error', 'error', 'Failed to join session');
    }
}

function showWaitingRoom(session) {
    const otherUser = session.requester_id !== parseInt(document.body.dataset.userId) 
        ? session.requester_id 
        : session.recipient_id;
    
    document.getElementById('waitingUserName').textContent = `Waiting for other participant to join...`;
    document.getElementById('waitingRoomModal').style.display = 'flex';
    
    // Poll to check if other user is ready
    sessionReadyCheckInterval = setInterval(async () => {
        try {
            const url = `./backend/check_session_ready.php?session_id=${session.request_id}`;
            
            const response = await fetch(url, {
                credentials: 'include'
            });
            const data = await response.json();
            
            if (data.success && data.both_ready) {
                clearInterval(sessionReadyCheckInterval);
                document.getElementById('waitingRoomModal').style.display = 'none';
                openSessionRoom(session);
            }
        } catch (error) {
            console.error('Error checking session ready:', error);
        }
    }, 2000); // Check every 2 seconds
}

function closeWaitingRoom() {
    document.getElementById('waitingRoomModal').style.display = 'none';
    if (sessionReadyCheckInterval) {
        clearInterval(sessionReadyCheckInterval);
    }
}

function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
    
    // Load content for selected tab
    if (tabName === 'sessions-tab') {
        loadActiveSessions();
    } else if (tabName === 'requests-tab') {
        loadSessionRequests();
    }
}

async function loadActiveSessions() {
    try {
        const response = await fetch('./backend/get_active_sessions.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            renderActiveSessions(data.sessions);
            if (data.count > 0) {
                document.getElementById('sessionBadge').textContent = data.count;
                document.getElementById('sessionBadge').style.display = 'inline';
            }
        } else {
            showToast('Error', 'error', data.error || 'Failed to load sessions');
        }
    } catch (error) {
        console.error('Error loading sessions:', error);
        showToast('Error', 'error', 'Failed to load sessions');
    }
}

function renderActiveSessions(sessions) {
    const container = document.getElementById('sessionsList');
    container.innerHTML = '';
    
    if (sessions.length === 0) {
        container.innerHTML = '<p style="padding: 15px; color: var(--text-secondary);">No active sessions</p>';
        return;
    }
    
    sessions.forEach(session => {
        const otherUser = session.requester_id !== parseInt(document.body.dataset.userId) 
            ? session.requester_name 
            : session.recipient_name;
        
        const sessionDiv = document.createElement('div');
        sessionDiv.className = 'session-item';
        sessionDiv.innerHTML = `
            <h4>${otherUser}</h4>
            <p>📅 ${new Date(session.session_date).toLocaleDateString()}</p>
            <p>🕐 ${session.session_start_time} - ${session.session_end_time}</p>
            <p>${session.area_of_help}</p>
        `;
        sessionDiv.onclick = () => openSessionRoom(session);
        container.appendChild(sessionDiv);
    });
}

async function openSessionRoom(session) {
    currentSessionId = session.request_id;
    lastRenderedMessageIds.clear();
    
    const otherUser = session.requester_id !== parseInt(document.body.dataset.userId) 
        ? session.requester_name 
        : session.recipient_name;
    
    document.getElementById('sessionTitle').textContent = `Session with ${otherUser}`;
    document.getElementById('sessionDetails').textContent = `${session.area_of_help} • ${new Date(session.session_date).toLocaleDateString()} at ${session.session_start_time}`;
    
    const modal = document.getElementById('sessionRoomModal');
    modal.style.display = 'flex';
    
    // Clear messages container
    document.getElementById('sessionMessagesContainer').innerHTML = '';
    
    // Load messages
    await loadSessionMessages();
    
    // Start auto-refresh
    if (sessionMessageRefreshInterval) clearInterval(sessionMessageRefreshInterval);
    sessionMessageRefreshInterval = setInterval(loadSessionMessages, 3000);
}

function closeSessionRoom() {
    document.getElementById('sessionRoomModal').style.display = 'none';
    currentSessionId = null;
    if (sessionMessageRefreshInterval) {
        clearInterval(sessionMessageRefreshInterval);
    }
}

async function loadSessionMessages() {
    if (!currentSessionId) return;
    
    try {
        const response = await fetch(`./backend/get_session_messages.php?session_id=${currentSessionId}`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            renderSessionMessages(data.messages);
        }
    } catch (error) {
        console.error('Error loading messages:', error);
    }
}

function renderSessionMessages(messages) {
    const container = document.getElementById('sessionMessagesContainer');
    const currentUserId = parseInt(document.body.dataset.userId);
    let shouldScroll = false;
    
    messages.forEach(msg => {
        // Only render messages we haven't seen before
        if (!lastRenderedMessageIds.has(msg.message_id)) {
            lastRenderedMessageIds.add(msg.message_id);
            shouldScroll = true;
            
            const messageDiv = document.createElement('div');
            const isOwn = msg.user_id === currentUserId;
            messageDiv.className = `session-message ${isOwn ? 'sent' : 'received'}`;
            
            const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            messageDiv.innerHTML = `
                ${!isOwn ? `<div class="session-message-username">${msg.username}</div>` : ''}
                <div class="session-message-bubble">${escapeHtml(msg.message)}</div>
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">${time}</div>
            `;
            
            container.appendChild(messageDiv);
        }
    });
    
    // Only scroll if new messages were added
    if (shouldScroll) {
        container.scrollTop = container.scrollHeight;
    }
}

async function sendSessionMessage() {
    const input = document.getElementById('sessionMessageInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    try {
        const response = await fetch('./backend/send_session_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                session_id: currentSessionId,
                message: message
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            input.value = '';
            await loadSessionMessages();
        } else {
            showToast('Error', 'error', data.error || 'Failed to send message');
        }
    } catch (error) {
        console.error('Error sending message:', error);
        showToast('Error', 'error', 'Failed to send message');
    }
}

function handleSessionKeyPress(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendSessionMessage();
    }
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}