<?php 
session_start();
require_once '../database/DatabaseConnection.php';
require_once '../database/User.php';
require_once '../database/Connection.php';
require_once '../components/sidecontent.php';

$db = new DatabaseConnection();
$con = $db->connection;

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header('location: ../landing/landing.php');
    exit();
}

$currentUserId = $_SESSION['user_id'];
$user = new User();
$user->populate($currentUserId);

// Get user profile info for current user
$profileSql = "SELECT user_firstname, user_lastname FROM profile WHERE user_id = ?";
$profileStmt = $con->prepare($profileSql);
$profileStmt->bind_param("i", $currentUserId);
$profileStmt->execute();
$profileResult = $profileStmt->get_result();
$userProfile = $profileResult->fetch_assoc();
$profileStmt->close();

$userFirstName = $userProfile['user_firstname'] ?? $user->user_username;
$userLastName = $userProfile['user_lastname'] ?? '';
$userFullName = trim($userFirstName . ' ' . $userLastName) ?: $user->user_username;

// Get all users except current user with their profile info
$sql = "SELECT u.user_id, u.user_username, u.user_email, p.user_firstname, p.user_lastname 
        FROM user u
        LEFT JOIN profile p ON u.user_id = p.user_id
        WHERE u.user_id != ? 
        ORDER BY u.user_username ASC";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$allUsers = $stmt->get_result();
$stmt->close();

// Get current user's connections to check status
$connObj = new Connection($db->connection);
$connections = $connObj->getConnections($currentUserId);
$connectedUserIds = [];
if ($connections) {
    while ($row = $connections->fetch_assoc()) {
        $connectedUserIds[] = $row['user_id'];
    }
}

// Get pending requests (sent and received)
$pendingSentSql = "SELECT receiver_id FROM connections WHERE requester_id = ? AND status = 'pending'";
$stmt = $con->prepare($pendingSentSql);
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$pendingSent = $stmt->get_result();
$pendingSentIds = [];
while ($row = $pendingSent->fetch_assoc()) {
    $pendingSentIds[] = $row['receiver_id'];
}
$stmt->close();

$pendingReceivedSql = "SELECT requester_id FROM connections WHERE receiver_id = ? AND status = 'pending'";
$stmt = $con->prepare($pendingReceivedSql);
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$pendingReceived = $stmt->get_result();
$pendingReceivedIds = [];
while ($row = $pendingReceived->fetch_assoc()) {
    $pendingReceivedIds[] = $row['requester_id'];
}
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!empty($_GET['action']) && $_GET['action'] == 'logout') {
        $_SESSION = [];
        session_destroy();
        setcookie("PHPSESSID", "", time() - 3600, "/");
        header('location: ../landing/landing.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Students | SkillSwap</title>
    <!-- Shared styles for navbar and layout -->
    <link rel="stylesheet" href="../calendar/calendar.css">
    <link rel="stylesheet" href="../components/sidecontent.css">
    <!-- Page-specific styles -->
    <link rel="stylesheet" href="connections.css">
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
            <div class="profile-section">
                <div class="profile-avatar"></div>
                <div class="profile-info">
                    <div class="profile-name"><?php echo htmlspecialchars($userFullName); ?></div>
                    <div class="profile-subtitle">Student</div>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="../calendar/calendar.php" class="nav-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                        </svg>
                        <span class="nav-text">Calendar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../connections/connections.php" class="nav-link active">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                        </svg>
                        <span class="nav-text">Connections</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../courses/courses.php" class="nav-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/>
                        </svg>
                        <span class="nav-text">Courses</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../events/events.php" class="nav-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-5 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/>
                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V5h16V4H0V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5"/>
                        </svg>
                        <span class="nav-text">Events</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Bottom Section: Theme Toggle & Logout -->
        <div class="sidebar-bottom">
            <button class="theme-toggle-btn" id="themeToggle">
                <svg class="theme-icon sun-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6m0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8m10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.414a.5.5 0 1 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707M4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707"/>
                </svg>
                <svg class="theme-icon moon-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278"/>
                </svg>
                <span class="theme-text">Dark Mode</span>
            </button>
            <a href="?action=logout" class="logout-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/>
                    <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
                </svg>
                <span class="logout-text">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
            <div class="topbar-center">
                <h1>Find Students</h1>
            </div>
            <div class="topbar-right">
                <button class="icon-btn" aria-label="Notifications">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2M8 1.918l-.797.161A4 4 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4 4 0 0 0-3.203-3.92zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5 5 0 0 1 13 6c0 .88.32 4.2 1.22 6"/>
                    </svg>
                    <span class="notification-badge">3</span>
                </button>
                <button class="icon-btn" aria-label="Messages">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M2.678 11.894a1 1 0 0 1 .287.801 11 11 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8 8 0 0 0 8 14c3.996 0 7-2.807 7-6s-3.004-6-7-6-7 2.808-7 6c0 1.468.617 2.83 1.678 3.894"/>
                    </svg>
                </button>
                <div class="profile-menu">
                    <button class="profile-btn">
                        <div class="profile-avatar-small"></div>
                        <span><?php echo htmlspecialchars($userFirstName); ?></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="calendar-container">
            <!-- Header Card -->
            <div class="connections-header-card">
                <div class="page-header-content">
                    <h1 class="page-main-title">Find Students</h1>
                    <p class="page-subtitle">Discover and connect with students across the platform</p>
                </div>
                <div class="page-header-actions">
                    <a href="connections.php" class="calendar-today">Back to Connections</a>
                </div>
            </div>

            <!-- Search Card -->
            <div class="connections-content-card">
                <h3>Search Students</h3>
                <div class="search-container">
                    <input type="text" id="studentSearch" placeholder="Search by name or username..." class="search-input">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" class="search-icon">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                    </svg>
                </div>
            </div>

            <!-- All Students Card -->
            <div class="connections-content-card">
                <h3>All Students 
                    <?php if ($allUsers && $allUsers->num_rows > 0): ?>
                        <span class="count-badge"><?php echo $allUsers->num_rows; ?></span>
                    <?php endif; ?>
                </h3>

                <?php if ($allUsers && $allUsers->num_rows > 0): ?>
                    <div class="students-grid" id="studentsGrid">
                        <?php while ($row = $allUsers->fetch_assoc()): ?>
                            <?php
                            $userId = $row['user_id'];
                            $isConnected = in_array($userId, $connectedUserIds);
                            $isPendingSent = in_array($userId, $pendingSentIds);
                            $isPendingReceived = in_array($userId, $pendingReceivedIds);
                            $fullName = trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? ''));
                            $displayName = $fullName ?: 'Student';
                            ?>
                            <div class="student-card" data-name="<?php echo strtolower(htmlspecialchars($row['user_username'] . ' ' . $fullName)); ?>">
                                <div class="connection-header">
                                    <div class="user-avatar"></div>
                                    <div class="user-info">
                                        <h4 class="user-name"><?php echo htmlspecialchars($row['user_username']); ?></h4>
                                        <p class="user-details"><?php echo htmlspecialchars($displayName); ?></p>
                                    </div>
                                </div>
                                <div class="connection-actions">
                                    <?php if ($isConnected): ?>
                                        <span class="status-badge connected">Connected</span>
                                        <a href="../dms/dms.php?user_id=<?php echo $userId; ?>" class="btn-message">Message</a>
                                    <?php elseif ($isPendingSent): ?>
                                        <span class="status-badge pending">Request Sent</span>
                                    <?php elseif ($isPendingReceived): ?>
                                        <span class="status-badge pending">Pending Response</span>
                                    <?php else: ?>
                                        <form action="send_request.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="receiver_id" value="<?php echo htmlspecialchars($userId); ?>">
                                            <button type="submit" class="btn-connect">Connect</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>
                        </svg>
                        <p>No students found</p>
                        <p class="empty-state-subtitle">There are no other users on the platform yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Side Content -->
        <?php renderSideContent(); ?>
    </main>

    <script src="../components/sidecontent.js"></script>
    <script src="find-students.js"></script>
</body>
</html>