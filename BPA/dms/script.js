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
});

// Load all conversations
async function loadConversations() {
    try {
        const response = await fetch('backend/list_conversations.php');
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
        
        item.innerHTML = `
            <div class="dm-avatar ${avatarClass}"></div>
            <div class="dm-list-info">
                <div class="dm-list-name">${escapeHtml(conv.other_user_username)} <span class="dm-list-time">${timeStr}</span> ${unreadBadge}</div>
                <div class="dm-list-preview">Click to view messages</div>
            </div>
        `;
        
        item.addEventListener('click', () => selectConversation(conv.conversation_id, conv.other_user_id, conv.other_user_username));
        
        conversationList.appendChild(item);
    });
}

// Select a conversation and load messages
async function selectConversation(conversationId, otherUserId, otherUsername) {
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
    
    // Update header
    headerName.textContent = otherUsername;
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
        const response = await fetch('backend/send_message.php', {
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
        await fetch('backend/mark_read.php', {
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
        const response = await fetch('backend/start_conversation.php', {
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