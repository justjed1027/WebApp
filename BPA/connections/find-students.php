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

        <!-- Sidebar Navigation (same shell as Connections) -->
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
                    <a href="../courses/courses.php" class="nav-link" data-tooltip="Dashboard">
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

                    <a href="../connections/connections.php" class="nav-link active" data-tooltip="Connections">
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

                <a href="#" class="nav-link" data-tooltip="Settings">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z" />
                    </svg>
                    <span>Settings</span>
                </a>

                <a href="../login/login.php" class="nav-link" data-tooltip="Log Out">
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

    <!-- Top Bar Navigation (match Connections) -->
    <header class="topbar">
        <div class="topbar-left"></div>
        <div class="topbar-center"></div>
        <div class="topbar-right">
            <div class="search-container">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
                </svg>
                <input type="text" id="connections-search" class="search-input" placeholder="Search people, posts, and courses..." >
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
                    <path d="m2.165 15.803.02-.004c1.83-.363 2.948-.842 3.468-1.105A9 9 0 0 0 8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6a10.4 10.4 0 0 1-.524 2.318l-.003.011a11 11 0 0 1-.244.637c-.079.186.074.394.273.362a22 22 0  0 0 .693-.125m.8-3.108a1 1 0  0 0-.287-.801C1.618 10.83 1 9.468 1 8c0-3.192 3.004-6 7-6s7 2.808 7 6-3.004 6-7 6a8 8 0 0 1-2.088-.272 1 1 0  0 0-.711.074c-.387.196-1.24.57-2.634.893a11 11 0  0 0 .398-2" />
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
        <?php renderSideContent('connections', ['hide' => ['trending_topics']]); ?>
    </main>

    <script src="../components/sidecontent.js"></script>
    <script src="find-students.js"></script>
</body>
</html>