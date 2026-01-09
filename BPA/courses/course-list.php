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

// Get the category ID from URL parameter
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Category icon and color mapping
$categoryStyles = [
  'Mathematics' => ['icon' => '📐', 'color' => '#3b82f6'],
  'Computer Science' => ['icon' => '💻', 'color' => '#8b5cf6'],
  'Science' => ['icon' => '🔬', 'color' => '#10b981'],
  'English' => ['icon' => '📚', 'color' => '#f59e0b'],
  'History' => ['icon' => '🏛️', 'color' => '#ef4444'],
  'Art & Design' => ['icon' => '🎨', 'color' => '#ec4899'],
  'Business & Economics' => ['icon' => '💼', 'color' => '#06b6d4'],
  'Music' => ['icon' => '🎵', 'color' => '#a855f7'],
  'Languages' => ['icon' => '🌐', 'color' => '#14b8a6']
];

// Fetch category information
$categoryQuery = "SELECT category_id, category_name FROM subjectcategories WHERE category_id = ? LIMIT 1";
$stmt = $conn->prepare($categoryQuery);
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$result = $stmt->get_result();
$categoryInfo = $result->fetch_assoc();
$stmt->close();

if (!$categoryInfo) {
  header('Location: courses.php');
  exit();
}

$categoryName = $categoryInfo['category_name'];
$group = [
  'name' => $categoryName,
  'icon' => $categoryStyles[$categoryName]['icon'] ?? '📖',
  'color' => $categoryStyles[$categoryName]['color'] ?? '#6b7280'
];

// Fetch user's subject interests (subjects they want to learn)
$userInterestsQuery = "SELECT ui_subject_id FROM user_interests WHERE ui_user_id = ?";
$stmt = $conn->prepare($userInterestsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userInterestIds = [];
while ($row = $result->fetch_assoc()) {
  $userInterestIds[] = $row['ui_subject_id'];
}
$stmt->close();

// Fetch user's skills (subjects they know)
$userSkillsQuery = "SELECT us_subject_id FROM user_skills WHERE us_user_id = ?";
$stmt = $conn->prepare($userSkillsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userSkillIds = [];
while ($row = $result->fetch_assoc()) {
  $userSkillIds[] = $row['us_subject_id'];
}
$stmt->close();

// Fetch all subjects in this category with engagement stats
$subjectsQuery = "
  SELECT 
    s.subject_id,
    s.subject_name,
    s.description,
    COUNT(DISTINCT ui.ui_user_id) as students_learning,
    COUNT(DISTINCT es.es_event_id) as event_count,
    0 as resource_count
  FROM subjects s
  LEFT JOIN user_interests ui ON s.subject_id = ui.ui_subject_id
  LEFT JOIN event_subjects es ON s.subject_id = es.es_subject_id
  WHERE s.category_id = ?
  GROUP BY s.subject_id, s.subject_name, s.description
  ORDER BY s.subject_name ASC
";;
$stmt = $conn->prepare($subjectsQuery);
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$result = $stmt->get_result();

// Organize subjects into three sections
$interestedIn = [];    // Subjects user wants to learn
$skillsIn = [];        // Subjects user has knowledge in
$otherSubjects = [];   // Subjects not in either list

while ($subject = $result->fetch_assoc()) {
  $subjectId = $subject['subject_id'];
  
  $subjectData = [
    'id' => $subject['subject_id'],
    'title' => $subject['subject_name'],
    'description' => $subject['description'] ?? '',
    'studentsLearning' => $subject['students_learning'],
    'eventCount' => $subject['event_count'],
    'resourceCount' => $subject['resource_count']
  ];
  
  $isInterest = in_array($subjectId, $userInterestIds);
  $isSkill = in_array($subjectId, $userSkillIds);
  
  if ($isInterest) {
    $interestedIn[] = $subjectData;
  } elseif ($isSkill) {
    $skillsIn[] = $subjectData;
  } else {
    $otherSubjects[] = $subjectData;
  }
}

$stmt->close();
$conn->close();

// Calculate total subject count
$totalSubjects = count($interestedIn) + count($skillsIn) + count($otherSubjects);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkillSwap — <?php echo $group['name']; ?> Courses</title>
  <link rel="stylesheet" href="courses.css">
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
        <a href="courses.php" class="nav-link active" data-tooltip="Dashboard">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5" />
          </svg>
          <span>Dashboard</span>
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

  <!-- Top Bar Navigation -->
  <header class="topbar">
    <div class="topbar-left"></div>
    <div class="topbar-center">
      <h1 class="page-title"><?php echo $group['icon']; ?> <?php echo $group['name']; ?></h1>
    </div>
    <div class="topbar-right">
      <div class="search-container">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
          <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
        </svg>
        <input type="text" id="search-input" class="search-input" placeholder="Search people, posts, and courses...">
      </div>

      <button class="icon-btn" aria-label="Notifications">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z" />
        </svg>
        <span class="badge">3</span>
      </button>

      <button class="icon-btn" aria-label="Messages">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M5 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
          <path d="m2.165 15.803.02-.004c1.83-.363 2.948-.842 3.468-1.105A9 9 0 0 0 8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6a10.4 10.4 0 0 1-.524 2.318l-.003.011a11 11 0 0 1-.244.637c-.079.186.074.394.273.362a22 22 0 0 0 .693-.125m.8-3.108a1 1 0 0 0-.287-.801C1.618 10.83 1 9.468 1 8c0-3.192 3.004-6 7-6s7 2.808 7 6-3.004 6-7 6a8 8 0 0 1-2.088-.272 1 1 0 0 0-.711.074c-.387.196-1.24.57-2.634.893a11 11 0 0 0 .398-2" />
        </svg>
        <span class="badge">7</span>
      </button>

      <div class="profile-dropdown">
        <button class="profile-btn">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
          </svg>
        </button>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="main-content">
    <div class="page-content">
      <div class="course-list-content">
        <!-- Back Button & Filters -->
        <div class="course-list-header">
          <a href="courses.php" class="back-button">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8" />
            </svg>
            Back to Courses
          </a>
          
        </div>

        <!-- Topic Count -->
        <div class="course-count">
          <span id="courseCount"><?php echo $totalSubjects; ?></span> topics available
        </div>

        <!-- Topics List - Organized by User Preferences -->
        
        <?php if (!empty($interestedIn)): ?>
        <div class="subject-section">
          <div class="subject-section-header">
            <h3 class="subject-section-title">📚 Want to Learn</h3>
            <p class="subject-section-description">Subjects you're interested in exploring</p>
          </div>
          <div class="courses-list-grid">
            <?php foreach ($interestedIn as $subject): ?>
              <a href="course-detail.php?id=<?php echo $subject['id']; ?>" class="course-list-card">
                <div class="course-list-header" style="text-align: center;">
                  <h3><?php echo htmlspecialchars($subject['title']); ?></h3>
                  <p class="subject-description"><?php echo htmlspecialchars($subject['description']); ?></p>
                </div>
                <div class="course-list-stats" style="justify-content: center;">
                  <span class="stat">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                    </svg>
                    <?php echo $subject['studentsLearning']; ?> exploring
                  </span>
                  <span class="stat">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M6 0a.5.5 0 0 1 .5.5V3h3V.5a.5.5 0 0 1 1 0V3h1a2 2 0 0 1 2 2v3.5a.5.5 0 0 1-1 0V5h-11v8a1 1 0 0 0 1 1h4.5a.5.5 0 0 1 0 1h-4.5A2 2 0 0 1 0 13V5a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 1 0V3h3V.5A.5.5 0 0 1 6 0M9.5 8a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-.5.5h-4a.5.5 0 0 1-.5-.5z"/>
                    </svg>
                    <?php echo $subject['eventCount']; ?> events
                  </span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($skillsIn)): ?>
        <div class="subject-section">
          <div class="subject-section-header">
            <h3 class="subject-section-title">⭐ My Skills</h3>
            <p class="subject-section-description">Subjects you have knowledge in</p>
          </div>
          <div class="courses-list-grid">
            <?php foreach ($skillsIn as $subject): ?>
              <a href="course-detail.php?id=<?php echo $subject['id']; ?>" class="course-list-card">
                <div class="course-list-header" style="text-align: center;">
                  <h3><?php echo htmlspecialchars($subject['title']); ?></h3>
                  <p class="subject-description"><?php echo htmlspecialchars($subject['description']); ?></p>
                </div>
                <div class="course-list-stats" style="justify-content: center;">
                  <span class="stat">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                    </svg>
                    <?php echo $subject['studentsLearning']; ?> exploring
                  </span>
                  <span class="stat">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M6 0a.5.5 0 0 1 .5.5V3h3V.5a.5.5 0 0 1 1 0V3h1a2 2 0 0 1 2 2v3.5a.5.5 0 0 1-1 0V5h-11v8a1 1 0 0 0 1 1h4.5a.5.5 0 0 1 0 1h-4.5A2 2 0 0 1 0 13V5a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 1 0V3h3V.5A.5.5 0 0 1 6 0M9.5 8a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-.5.5h-4a.5.5 0 0 1-.5-.5z"/>
                    </svg>
                    <?php echo $subject['eventCount']; ?> events
                  </span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($otherSubjects)): ?>
        <div class="subject-section">
          <div class="subject-section-header">
            <h3 class="subject-section-title">🌟 Other Topics</h3>
            <p class="subject-section-description">Explore more subjects and discover new interests</p>
          </div>
          <div class="courses-list-grid">
            <?php foreach ($otherSubjects as $subject): ?>
              <a href="course-detail.php?id=<?php echo $subject['id']; ?>" class="course-list-card">
                <div class="course-list-header" style="text-align: center;">
                  <h3><?php echo htmlspecialchars($subject['title']); ?></h3>
                  <p class="subject-description"><?php echo htmlspecialchars($subject['description']); ?></p>
                </div>
                <div class="course-list-stats" style="justify-content: center;">
                  <span class="stat">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                    </svg>
                    <?php echo $subject['studentsLearning']; ?> exploring
                  </span>
                  <span class="stat">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M6 0a.5.5 0 0 1 .5.5V3h3V.5a.5.5 0 0 1 1 0V3h1a2 2 0 0 1 2 2v3.5a.5.5 0 0 1-1 0V5h-11v8a1 1 0 0 0 1 1h4.5a.5.5 0 0 1 0 1h-4.5A2 2 0 0 1 0 13V5a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 1 0V3h3V.5A.5.5 0 0 1 6 0M9.5 8a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-.5.5h-4a.5.5 0 0 1-.5-.5z"/>
                    </svg>
                    <?php echo $subject['eventCount']; ?> events
                  </span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Side Content -->
      <?php renderSideContent('courses'); ?>
    </div>
  </main>

  <script src="courses.js"></script>
  <script src="../components/sidecontent.js"></script>
</body>
</html>
