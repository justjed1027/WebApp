<?php
session_start();

require_once '../database/DatabaseConnection.php';
require_once '../database/User.php';
require_once '../components/sidecontent.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../landing/landing.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
  if (!empty($_GET['action']) && $_GET['action'] == 'logout') {
    $_SESSION = [];
    session_destroy();
    setcookie("PHPSESSID", "", time() - 3600, "/");
    header('location: ../landing/landing.php');
    exit();
  }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Direct Messages | SkillSwap</title>
  <link rel="icon" type="image/png" href="../images/skillswaplogotrans.png">
  <!-- Shared styles for navbar and layout -->
  <link rel="stylesheet" href="../calendar/calendar.css">
  <link rel="stylesheet" href="../components/sidecontent.css">
  <!-- Page-specific styles -->
  <link rel="stylesheet" href="style.css">
</head>
<body class="has-side-content">

  <!-- Sidebar Navigation (reused from existing pages) -->
  <aside class="sidebar" id="sidebar">
    <!-- Top Section: Logo & Profile -->
    <div class="sidebar-top">
      <div class="sidebar-logo">
        <img src="../images/skillswaplogotrans.png" style="width:40px;">
        <span class="logo-text">SkillSwap</span>
      </div>

      <div class="sidebar-profile">
        <div class="profile-avatar">
          <?php require_once '../components/sidecontent.php'; echo renderProfileAvatar(); ?>
        </div>
        <div class="profile-info">
          <a href="..//profile/profile.php" class="view-profile-link">View Profile - <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === 1){
          echo 'Admin';}

           else{
            echo 'Student';  
        } 
        ?></a>
        </div>
      </div>
    </div>

    <!-- Middle Section: Main Navigation -->
    <div class="sidebar-middle">
      <div class="nav-group">
        <a href="../courses/courses.php" class="nav-link" data-tooltip="Dashboard">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5" />
          </svg>
          <span>Dashboard</span>
        </a>

        <a href="../post/post.php" class="nav-link" data-tooltip="Posts">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M6.428 1.151C6.708.591 7.213 0 8 0s1.292.592 1.572 1.151C9.861 1.73 10 2.431 10 3v3.691l5.17 2.585a1.5 1.5 0 0 1 .83 1.342V12a.5.5 0 0 1-.582.493l-5.507-.918-.375 2.253 1.318 1.318A.5.5 0 0 1 10.5 16h-5a.5.5 0 0 1-.354-.854l1.319-1.318-.376-2.253-5.507.918A.5.5 0 0 1 0 12v-1.382a1.5 1.5 0 0 1 .83-1.342L6 6.691V3c0-.568.14-1.271.428-1.849"/>
          </svg>
          <span>Posts</span>
        </a>

        <a href="../dms/dms.php" class="nav-link active" data-tooltip="Direct Messages">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M16 8c0 3.866-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.584.296-1.925.864-4.181 1.234-.2.032-.352-.176-.273-.362.354-.836.674-1.95.77-2.966C.744 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7M5 8a1 1 0 1 0-2 0 1 1 0 0 0 2 0m4 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
          </svg>
          <span>DMs</span>
        </a>

        <a href="../connections/connections.php" class="nav-link" data-tooltip="Connections">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5" />
          </svg>
          <span>Connections</span>
        </a>

        <a href="../calendar/calendar.php" class="nav-link" data-tooltip="Calendar">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z" />
          </svg>
          <span>Calendar</span>
        </a>

        <a href="../events/events.php" class="nav-link" data-tooltip="Events">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z" />
            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z" />
          </svg>
          <span>Events</span>
        </a>
      </div>
    </div>

    <!-- Bottom Section: Utilities -->
    <div class="sidebar-bottom">
      <div class="nav-divider"></div>

      <a href="#" class="nav-link" data-tooltip="Edit User">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z" />
        </svg>
        <span>Edit User</span>
      </a>

      <a href="dms.php?action=logout" class="nav-link" data-tooltip="Log Out">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 0-1 0z" />
          <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z" />
        </svg>
        <span>Log Out</span>
      </a>

      <div class="theme-toggle">
        <button class="theme-toggle-btn" id="themeToggle">
          <div class="toggle-switch">
            <div class="toggle-slider">
              <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6m0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8m10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0m-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707M4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708" />
              </svg>
              <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                <path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278M4.858 1.311A7.27 7.27 0 0 0 1.025 7.71c0 4.02 3.279 7.276 7.319 7.276a7.32 7.32 0 0 0 5.205-2.162q-.506.063-1.029.063c-4.61 0-8.343-3.714-8.343-8.29 0-1.167.242-2.278.681-3.286" />
              </svg>
            </div>
          </div>
        </button>
      </div>
    </div>
  </aside>

  <!-- Main Content Area -->
  <main class="main-content">
    <div class="calendar-container">
      <div class="dm-container">
        <!-- Left Sidebar: Conversations List -->
        <div class="dm-sidebar">
          <div class="dm-sidebar-header">
            <div class="dm-sidebar-tabs">
              <button class="dm-tab-btn active" data-tab="messages">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M16 8c0 3.866-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.584.296-1.925.864-4.181 1.234-.2.032-.352-.176-.273-.362.354-.836.674-1.95.77-2.966C.744 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7M5 8a1 1 0 1 0-2 0 1 1 0 0 0 2 0m4 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
                </svg>
                Messages
              </button>
              <button class="dm-tab-btn" data-tab="requests" id="requestsTabBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                  <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                </svg>
                <span>Requests</span>
                <span class="request-badge" id="requestBadge" style="display: none;">0</span>
              </button>
            </div>
          </div>
      <div class="dm-search">
        <input type="text" id="searchInput" placeholder="Search messages...">
      </div>
      
      <!-- Messages Tab -->
      <div class="dm-tab-content active" id="messagesTab">
        <div class="dm-list" id="conversationList">
          <!-- Conversations will be loaded here by JavaScript -->
          <div class="dm-empty-state">Loading conversations...</div>
        </div>
      </div>
      
      <!-- Requests Tab -->
      <div class="dm-tab-content" id="requestsTab">
        <div class="dm-requests" id="requestsList">
          <!-- Session requests will be loaded here by JavaScript -->
          <div class="dm-empty-state">No pending requests</div>
        </div>
      </div>
    </div>
    <div class="dm-main">
      <div class="dm-header" id="chatHeader" style="display: none;">
        <div class="dm-header-avatar" id="headerAvatar"></div>
        <div class="dm-header-info">
          <div class="dm-header-name" id="headerName"></div>
          <div class="dm-header-status">Online</div>
        </div>
        <div class="dm-header-actions">
          <button title="Request Private Session" id="requestSessionBtn" class="session-request-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-incognito" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="m4.736 1.968-.892 3.269-.014.058C2.113 5.568 1 6.006 1 6.5 1 7.328 4.134 8 8 8s7-.672 7-1.5c0-.494-1.113-.932-2.83-1.205l-.014-.058-.892-3.27c-.146-.533-.698-.849-1.239-.734C9.411 1.363 8.62 1.5 8 1.5s-1.411-.136-2.025-.267c-.541-.115-1.093.2-1.239.735m.015 3.867a.25.25 0 0 1 .274-.224c.9.092 1.91.143 2.975.143a30 30 0 0 0 2.975-.143.25.25 0 0 1 .05.498c-.918.093-1.944.145-3.025.145s-2.107-.052-3.025-.145a.25.25 0 0 1-.224-.274M3.5 10h2a.5.5 0 0 1 .5.5v1a1.5 1.5 0 0 1-3 0v-1a.5.5 0 0 1 .5-.5m-1.5.5q.001-.264.085-.5H2a.5.5 0 0 1 0-1h3.5a1.5 1.5 0 0 1 1.488 1.312 3.5 3.5 0 0 1 2.024 0A1.5 1.5 0 0 1 10.5 9H14a.5.5 0 0 1 0 1h-.085q.084.236.085.5v1a2.5 2.5 0 0 1-5 0v-.14l-.21-.07a2.5 2.5 0 0 0-1.58 0l-.21.07v.14a2.5 2.5 0 0 1-5 0zm8.5-.5h2a.5.5 0 0 1 .5.5v1a1.5 1.5 0 0 1-3 0v-1a.5.5 0 0 1 .5-.5"/>
            </svg>
          </button>
        </div>
      </div>
      <div class="dm-messages" id="messagesContainer">
        <!-- Messages will be loaded here by JavaScript -->
        <div class="dm-empty-state">Select a conversation to start messaging</div>
      </div>
      <div class="dm-input-row" id="messageInput" style="display: none;">
        <input type="text" class="dm-input" id="messageText" placeholder="Type a message...">
        <button class="dm-send-btn" id="sendBtn" title="Send">&#10148;</button>
      </div>
    </div>
  </div>
    </div><!-- Close calendar-container -->

    <!-- Side Content -->
    <?php renderSideContent('dms', [
        'limit' => ['notifications' => 1, 'upcoming_events' => 2, 'suggested_collaborators' => 1]
    ]); ?>
    
  </main><!-- Close main-content -->

  <!-- Toast Notification Container -->
  <div id="notificationContainer" class="notification-container"></div>

  <!-- Private Session Request Modal -->
  <div id="sessionRequestModal" class="modal" style="display: none;">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Request Private Session</h2>
        <button class="modal-close" id="closeSessionModal">&times;</button>
      </div>
      <form id="sessionRequestForm">
        <div class="form-group">
          <label for="areaOfHelp">Area Needing Help *</label>
          <select id="areaOfHelp" required>
            <option value="">-- Select Subject --</option>
            <option value="Mathematics">Mathematics</option>
            <option value="Science">Science</option>
            <option value="English">English</option>
            <option value="History">History</option>
            <option value="Programming">Programming</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div class="form-group">
          <label for="sessionDescription">What would you like help with? *</label>
          <textarea id="sessionDescription" placeholder="Brief description of what you need help with..." required rows="4"></textarea>
        </div>

        <div class="form-group">
          <label for="sessionDuration">Preferred Duration *</label>
          <select id="sessionDuration" required>
            <option value="">-- Select Duration --</option>
            <option value="15">15 minutes</option>
            <option value="30">30 minutes</option>
            <option value="60">1 hour</option>
            <option value="120">2 hours</option>
            <option value="flexible">Flexible</option>
          </select>
        </div>

        <div class="form-group">
          <label for="sessionType">Session Type *</label>
          <select id="sessionType" required>
            <option value="">-- Select Type --</option>
            <option value="tutoring">Tutoring</option>
            <option value="study_group">Study Group</option>
            <option value="collaboration">Collaboration</option>
            <option value="review">Review/Feedback</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div class="form-actions">
          <button type="button" class="btn-cancel" id="cancelSessionModal">Cancel</button>
          <button type="submit" class="btn-submit">Send Request</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Time Selection Modal for Accepting Request -->
  <div id="timeSelectionModal" class="modal" style="display: none;">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Choose Session Time</h2>
        <button class="modal-close" id="closeTimeModal">&times;</button>
      </div>
      <form id="timeSelectionForm">
        <div class="form-group">
          <label for="sessionDate">Preferred Date *</label>
          <input type="date" id="sessionDate" required />
        </div>

        <div class="form-group">
          <label for="sessionStartTime">Start Time *</label>
          <input type="time" id="sessionStartTime" required />
        </div>

        <div class="form-group">
          <label for="sessionEndTime">End Time *</label>
          <input type="time" id="sessionEndTime" required />
        </div>

        <div class="form-group">
          <label for="sessionNotes">Additional Notes (Optional)</label>
          <textarea id="sessionNotes" placeholder="Any additional details or preferences..." rows="3"></textarea>
        </div>

        <div class="form-actions">
          <button type="button" class="btn-cancel" id="cancelTimeModal">Cancel</button>
          <button type="submit" class="btn-submit">Confirm & Accept</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Waiting Room Modal -->
  <div id="waitingRoomModal" class="modal" style="display: none;">
    <div class="modal-content waiting-room">
      <div class="waiting-room-content">
        <h2>Waiting for Participant</h2>
        <p id="waitingUserName"></p>
        <div class="waiting-spinner">
          <div class="spinner"></div>
        </div>
        <p class="waiting-message">Please wait while we connect you with your peer...</p>
        <button class="btn-cancel" onclick="closeWaitingRoom()">Cancel</button>
      </div>
    </div>
  </div>

  <!-- Session Room Modal -->
  <div id="sessionRoomModal" class="modal" style="display: none;">
    <div class="modal-content session-room">
      <div class="session-room-header">
        <div class="session-info">
          <h3 id="sessionTitle">Session Room</h3>
          <p id="sessionDetails"></p>
        </div>
        
        <button class="place-review-btn" id="placeReviewBtn" title="Place a review" aria-haspopup="dialog" aria-controls="reviewModal">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right:8px;">
            <path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.523-3.356c.33-.314.158-.888-.283-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.93 0L5.69 4.12l-4.898.696c-.441.062-.613.636-.283.95l3.523 3.356-.83 4.73Z"/>
          </svg>
          Place Review
          <span class="review-experimental-badge" aria-label="Experimental feature">Experimental</span>
        </button>
        <button class="close-btn" onclick="closeSessionRoom()">&times;</button>
      </div>
      <div class="session-messages" id="sessionMessagesContainer"></div>
      <div class="session-input-area">
        <input type="text" id="sessionMessageInput" placeholder="Type your message..." onkeypress="handleSessionKeyPress(event)">
        <button onclick="sendSessionMessage()" class="send-btn">Send</button>
      </div>
    </div>
  </div>

  <!-- Experimental Review Modal -->
  <div id="reviewModal" class="modal" style="display: none;">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Place a Review</h2>
        <button class="modal-close" id="closeReviewModal" aria-label="Close">&times;</button>
      </div>
      <form id="reviewForm">
        <div class="experimental-banner">
          <strong>Experimental:</strong> This review system is in testing and may not persist.
        </div>
        <div class="form-group">
          <label for="reviewRating">Rating</label>
          <div id="reviewRating" class="star-rating" role="radiogroup" aria-label="Star rating">
            <button type="button" class="star" data-value="1" aria-label="1 star">★</button>
            <button type="button" class="star" data-value="2" aria-label="2 stars">★</button>
            <button type="button" class="star" data-value="3" aria-label="3 stars">★</button>
            <button type="button" class="star" data-value="4" aria-label="4 stars">★</button>
            <button type="button" class="star" data-value="5" aria-label="5 stars">★</button>
            <input type="hidden" name="rating" id="ratingValue" value="0" />
          </div>
        </div>
        <div class="form-group">
          <label for="reviewTitle">Title (optional)</label>
          <input type="text" id="reviewTitle" name="title" placeholder="e.g., Great session on calculus" style="padding:12px 14px;border:1px solid var(--border-color);border-radius:8px;background:var(--background-card);color:var(--text-primary);font-family:inherit;font-size:0.95rem;" />
        </div>
        <div class="form-group">
          <label for="reviewText">Your Review *</label>
          <textarea id="reviewText" name="text" required rows="4" placeholder="Share your experience..." style="resize:vertical;"></textarea>
        </div>
        <div class="form-actions">
          <button type="button" class="btn-cancel" id="cancelReview">Cancel</button>
          <button type="submit" class="btn-submit">Submit Review</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Review modal handlers (experimental)
    (function() {
      const openBtn = document.getElementById('placeReviewBtn');
      const modal = document.getElementById('reviewModal');
      const closeBtn = document.getElementById('closeReviewModal');
      const cancelBtn = document.getElementById('cancelReview');
      const form = document.getElementById('reviewForm');
      const stars = Array.from(document.querySelectorAll('#reviewRating .star'));
      const ratingValue = document.getElementById('ratingValue');

      function openModal() { modal.style.display = 'flex'; }
      function closeModal() { modal.style.display = 'none'; }

      openBtn.addEventListener('click', openModal);
      closeBtn.addEventListener('click', closeModal);
      cancelBtn.addEventListener('click', closeModal);
      window.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });
      modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

      // Star rating interactions
      stars.forEach((star, idx) => {
        star.addEventListener('mouseenter', () => highlight(idx + 1));
        star.addEventListener('mouseleave', () => highlight(parseInt(ratingValue.value || '0', 10)));
        star.addEventListener('click', () => { ratingValue.value = String(idx + 1); highlight(idx + 1); });
      });

      function highlight(count) {
        stars.forEach((s, i) => {
          s.classList.toggle('active', i < count);
        });
      }

      // Submit (experimental: no backend persistence)
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const rating = parseInt(ratingValue.value || '0', 10);
        const title = (document.getElementById('reviewTitle').value || '').trim();
        const text = (document.getElementById('reviewText').value || '').trim();

        if (!text) { showToast('Please write a review', 'warning'); return; }

        // Simulate success
        closeModal();
        form.reset();
        ratingValue.value = '0';
        highlight(0);
        showToast('Review submitted (experimental)', 'info');
      });

      // Minimal toast helper using existing container
      function showToast(message, type) {
        const container = document.getElementById('notificationContainer');
        if (!container) return alert(message);
        const toast = document.createElement('div');
        toast.className = `toast ${type || 'info'}`;
        toast.innerHTML = `
          <div class="toast-icon">💬</div>
          <div class="toast-content">
            <div class="toast-title">Review</div>
            <div class="toast-message">${message}</div>
          </div>
          <button class="toast-close" aria-label="Close">&times;</button>
        `;
        const close = () => { toast.classList.add('removing'); setTimeout(() => toast.remove(), 300); };
        toast.querySelector('.toast-close').addEventListener('click', close);
        setTimeout(close, 3500);
        container.appendChild(toast);
      }
    })();
  </script>
  <script>
    // Set current user ID from PHP session
    window.currentUserId = <?php echo $_SESSION['user_id']; ?>;
    
    // Check if coming from connections page with user_id
    const urlParams = new URLSearchParams(window.location.search);
    window.startUserId = urlParams.has('user_id') ? parseInt(urlParams.get('user_id')) : null;
    console.log('URL parameter user_id:', window.startUserId);
    console.log('Current user ID:', window.currentUserId);
  </script>
  <script src="../components/sidecontent.js"></script>
  <script src="script.js"></script>
</body>
</html>