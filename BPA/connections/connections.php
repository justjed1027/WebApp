<?php 
session_start();
require_once '../database/DatabaseConnection.php';
require_once '../database/User.php';
require_once '../database/Connection.php';
require_once '../components/sidecontent.php';

$db = new DatabaseConnection();
$con = $db->connection;

// Helper: compute mutual connections between two users
function getMutualConnectionsCount(mysqli $con, int $userA, int $userB): int {
  $sql = "
    SELECT COUNT(DISTINCT a.other_id) AS mutual
    FROM (
      SELECT CASE WHEN requester_id = ? THEN receiver_id ELSE requester_id END AS other_id
      FROM connections
      WHERE status = 'accepted' AND (requester_id = ? OR receiver_id = ?)
    ) a
    INNER JOIN (
      SELECT CASE WHEN requester_id = ? THEN receiver_id ELSE requester_id END AS other_id
      FROM connections
      WHERE status = 'accepted' AND (requester_id = ? OR receiver_id = ?)
    ) b ON a.other_id = b.other_id
    WHERE a.other_id <> ? AND b.other_id <> ?
  ";
  $stmt = $con->prepare($sql);
  if (!$stmt) { return 0; }
  $stmt->bind_param('iiiiiiii', $userA, $userA, $userA, $userB, $userB, $userB, $userA, $userB);
  $stmt->execute();
  $res = $stmt->get_result();
  $count = 0;
  if ($res && $row = $res->fetch_assoc()) { $count = (int)$row['mutual']; }
  $stmt->close();
  return $count;
}

$pendingSql = "
  SELECT c.connection_id, c.requester_id, u.user_username 
  FROM connections c
  JOIN user u ON u.user_id = c.requester_id
  WHERE c.receiver_id = ? AND c.status = 'pending'
";

$stmt = $con->prepare($pendingSql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pending = $stmt->get_result();
$pendingCount = ($pending) ? $pending->num_rows : 0;





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

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connections | SkillSwap</title>
  <!-- Shared styles for navbar and layout -->
  <link rel="stylesheet" href="../calendar/calendar.css">
  <link rel="stylesheet" href="../components/sidecontent.css">
  <!-- Page-specific styles -->
  <link rel="stylesheet" href="connections.css">
  
</head>
<body class="has-side-content">

  <!-- Sidebar Navigation (reused from existing pages) -->
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
      <div class="calendar-main">
      
      <div class="calendar-card" id="connectionsViews">
        
        <!-- Page Header Card -->
        <div class="connections-header-card">
          <div class="page-header-content">
            <h1 class="page-main-title">Connections</h1>
            <p class="page-subtitle">Build your learning network and collaborate with other students</p>
          </div>
          <div class="page-header-actions">
            <a href="find-students.php" class="calendar-today find-students-btn">Find Students</a>
          </div>
        </div>

        <!-- Connection Requests Card -->
        <div class="connections-content-card">
        <h3>Connection Requests 
          <?php if ($pendingCount > 0): ?>
            <span class="count-badge"><?php echo (int)$pendingCount; ?></span>
          <?php endif; ?>
        </h3>
        
        <?php if ($pending && $pending->num_rows > 0): ?>
          <div class="connections-grid">
            <?php while ($req = $pending->fetch_assoc()): ?>
              <div class="connection-card">
                <a href="../profile/profile.php?user_id=<?php echo intval($req['requester_id']); ?>" style="text-decoration:none;color:inherit;display:block;">
                  <div class="connection-header" style="cursor:pointer;">
                    <div class="user-avatar" style="transition:background 0.2s;" onmouseover="this.style.background='#d9dcdf'" onmouseout="this.style.background=''">
                      <?php require_once '../components/sidecontent.php'; echo renderSideContentAvatar($req['requester_id']); ?>
                    </div>
                    <div class="user-info">
                      <h4 class="user-name" style="transition:color 0.2s;" onmouseover="this.style.color='#551A8B'" onmouseout="this.style.color=''"><?php echo htmlspecialchars($req['user_username']); ?></h4>
                      <?php $mutual = getMutualConnectionsCount($con, (int)$_SESSION['user_id'], (int)$req['requester_id']); ?>
                      <p class="user-details"><?php echo (int)$mutual; ?> mutual connections</p>
                    </div>
                  </div>
                </a>
                <div class="connection-actions">
                  <form action="respond_request.php" method="POST" style="display:inline">
                    <input type="hidden" name="connection_id" value="<?php echo $req['connection_id']; ?>">
                    <input type="hidden" name="action" value="accept">
                    <button type="submit" class="btn-accept">Accept</button>
                  </form>
                  <form action="respond_request.php" method="POST" style="display:inline">
                    <input type="hidden" name="connection_id" value="<?php echo $req['connection_id']; ?>">
                    <input type="hidden" name="action" value="decline">
                    <button type="submit" class="btn-decline">Decline</button>
                  </form>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
              <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
            </svg>
            <p>No pending connection requests</p>
            <p class="empty-state-subtitle">When students send you connection requests, they'll appear here</p>
          </div>
        <?php endif; ?>
        </div>

        <!-- My Connections Card -->
        <div class="connections-content-card">
        <?php
        $userId = $_SESSION['user_id'];
        $connObj = new Connection($db->connection);
        $connections = $connObj->getConnections($userId);
        $connectionsCount = ($connections) ? $connections->num_rows : 0;
        ?>
        <h3>My Connections 
          <?php if ($connectionsCount > 0): ?>
            <span class="count-badge"><?php echo (int)$connectionsCount; ?></span>
          <?php endif; ?>
        </h3>

        <?php if ($connections && $connections->num_rows > 0): ?>
          <div class="connections-grid">
            <?php while ($row = $connections->fetch_assoc()): ?>
              <div class="connection-card">
                <form action="remove_connection.php" method="POST" class="unconnect-form">
                  <input type="hidden" name="connection_id" value="<?php echo (int)$row['connection_id']; ?>">
                  <button type="button" class="btn-unconnect" aria-label="Remove connection" data-connection-id="<?php echo (int)$row['connection_id']; ?>">&times;</button>
                </form>
                <a href="../profile/profile.php?user_id=<?php echo intval($row['user_id']); ?>" style="text-decoration:none;color:inherit;display:block;">
                  <div class="connection-header" style="cursor:pointer;">
                    <div class="user-avatar" style="transition:background 0.2s;" onmouseover="this.style.background='#d9dcdf'" onmouseout="this.style.background=''">
                      <?php require_once '../components/sidecontent.php'; echo renderSideContentAvatar($row['user_id']); ?>
                    </div>
                    <div class="user-info">
                      <h4 class="user-name" style="transition:color 0.2s;" onmouseover="this.style.color='#551A8B'" onmouseout="this.style.color=''"><?php echo htmlspecialchars($row['user_username']); ?></h4>
                      <p class="user-details">Connected Student</p>
                    </div>
                  </div>
                </a>
                <div class="connection-actions">
                  <a href="../dms/dms.php?user_id=<?php echo $row['user_id']; ?>" class="btn-message">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M2.678 11.894a1 1 0 0 1 .287.801 11 11 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8 8 0 0 0 8 14c3.996 0 7-2.807 7-6s-3.004-6-7-6-7 2.808-7 6c0 1.468.617 2.83 1.678 3.894"/>
                    </svg>
                    Message
                  </a>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
              <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>
            </svg>
            <p>No connections yet</p>
            <p class="empty-state-subtitle">Start connecting with other students to build your learning network</p>
          </div>
        <?php endif; ?>
        </div>

        <!-- Recommended Students Card -->
        <div class="connections-content-card">
        <?php
        $userId = $_SESSION['user_id'];
        $connObj = new Connection($db->connection);
        $recommended = $connObj->getRecommendedUsers($userId);
        ?>
        <h3>Recommended Students</h3>
        
        <?php if ($recommended && $recommended->num_rows > 0): ?>
          <div class="recommendations-grid">
            <?php while ($row = $recommended->fetch_assoc()): ?>
              <?php $mutual = getMutualConnectionsCount($con, (int)$userId, (int)$row['user_id']); ?>
              <div class="recommendation-card">
                <a href="../profile/profile.php?user_id=<?php echo intval($row['user_id']); ?>" style="text-decoration:none;color:inherit;display:block;flex-grow:1;">
                  <div class="user-avatar" style="cursor:pointer;transition:background 0.2s;margin:0 auto;">
                    <?php require_once '../components/sidecontent.php'; echo renderSideContentAvatar($row['user_id']); ?>
                  </div>
                  <div class="user-info">
                    <h4 class="user-name" style="cursor:pointer;transition:color 0.2s;" onmouseover="this.style.color='#551A8B'" onmouseout="this.style.color=''"><?php echo htmlspecialchars($row['user_username']); ?></h4>
                    <p class="user-details"><?php echo (int)$mutual; ?> mutual connections</p>
                  </div>
                </a>
                <form action="send_request.php" method="POST">
                  <input type="hidden" name="receiver_id" value="<?php echo htmlspecialchars($row['user_id']); ?>">
                  <button type="submit" class="btn-connect">Connect</button>
                </form>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-person-fill-add" viewBox="0 0 16 16">
              <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0m-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
              <path d="M2 13c0 1 1 1 1 1h5.256A4.5 4.5 0 0 1 8 12.5a4.5 4.5 0 0 1 1.544-3.393Q8.844 9.002 8 9c-5 0-6 3-6 4"/>
            </svg>
            <p>No recommendations available</p>
            <p class="empty-state-subtitle">Check back later for personalized student recommendations</p>
          </div>
        <?php endif; ?>
        </div>
      
      </div> <!-- End connections-content-card -->
      </div> <!-- End calendar-card -->
      </div> <!-- End calendar-main -->
    </div> <!-- End calendar-container -->
    
    <!-- Side Content -->
    <?php renderSideContent('connections', ['hide' => []]); ?>
  </main>

  <!-- Unconnect confirmation modal -->
  <div id="unconnectModal" class="modal-overlay" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="unconnectTitle">
      <h4 id="unconnectTitle">Remove connection?</h4>
      <p class="modal-body">This will remove the connection. You can send a new request later.</p>
      <div class="modal-actions">
        <button type="button" class="btn-decline" id="cancelUnconnect">Keep Connection</button>
        <button type="button" class="btn-accept" id="confirmUnconnect">Remove Connection</button>
      </div>
    </div>
  </div>
  
  <script src="../components/sidecontent.js"></script>
  <script src="connections.js"></script>
  <script>
    // Hide placeholder icons when images are loaded
    document.querySelectorAll('.user-avatar').forEach(avatar => {
      if (avatar.querySelector('img')) {
        avatar.classList.add('has-image');
      }
      if (avatar.querySelector('span')) {
        avatar.classList.add('has-initials');
      }
    });
    
    // Watch for dynamically added avatars
    const observer = new MutationObserver(mutations => {
      mutations.forEach(mutation => {
        mutation.addedNodes.forEach(node => {
          if (node.nodeType === 1 && node.classList) {
            if (node.classList.contains('user-avatar')) {
              if (node.querySelector('img')) {
                node.classList.add('has-image');
              }
              if (node.querySelector('span')) {
                node.classList.add('has-initials');
              }
            }
          }
        });
      });
    });
    
    observer.observe(document.body, { 
      childList: true, 
      subtree: true 
    });
  </script>
</body>
</html>