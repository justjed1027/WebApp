<?php
session_start();
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login/login.php');
  exit();
}
require_once '../components/sidecontent.php';

// Get course ID from URL
$courseId = isset($_GET['id']) ? $_GET['id'] : 'cs-102';

// Hardcoded comprehensive course data
$courseDatabase = [
  'cs-102' => [
    'id' => 'cs-102',
    'title' => 'Python Programming',
    'group' => 'Computer Science',
    'groupId' => 'computer-science',
    'instructor' => 'Prof. Maria Garcia',
    'instructorBio' => 'Senior Software Engineer with 15 years of experience in Python development and data science.',
    'students' => 248,
    'rating' => 4.8,
    'reviews' => 127,
    'level' => 'Beginner',
    'duration' => '12 weeks',
    'description' => 'Learn Python programming from scratch with hands-on projects and real-world applications. This comprehensive course covers fundamental programming concepts, data structures, object-oriented programming, and practical applications in web development and data analysis.',
    'objectives' => [
      'Master Python syntax and fundamental programming concepts',
      'Understand data types, variables, and control flow',
      'Work with functions, modules, and packages',
      'Implement object-oriented programming principles',
      'Build real-world projects and applications'
    ],
    'syllabus' => [
      ['week' => 1, 'title' => 'Introduction to Python', 'topics' => ['Setup and environment', 'Basic syntax', 'Variables and data types']],
      ['week' => 2, 'title' => 'Control Flow', 'topics' => ['If statements', 'Loops', 'Conditional logic']],
      ['week' => 3, 'title' => 'Functions', 'topics' => ['Defining functions', 'Parameters and arguments', 'Return values']],
      ['week' => 4, 'title' => 'Data Structures', 'topics' => ['Lists', 'Tuples', 'Dictionaries', 'Sets']],
      ['week' => 5, 'title' => 'String Manipulation', 'topics' => ['String methods', 'Formatting', 'Regular expressions']],
      ['week' => 6, 'title' => 'File Handling', 'topics' => ['Reading files', 'Writing files', 'Working with CSV']],
      ['week' => 7, 'title' => 'Object-Oriented Programming I', 'topics' => ['Classes and objects', 'Attributes', 'Methods']],
      ['week' => 8, 'title' => 'Object-Oriented Programming II', 'topics' => ['Inheritance', 'Polymorphism', 'Encapsulation']],
      ['week' => 9, 'title' => 'Modules and Packages', 'topics' => ['Importing modules', 'Creating packages', 'Standard library']],
      ['week' => 10, 'title' => 'Error Handling', 'topics' => ['Try-except blocks', 'Custom exceptions', 'Debugging']],
      ['week' => 11, 'title' => 'Working with Libraries', 'topics' => ['NumPy basics', 'Pandas introduction', 'Data visualization']],
      ['week' => 12, 'title' => 'Final Project', 'topics' => ['Project planning', 'Implementation', 'Presentation']]
    ],
    'prerequisites' => ['None - This is a beginner-friendly course'],
    'materials' => [
      ['type' => 'textbook', 'name' => 'Python Crash Course', 'author' => 'Eric Matthes'],
      ['type' => 'online', 'name' => 'Official Python Documentation', 'url' => 'https://docs.python.org'],
      ['type' => 'tool', 'name' => 'VS Code or PyCharm IDE']
    ]
  ],
  'math-301' => [
    'id' => 'math-301',
    'title' => 'Calculus I',
    'group' => 'Mathematics',
    'groupId' => 'mathematics',
    'instructor' => 'Prof. David Kim',
    'instructorBio' => 'PhD in Mathematics with specialization in analysis and a passion for teaching calculus.',
    'students' => 186,
    'rating' => 4.9,
    'reviews' => 98,
    'level' => 'Advanced',
    'duration' => '14 weeks',
    'description' => 'Master differential calculus including limits, derivatives, and their applications. This rigorous course provides a solid foundation for advanced mathematics and its applications in science and engineering.',
    'objectives' => [
      'Understand the concept of limits and continuity',
      'Master differentiation techniques',
      'Apply derivatives to optimization problems',
      'Analyze functions using calculus',
      'Solve real-world problems using calculus'
    ],
    'syllabus' => [
      ['week' => 1, 'title' => 'Functions and Models', 'topics' => ['Function types', 'Models', 'Transformations']],
      ['week' => 2, 'title' => 'Limits and Continuity', 'topics' => ['Limit definition', 'Limit laws', 'Continuity']],
      ['week' => 3, 'title' => 'Derivatives', 'topics' => ['Derivative definition', 'Power rule', 'Product rule']],
      ['week' => 4, 'title' => 'Differentiation Rules', 'topics' => ['Chain rule', 'Implicit differentiation', 'Related rates']],
      ['week' => 5, 'title' => 'Applications of Derivatives', 'topics' => ['Curve sketching', 'Optimization', 'Related rates']],
      ['week' => 6, 'title' => 'Trigonometric Functions', 'topics' => ['Trig derivatives', 'Inverse trig', 'Applications']],
      ['week' => 7, 'title' => 'Exponential Functions', 'topics' => ['e and ln', 'Exponential growth', 'Applications']],
      ['week' => 8, 'title' => 'Inverse Functions', 'topics' => ['Function inverses', 'Logarithmic differentiation']],
      ['week' => 9, 'title' => 'L\'Hospital\'s Rule', 'topics' => ['Indeterminate forms', 'Limit evaluation']],
      ['week' => 10, 'title' => 'Optimization Problems', 'topics' => ['Max/min problems', 'Applied optimization']],
      ['week' => 11, 'title' => 'Antiderivatives', 'topics' => ['Integration introduction', 'Basic techniques']],
      ['week' => 12, 'title' => 'Definite Integrals', 'topics' => ['Riemann sums', 'Fundamental theorem']],
      ['week' => 13, 'title' => 'Integration Applications', 'topics' => ['Area', 'Volume', 'Applications']],
      ['week' => 14, 'title' => 'Review and Final Exam', 'topics' => ['Comprehensive review', 'Problem solving']]
    ],
    'prerequisites' => ['Pre-Calculus or equivalent', 'Strong algebra skills'],
    'materials' => [
      ['type' => 'textbook', 'name' => 'Calculus: Early Transcendentals', 'author' => 'James Stewart'],
      ['type' => 'online', 'name' => 'Khan Academy Calculus', 'url' => 'https://khanacademy.org'],
      ['type' => 'tool', 'name' => 'Graphing calculator (TI-84 or equivalent)']
    ]
  ]
];

// Get course or default
$course = isset($courseDatabase[$courseId]) ? $courseDatabase[$courseId] : $courseDatabase['cs-102'];
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
      <h1 class="page-title"><?php echo $course['title']; ?></h1>
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
      <div class="course-detail-content">
        <!-- Navigation Breadcrumb -->
        <div class="breadcrumb">
          <a href="courses.php">Courses</a>
          <span>/</span>
          <a href="course-list.php?group=<?php echo $course['groupId']; ?>"><?php echo $course['group']; ?></a>
          <span>/</span>
          <span class="current"><?php echo $course['title']; ?></span>
        </div>

        <!-- Course Header -->
        <div class="course-detail-header">
          <div class="course-header-content">
            <div class="course-meta-row">
              <span class="level-badge level-<?php echo strtolower($course['level']); ?>"><?php echo $course['level']; ?></span>
              <span class="course-group-badge"><?php echo $course['group']; ?></span>
            </div>
            <h1><?php echo $course['title']; ?></h1>
            <p class="course-short-desc"><?php echo $course['description']; ?></p>
            
            <div class="course-header-stats">
              <div class="stat-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
                </svg>
                <div>
                  <strong><?php echo $course['instructor']; ?></strong>
                  <span>Instructor</span>
                </div>
              </div>
              
              <div class="stat-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                </svg>
                <div>
                  <strong><?php echo $course['students']; ?></strong>
                  <span>Students Enrolled</span>
                </div>
              </div>
              
              <div class="stat-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                </svg>
                <div>
                  <strong><?php echo $course['rating']; ?>/5.0</strong>
                  <span><?php echo $course['reviews']; ?> reviews</span>
                </div>
              </div>
              
              <div class="stat-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
                  <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
                </svg>
                <div>
                  <strong><?php echo $course['duration']; ?></strong>
                  <span>Duration</span>
                </div>
              </div>
            </div>
            
            <div class="course-actions">
              <button class="btn-primary btn-enroll">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.5.5 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11z"/>
                </svg>
                Enroll in Course
              </button>
              <button class="btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                  <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385.92 1.815 2.834 3.989 6.286 6.357 3.452-2.368 5.365-4.542 6.286-6.357.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143q.09.083.176.171a3 3 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15"/>
                </svg>
                Add to Wishlist
              </button>
            </div>
          </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="course-tabs">
          <button class="tab-btn active" data-tab="overview">Overview</button>
          <button class="tab-btn" data-tab="syllabus">Syllabus</button>
          <button class="tab-btn" data-tab="materials">Materials</button>
          <button class="tab-btn" data-tab="discussion">Discussion</button>
        </div>

        <!-- Tab Content -->
        <div class="tab-content active" id="overview">
          <div class="content-card">
            <h2>Course Description</h2>
            <p><?php echo $course['description']; ?></p>
          </div>
          
          <div class="content-card">
            <h2>Learning Objectives</h2>
            <ul class="objectives-list">
              <?php foreach ($course['objectives'] as $objective): ?>
                <li>
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z"/>
                  </svg>
                  <?php echo $objective; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
          
          <div class="content-card">
            <h2>About the Instructor</h2>
            <div class="instructor-info">
              <div class="instructor-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
                </svg>
              </div>
              <div>
                <h3><?php echo $course['instructor']; ?></h3>
                <p><?php echo $course['instructorBio']; ?></p>
              </div>
            </div>
          </div>
          
          <div class="content-card">
            <h2>Prerequisites</h2>
            <ul class="simple-list">
              <?php foreach ($course['prerequisites'] as $prereq): ?>
                <li><?php echo $prereq; ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>

        <div class="tab-content" id="syllabus">
          <div class="content-card">
            <h2>Course Syllabus</h2>
            <div class="syllabus-list">
              <?php foreach ($course['syllabus'] as $week): ?>
                <div class="syllabus-item">
                  <div class="week-header">
                    <span class="week-number">Week <?php echo $week['week']; ?></span>
                    <h3><?php echo $week['title']; ?></h3>
                  </div>
                  <ul class="topics-list">
                    <?php foreach ($week['topics'] as $topic): ?>
                      <li><?php echo $topic; ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="tab-content" id="materials">
          <div class="content-card">
            <h2>Course Materials</h2>
            <div class="materials-list">
              <?php foreach ($course['materials'] as $material): ?>
                <div class="material-item">
                  <div class="material-icon">
                    <?php if ($material['type'] == 'textbook'): ?>
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783" />
                      </svg>
                    <?php elseif ($material['type'] == 'online'): ?>
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m7.5-6.923c-.67.204-1.335.82-1.887 1.855A8 8 0 0 0 5.145 4H7.5zM4.09 4a9.3 9.3 0 0 1 .64-1.539 7 7 0 0 1 .597-.933A7.03 7.03 0 0 0 2.255 4zm-.582 3.5c.03-.877.138-1.718.312-2.5H1.674a7 7 0 0 0-.656 2.5zM4.847 5a12.5 12.5 0 0 0-.338 2.5H7.5V5zM8.5 5v2.5h2.99a12.5 12.5 0 0 0-.337-2.5zM4.51 8.5a12.5 12.5 0 0 0 .337 2.5H7.5V8.5zm3.99 0V11h2.653c.187-.765.306-1.608.338-2.5zM5.145 12q.208.58.468 1.068c.552 1.035 1.218 1.65 1.887 1.855V12zm.182 2.472a7 7 0 0 1-.597-.933A9.3 9.3 0 0 1 4.09 12H2.255a7 7 0 0 0 3.072 2.472M3.82 11a13.7 13.7 0 0 1-.312-2.5h-2.49c.062.89.291 1.733.656 2.5zm6.853 3.472A7 7 0 0 0 13.745 12H11.91a9.3 9.3 0 0 1-.64 1.539 7 7 0 0 1-.597.933M8.5 12v2.923c.67-.204 1.335-.82 1.887-1.855q.26-.487.468-1.068zm3.68-1h2.146c.365-.767.594-1.61.656-2.5h-2.49a13.7 13.7 0 0 1-.312 2.5m2.802-3.5a7 7 0 0 0-.656-2.5H12.18c.174.782.282 1.623.312 2.5zM11.27 2.461c.247.464.462.98.64 1.539h1.835a7 7 0 0 0-3.072-2.472c.218.284.418.598.597.933M10.855 4a8 8 0 0 0-.468-1.068C9.835 1.897 9.17 1.282 8.5 1.077V4z"/>
                      </svg>
                    <?php else: ?>
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM5 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/>
                        <path d="M8 14a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
                      </svg>
                    <?php endif; ?>
                  </div>
                  <div class="material-info">
                    <h4><?php echo $material['name']; ?></h4>
                    <?php if (isset($material['author'])): ?>
                      <p>by <?php echo $material['author']; ?></p>
                    <?php endif; ?>
                    <?php if (isset($material['url'])): ?>
                      <a href="<?php echo $material['url']; ?>" target="_blank" class="material-link">Visit Resource →</a>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="tab-content" id="discussion">
          <div class="content-card">
            <h2>Course Discussion</h2>
            <p class="discussion-placeholder">Join the discussion forum to ask questions, share insights, and collaborate with fellow students.</p>
            <button class="btn-primary">Go to Forum →</button>
          </div>
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
