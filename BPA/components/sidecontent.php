<?php
/**
 * Side Content Component
 * Displays contextual information based on the current page
 * 
 * @param string $currentPage - Current page identifier (calendar, events, forum, etc.)
 * @param array $options - Additional options for customization
 */

function renderSideContent($currentPage = '', $options = []) {
    // Show only 3 sections on all pages: Notifications, Events, Suggested Collaborators
    $showNotifications = true;
    $showUpcomingEvents = true;
    $showRecentDMs = false;
    $showSuggestedCollaborators = true;
    $showTrendingTopics = false;

    // Optional overrides per page via $options['hide']
    // Example: renderSideContent('courses', ['hide' => ['notifications', 'recentDMs']])
    if (isset($options['hide']) && is_array($options['hide'])) {
        $hide = array_map('strtolower', $options['hide']);
        if (in_array('notifications', $hide)) { $showNotifications = false; }
        if (in_array('upcomingevents', $hide) || in_array('upcoming_events', $hide)) { $showUpcomingEvents = false; }
        if (in_array('recentdms', $hide) || in_array('recent_dms', $hide) || in_array('messages', $hide)) { $showRecentDMs = false; }
        if (in_array('suggestedcollaborators', $hide) || in_array('suggested_collaborators', $hide) || in_array('collaborators', $hide)) { $showSuggestedCollaborators = false; }
        // Trending topics kept for backward compatibility but no longer shown by default
        if (in_array('trendingtopics', $hide) || in_array('trending_topics', $hide) || in_array('topics', $hide)) { $showTrendingTopics = false; }
    }

    // Optional limits per widget via $options['limit']
    // Example: ['limit' => ['trendingTopics' => 3]] to show only first 3 topics
    $limitTrendingTopics = null;
    $limitNotifications = null;
    $limitRecentDMs = null;
    $limitUpcomingEvents = null;
    $limitSuggestedCollaborators = null;
    if (isset($options['limit']) && is_array($options['limit'])) {
        if (isset($options['limit']['trendingTopics']) || isset($options['limit']['trending_topics'])) {
            $limitTrendingTopics = (int)($options['limit']['trendingTopics'] ?? $options['limit']['trending_topics']);
        }
        if (isset($options['limit']['notifications'])) {
            $limitNotifications = (int)$options['limit']['notifications'];
        }
        if (isset($options['limit']['recentDms']) || isset($options['limit']['recent_dms'])) {
            $limitRecentDMs = (int)($options['limit']['recentDms'] ?? $options['limit']['recent_dms']);
        }
        if (isset($options['limit']['upcomingEvents']) || isset($options['limit']['upcoming_events'])) {
            $limitUpcomingEvents = (int)($options['limit']['upcomingEvents'] ?? $options['limit']['upcoming_events']);
        }
        if (isset($options['limit']['suggestedCollaborators']) || isset($options['limit']['suggested_collaborators'])) {
            $limitSuggestedCollaborators = (int)($options['limit']['suggestedCollaborators'] ?? $options['limit']['suggested_collaborators']);
        }
    }
    ?>
    
    <aside class="side-content" id="sideContent">
        
        <?php if ($showNotifications): ?>
        <!-- Notifications Widget -->
        <div class="side-card">
            <div class="side-card-header">
                <h3 class="side-card-title">Notifications</h3>
                <a href="../notifications/notifications.php" class="side-card-link">See All</a>
            </div>
            <div class="side-card-body">
                <?php 
                // Fetch recent notifications from database
                $notifications = [];
                if (isset($_SESSION['user_id'])) {
                    $currentUserId = $_SESSION['user_id'];
                    require_once __DIR__ . '/../database/DatabaseConnection.php';
                    require_once __DIR__ . '/../database/Notification.php';
                    
                    $dbConn = new DatabaseConnection();
                    $db = $dbConn->connection;
                    $notificationObj = new Notification($db);
                    
                    // Fetch recent notifications
                    $maxNotifications = $limitNotifications ?? 1;
                    $result = $notificationObj->getRecentNotifications($currentUserId, $maxNotifications);
                    
                    while ($row = $result->fetch_assoc()) {
                        // Calculate time ago
                        $createdTime = strtotime($row['created_at']);
                        $now = time();
                        $diff = $now - $createdTime;
                        
                        if ($diff < 60) {
                            $timeAgo = 'Just now';
                        } elseif ($diff < 3600) {
                            $minutes = floor($diff / 60);
                            $timeAgo = $minutes . 'm ago';
                        } elseif ($diff < 86400) {
                            $hours = floor($diff / 3600);
                            $timeAgo = $hours . 'h ago';
                        } else {
                            $days = floor($diff / 86400);
                            $timeAgo = $days . 'd ago';
                        }
                        
                        $notifications[] = [
                            'type' => $row['type'],
                            'title' => $row['title'],
                            'description' => $row['description'],
                            'time' => $timeAgo,
                            'is_read' => $row['is_read'],
                            'actor' => $row['user_username'] ?? 'Someone'
                        ];
                    }
                    $dbConn->closeConnection();
                }
                
                // Display notifications or empty state
                if (empty($notifications)): 
                ?>
                    <p style="color: #666; text-align: center; padding: 20px; font-size: 14px;">No notifications yet</p>
                <?php 
                else:
                    foreach ($notifications as $notif):
                        $unreadClass = !$notif['is_read'] ? ' unread' : '';
                        
                        // Determine icon based on notification type
                        $iconType = 'notification-comment';
                        if ($notif['type'] == 'friend_accepted') {
                            $iconType = 'notification-like';
                        } elseif ($notif['type'] == 'event_created') {
                            $iconType = 'notification-event';
                        } elseif ($notif['type'] == 'post_comment') {
                            $iconType = 'notification-comment';
                        }
                ?>
                <div class="notification-item<?php echo $unreadClass; ?>">
                    <div class="notification-icon <?php echo $iconType; ?>">
                        <?php if ($notif['type'] == 'friend_accepted'): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143q.09.083.176.171a3 3 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15"/>
                        </svg>
                        <?php elseif ($notif['type'] == 'event_created'): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                        </svg>
                        <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M2.678 11.894a1 1 0 0 1 .287.801 11 11 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8 8 0 0 0 8 14c3.996 0 7-2.807 7-6s-3.004-6-7-6-7 2.808-7 6c0 1.468.617 2.83 1.678 3.894m-.493 3.905a22 22 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a10 10 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105"/>
                        </svg>
                        <?php endif; ?>
                    </div>
                    <div class="notification-content">
                        <p><?php echo htmlspecialchars($notif['title']); ?></p>
                        <span class="notification-time"><?php echo htmlspecialchars($notif['time']); ?></span>
                    </div>
                </div>
                <?php 
                    endforeach;
                endif;
                ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($showUpcomingEvents): ?>
        <!-- Upcoming Events Widget -->
        <div class="side-card">
            <div class="side-card-header">
                <h3 class="side-card-title">Upcoming Events</h3>
                <a href="../events/events.php" class="side-card-link">See All</a>
            </div>
            <div class="side-card-body">
                <?php 
                // Fetch upcoming events from database
                $upcomingEvents = [];
                if (isset($_SESSION['user_id'])) {
                    $currentUserId = $_SESSION['user_id'];
                    require_once __DIR__ . '/../database/DatabaseConnection.php';
                    $dbConn = new DatabaseConnection();
                    $db = $dbConn->connection;
                    
                    // Get upcoming events matching user's interests
                    $sql = "
                    SELECT DISTINCT
                        e.events_id,
                        e.events_title,
                        e.events_date,
                        e.events_start,
                        e.events_end,
                        e.events_deadline
                    FROM events e
                    INNER JOIN event_subjects es ON e.events_id = es.es_event_id
                    INNER JOIN user_interests ui ON es.es_subject_id = ui.ui_subject_id AND ui.ui_user_id = ?
                    WHERE 
                        TIMESTAMP(e.events_date, COALESCE(e.events_start, '23:59:59')) > NOW()
                        AND (e.events_deadline IS NULL OR TIMESTAMP(e.events_deadline, '23:59:59') > NOW())
                        AND NOT EXISTS(
                            SELECT 1 FROM event_participants ep 
                            WHERE ep.ep_event_id = e.events_id AND ep.ep_user_id = ?
                        )
                        AND e.events_visibility = 'public'
                    GROUP BY e.events_id
                    ORDER BY e.events_date ASC
                    LIMIT " . ($limitUpcomingEvents ?? 1);
                    
                    $stmt = $db->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("ii", $currentUserId, $currentUserId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        while ($row = $result->fetch_assoc()) {
                            $eventDate = new DateTime($row['events_date']);
                            $day = $eventDate->format('d');
                            $month = $eventDate->format('M');
                            
                            // Format time display
                            $timeDisplay = '';
                            if ($row['events_start']) {
                                $startTime = new DateTime($row['events_date'] . ' ' . $row['events_start']);
                                $endTime = $row['events_end'] ? new DateTime($row['events_date'] . ' ' . $row['events_end']) : null;
                                $timeDisplay = $startTime->format('h:i A');
                                if ($endTime) {
                                    $timeDisplay .= ' - ' . $endTime->format('h:i A');
                                }
                            }
                            
                            $upcomingEvents[] = [
                                'id' => $row['events_id'],
                                'title' => $row['events_title'],
                                'day' => $day,
                                'month' => $month,
                                'time' => $timeDisplay,
                                'date' => $eventDate
                            ];
                        }
                        $stmt->close();
                    }
                    $dbConn->closeConnection();
                }
                
                // Display events
                if (empty($upcomingEvents)) {
                    echo '<p style="color: #666; text-align: center; padding: 20px; font-size: 14px;">No upcoming events matching your interests</p>';
                } else {
                    foreach ($upcomingEvents as $event):
                ?>
                <a href="../events/events.php?event_id=<?php echo $event['id']; ?>" class="side-event-item">
                    <div class="side-event-date">
                        <span class="side-event-day"><?php echo htmlspecialchars($event['day']); ?></span>
                        <span class="side-event-month"><?php echo htmlspecialchars($event['month']); ?></span>
                    </div>
                    <div class="side-event-info">
                        <h4 class="side-event-title"><?php echo htmlspecialchars($event['title']); ?></h4>
                        <?php if ($event['time']): ?>
                        <p class="side-event-meta"><?php echo htmlspecialchars($event['time']); ?></p>
                        <?php endif; ?>
                    </div>
                </a>
                <?php 
                    endforeach;
                }
                ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($showRecentDMs): ?>
        <!-- Recent Messages Widget -->
        <div class="side-card">
            <div class="side-card-header">
                <h3 class="side-card-title">Recent Messages</h3>
                <a href="../dms/index.html" class="side-card-link">View All</a>
            </div>
            <div class="side-card-body">
                <?php 
                // Fetch recent messages from database
                $recentMessages = [];
                if (isset($_SESSION['user_id'])) {
                    $currentUserId = $_SESSION['user_id'];
                    require_once __DIR__ . '/../database/DatabaseConnection.php';
                    $dbConn = new DatabaseConnection();
                    $db = $dbConn->connection;
                    
                    // Get recent conversations with last message info
                    $sql = "SELECT 
                        c.conversation_id,
                        CASE 
                            WHEN c.user1_id = ? THEN c.user2_id
                            ELSE c.user1_id
                        END AS other_user_id,
                        (SELECT user_username FROM user WHERE user_id = 
                            CASE 
                                WHEN c.user1_id = ? THEN c.user2_id
                                ELSE c.user1_id
                            END
                        ) AS other_username,
                        (SELECT message_text FROM messages 
                         WHERE conversation_id = c.conversation_id 
                         ORDER BY sent_at DESC LIMIT 1) AS last_message,
                        (SELECT sent_at FROM messages 
                         WHERE conversation_id = c.conversation_id 
                         ORDER BY sent_at DESC LIMIT 1) AS last_message_time,
                        (SELECT COUNT(*) FROM messages 
                         WHERE conversation_id = c.conversation_id 
                         AND sender_id != ? 
                         AND is_read = FALSE) AS unread_count
                    FROM conversations c
                    WHERE (c.user1_id = ? OR c.user2_id = ?)
                    AND EXISTS (
                        SELECT 1 FROM connections conn
                        WHERE conn.status = 'accepted'
                        AND (
                            (conn.requester_id = c.user1_id AND conn.receiver_id = c.user2_id)
                            OR (conn.requester_id = c.user2_id AND conn.receiver_id = c.user1_id)
                        )
                    )
                    ORDER BY last_message_time DESC
                    LIMIT " . ($limitRecentDMs ?? 1);
                    
                    $stmt = $db->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("iiiii", $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        while ($row = $result->fetch_assoc()) {
                            // Calculate time ago
                            $sentTime = strtotime($row['last_message_time']);
                            $now = time();
                            $diff = $now - $sentTime;
                            
                            if ($diff < 60) {
                                $timeAgo = 'Just now';
                            } elseif ($diff < 3600) {
                                $minutes = floor($diff / 60);
                                $timeAgo = $minutes . 'm ago';
                            } elseif ($diff < 86400) {
                                $hours = floor($diff / 3600);
                                $timeAgo = $hours . 'h ago';
                            } elseif ($diff < 604800) {
                                $days = floor($diff / 86400);
                                $timeAgo = $days . 'd ago';
                            } else {
                                $timeAgo = date('M j', $sentTime);
                            }
                            
                            // Truncate message preview
                            $messagePreview = $row['last_message'];
                            if (strlen($messagePreview) > 35) {
                                $messagePreview = substr($messagePreview, 0, 32) . '...';
                            }
                            
                            $recentMessages[] = [
                                'conversation_id' => $row['conversation_id'],
                                'name' => $row['other_username'],
                                'message' => $messagePreview,
                                'time' => $timeAgo,
                                'avatar' => 'avatar-' . (($row['other_user_id'] % 6) + 1),
                                'unread' => (int)$row['unread_count'] > 0,
                                'badge' => (int)$row['unread_count']
                            ];
                        }
                        $stmt->close();
                    }
                    $dbConn->closeConnection();
                }
                
                // Fallback to sample data if no messages
                if (empty($recentMessages)) {
                    $recentMessages = [
                        ['conversation_id' => null, 'name' => 'No Messages', 'message' => 'Start a conversation with your connections', 'time' => '', 'avatar' => 'avatar-1', 'unread' => false, 'badge' => 0]
                    ];
                }
                
                foreach ($recentMessages as $msg):
                    $unreadClass = $msg['unread'] ? ' unread' : '';
                    $clickable = isset($msg['conversation_id']) ? ' style="cursor: pointer;"' : '';
                    $conversationId = $msg['conversation_id'] ?? '';
                ?>
                <a href="<?php echo $conversationId ? '../dms/dms.php?conversation_id=' . $conversationId : '#'; ?>" class="side-dm-item<?php echo $unreadClass; ?>" <?php echo $clickable; ?>>
                    <div class="side-dm-avatar <?php echo $msg['avatar']; ?>"></div>
                    <div class="side-dm-content">
                        <h4 class="side-dm-name"><?php echo htmlspecialchars($msg['name']); ?></h4>
                        <p class="side-dm-message"><?php echo htmlspecialchars($msg['message']); ?></p>
                        <?php if (!empty($msg['time'])): ?>
                        <span class="side-dm-time"><?php echo htmlspecialchars($msg['time']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($msg['badge']) && $msg['badge'] > 0): ?>
                    <span class="side-dm-badge"><?php echo $msg['badge']; ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($showSuggestedCollaborators): ?>
        <!-- Suggested Collaborators Widget -->
        <div class="side-card">
            <div class="side-card-header">
                <h3 class="side-card-title">Suggested Collaborators</h3>
                <a href="../connections/connections.php" class="side-card-link">See All</a>
            </div>
            <div class="side-card-body" id="suggestedCollaboratorsContainer">
                <div class="loading-placeholder">Loading collaborators...</div>
            </div>
        </div>
        
        <script>
        (function() {
            const limit = <?php echo $limitSuggestedCollaborators ?? 1; ?>;
            
            // Fetch suggested collaborators from backend
            fetch('/WebApp/BPA/components/get_suggested_collaborators.php?limit=' + limit)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.text();
                })
                .then(text => {
                    console.log('Raw response:', text);
                    const data = JSON.parse(text);
                    const container = document.getElementById('suggestedCollaboratorsContainer');
                    
                    console.log('Parsed data:', data);
                    
                    if (data.error) {
                        console.error('Collaborators API error:', data.error);
                        if (data.debug) console.error('Debug info:', data.debug);
                        container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px; font-size: 12px;">Error: ' + data.error + '</p>';
                        return;
                    }
                    
                    if (!data.success) {
                        console.log('Request not successful');
                        container.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">No suggestions at this time</p>';
                        return;
                    }
                    
                    if (!data.collaborators || data.collaborators.length === 0) {
                        console.log('No collaborators in response');
                        container.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">No suggestions at this time</p>';
                        return;
                    }
                    
                    console.log('Found ' + data.collaborators.length + ' collaborators');
                    let html = '';
                    data.collaborators.forEach((collab, index) => {
                        const avatarClass = 'avatar-' + ((index % 6) + 1);
                        // Truncate skills to first 4, add ellipsis if more
                        const skillsArray = collab.field.split(', ');
                        const displaySkills = skillsArray.slice(0, 4).join(', ') + (skillsArray.length > 4 ? ', ...' : '');
                        html += `
                            <div class="side-collab-item">
                                <div class="side-collab-avatar ${avatarClass}"></div>
                                <div class="side-collab-info">
                                    <h4 class="side-collab-name">${collab.firstname} ${collab.lastname}</h4>
                                    <p class="side-collab-field" title="All skills: ${collab.field}">${displaySkills}</p>
                                </div>
                                <button class="side-collab-btn" data-user-id="${collab.user_id}">Follow</button>
                            </div>
                        `;
                    });
                    
                    container.innerHTML = html;
                    
                    // Check connection status for each user and update button
                    container.querySelectorAll('.side-collab-btn').forEach(btn => {
                        const userId = btn.getAttribute('data-user-id');
                        
                        // Check connection status
                        fetch('/WebApp/BPA/components/check_connection_status.php?user_id=' + userId)
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'connected') {
                                    btn.textContent = 'Connected';
                                    btn.disabled = true;
                                    btn.style.borderColor = '#666';
                                    btn.style.color = '#666';
                                } else if (data.status === 'pending') {
                                    btn.textContent = 'Pending';
                                    btn.disabled = true;
                                    btn.style.borderColor = '#ff9f00';
                                    btn.style.color = '#ff9f00';
                                }
                            })
                            .catch(error => console.error('Error checking status:', error));
                        
                        // Add click listener
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            const userId = this.getAttribute('data-user-id');
                            sendConnectionRequest(userId, this);
                        });
                    });
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    const container = document.getElementById('suggestedCollaboratorsContainer');
                    container.innerHTML = '<p style="color: #666; text-align: center; padding: 20px;">Unable to load suggestions</p>';
                });
            
            function sendConnectionRequest(userId, button) {
                // First check if already connected
                fetch('/WebApp/BPA/components/check_connection_status.php?user_id=' + userId)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Connection status:', data);
                        if (data.status === 'connected') {
                            button.textContent = 'Connected';
                            button.disabled = true;
                            button.style.borderColor = '#666';
                            button.style.color = '#666';
                            return;
                        } else if (data.status === 'pending') {
                            button.textContent = 'Pending';
                            button.disabled = true;
                            button.style.borderColor = '#ff9f00';
                            button.style.color = '#ff9f00';
                            return;
                        }
                        
                        // Not connected, send request
                        console.log('Sending connection request to user', userId);
                        fetch('/WebApp/BPA/connections/send_request.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'receiver_id=' + encodeURIComponent(userId)
                        })
                        .then(response => {
                            console.log('Send request response status:', response.status);
                            return response.text();
                        })
                        .then(data => {
                            console.log('Send request response:', data);
                            // If we got here without error, request was sent
                            button.textContent = 'Pending';
                            button.disabled = true;
                            button.style.borderColor = '#ff9f00';
                            button.style.color = '#ff9f00';
                        })
                        .catch(error => console.error('Error sending request:', error));
                    })
                    .catch(error => console.error('Error checking connection:', error));
            }
        })();
        </script>
        <?php endif; ?>

        <?php if ($showTrendingTopics): ?>
        <!-- Trending Topics Widget -->
        <div class="side-card">
            <div class="side-card-header">
                <h3 class="side-card-title">Trending Topics</h3>
                <a href="../forum/forums.html" class="side-card-link">See All</a>
            </div>
            <div class="side-card-body">
                <?php 
                  $topics = [
                    ['tag' => '#machinelearning', 'count' => '1,243'],
                    ['tag' => '#reactjs', 'count' => '892'],
                    ['tag' => '#finalexams', 'count' => '754'],
                    ['tag' => '#capstoneprojects', 'count' => '621'],
                    ['tag' => '#internships', 'count' => '543']
                  ];
                  $max = ($limitTrendingTopics !== null) ? max(0, min($limitTrendingTopics, count($topics))) : count($topics);
                  for ($i = 0; $i < $max; $i++): 
                    $t = $topics[$i];
                ?>
                <div class="side-topic-item">
                    <a href="#" class="side-topic-tag"><?php echo htmlspecialchars($t['tag']); ?></a>
                    <span class="side-topic-count"><?php echo htmlspecialchars($t['count']); ?> posts</span>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer (always show) -->
        <div class="side-footer">
            <div class="side-footer-links">
                <a href="../about/index.html">About</a>
                <a href="#">Help</a>
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
            </div>
            <p class="side-footer-copy">© 2023 SkillSwap Student Platform</p>
        </div>

    </aside>
    
    <?php
}
?>
