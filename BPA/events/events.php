<?php
session_start();
require_once '../database/DatabaseConnection.php';
require_once '../database/User.php';
// Database connection


$db = new DatabaseConnection();
$conn = $db->connection;
// Query to test event-subject relationships




$user = new User();

//If userid exists in $_SESSION, then account is being updated. 
//Otherwise, a new account is being created. 
//We will use this page to insert and update user accounts. 
if (!empty($_SESSION['user_id'])) {

  $user->populate($_SESSION['user_id']);
} else {
  header('location: ../landing/landing.php');
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {

  if (!empty($_GET['action']) && $_GET['action'] == 'logout') {

    $_SESSION = [];
    session_destroy();
    setcookie("PHPSESSID", "", time() - 3600, "/");
    header('location: ../landing/landing.php');
  }
}


?>
<?php
// Load subjects for the Create Event category list
$subjects = [];
$subRes = $conn->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC");
if ($subRes && $subRes->num_rows > 0) {
  while ($s = $subRes->fetch_assoc()) {
    $subjects[] = $s;
  }
}
?>
<?php require_once '../components/sidecontent.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Events | SkillSwap</title>
  <link rel="stylesheet" href="../calendar/calendar.css">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="../components/sidecontent.css">
</head>
<body class="has-side-content">

  <!-- Sidebar Navigation -->
  <aside class="sidebar" id="sidebar">
    <!-- Top Section: Logo & Profile -->
    <div class="sidebar-top">
      <div class="sidebar-logo">
        <div class="logo-placeholder"></div>
        <span class="logo-text">SkillSwap</span>
      </div>

      <div class="sidebar-profile">
        <div class="profile-avatar">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
          </svg>
        </div>
        <div class="profile-info">
          <a href="#" class="view-profile-link">View Profile</a>
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

        <a href="../dms/index.html" class="nav-link" data-tooltip="Direct Messages">
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

        <a href="events.php" class="nav-link active" data-tooltip="Events">
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

      <a href="#" class="nav-link" data-tooltip="Settings">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z" />
        </svg>
        <span>Settings</span>
      </a>

      <a href="../login/login.php" class="nav-link" data-tooltip="Log Out">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z" />
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
    <div class="page-content">
      <div class="events-container">
        <div class="events-main">
        <!-- Featured Events Carousel -->
        <div class="section-label">Featured Events For You</div>
        <div class="featured-carousel" aria-label="Featured events carousel">
          <button class="carousel-btn prev" aria-label="Previous featured">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 16 16" fill="currentColor"><path d="M11.354 1.646a.5.5 0 0 1 0 .708L6.707 7l4.647 4.646a.5.5 0 0 1-.708.708l-5-5a.5.5 0 0 1 0-.708l5-5a.5.5 0 0 1 .708 0z"/></svg>
          </button>
          <div class="featured-track">
            <!-- Featured events will be dynamically loaded here -->
          </div>
          <button class="carousel-btn next" aria-label="Next featured">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 16 16" fill="currentColor"><path d="M4.646 1.646a.5.5 0 0 1 .708 0l5 5a.5.5 0 0 1 0 .708l-5 5a.5.5 0 1 1-.708-.708L9.293 7 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
          </button>
          <div class="carousel-dots" aria-label="Featured pagination" role="tablist"></div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="events-topbar">
          <div class="search-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="search-icon" viewBox="0 0 16 16">
              <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
            </svg>
            <input type="text" class="events-search" placeholder="Search events...">
          </div>
          <select class="events-category">
            <option>All Categories</option>
            <?php foreach ($subjects as $sub): ?>
              <option value="<?php echo htmlspecialchars($sub['subject_id']); ?>"><?php echo htmlspecialchars($sub['subject_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Upcoming Events Section -->
        <div class="section-label">Upcoming Events</div>
        <div class="upcoming-events-grid">
          <!-- Events will be populated by JavaScript loader -->
        </div>

        <!-- Show all upcoming toggle -->
        <div class="show-all-wrapper" id="upcomingToggleWrapper" hidden>
          <button class="btn-show-all" id="toggleUpcoming" aria-expanded="false" aria-controls="upcomingGrid">
            <span class="btn-label">Show all upcoming events</span>
            <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M1.5 5.5a.5.5 0 0 1 .8-.4l5.2 3.9 5.2-3.9a.5.5 0 1 1 .6.8l-5.5 4.1a.5.5 0 0 1-.6 0L1.7 5.9a.5.5 0 0 1-.2-.4z"/></svg>
          </button>
        </div>

        <!-- Past Events Section (renamed to Coming Events) -->
        <div class="section-label">Coming Registered Events</div>
        <div class="past-events-grid">
          <!-- Past events will be populated by JavaScript loader -->
        </div>

        <!-- Show all coming toggle -->
        <div class="show-all-wrapper" id="pastToggleWrapper" hidden>
          <button class="btn-show-all" id="togglePast" aria-expanded="false" aria-controls="pastGrid">
            <span class="btn-label">Show all coming events</span>
            <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M1.5 5.5a.5.5 0 0 1 .8-.4l5.2 3.9 5.2-3.9a.5.5 0 1 1 .6.8l-5.5 4.1a.5.5 0 0 1-.6 0L1.7 5.9a.5.5 0 0 1-.2-.4z"/></svg>
          </button>
        </div>
        </div>
      </div>
      <?php renderSideContent('events'); ?>
  </main>

  <!-- Event Detail Modal -->
  <div class="event-modal" id="eventModal" hidden>
    <div class="modal-overlay" id="modalOverlay"></div>
    <div class="modal-content">
      <button class="modal-close" id="modalClose" aria-label="Close modal">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
          <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
        </svg>
      </button>
      
      <div class="modal-header">
        <img class="modal-event-image" id="modalImage" src="" alt="Event">
        <div class="modal-header-content">
          <h2 class="modal-event-title" id="modalTitle"></h2>
          <div class="modal-event-meta">
            <div class="modal-meta-item" id="modalDate"></div>
            <div class="modal-meta-item" id="modalTime"></div>
            <div class="modal-meta-item" id="modalLocation"></div>
            <div class="modal-meta-item" id="modalParticipants"></div>
            <div class="modal-meta-item" id="modalAttending"></div>
          </div>
          <div class="modal-event-tags" id="modalTags"></div>
        </div>
      </div>
      
      <div class="modal-body">
        <div class="modal-section">
          <h3 class="modal-section-title">About This Event</h3>
          <div class="modal-description">
            <p class="modal-description-text" id="modalDescription"></p>
            <button class="btn-expand-description" id="btnExpandDescription">Read more</button>
          </div>
        </div>
        
        <div class="modal-section">
          <h3 class="modal-section-title">Event Details</h3>
          <div class="modal-details-grid">
            <div class="modal-detail-row">
              <span class="modal-detail-label">Category</span>
              <span class="modal-detail-value" id="modalCategory">Workshop</span>
            </div>
            <div class="modal-detail-row">
              <span class="modal-detail-label">Organizer</span>
              <span class="modal-detail-value" id="modalOrganizer">Student Technology Association</span>
            </div>
            <div class="modal-detail-row">
              <span class="modal-detail-label">Capacity</span>
              <span class="modal-detail-value" id="modalCapacity">500 spots</span>
            </div>
            <div class="modal-detail-row">
              <span class="modal-detail-label">Registration</span>
              <span class="modal-detail-value" id="modalRegistration">Open until Oct 14</span>
            </div>
          </div>
        </div>
        
        <div class="modal-section">
          <h3 class="modal-section-title">Created By</h3>
          <div class="modal-creator">
            <div class="creator-avatar">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
              </svg>
            </div>
            <div class="creator-info">
              <div class="creator-name" id="modalCreator">John Smith</div>
              <div class="creator-role" id="modalCreatorRole">Event Coordinator</div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Host Controls Section (only visible to event host) -->
      <div class="modal-host-controls" id="modalHostControls" hidden>
        <h3 class="modal-section-title">Manage Event</h3>
        <div class="host-controls-grid">
          <button class="btn-host-action" id="btnEditTags">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M2 2a2 2 0 0 1 2-2h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 3 5.586V4a2 2 0 0 1-2-2m3.5 4a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
            </svg>
            Edit Tags
          </button>
          <button class="btn-host-action" id="btnEditDate">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M2 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
            </svg>
            Edit Date
          </button>
          <button class="btn-host-action" id="btnCloseRegistration">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
              <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
            </svg>
            Close Registration
          </button>
          <button class="btn-host-action danger" id="btnDeleteEvent">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
              <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
            </svg>
            Delete Event
          </button>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn-modal-register">
          Register Now
        </button>
      </div>
    </div>
  </div>

  <!-- Create Event Modal -->
  <div class="event-modal" id="createEventModal" hidden>
    <div class="modal-overlay" id="createModalOverlay"></div>
    <div class="modal-content create-event-modal-content">
      <button class="modal-close" id="createModalClose" aria-label="Close modal">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
          <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
        </svg>
      </button>
      
      <div class="create-event-header">
        <h2 class="create-event-title">Create New Event</h2>
        <p class="create-event-subtitle">Fill in the details to create your event</p>
      </div>
      
      <form class="create-event-form" id="createEventForm" novalidate>
        <div class="form-section">
          <h3 class="form-section-title">Basic Information</h3>
          
          <div class="form-group">
            <label class="form-label" for="eventTitle">Event Title <span class="required">*</span></label>
            <input type="text" id="eventTitle" class="form-input" placeholder="Enter event title" required>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="eventCategory">Category <span class="required">*</span></label>
            <select id="eventCategory" class="form-select" required>
              <option value="">Select category</option>
              <?php foreach ($subjects as $sub): ?>
                <option value="<?php echo htmlspecialchars($sub['subject_id']); ?>"><?php echo htmlspecialchars($sub['subject_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="eventDescription">Description <span class="required">*</span></label>
            <textarea id="eventDescription" class="form-textarea" rows="5" placeholder="Describe your event..." required></textarea>
            <span class="form-hint">Minimum 50 characters</span>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="eventImage">Event Image URL</label>
            <input type="url" id="eventImage" class="form-input" placeholder="https://example.com/image.jpg">
          </div>
        </div>
        
        <div class="form-section">
          <h3 class="form-section-title">Date & Time</h3>
          
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="eventDate">Date <span class="required">*</span></label>
              <input type="date" id="eventDate" class="form-input" required>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="eventStartTime">Start Time <span class="required">*</span></label>
              <input type="time" id="eventStartTime" class="form-input" required>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="eventEndTime">End Time</label>
              <input type="time" id="eventEndTime" class="form-input">
            </div>
          </div>
        </div>
        
        <div class="form-section">
          <h3 class="form-section-title">Location</h3>
          
          <div class="form-group">
            <label class="form-label" for="eventLocation">Venue <span class="required">*</span></label>
            <input type="text" id="eventLocation" class="form-input" placeholder="Enter location" required>
          </div>
        </div>
        
        <div class="form-section">
          <h3 class="form-section-title">Event Settings</h3>
          
          <div class="form-group">
            <label class="form-label" for="eventCapacity">Maximum Capacity</label>
            <input type="number" id="eventCapacity" class="form-input" placeholder="e.g., 100" min="1">
            <span class="form-hint">Leave empty for unlimited</span>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="eventOrganizer">Organizer Name <span class="required">*</span></label>
            <input type="text" id="eventOrganizer" class="form-input" placeholder="Your name or organization" required>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="eventTags">Tags</label>
            <div id="eventTags" class="tag-checkbox-list" aria-live="polite"></div>
            <span class="form-hint">Choose tags relevant to selected category (click to toggle)</span>
          </div>
          
          <div class="form-group">
            <label class="form-label">Visibility <span class="required">*</span></label>
            <div class="radio-group">
              <label class="radio-label">
                <input type="radio" name="eventVisibility" value="public" checked>
                <span class="radio-custom"></span>
                <div class="radio-content">
                  <span class="radio-title">Public</span>
                  <span class="radio-description">Anyone can see and register for this event</span>
                </div>
              </label>
              
              <label class="radio-label">
                <input type="radio" name="eventVisibility" value="private">
                <span class="radio-custom"></span>
                <div class="radio-content">
                  <span class="radio-title">Private</span>
                  <span class="radio-description">Only invited people can see this event</span>
                </div>
              </label>
            </div>
          </div>
          
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" id="eventRequireApproval">
              <span class="checkbox-custom"></span>
              <span>Require approval for registrations</span>
            </label>
          </div>
          
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" id="eventFeatured">
              <span class="checkbox-custom"></span>
              <span>Request featured placement</span>
            </label>
          </div>
        </div>
        
        <div class="form-section">
          <h3 class="form-section-title">Additional Information</h3>
          
          <div class="form-group">
            <label class="form-label" for="eventRegistrationDeadline">Registration Deadline</label>
            <input type="date" id="eventRegistrationDeadline" class="form-input">
          </div>
          
          <div class="form-group">
            <label class="form-label" for="eventContactEmail">Contact Email</label>
            <input type="email" id="eventContactEmail" class="form-input" placeholder="contact@example.com">
          </div>
        </div>
        
        <div class="form-actions">
          <button type="button" class="btn-form-cancel" id="btnCancelCreate">Cancel</button>
          <button type="submit" class="btn-form-submit">Create Event</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Tags Modal -->
  <div class="event-modal" id="editTagsModal" hidden>
    <div class="modal-overlay" id="editTagsOverlay"></div>
    <div class="modal-content create-event-modal-content" style="max-width: 600px;">
      <button class="modal-close" id="editTagsClose" aria-label="Close modal">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
          <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
        </svg>
      </button>
      
      <div class="create-event-header">
        <h2 class="create-event-title">Edit Event Tags</h2>
        <p class="create-event-subtitle">Choose tags relevant to selected category (click to toggle)</p>
      </div>
      
      <form class="create-event-form" id="editTagsForm">
        <div class="form-section">
          <h3 class="form-section-title">Tags</h3>
          <div class="form-group">
            <div class="tag-selector" id="editTagsSelector">
              <!-- Tags will be populated dynamically -->
            </div>
          </div>
        </div>
        
        <div class="form-actions">
          <button type="button" class="btn-form-cancel" id="btnCancelEditTags">Cancel</button>
          <button type="submit" class="btn-form-submit">Save Tags</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Date Modal -->
  <div class="event-modal" id="editDateModal" hidden>
    <div class="modal-overlay" id="editDateOverlay"></div>
    <div class="modal-content create-event-modal-content" style="max-width: 600px;">
      <button class="modal-close" id="editDateClose" aria-label="Close modal">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
          <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
        </svg>
      </button>
      
      <div class="create-event-header">
        <h2 class="create-event-title">Edit Event Date & Time</h2>
        <p class="create-event-subtitle">Update the event schedule</p>
      </div>
      
      <form class="create-event-form" id="editDateForm" novalidate>
        <div class="form-section">
          <h3 class="form-section-title">Date & Time</h3>
          
          <div class="form-row">
            <div class="form-group">
              <label for="editEventDate">Date <span class="required">*</span></label>
              <input type="date" id="editEventDate" name="date" required>
            </div>
            
            <div class="form-group">
              <label for="editEventStartTime">Start Time <span class="required">*</span></label>
              <input type="time" id="editEventStartTime" name="startTime" required>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="editEventEndTime">End Time <span class="required">*</span></label>
              <input type="time" id="editEventEndTime" name="endTime" required>
            </div>
            
            <div class="form-group">
              <label for="editEventDeadline">Registration Deadline <span class="required">*</span></label>
              <input type="date" id="editEventDeadline" name="deadline" required>
            </div>
          </div>
        </div>
        
        <div class="form-actions">
          <button type="button" class="btn-form-cancel" id="btnCancelEditDate">Cancel</button>
          <button type="submit" class="btn-form-submit">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Floating Create Event Button -->
  <button class="fab-create-event" id="fabCreateEvent" aria-label="Create Event">
    <svg class="fab-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
      <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
    </svg>
    <span class="fab-label">Create Event</span>
  </button>

  <script>
    // Sidebar collapse on double-click
    document.addEventListener('dblclick', function() {
      const sidebar = document.getElementById('sidebar');
      if (sidebar) {
        sidebar.classList.toggle('collapsed');
      }
    });

    // Prevent text selection on double-click
    document.addEventListener('mousedown', function(e) {
      if (e.detail > 1) {
        e.preventDefault();
      }
    });

    // Theme toggle
    document.addEventListener('DOMContentLoaded', () => {
      const themeToggle = document.getElementById('themeToggle');
      const body = document.body;
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme === 'light') body.classList.add('light-mode');
      themeToggle.addEventListener('click', () => {
        body.classList.toggle('light-mode');
        localStorage.setItem('theme', body.classList.contains('light-mode') ? 'light' : 'dark');
      });
    });
  </script>
  <script>
    // Pass PHP session data to JavaScript
    window.CURRENT_USER_ID = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
  </script>
  <script src="script.js"></script>
  <script src="modal.js"></script>
  <script src="create-event.js"></script>
  <script src="edit-host-modals.js"></script>
  <script src="featured-loader.js"></script>
  <script src="events-loader.js"></script>
  <script src="../components/sidecontent.js"></script>
</body>
</html>