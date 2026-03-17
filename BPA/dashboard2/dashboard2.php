<?php
session_start();
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';
require_once '../database/UserPreferences.php';
require_once '../components/sidecontent.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    session_destroy();
    setcookie('PHPSESSID', '', time() - 3600, '/');
    header('Location: ../landing/landing.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$db = new DatabaseConnection();
$conn = $db->connection;

// Get user preferences and set logo based on color
$userPreferences = UserPreferences::getForUser($conn, $userId);
$userColor = $userPreferences['primary_color'] ?? '#00D97E';

// Logo path based on user's color preference
if (empty($userColor) || $userColor === 'Silver' || $userColor === '#00D97E') {
    $logoPath = '../images/skillswaplogotrans.png';
} else {
    $cleanColor = strtolower(ltrim($userColor, '#'));
    $logoPath = '../images/logo' . $cleanColor . '.png';
}

$imageMap = [
    'Art & Design' => 'https://images.pexels.com/photos/196644/pexels-photo-196644.jpeg?auto=compress&cs=tinysrgb&w=1200',
    'Business & Economics' => 'https://images.pexels.com/photos/3183150/pexels-photo-3183150.jpeg?auto=compress&cs=tinysrgb&w=1200',
    'Computer Science' => 'https://images.pexels.com/photos/1181263/pexels-photo-1181263.jpeg?auto=compress&cs=tinysrgb&w=1200',
    'English' => 'https://images.pexels.com/photos/590493/pexels-photo-590493.jpeg?auto=compress&cs=tinysrgb&w=1200',
    'History' => 'https://images.pexels.com/photos/159866/books-book-pages-read-literature-159866.jpeg?auto=compress&cs=tinysrgb&w=1200',
    'Languages' => 'https://images.pexels.com/photos/267669/pexels-photo-267669.jpeg?auto=compress&cs=tinysrgb&w=1200',
    'Mathematics' => 'https://images.pexels.com/photos/6238297/pexels-photo-6238297.jpeg?auto=compress&cs=tinysrgb&w=1200',
    'Music' => 'https://images.pexels.com/photos/164938/pexels-photo-164938.jpeg?auto=compress&cs=tinysrgb&w=1200',
    'Science' => 'https://images.pexels.com/photos/2280571/pexels-photo-2280571.jpeg?auto=compress&cs=tinysrgb&w=1200'
];


$userSkillCategories = [];
$userSkillsQuery = '
    SELECT DISTINCT s.category_id
    FROM user_skills us
    INNER JOIN subjects s ON us.us_subject_id = s.subject_id
    WHERE us.us_user_id = ? AND s.category_id != 3
';
$stmt = $conn->prepare($userSkillsQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $userSkillCategories[] = (int) $row['category_id'];
}
$stmt->close();

$userInterestCategories = [];
$userInterestsQuery = '
    SELECT DISTINCT s.category_id
    FROM user_interests ui
    INNER JOIN subjects s ON ui.ui_subject_id = s.subject_id
    WHERE ui.ui_user_id = ? AND s.category_id != 3
';
$stmt = $conn->prepare($userInterestsQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $userInterestCategories[] = (int) $row['category_id'];
}
$stmt->close();

$bucketByCategory = [];
$wantToLearn = [];
$buildingSkills = [];
$myExpertise = [];
$otherCourses = [];

$categoriesSummaryQuery = '
    SELECT
        sc.category_id,
        sc.category_name,
        sc.category_description,
        COUNT(s.subject_id) AS resource_count
    FROM subjectcategories sc
    LEFT JOIN subjects s ON sc.category_id = s.category_id
    WHERE sc.category_id != 3
    GROUP BY sc.category_id, sc.category_name, sc.category_description
    ORDER BY sc.category_name ASC
';
$categoriesSummaryResult = $conn->query($categoriesSummaryQuery);

while ($cat = $categoriesSummaryResult->fetch_assoc()) {
    $catId = (int) $cat['category_id'];
    $catName = $cat['category_name'];
    $categoryData = [
        'id' => $catId,
        'name' => $catName,
        'description' => $cat['category_description'] ?? '',
        'icon' => $categoryStyles[$catName]['icon'] ?? 'Topic',
        'color' => $categoryStyles[$catName]['color'] ?? '#6b7280',
        'resourceCount' => (int) $cat['resource_count']
    ];

    $inSkills = in_array($catId, $userSkillCategories, true);
    $inInterests = in_array($catId, $userInterestCategories, true);

    if ($inInterests && !$inSkills) {
        $wantToLearn[] = $categoryData;
        $bucketByCategory[$catId] = 'Want to Learn';
    } elseif ($inInterests && $inSkills) {
        $buildingSkills[] = $categoryData;
        $bucketByCategory[$catId] = 'Building Skills';
    } elseif ($inSkills && !$inInterests) {
        $myExpertise[] = $categoryData;
        $bucketByCategory[$catId] = 'My Expertise';
    } else {
        $otherCourses[] = $categoryData;
        $bucketByCategory[$catId] = 'Other Courses';
    }
}

$cardsQuery = '
    SELECT
        sc.category_id,
        sc.category_name,
        sc.category_description,
        s.subject_id,
        s.subject_name,
        (SELECT COUNT(*) FROM user_skills   WHERE us_user_id = ? AND us_subject_id = s.subject_id) AS in_skills,
        (SELECT COUNT(*) FROM user_interests WHERE ui_user_id = ? AND ui_subject_id = s.subject_id) AS in_interests
    FROM subjectcategories sc
    LEFT JOIN subjects s ON s.category_id = sc.category_id
    WHERE sc.category_id != 3
    ORDER BY sc.category_name ASC, s.subject_name ASC
';
$stmt = $conn->prepare($cardsQuery);
$stmt->bind_param('ii', $userId, $userId);
$stmt->execute();
$cardsResult = $stmt->get_result();

$categories = [];
$totalResources = 0;
while ($row = $cardsResult->fetch_assoc()) {
    $categoryId = (int) $row['category_id'];

    if (!isset($categories[$categoryId])) {
        $categoryName = $row['category_name'];
        $colors = $accentMap[$categoryName] ?? ['#f3f7a5', '#c9d85b'];
        $categories[$categoryId] = [
            'id' => $categoryId,
            'name' => $categoryName,
            'description' => trim((string) ($row['category_description'] ?? '')),
            'image' => $imageMap[$categoryName] ?? 'https://images.pexels.com/photos/159740/library-la-trobe-study-students-159740.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'colors' => $colors,
            'bucket' => $bucketByCategory[$categoryId] ?? 'Other Courses',
            'subjects' => [
                'Want to Learn'   => [],
                'Building Skills' => [],
                'My Expertise'    => [],
                'Other Courses'   => []
            ]
        ];
    }

    if (!empty($row['subject_id'])) {
        $inS = (int) $row['in_skills'];
        $inI = (int) $row['in_interests'];
        if      ($inI && !$inS) $subjectBucket = 'Want to Learn';
        elseif  ($inI && $inS)  $subjectBucket = 'Building Skills';
        elseif  ($inS && !$inI) $subjectBucket = 'My Expertise';
        else                    $subjectBucket = 'Other Courses';

        $categories[$categoryId]['subjects'][$subjectBucket][] = [
            'id'   => (int) $row['subject_id'],
            'name' => $row['subject_name']
        ];
        $totalResources++;
    }
}

$db->closeConnection();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillSwap - Courses 2</title>
    <link rel="icon" type="image/png" href="<?php echo $logoPath; ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../courses/courses.css">
    <link rel="stylesheet" href="../components/sidecontent.css">
    <link rel="stylesheet" href="dashboard2.css?v=<?php echo filemtime(__DIR__ . '/dashboard2.css'); ?>">
</head>
<body class="has-side-content dashboard2-page" data-logo-path="<?php echo htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8'); ?>">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-top">
            <div class="sidebar-logo">
                <img src="<?php echo $logoPath; ?>" alt="SkillSwap logo" style="width:40px;">
                <span class="logo-text">SkillSwap</span>
            </div>

            <div class="sidebar-profile">
                <div class="profile-avatar">
                    <?php echo renderProfileAvatar(); ?>
                </div>
                <div class="profile-info">
                    <a href="../profile/profile.php" class="view-profile-link">View Profile - <?php echo isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === 1 ? 'Admin' : 'Student'; ?></a>
                </div>
            </div>
        </div>

        <div class="sidebar-middle">
            <div class="nav-group">
                <a href="dashboard2.php" class="nav-link active" data-tooltip="Dashboard">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5" /></svg>
                    <span>Dashboard</span>
                </a>
                <a href="../post/post.php" class="nav-link" data-tooltip="Posts">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M6.428 1.151C6.708.591 7.213 0 8 0s1.292.592 1.572 1.151C9.861 1.73 10 2.431 10 3v3.691l5.17 2.585a1.5 1.5 0 0 1 .83 1.342V12a.5.5 0 0 1-.582.493l-5.507-.918-.375 2.253 1.318 1.318A.5.5 0 0 1 10.5 16h-5a.5.5 0 0 1-.354-.854l1.319-1.318-.376-2.253-5.507.918A.5.5 0 0 1 0 12v-1.382a1.5 1.5 0 0 1 .83-1.342L6 6.691V3c0-.568.14-1.271.428-1.849"/></svg>
                    <span>Posts</span>
                </a>
                <a href="../dms/dms.php" class="nav-link" data-tooltip="Direct Messages">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8c0 3.866-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.584.296-1.925.864-4.181 1.234-.2.032-.352-.176-.273-.362.354-.836.674-1.95.77-2.966C.744 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7M5 8a1 1 0 1 0-2 0 1 1 0 0 0 2 0m4 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2" /></svg>
                    <span>DMs</span>
                </a>
                <a href="../connections/connections.php" class="nav-link" data-tooltip="Connections">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5" /></svg>
                    <span>Connections</span>
                </a>
                <a href="../calendar/calendar.php" class="nav-link" data-tooltip="Calendar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z" /></svg>
                    <span>Calendar</span>
                </a>
                <a href="../events/events.php" class="nav-link" data-tooltip="Events">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z" /><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z" /></svg>
                    <span>Events</span>
                </a>
            </div>
        </div>

        <div class="sidebar-bottom">
            <div class="nav-divider"></div>
            <a href="../profile/profile.php" class="nav-link" data-tooltip="Edit User">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z" /></svg>
                <span>Edit User</span>
            </a>
            <a href="dashboard2.php?action=logout" class="nav-link" data-tooltip="Log Out">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z" /><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z" /></svg>
                <span>Log Out</span>
            </a>
            <div class="theme-toggle">
                <button class="theme-toggle-btn" id="themeToggle">
                    <div class="toggle-switch">
                        <div class="toggle-slider">
                            <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6m0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8m10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0m-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707M4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708" /></svg>
                            <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278M4.858 1.311A7.27 7.27 0 0 0 1.025 7.71c0 4.02 3.279 7.276 7.319 7.276a7.32 7.32 0 0 0 5.205-2.162q-.506.063-1.029.063c-4.61 0-8.343-3.714-8.343-8.29 0-1.167.242-2.278.681-3.286" /></svg>
                        </div>
                    </div>
                </button>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-content">
            <div class="dashboard2-main">
                <section class="dashboard2-grid" id="dashboard2Grid">
                    <?php foreach ($categories as $category): ?>
                        <?php
                            $allSubjects = array_merge(...array_values($category['subjects']));
                            $subjectCount = count($allSubjects);
                        ?>
                        <article class="dashboard2-card" data-card data-default-bucket="<?php echo htmlspecialchars($category['bucket'], ENT_QUOTES, 'UTF-8'); ?>" data-subjects="<?php echo htmlspecialchars(json_encode($category['subjects'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>" style="--accent-a: <?php echo htmlspecialchars($category['colors'][0], ENT_QUOTES, 'UTF-8'); ?>; --accent-b: <?php echo htmlspecialchars($category['colors'][1], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="dashboard2-card-inner">
                                <div class="dashboard2-face dashboard2-front" data-flip-surface style="background-image: linear-gradient(180deg, rgba(3, 7, 18, 0.12), rgba(3, 7, 18, 0.82)), url('<?php echo htmlspecialchars($category['image'], ENT_QUOTES, 'UTF-8'); ?>');">
                                    <div class="dashboard2-card-topline">
                                        <span class="dashboard2-topic-pill"><?php echo $subjectCount; ?> subjects</span>
                                    </div>
                                    <div class="dashboard2-front-copy">
                                        <p class="dashboard2-front-kicker"><?php echo htmlspecialchars($category['bucket']); ?></p>
                                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                        <p><?php echo htmlspecialchars($category['description'] ?: 'Open the back of this card to see every subject in the category.', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </div>

                                <div class="dashboard2-face dashboard2-back">
                                    <div class="dashboard2-back-header">
                                        <div class="dashboard2-back-header-inner">
                                            <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                            <div class="d2-bucket-grid">
                                                <button class="d2-bucket-box" data-bucket="Want to Learn">Want to Learn</button>
                                                <button class="d2-bucket-box" data-bucket="Building Skills">Building Skills</button>
                                                <button class="d2-bucket-box" data-bucket="My Expertise">My Expertise</button>
                                                <button class="d2-bucket-box" data-bucket="Other Courses">Other Courses</button>
                                            </div>

                                    <div class="dashboard2-subject-list" data-subject-list></div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            </div>

            <?php renderSideContent('courses'); ?>
        </div>
    </main>

    <script src="../courses/courses.js"></script>
    <script src="../components/sidecontent.js"></script>
    <script src="dashboard2.js?v=<?php echo filemtime(__DIR__ . '/dashboard2.js'); ?>"></script>
</body>
</html>