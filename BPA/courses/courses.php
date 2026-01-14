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

// Category icon and color mapping
$categoryStyles = [
  'Mathematics' => ['icon' => '', 'color' => '#3b82f6'],
  'Computer Science' => ['icon' => '', 'color' => '#8b5cf6'],
  'Science' => ['icon' => '', 'color' => '#10b981'],
  'English' => ['icon' => '', 'color' => '#f59e0b'],
  'History' => ['icon' => '', 'color' => '#ef4444'],
  'Art & Design' => ['icon' => '', 'color' => '#ec4899'],
  'Business & Economics' => ['icon' => '', 'color' => '#06b6d4'],
  'Music' => ['icon' => '', 'color' => '#a855f7'],
  'Languages' => ['icon' => '', 'color' => '#14b8a6']
];

// Fetch user's skills (subjects they know)
$userSkillsQuery = "
  SELECT DISTINCT s.category_id, sc.category_name
  FROM user_skills us
  INNER JOIN subjects s ON us.us_subject_id = s.subject_id
  INNER JOIN subjectcategories sc ON s.category_id = sc.category_id
  WHERE us.us_user_id = ? AND sc.category_id != 3
";
$stmt = $conn->prepare($userSkillsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userSkillCategories = [];
while ($row = $result->fetch_assoc()) {
  $userSkillCategories[] = $row['category_id'];
}
$stmt->close();

// Fetch user's interests (subjects they want to learn)
$userInterestsQuery = "
  SELECT DISTINCT s.category_id, sc.category_name
  FROM user_interests ui
  INNER JOIN subjects s ON ui.ui_subject_id = s.subject_id
  INNER JOIN subjectcategories sc ON s.category_id = sc.category_id
  WHERE ui.ui_user_id = ? AND sc.category_id != 3
";
$stmt = $conn->prepare($userInterestsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userInterestCategories = [];
while ($row = $result->fetch_assoc()) {
  $userInterestCategories[] = $row['category_id'];
}
$stmt->close();

// Fetch all categories with subject counts (excluding category_id 3)
$categoriesQuery = "
  SELECT 
    sc.category_id,
    sc.category_name,
    sc.category_description,
    COUNT(s.subject_id) as resource_count
  FROM subjectcategories sc
  LEFT JOIN subjects s ON sc.category_id = s.category_id
  WHERE sc.category_id != 3
  GROUP BY sc.category_id, sc.category_name, sc.category_description
  ORDER BY sc.category_name ASC
";
$categoriesResult = $conn->query($categoriesQuery);

// Organize categories into sections
$wantToLearn = [];      // In interests but NOT in skills
$buildingSkills = [];   // In BOTH interests AND skills
$myExpertise = [];      // In skills but NOT in interests
$otherCourses = [];     // Not in either interests or skills

while ($cat = $categoriesResult->fetch_assoc()) {
  $catId = $cat['category_id'];
  $catName = $cat['category_name'];
  
  $categoryData = [
    'id' => $catId,
    'name' => $catName,
    'description' => $cat['category_description'] ?? '',
    'icon' => $categoryStyles[$catName]['icon'] ?? '',
    'color' => $categoryStyles[$catName]['color'] ?? '#6b7280',
    'resourceCount' => $cat['resource_count']
  ];
  
  $inSkills = in_array($catId, $userSkillCategories);
  $inInterests = in_array($catId, $userInterestCategories);
  
  if ($inInterests && !$inSkills) {
    $wantToLearn[] = $categoryData;
  } elseif ($inInterests && $inSkills) {
    $buildingSkills[] = $categoryData;
  } elseif ($inSkills && !$inInterests) {
    $myExpertise[] = $categoryData;
  } else {
    $otherCourses[] = $categoryData;
  }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkillSwap — Courses</title>
  <link rel="stylesheet" href="courses.css">
  <link rel="stylesheet" href="../components/sidecontent.css">
</head>
<body class="has-side-content">

  <!-- Sidebar Navigation -->
  <aside class="sidebar" id="sidebar">
    <!-- Top Section: Logo & Profile -->
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
          <a href="../profile/profile.php" class="view-profile-link">View Profile - <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === 1){
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

    <!-- Bottom Section: Utilities -->
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
      <div class="courses-content">
        <!-- Hero Section -->
        <section class="courses-hero">
          <h2>Learning Resources</h2>
          <p>Explore topics across all subjects. Share knowledge and learn together.</p>
        </section>

        <!-- Topic Categories Grid - Dynamically Organized -->
        
        <?php if (!empty($wantToLearn)): ?>
        <section class="course-section">
          <div class="section-header">
            <h3 class="section-title">Want to Learn</h3>
            <p class="section-description">Subjects you're interested in exploring</p>
          </div>
          <div class="course-groups">
            <?php foreach ($wantToLearn as $group): ?>
              <a href="course-list.php?category=<?php echo $group['id']; ?>" class="course-group-card" data-group="<?php echo $group['id']; ?>">
                <div class="group-icon" style="background: <?php echo $group['color']; ?>20; color: <?php echo $group['color']; ?>">
                  <span class="icon-emoji"><?php echo $group['icon']; ?></span>
                </div>
                <div class="group-info" style="text-align: center;">
                  <h3><?php echo htmlspecialchars($group['name']); ?></h3>
                  <p class="group-description"><?php echo htmlspecialchars($group['description']); ?></p>
                  <div class="group-meta" style="justify-content: center;">
                    <span class="course-count"><?php echo $group['resourceCount']; ?> resources</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                      <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                    </svg>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($buildingSkills)): ?>
        <section class="course-section">
          <div class="section-header">
            <h3 class="section-title">Building Skills</h3>
            <p class="section-description">Areas where you're expanding your expertise</p>
          </div>
          <div class="course-groups">
            <?php foreach ($buildingSkills as $group): ?>
              <a href="course-list.php?category=<?php echo $group['id']; ?>" class="course-group-card" data-group="<?php echo $group['id']; ?>">
                <div class="group-icon" style="background: <?php echo $group['color']; ?>20; color: <?php echo $group['color']; ?>">
                  <span class="icon-emoji"><?php echo $group['icon']; ?></span>
                </div>
                <div class="group-info" style="text-align: center;">
                  <h3><?php echo htmlspecialchars($group['name']); ?></h3>
                  <p class="group-description"><?php echo htmlspecialchars($group['description']); ?></p>
                  <div class="group-meta" style="justify-content: center;">
                    <span class="course-count"><?php echo $group['resourceCount']; ?> resources</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                      <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                    </svg>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($myExpertise)): ?>
        <section class="course-section">
          <div class="section-header">
            <h3 class="section-title">My Expertise</h3>
            <p class="section-description">Subjects you have skills in</p>
          </div>
          <div class="course-groups">
            <?php foreach ($myExpertise as $group): ?>
              <a href="course-list.php?category=<?php echo $group['id']; ?>" class="course-group-card" data-group="<?php echo $group['id']; ?>">
                <div class="group-icon" style="background: <?php echo $group['color']; ?>20; color: <?php echo $group['color']; ?>">
                  <span class="icon-emoji"><?php echo $group['icon']; ?></span>
                </div>
                <div class="group-info" style="text-align: center;">
                  <h3><?php echo htmlspecialchars($group['name']); ?></h3>
                  <p class="group-description"><?php echo htmlspecialchars($group['description']); ?></p>
                  <div class="group-meta" style="justify-content: center;">
                    <span class="course-count"><?php echo $group['resourceCount']; ?> resources</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                      <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                    </svg>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($otherCourses)): ?>
        <section class="course-section">
          <div class="section-header">
            <h3 class="section-title">Other Courses</h3>
            <p class="section-description">Explore more subjects and discover new interests</p>
          </div>
          <div class="course-groups">
            <?php foreach ($otherCourses as $group): ?>
              <a href="course-list.php?category=<?php echo $group['id']; ?>" class="course-group-card" data-group="<?php echo $group['id']; ?>">
                <div class="group-icon" style="background: <?php echo $group['color']; ?>20; color: <?php echo $group['color']; ?>">
                  <span class="icon-emoji"><?php echo $group['icon']; ?></span>
                </div>
                <div class="group-info" style="text-align: center;">
                  <h3><?php echo htmlspecialchars($group['name']); ?></h3>
                  <p class="group-description"><?php echo htmlspecialchars($group['description']); ?></p>
                  <div class="group-meta" style="justify-content: center;">
                    <span class="course-count"><?php echo $group['resourceCount']; ?> resources</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                      <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                    </svg>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>
      </div>

      <!-- Side Content -->
      <?php 
        // Display all side components on the courses page
        renderSideContent('courses');
      ?>
    </div>
  </main>

  <script src="courses.js"></script>
  <script src="../components/sidecontent.js"></script>
</body>
</html>
