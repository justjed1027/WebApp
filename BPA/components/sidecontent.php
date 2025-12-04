<?php
/**
 * Side Content Component
 * Displays contextual information based on the current page
 * 
 * @param string $currentPage - Current page identifier (calendar, events, forum, etc.)
 * @param array $options - Additional options for customization
 */

function renderSideContent($currentPage = '', $options = []) {
    $showNotifications = !in_array($currentPage, ['notifications']);
    $showUpcomingEvents = !in_array($currentPage, ['calendar', 'events']);
    $showRecentDMs = !in_array($currentPage, ['dms', 'messages']);
    $showSuggestedCollaborators = !in_array($currentPage, ['connections']);
    $showTrendingTopics = !in_array($currentPage, ['forum']);
    ?>
    
    <aside class="side-content" id="sideContent">
        
        <?php if ($showNotifications): ?>
        <!-- Notifications Widget -->
        <div class="side-card">
            <div class="side-card-header">
                <h3 class="side-card-title">Notifications</h3>
                <a href="#" class="side-card-link">See All</a>
            </div>
            <div class="side-card-body">
                <div class="notification-item unread">
                    <div class="notification-icon notification-like">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143q.09.083.176.171a3 3 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15"/>
                        </svg>
                    </div>
                    <div class="notification-content">
                        <p><strong>Sarah Chen</strong> liked your post</p>
                        <span class="notification-time">5m ago</span>
                    </div>
                </div>
                <div class="notification-item unread">
                    <div class="notification-icon notification-comment">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M2.678 11.894a1 1 0 0 1 .287.801 11 11 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8 8 0 0 0 8 14c3.996 0 7-2.807 7-6s-3.004-6-7-6-7 2.808-7 6c0 1.468.617 2.83 1.678 3.894m-.493 3.905a22 22 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a10 10 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105"/>
                        </svg>
                    </div>
                    <div class="notification-content">
                        <p><strong>Alex Kim</strong> commented on your post</p>
                        <span class="notification-time">12m ago</span>
                    </div>
                </div>
                <div class="notification-item">
                    <div class="notification-icon notification-event">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                        </svg>
                    </div>
                    <div class="notification-content">
                        <p>Event reminder: <strong>Web Dev Workshop</strong></p>
                        <span class="notification-time">1h ago</span>
                    </div>
                </div>
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
                <div class="side-event-item">
                    <div class="side-event-date">
                        <span class="side-event-day">14</span>
                        <span class="side-event-month">Oct</span>
                    </div>
                    <div class="side-event-info">
                        <h4 class="side-event-title">Data Structures Final Exam</h4>
                        <p class="side-event-meta">10:00 AM - 12:00 PM</p>
                        <span class="side-event-badge exam">Exam</span>
                    </div>
                </div>
                <div class="side-event-item">
                    <div class="side-event-date">
                        <span class="side-event-day">17</span>
                        <span class="side-event-month">Oct</span>
                    </div>
                    <div class="side-event-info">
                        <h4 class="side-event-title">Web Development Workshop</h4>
                        <p class="side-event-meta">2:00 PM - 5:00 PM</p>
                        <span class="side-event-badge workshop">Workshop</span>
                    </div>
                </div>
                <div class="side-event-item">
                    <div class="side-event-date">
                        <span class="side-event-day">19</span>
                        <span class="side-event-month">Oct</span>
                    </div>
                    <div class="side-event-info">
                        <h4 class="side-event-title">Research Paper Deadline</h4>
                        <p class="side-event-meta">11:59 PM</p>
                        <span class="side-event-badge assignment">Assignment</span>
                    </div>
                </div>
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
                <div class="side-dm-item unread">
                    <div class="side-dm-avatar avatar-1"></div>
                    <div class="side-dm-content">
                        <h4 class="side-dm-name">Jessica Williams</h4>
                        <p class="side-dm-message">Can you review my code before...</p>
                        <span class="side-dm-time">2m ago</span>
                    </div>
                    <span class="side-dm-badge">3</span>
                </div>
                <div class="side-dm-item">
                    <div class="side-dm-avatar avatar-2"></div>
                    <div class="side-dm-content">
                        <h4 class="side-dm-name">Michael Chen</h4>
                        <p class="side-dm-message">Thanks for the help with the project!</p>
                        <span class="side-dm-time">1h ago</span>
                    </div>
                </div>
                <div class="side-dm-item">
                    <div class="side-dm-avatar avatar-3"></div>
                    <div class="side-dm-content">
                        <h4 class="side-dm-name">Study Group</h4>
                        <p class="side-dm-message">Meeting at 3pm tomorrow?</p>
                        <span class="side-dm-time">3h ago</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($showSuggestedCollaborators): ?>
        <!-- Suggested Collaborators Widget -->
        <div class="side-card">
            <div class="side-card-header">
                <h3 class="side-card-title">Suggested Collaborators</h3>
                <a href="../connections/connections.html" class="side-card-link">See All</a>
            </div>
            <div class="side-card-body">
                <div class="side-collab-item">
                    <div class="side-collab-avatar avatar-4"></div>
                    <div class="side-collab-info">
                        <h4 class="side-collab-name">Emily Chen</h4>
                        <p class="side-collab-field">Data Science</p>
                    </div>
                    <button class="side-collab-btn">Follow</button>
                </div>
                <div class="side-collab-item">
                    <div class="side-collab-avatar avatar-5"></div>
                    <div class="side-collab-info">
                        <h4 class="side-collab-name">Marcus Johnson</h4>
                        <p class="side-collab-field">Mechanical Engineering</p>
                    </div>
                    <button class="side-collab-btn">Follow</button>
                </div>
                <div class="side-collab-item">
                    <div class="side-collab-avatar avatar-6"></div>
                    <div class="side-collab-info">
                        <h4 class="side-collab-name">Sophia Williams</h4>
                        <p class="side-collab-field">Graphic Design</p>
                    </div>
                    <button class="side-collab-btn">Follow</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($showTrendingTopics): ?>
        <!-- Trending Topics Widget -->
        <div class="side-card">
            <div class="side-card-header">
                <h3 class="side-card-title">Trending Topics</h3>
                <a href="../forum/forums.html" class="side-card-link">See All</a>
            </div>
            <div class="side-card-body">
                <div class="side-topic-item">
                    <a href="#" class="side-topic-tag">#machinelearning</a>
                    <span class="side-topic-count">1,243 posts</span>
                </div>
                <div class="side-topic-item">
                    <a href="#" class="side-topic-tag">#reactjs</a>
                    <span class="side-topic-count">892 posts</span>
                </div>
                <div class="side-topic-item">
                    <a href="#" class="side-topic-tag">#finalexams</a>
                    <span class="side-topic-count">754 posts</span>
                </div>
                <div class="side-topic-item">
                    <a href="#" class="side-topic-tag">#capstoneprojects</a>
                    <span class="side-topic-count">621 posts</span>
                </div>
                <div class="side-topic-item">
                    <a href="#" class="side-topic-tag">#internships</a>
                    <span class="side-topic-count">543 posts</span>
                </div>
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
