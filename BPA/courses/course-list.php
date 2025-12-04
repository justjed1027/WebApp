<?php
session_start();
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login/login.php');
  exit();
}
require_once '../components/sidecontent.php';

// Get the course group from URL
$groupId = isset($_GET['group']) ? $_GET['group'] : 'mathematics';

// Hardcoded course data organized by group
$allCourses = [
  'mathematics' => [
    ['id' => 'math-101', 'title' => 'Algebra I', 'instructor' => 'Dr. Sarah Chen', 'students' => 142, 'rating' => 4.8, 'level' => 'Beginner', 'duration' => '12 weeks'],
    ['id' => 'math-102', 'title' => 'Algebra II', 'instructor' => 'Prof. Michael Torres', 'students' => 98, 'rating' => 4.7, 'level' => 'Intermediate', 'duration' => '12 weeks'],
    ['id' => 'math-201', 'title' => 'Geometry', 'instructor' => 'Dr. Emily Wang', 'students' => 156, 'rating' => 4.9, 'level' => 'Beginner', 'duration' => '10 weeks'],
    ['id' => 'math-301', 'title' => 'Calculus I', 'instructor' => 'Prof. David Kim', 'students' => 186, 'rating' => 4.9, 'level' => 'Advanced', 'duration' => '14 weeks'],
    ['id' => 'math-302', 'title' => 'Calculus II', 'instructor' => 'Dr. James Rodriguez', 'students' => 134, 'rating' => 4.8, 'level' => 'Advanced', 'duration' => '14 weeks'],
    ['id' => 'math-401', 'title' => 'Linear Algebra', 'instructor' => 'Prof. Rachel Green', 'students' => 89, 'rating' => 4.7, 'level' => 'Advanced', 'duration' => '12 weeks'],
    ['id' => 'math-501', 'title' => 'Statistics', 'instructor' => 'Dr. Robert Lee', 'students' => 201, 'rating' => 4.8, 'level' => 'Intermediate', 'duration' => '10 weeks'],
    ['id' => 'math-601', 'title' => 'Discrete Mathematics', 'instructor' => 'Prof. Lisa Park', 'students' => 76, 'rating' => 4.6, 'level' => 'Advanced', 'duration' => '12 weeks']
  ],
  'computer-science' => [
    ['id' => 'cs-101', 'title' => 'Introduction to Programming', 'instructor' => 'Dr. Alex Johnson', 'students' => 312, 'rating' => 4.9, 'level' => 'Beginner', 'duration' => '10 weeks'],
    ['id' => 'cs-102', 'title' => 'Python Programming', 'instructor' => 'Prof. Maria Garcia', 'students' => 248, 'rating' => 4.8, 'level' => 'Beginner', 'duration' => '12 weeks'],
    ['id' => 'cs-201', 'title' => 'Data Structures', 'instructor' => 'Dr. Kevin Wu', 'students' => 189, 'rating' => 4.7, 'level' => 'Intermediate', 'duration' => '14 weeks'],
    ['id' => 'cs-202', 'title' => 'Algorithms', 'instructor' => 'Prof. Jennifer Smith', 'students' => 167, 'rating' => 4.8, 'level' => 'Intermediate', 'duration' => '14 weeks'],
    ['id' => 'cs-301', 'title' => 'Web Development', 'instructor' => 'Dr. Tom Anderson', 'students' => 276, 'rating' => 4.9, 'level' => 'Intermediate', 'duration' => '12 weeks'],
    ['id' => 'cs-302', 'title' => 'Database Systems', 'instructor' => 'Prof. Nancy Brown', 'students' => 145, 'rating' => 4.6, 'level' => 'Intermediate', 'duration' => '10 weeks'],
    ['id' => 'cs-401', 'title' => 'Machine Learning', 'instructor' => 'Dr. Andrew Ng', 'students' => 298, 'rating' => 4.9, 'level' => 'Advanced', 'duration' => '16 weeks'],
    ['id' => 'cs-402', 'title' => 'Artificial Intelligence', 'instructor' => 'Prof. Susan Taylor', 'students' => 234, 'rating' => 4.8, 'level' => 'Advanced', 'duration' => '16 weeks'],
    ['id' => 'cs-501', 'title' => 'Computer Networks', 'instructor' => 'Dr. Richard Martinez', 'students' => 132, 'rating' => 4.7, 'level' => 'Advanced', 'duration' => '12 weeks'],
    ['id' => 'cs-502', 'title' => 'Operating Systems', 'instructor' => 'Prof. Laura White', 'students' => 156, 'rating' => 4.8, 'level' => 'Advanced', 'duration' => '14 weeks'],
    ['id' => 'cs-601', 'title' => 'Cybersecurity', 'instructor' => 'Dr. Chris Evans', 'students' => 189, 'rating' => 4.9, 'level' => 'Advanced', 'duration' => '12 weeks'],
    ['id' => 'cs-602', 'title' => 'Mobile App Development', 'instructor' => 'Prof. Emma Davis', 'students' => 212, 'rating' => 4.7, 'level' => 'Intermediate', 'duration' => '10 weeks']
  ],
  'science' => [
    ['id' => 'sci-101', 'title' => 'General Biology', 'instructor' => 'Dr. Patricia Moore', 'students' => 178, 'rating' => 4.8, 'level' => 'Beginner', 'duration' => '12 weeks'],
    ['id' => 'sci-102', 'title' => 'General Chemistry', 'instructor' => 'Prof. Mark Wilson', 'students' => 205, 'rating' => 4.7, 'level' => 'Beginner', 'duration' => '12 weeks'],
    ['id' => 'sci-201', 'title' => 'Physics I', 'instructor' => 'Dr. Linda Thompson', 'students' => 142, 'rating' => 4.8, 'level' => 'Intermediate', 'duration' => '14 weeks'],
    ['id' => 'sci-202', 'title' => 'Physics II', 'instructor' => 'Prof. Daniel Clark', 'students' => 98, 'rating' => 4.7, 'level' => 'Intermediate', 'duration' => '14 weeks'],
    ['id' => 'sci-301', 'title' => 'Organic Chemistry', 'instructor' => 'Dr. Carol Adams', 'students' => 134, 'rating' => 4.6, 'level' => 'Advanced', 'duration' => '16 weeks'],
    ['id' => 'sci-302', 'title' => 'Molecular Biology', 'instructor' => 'Prof. Steven Hall', 'students' => 112, 'rating' => 4.8, 'level' => 'Advanced', 'duration' => '14 weeks'],
    ['id' => 'sci-401', 'title' => 'Genetics', 'instructor' => 'Dr. Barbara Young', 'students' => 156, 'rating' => 4.9, 'level' => 'Advanced', 'duration' => '12 weeks'],
    ['id' => 'sci-501', 'title' => 'Environmental Science', 'instructor' => 'Prof. George King', 'students' => 189, 'rating' => 4.7, 'level' => 'Intermediate', 'duration' => '10 weeks'],
    ['id' => 'sci-601', 'title' => 'Astronomy', 'instructor' => 'Dr. Margaret Scott', 'students' => 234, 'rating' => 4.9, 'level' => 'Beginner', 'duration' => '8 weeks'],
    ['id' => 'sci-701', 'title' => 'Anatomy & Physiology', 'instructor' => 'Prof. Paul Wright', 'students' => 167, 'rating' => 4.8, 'level' => 'Intermediate', 'duration' => '14 weeks']
  ],
  'english' => [
    ['id' => 'eng-101', 'title' => 'English Composition', 'instructor' => 'Dr. Helen Mitchell', 'students' => 198, 'rating' => 4.7, 'level' => 'Beginner', 'duration' => '10 weeks'],
    ['id' => 'eng-201', 'title' => 'American Literature', 'instructor' => 'Prof. William Turner', 'students' => 145, 'rating' => 4.8, 'level' => 'Intermediate', 'duration' => '12 weeks'],
    ['id' => 'eng-202', 'title' => 'British Literature', 'instructor' => 'Dr. Elizabeth Collins', 'students' => 132, 'rating' => 4.9, 'level' => 'Intermediate', 'duration' => '12 weeks'],
    ['id' => 'eng-301', 'title' => 'Creative Writing', 'instructor' => 'Prof. Jessica Cooper', 'students' => 176, 'rating' => 4.8, 'level' => 'Intermediate', 'duration' => '10 weeks'],
    ['id' => 'eng-401', 'title' => 'Shakespeare Studies', 'instructor' => 'Dr. Thomas Bailey', 'students' => 89, 'rating' => 4.9, 'level' => 'Advanced', 'duration' => '8 weeks'],
    ['id' => 'eng-501', 'title' => 'World Literature', 'instructor' => 'Prof. Dorothy Stewart', 'students' => 156, 'rating' => 4.7, 'level' => 'Intermediate', 'duration' => '12 weeks']
  ],
  'history' => [
    ['id' => 'hist-101', 'title' => 'World History I', 'instructor' => 'Dr. Charles Reed', 'students' => 167, 'rating' => 4.8, 'level' => 'Beginner', 'duration' => '12 weeks'],
    ['id' => 'hist-102', 'title' => 'World History II', 'instructor' => 'Prof. Sandra Murphy', 'students' => 142, 'rating' => 4.7, 'level' => 'Beginner', 'duration' => '12 weeks'],
    ['id' => 'hist-201', 'title' => 'American History', 'instructor' => 'Dr. Kenneth Bell', 'students' => 189, 'rating' => 4.9, 'level' => 'Intermediate', 'duration' => '14 weeks'],
    ['id' => 'hist-301', 'title' => 'European History', 'instructor' => 'Prof. Betty Ross', 'students' => 134, 'rating' => 4.8, 'level' => 'Intermediate', 'duration' => '14 weeks'],
    ['id' => 'hist-401', 'title' => 'Ancient Civilizations', 'instructor' => 'Dr. Frank Powell', 'students' => 156, 'rating' => 4.9, 'level' => 'Advanced', 'duration' => '12 weeks'],
    ['id' => 'hist-501', 'title' => 'Medieval History', 'instructor' => 'Prof. Catherine Ward', 'students' => 98, 'rating' => 4.7, 'level' => 'Advanced', 'duration' => '10 weeks'],
    ['id' => 'hist-601', 'title' => 'Modern History', 'instructor' => 'Dr. Gerald Foster', 'students' => 178, 'rating' => 4.8, 'level' => 'Intermediate', 'duration' => '12 weeks']
  ],
  'art' => [
    ['id' => 'art-101', 'title' => 'Introduction to Art', 'instructor' => 'Dr. Diana Gray', 'students' => 212, 'rating' => 4.8, 'level' => 'Beginner', 'duration' => '8 weeks'],
    ['id' => 'art-201', 'title' => 'Drawing & Sketching', 'instructor' => 'Prof. Ryan Howard', 'students' => 167, 'rating' => 4.9, 'level' => 'Beginner', 'duration' => '10 weeks'],
    ['id' => 'art-301', 'title' => 'Painting Techniques', 'instructor' => 'Dr. Sharon Ward', 'students' => 134, 'rating' => 4.7, 'level' => 'Intermediate', 'duration' => '12 weeks'],
    ['id' => 'art-401', 'title' => 'Digital Art & Design', 'instructor' => 'Prof. Brandon Cox', 'students' => 198, 'rating' => 4.9, 'level' => 'Intermediate', 'duration' => '10 weeks'],
    ['id' => 'art-501', 'title' => 'Art History', 'instructor' => 'Dr. Michelle Russell', 'students' => 145, 'rating' => 4.8, 'level' => 'Intermediate', 'duration' => '12 weeks']
  ],
  'business' => [
    ['id' => 'bus-101', 'title' => 'Introduction to Business', 'instructor' => 'Dr. Larry Griffin', 'students' => 234, 'rating' => 4.7, 'level' => 'Beginner', 'duration' => '10 weeks'],
    ['id' => 'bus-201', 'title' => 'Marketing Fundamentals', 'instructor' => 'Prof. Kimberly Hayes', 'students' => 189, 'rating' => 4.8, 'level' => 'Intermediate', 'duration' => '12 weeks'],
    ['id' => 'bus-301', 'title' => 'Financial Accounting', 'instructor' => 'Dr. Eugene West', 'students' => 156, 'rating' => 4.6, 'level' => 'Intermediate', 'duration' => '14 weeks'],
    ['id' => 'bus-401', 'title' => 'Entrepreneurship', 'instructor' => 'Prof. Nicole Long', 'students' => 198, 'rating' => 4.9, 'level' => 'Advanced', 'duration' => '12 weeks'],
    ['id' => 'bus-501', 'title' => 'Business Strategy', 'instructor' => 'Dr. Philip Hughes', 'students' => 142, 'rating' => 4.8, 'level' => 'Advanced', 'duration' => '10 weeks'],
    ['id' => 'bus-601', 'title' => 'Economics', 'instructor' => 'Prof. Stephanie Price', 'students' => 176, 'rating' => 4.7, 'level' => 'Intermediate', 'duration' => '12 weeks']
  ],
  'music' => [
    ['id' => 'mus-101', 'title' => 'Music Theory I', 'instructor' => 'Dr. Jonathan Barnes', 'students' => 134, 'rating' => 4.8, 'level' => 'Beginner', 'duration' => '10 weeks'],
    ['id' => 'mus-201', 'title' => 'Music History', 'instructor' => 'Prof. Angela Fisher', 'students' => 98, 'rating' => 4.7, 'level' => 'Intermediate', 'duration' => '12 weeks'],
    ['id' => 'mus-301', 'title' => 'Piano Performance', 'instructor' => 'Dr. Victor Morris', 'students' => 112, 'rating' => 4.9, 'level' => 'Intermediate', 'duration' => '14 weeks'],
    ['id' => 'mus-401', 'title' => 'Music Composition', 'instructor' => 'Prof. Melissa Jenkins', 'students' => 76, 'rating' => 4.8, 'level' => 'Advanced', 'duration' => '12 weeks']
  ]
];

// Group metadata
$groupInfo = [
  'mathematics' => ['name' => 'Mathematics', 'icon' => '📐', 'color' => '#3b82f6'],
  'computer-science' => ['name' => 'Computer Science', 'icon' => '💻', 'color' => '#8b5cf6'],
  'science' => ['name' => 'Science', 'icon' => '🔬', 'color' => '#10b981'],
  'english' => ['name' => 'English', 'icon' => '📚', 'color' => '#f59e0b'],
  'history' => ['name' => 'History', 'icon' => '🏛️', 'color' => '#ef4444'],
  'art' => ['name' => 'Art', 'icon' => '🎨', 'color' => '#ec4899'],
  'business' => ['name' => 'Business', 'icon' => '💼', 'color' => '#06b6d4'],
  'music' => ['name' => 'Music', 'icon' => '🎵', 'color' => '#a855f7']
];

$courses = isset($allCourses[$groupId]) ? $allCourses[$groupId] : [];
$group = isset($groupInfo[$groupId]) ? $groupInfo[$groupId] : ['name' => 'Courses', 'icon' => '📖', 'color' => '#6b7280'];
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
        <a href="../post/post.php" class="nav-link" data-tooltip="Posts">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5" />
          </svg>
          <span>Posts</span>
        </a>

        <a href="../dms/index.html" class="nav-link" data-tooltip="Direct Messages">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M16 8c0 3.866-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.584.296-1.925.864-4.181 1.234-.2.032-.352-.176-.273-.362.354-.836.674-1.95.77-2.966C.744 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7M5 8a1 1 0 1 0-2 0 1 1 0 0 0 2 0m4 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
          </svg>
          <span>DMs</span>
        </a>

        <a href="../forum/forums.html" class="nav-link" data-tooltip="Forum">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M12 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2M5 4h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1m-.5 2.5A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5M5 8h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1m0 2h3a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1" />
          </svg>
          <span>Forum</span>
        </a>

        <a href="../connections/connections.html" class="nav-link" data-tooltip="Connections">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5" />
          </svg>
          <span>Connections</span>
        </a>
      </div>

      <div class="nav-divider"></div>

      <div class="nav-group">
        <a href="courses.php" class="nav-link active" data-tooltip="Courses">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783" />
          </svg>
          <span>Courses</span>
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
          
          <div class="filter-controls">
            <select class="filter-select" id="levelFilter">
              <option value="all">All Levels</option>
              <option value="beginner">Beginner</option>
              <option value="intermediate">Intermediate</option>
              <option value="advanced">Advanced</option>
            </select>
            
            <select class="filter-select" id="sortBy">
              <option value="popular">Most Popular</option>
              <option value="rating">Highest Rated</option>
              <option value="students">Most Students</option>
              <option value="title">A-Z</option>
            </select>
          </div>
        </div>

        <!-- Course Count -->
        <div class="course-count">
          <span id="courseCount"><?php echo count($courses); ?></span> courses available
        </div>

        <!-- Courses List -->
        <div class="courses-list-grid" id="coursesList">
          <?php foreach ($courses as $course): ?>
            <a href="course-detail.php?id=<?php echo $course['id']; ?>" class="course-list-card" data-level="<?php echo strtolower($course['level']); ?>">
              <div class="course-list-header">
                <h3><?php echo $course['title']; ?></h3>
                <span class="level-badge level-<?php echo strtolower($course['level']); ?>"><?php echo $course['level']; ?></span>
              </div>
              <p class="course-instructor">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
                </svg>
                <?php echo $course['instructor']; ?>
              </p>
              <div class="course-list-stats">
                <span class="stat">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                  </svg>
                  <?php echo $course['students']; ?> students
                </span>
                <span class="stat">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                  </svg>
                  <?php echo $course['rating']; ?>
                </span>
                <span class="stat">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
                  </svg>
                  <?php echo $course['duration']; ?>
                </span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Side Content -->
      <?php renderSideContent('courses'); ?>
    </div>
  </main>

  <script src="courses.js"></script>
  <script src="../components/sidecontent.js"></script>
</body>
</html>
