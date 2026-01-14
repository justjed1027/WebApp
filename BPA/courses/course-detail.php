<?php
session_start();
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login/login.php');
  exit();
}
require_once '../components/sidecontent.php';

$user_id = $_SESSION['user_id'];
$db = new DatabaseConnection();
$conn = $db->connection;

// Get subject ID from URL parameter
$subject_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$subject_id) {
  header('Location: courses.php');
  exit();
}

// Fetch subject details
$subjectQuery = "
  SELECT s.subject_id, s.subject_name, s.description, sc.category_id, sc.category_name
  FROM subjects s
  JOIN subjectcategories sc ON s.category_id = sc.category_id
  WHERE s.subject_id = ?
";
$stmt = $conn->prepare($subjectQuery);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
$subject = $result->fetch_assoc();
$stmt->close();

if (!$subject) {
  header('Location: courses.php');
  exit();
}

// Fetch students learning this subject
$studentsLearningQuery = "SELECT COUNT(DISTINCT ui_user_id) as count FROM user_interests WHERE ui_subject_id = ?";
$stmt = $conn->prepare($studentsLearningQuery);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
$studentsLearning = $result->fetch_assoc()['count'];
$stmt->close();

// Fetch students fluent in this subject
$studentsFuentQuery = "SELECT COUNT(DISTINCT us_user_id) as count FROM user_skills WHERE us_subject_id = ?";
$stmt = $conn->prepare($studentsFuentQuery);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
$studentsFluent = $result->fetch_assoc()['count'];
$stmt->close();

// Fetch events for this subject (excluding events user is registered for)
$eventsQuery = "
  SELECT e.events_id, e.events_title, e.events_date, e.events_start, e.events_end, 
         e.events_location, e.events_description, e.events_img, e.events_capacity,
         e.events_organization, e.events_deadline,
         GROUP_CONCAT(DISTINCT s.subject_name SEPARATOR ', ') as subjects,
         (SELECT COUNT(*) FROM event_participants ep WHERE ep.ep_event_id = e.events_id) as registration_count,
         (EXISTS(SELECT 1 FROM event_participants ep2 WHERE ep2.ep_event_id = e.events_id AND ep2.ep_user_id = ?)) AS is_registered
  FROM events e
  JOIN event_subjects es ON e.events_id = es.es_event_id
  LEFT JOIN event_subjects es2 ON e.events_id = es2.es_event_id
  LEFT JOIN subjects s ON es2.es_subject_id = s.subject_id
  WHERE es.es_subject_id = ? 
    AND e.events_date >= CURDATE()
    AND NOT EXISTS(SELECT 1 FROM event_participants ep3 WHERE ep3.ep_event_id = e.events_id AND ep3.ep_user_id = ?)
  GROUP BY e.events_id
  ORDER BY e.events_date ASC
";
$stmt = $conn->prepare($eventsQuery);
$stmt->bind_param("iii", $user_id, $subject_id, $user_id);
$stmt->execute();
$eventsResult = $stmt->get_result();
$events = [];
while ($row = $eventsResult->fetch_assoc()) {
  $events[] = $row;
}
$stmt->close();

// Fetch posts/forum discussions for this subject
// Note: The current posts table doesn't have subject_id, so we'll fetch recent posts
// You may want to add a posts_subject_id column or use a different approach
$postsQuery = "
  SELECT p.post_id, p.content as posts_title, u.user_username as user_name, p.created_at as posts_timestamp,
         (SELECT COUNT(*) FROM post_comments WHERE post_id = p.post_id) as comment_count
  FROM posts p
  JOIN user u ON p.user_id = u.user_id
  ORDER BY p.created_at DESC
  LIMIT 10
";
$stmt = $conn->prepare($postsQuery);
$stmt->execute();
$postsResult = $stmt->get_result();
$posts = [];
while ($row = $postsResult->fetch_assoc()) {
  $posts[] = $row;
}
$stmt->close();

$course = [
  'id' => $subject['subject_id'],
  'title' => $subject['subject_name'],
  'group' => $subject['category_name'],
  'groupId' => strtolower(str_replace(' ', '-', $subject['category_name'])),
  'description' => $subject['description'],
  'studentsLearning' => $studentsLearning,
  'studentsFluent' => $studentsFluent,
  'relatedEvents' => $events,
  'recentPosts' => $posts
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkillSwap — <?php echo $course['title']; ?></title>
  <link rel="stylesheet" href="courses.css">
  <link rel="stylesheet" href="../components/sidecontent.css">
</head>
<body class="has-side-content">

  <!-- Sidebar Navigation (same as before) -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-top">
      <div class="sidebar-logo">
        <div class="logo-placeholder"><img src="../images/skillswaplogotrans.png" style="width:40px;"></div>
        <span class="logo-text">SkillSwap</span>
      </div>

      <div class="sidebar-profile">
        <div class="profile-avatar">
          <?php require_once '../components/sidecontent.php'; echo renderProfileAvatar(); ?>
        </div>
        <div class="profile-info">
          <a href="#" class="view-profile-link">View Profile- <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === 1){
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
        <a href="courses.php" class="nav-link active" data-tooltip="Dashboard">
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
        
        <a href="../dms/dms.php" class="nav-link" data-tooltip="Direct Messages">
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

    <div class="sidebar-bottom">
      <div class="nav-divider"></div>

      <a href="#" class="nav-link" data-tooltip="Edit User">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z" />
        </svg>
        <span>Edit User</span>
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

  <!-- Main Content -->
  <main class="main-content">
    <div class="page-content">
      <div class="course-detail-content">
        <!-- Navigation Breadcrumb -->
        <div class="breadcrumb">
          <a href="../courses/courses.php" class="breadcrumb-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5"/>
            </svg>
            Dashboard
          </a>
          <svg class="breadcrumb-separator" xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/>
          </svg>
          <a href="course-list.php?category=<?php echo $subject['category_id']; ?>" class="breadcrumb-link"><?php echo $course['group']; ?></a>
          <svg class="breadcrumb-separator" xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/>
          </svg>
          <span class="breadcrumb-current"><?php echo $course['title']; ?></span>
        </div>

        <!-- Course Header -->
        <div class="course-detail-header">
          <div class="course-header-content">
            <div class="course-meta-row">
              <span class="course-group-badge"><?php echo $course['group']; ?></span>
            </div>
            <h1><?php echo $course['title']; ?></h1>
            <p class="course-short-desc"><?php echo $course['description']; ?></p>
            
            <div class="course-header-stats">
              <div class="stat-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/>
                </svg>
                <div>
                  <strong><?php echo $course['studentsLearning']; ?></strong>
                  <span>Students Learning</span>
                </div>
              </div>
              
              <div class="stat-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0"/>
                  <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/>
                </svg>
                <div>
                  <strong><?php echo $course['studentsFluent']; ?></strong>
                  <span>Students Fluent</span>
                </div>
              </div>
              
              <div class="stat-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/>
                  <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                </svg>
                <div>
                  <strong><?php echo count($course['relatedEvents']); ?></strong>
                  <span>Upcoming Events</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="course-tabs">
          <button class="tab-btn active" data-tab="overview">Overview</button>
          <button class="tab-btn" data-tab="students">Students</button>
          <button class="tab-btn" data-tab="events">Events</button>
          <button class="tab-btn" data-tab="posts">Posts & Forums</button>
        </div>

        <!-- Tab Content -->
        <div class="tab-content active" id="overview">
          <div class="content-card">
            <h2>Topic Overview</h2>
            <p><?php echo $course['description']; ?></p>
          </div>
          
          <div class="content-card">
            <h2>Community Stats</h2>
            <div class="stats-grid">
              <div class="stat-box">
                <strong><?php echo $course['studentsLearning']; ?></strong>
                <span>Students actively learning this topic</span>
              </div>
              <div class="stat-box">
                <strong><?php echo $course['studentsFluent']; ?></strong>
                <span>Students fluent and available to help</span>
              </div>
              <div class="stat-box">
                <strong><?php echo count($course['relatedEvents']); ?></strong>
                <span>Upcoming related events</span>
              </div>
            </div>
          </div>
          
          <div class="content-card">
            <h2>Get Involved</h2>
            <p>Join study groups, attend events, share resources, and connect with other learners in this topic area. Use the tabs above to explore resources, find students to collaborate with, and join relevant discussions.</p>
          </div>
        </div>

        <div class="tab-content" id="students">
          <div class="content-card">
            <h2>Students Learning This Topic</h2>
            <p class="section-intro"><?php echo $course['studentsLearning']; ?> students are currently learning <?php echo $course['title']; ?>. Connect with them to form study groups!</p>
            <button class="btn-primary" id="browseLearners" data-subject-id="<?php echo $course['id']; ?>">Browse Learners →</button>
          </div>
          
          <div class="content-card">
            <h2>Students Fluent in This Topic</h2>
            <p class="section-intro"><?php echo $course['studentsFluent']; ?> students are fluent in <?php echo $course['title']; ?> and available to help. Reach out for guidance!</p>
            <button class="btn-primary" id="findMentors" data-subject-id="<?php echo $course['id']; ?>">Find Mentors →</button>
          </div>
        </div>

        <div class="tab-content" id="events">
          <div class="content-card">
            <div class="events-header-row">
              <h2>Related Events</h2>
              <?php if (count($course['relatedEvents']) > 3): ?>
              <div class="pagination-controls">
                <button class="pagination-btn" id="eventsPrevPage" disabled data-page="1">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
                  </svg>
                </button>
                
                <div class="page-numbers" id="eventsPageNumbers">
                  <?php
                  $totalEvents = count($course['relatedEvents']);
                  $eventsPerPage = 3;
                  $totalEventPages = ceil($totalEvents / $eventsPerPage);
                  
                  for ($i = 1; $i <= $totalEventPages; $i++) {
                    $activeClass = ($i == 1) ? ' active' : '';
                    echo '<button class="page-number' . $activeClass . '" data-page="' . $i . '">' . $i . '</button>';
                  }
                  ?>
                </div>
                
                <button class="pagination-btn" id="eventsNextPage" <?php echo $totalEventPages <= 1 ? 'disabled' : ''; ?> data-page="2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/>
                  </svg>
                </button>
              </div>
              <?php endif; ?>
            </div>
            <div class="events-list" id="eventsList">
              <?php if (!empty($course['relatedEvents'])): ?>
                <?php foreach ($course['relatedEvents'] as $index => $event): ?>
                  <div class="event-item" data-event-index="<?php echo $index; ?>" style="<?php echo $index >= 3 ? 'display: none;' : ''; ?>">
                    <div class="event-date-box">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/>
                        <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                      </svg>
                    </div>
                    <div class="event-info">
                      <h4><?php echo htmlspecialchars($event['events_title']); ?></h4>
                      <p><?php echo date('M d, Y', strtotime($event['events_date'])); ?> <?php echo $event['events_start'] ? '• ' . date('g:i A', strtotime($event['events_start'])) : ''; ?></p>
                    </div>
                    <button class="btn-secondary btn-sm course-view-details" data-event-id="<?php echo $event['events_id']; ?>">View Details</button>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p style="text-align: center; color: var(--text-secondary); padding: 20px;">No upcoming events for this subject yet.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="tab-content" id="posts">
          <div class="content-card">
            <h2>Recent Discussions</h2>
            <div class="posts-list">
              <?php if (!empty($course['recentPosts'])): ?>
                <?php foreach ($course['recentPosts'] as $post): ?>
                  <div class="post-item">
                    <div class="post-avatar">
                      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                      </svg>
                    </div>
                    <div class="post-content">
                      <h4><?php echo htmlspecialchars($post['posts_title']); ?></h4>
                      <p class="post-meta">by <?php echo htmlspecialchars($post['user_name']); ?> • <?php echo $post['comment_count']; ?> replies • <?php echo date('M d', strtotime($post['posts_timestamp'])); ?></p>
                    </div>
                    <button class="btn-secondary btn-sm">View Thread</button>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p style="text-align: center; color: var(--text-secondary); padding: 20px;">No discussions yet. Start one!</p>
              <?php endif; ?>
            </div>
            <button class="btn-primary" style="margin-top: 20px;">View All Discussions →</button>
          </div>
        </div>
      </div>

      <!-- Side Content -->
      <?php renderSideContent('courses'); ?>
    </div>
  </main>

  <!-- Students Modal -->
  <div id="studentsModal" class="students-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle">Students</h2>
        <button class="modal-close" id="closeModal">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
            <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
          </svg>
        </button>
      </div>
      
      <div class="modal-body">
        <div class="modal-header-row">
          <div class="modal-students-count" id="modalCount"></div>
          <div class="modal-pagination" id="modalPagination" style="display: none;"></div>
        </div>
        
        <div class="modal-students-grid" id="modalStudentsGrid">
          <div class="search-loading">Loading students...</div>
        </div>
      </div>
    </div>
  </div>

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
              <span class="modal-detail-value" id="modalCategory"></span>
            </div>
            <div class="modal-detail-row">
              <span class="modal-detail-label">Organizer</span>
              <span class="modal-detail-value" id="modalOrganizer"></span>
            </div>
            <div class="modal-detail-row">
              <span class="modal-detail-label">Capacity</span>
              <span class="modal-detail-value" id="modalCapacity"></span>
            </div>
            <div class="modal-detail-row">
              <span class="modal-detail-label">Registration</span>
              <span class="modal-detail-value" id="modalRegistration"></span>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn-modal-register" id="btnRegisterEvent">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/>
          </svg>
          Register for Event
        </button>
      </div>
    </div>
  </div>

  <script>
    // Store events data for modal
    const courseEventsData = <?php echo json_encode($course['relatedEvents']); ?>;
  </script>
  <script src="courses.js"></script>
  <script src="../components/sidecontent.js"></script>
</body>
</html>
