<?php
session_start();
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';
require_once '../database/Notification.php';

// Redirect if not logged in
if (empty($_SESSION['user_id'])) {
  header('location: ../landing/landing.php');
  exit;
}

$db = new DatabaseConnection();
$conn = $db->connection;
$user = new User();
$user->populate($_SESSION['user_id']);
$notif = new Notification($conn);

// Mark all as read when viewing this page
$notif->markAllAsRead($_SESSION['user_id']);

// Get all notifications
$limit = 50;
$result = $notif->getRecentNotifications($_SESSION['user_id'], $limit);
$notifications = [];
while ($row = $result->fetch_assoc()) {
  $notifications[] = $row;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notification'])) {
  $notificationId = intval($_POST['notification_id']);
  $notif->deleteNotification($notificationId);
  header('Location: notifications.php');
  exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkillSwap — Notifications</title>
  <link rel="stylesheet" href="../post/style.css?v=nav-20251022">
  <style>
    .notifications-container {
      max-width: 900px;
      margin: 0 auto;
    }

    .notification-card {
      background: var(--background-card);
      border: 1px solid var(--border-color);
      border-radius: var(--border-radius);
      padding: 16px;
      margin-bottom: 12px;
      display: flex;
      gap: 12px;
      align-items: flex-start;
      transition: background-color var(--transition-fast);
    }

    .notification-card:hover {
      background-color: var(--background-hover);
    }

    .notification-card.unread {
      background-color: rgba(31, 255, 147, 0.05);
      border-color: var(--primary-color);
    }

    .notification-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      background-color: var(--background-hover);
    }

    .notification-icon svg {
      width: 20px;
      height: 20px;
    }

    .notification-icon.friend-request {
      background-color: rgba(66, 133, 244, 0.15);
      color: #4285f4;
    }

    .notification-icon.friend-accepted {
      background-color: rgba(76, 175, 80, 0.15);
      color: #4caf50;
    }

    .notification-icon.event-created {
      background-color: rgba(255, 152, 0, 0.15);
      color: #ff9800;
    }

    .notification-content {
      flex: 1;
      min-width: 0;
    }

    .notification-title {
      font-size: 1rem;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 4px;
    }

    .notification-desc {
      font-size: 0.9rem;
      color: var(--text-secondary);
      margin-bottom: 6px;
      line-height: 1.4;
    }

    .notification-meta {
      display: flex;
      gap: 12px;
      font-size: 0.85rem;
      color: var(--text-muted);
    }

    .notification-actions {
      display: flex;
      gap: 8px;
      flex-shrink: 0;
    }

    .notification-delete-btn {
      background: none;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      padding: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 4px;
      transition: all var(--transition-fast);
    }

    .notification-delete-btn:hover {
      background-color: rgba(244, 67, 54, 0.2);
      color: #f44336;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--text-secondary);
    }

    .empty-state-icon {
      font-size: 3rem;
      margin-bottom: 16px;
      opacity: 0.5;
    }

    .empty-state-text {
      font-size: 1.1rem;
    }

    .notifications-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 1px solid var(--border-color);
    }

    .notifications-header h2 {
      margin: 0;
      font-size: 1.8rem;
    }

    .mark-all-read-btn {
      background: var(--primary-color);
      color: #000;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      font-size: 0.9rem;
      transition: background-color var(--transition-fast);
    }

    .mark-all-read-btn:hover {
      background: var(--primary-hover);
    }

    .notification-count {
      display: inline-block;
      background: var(--primary-color);
      color: #000;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.85rem;
      font-weight: 600;
      margin-left: 8px;
    }
  </style>
</head>

<body>

  <!-- Sidebar Navigation -->
  <aside class="sidebar" id="sidebar">
    <!-- Top Section: Logo & Profile -->
    <div class="sidebar-top">
      <div class="sidebar-logo">
        <div class="logo-placeholder"><img class=".logo-placeholder" src="../images/skillswaplogotrans.png"></div>
        <span class="logo-text">SkillSwap</span>
      </div>

      <div class="sidebar-profile">
        <div class="profile-avatar">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
          </svg>
        </div>
        <div class="profile-info">
          <h3 class="profile-name"><?php echo htmlspecialchars($user->user_username); ?></h3>
          <p class="profile-email"><?php echo htmlspecialchars($user->user_email); ?></p>
          <a href="../profile/profile.php" class="view-profile-link">View Profile</a>
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

        <a href="../dms/dms.php" class="nav-link" data-tooltip="Direct Messages">
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

        <a href="../connections/connections.php" class="nav-link" data-tooltip="Connections">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5" />
          </svg>
          <span>Connections</span>
        </a>
      </div>

      <div class="nav-divider"></div>

      <div class="nav-group">
        <a href="../courses/courses.php" class="nav-link" data-tooltip="Courses">
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

      <a href="../settings/settings.php" class="nav-link" data-tooltip="Settings">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z" />
        </svg>
        <span>Settings</span>
      </a>


      <a href="post.php?action=logout" class="nav-link" data-tooltip="Log Out">
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
    <!-- Top Bar Navigation -->
    <header class="topbar">
      <div class="topbar-left"></div>
      <div class="topbar-center">
        <h1 class="page-title">Notifications</h1>
      </div>
      <div class="topbar-right">
      </div>
    </header>

    <!-- Notifications Content -->
    <div class="notifications-container">
      <?php if (!empty($notifications)): ?>
        <div class="notifications-header">
          <div>
            <h2>Your Notifications</h2>
          </div>
        </div>

        <?php foreach ($notifications as $notif): ?>
          <div class="notification-card <?php echo ($notif['is_read'] == 0) ? 'unread' : ''; ?>">
            <div class="notification-icon <?php echo str_replace('_', '-', $notif['type']); ?>">
              <?php
              $iconSvg = '';
              switch ($notif['type']) {
                case 'friend_request':
                  $iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H1zM11 3a1 1 0 1 1 2 0v5h5v2h-5v5h-2v-5h-5v-2h5V3z"/></svg>';
                  break;
                case 'friend_accepted':
                  $iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>';
                  break;
                case 'event_created':
                  $iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/></svg>';
                  break;
                default:
                  $iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.646.327.646.3 0 .595-.249.873-.836l3.868-7.012a.6.6 0 0 0-.122-.782q-.603-.793-1.386-.793c-.508 0-.91.42-.951.957l-.313 1.48c-.05.287-.052.335-.141.335a.509.509 0 0 1-.513-.515c0-.364.096-.755.22-1.385l.007-.042L9.95 2.75c.085-.495.273-.915.993-.915.12 0 .rafts.053.897.1l.13.012c.481.06.664.228.665.896l.213-1.28c.039-.27.218-.42.531-.42.31 0 .514.19.513.514l-.7.853c-.202.247-.597.5-.8.858l-.358.86c-.088.286-.087.346-.001.346.087 0 .32-.066.694-.189l.008-.004c.19-.062.306-.1.359-.1.061 0 .077.027.077.108 0 .089-.036.19-.118.312l-.395.988c-.202.373-.853.9-1.294.9-.566 0-.898-.3-.898-.943a60 60 0 0 1 .564-2.795c.097-.744.188-1.474.191-1.532.003-.35-.149-.654-.543-.654-.595 0-.928.811-.672 1.005.002.002.221-.531.221-.531l1.562-3.9c.067-.165.102-.332.051-.501-.097-.368-.910-1.227-1.84-1.227-.55 0-.766.148-.766.514 0 .256.03.464.07.688.02.123.047.308.047.464 0 .159-.008.161-.085.161h-.128c-.085 0-.128-.002-.129-.162.012-.163.035-.336.048-.504.881-5.231-.024-6.141-1.271-6.141-.738 0-1.052.364-1.052.846 0 .334.035.865.116 1.467.015.132.03.269.03.401 0 .257-.006.511-.046.767-.08.774-.232 1.922-.232 2.654 0 1.159.237 1.573.871 1.573.27 0 .567-.103.882-.233l.12-.05a1.784 1.784 0 0 0 .596-.422c.109-.165.175-.362.154-.646-.023-.302-.342-.792-.342-.792l-.823-1.303a.393.393 0 0 0-.385-.155z"/></svg>';
              }
              echo $iconSvg;
              ?>
            </div>
            <div class="notification-content">
              <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
              <?php if (!empty($notif['description'])): ?>
                <div class="notification-desc"><?php echo htmlspecialchars($notif['description']); ?></div>
              <?php endif; ?>
              <div class="notification-meta">
                <span><?php 
                  $datetime = new DateTime($notif['created_at']);
                  $now = new DateTime();
                  $interval = $now->diff($datetime);
                  
                  if ($interval->y > 0) {
                    echo $interval->y . " year" . ($interval->y > 1 ? "s" : "") . " ago";
                  } elseif ($interval->m > 0) {
                    echo $interval->m . " month" . ($interval->m > 1 ? "s" : "") . " ago";
                  } elseif ($interval->d > 0) {
                    echo $interval->d . " day" . ($interval->d > 1 ? "s" : "") . " ago";
                  } elseif ($interval->h > 0) {
                    echo $interval->h . " hour" . ($interval->h > 1 ? "s" : "") . " ago";
                  } elseif ($interval->i > 0) {
                    echo $interval->i . " minute" . ($interval->i > 1 ? "s" : "") . " ago";
                  } else {
                    echo "just now";
                  }
                ?></span>
                <?php if (!empty($notif['user_username'])): ?>
                  <span><?php echo htmlspecialchars($notif['user_username']); ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="notification-actions">
              <form method="POST" style="display: inline;">
                <input type="hidden" name="notification_id" value="<?php echo $notif['notification_id']; ?>">
                <button type="submit" name="delete_notification" class="notification-delete-btn" title="Delete notification">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 1a.5.5 0 0 0-.5.5v1h11v-1a.5.5 0 0 0-.5-.5h-3V1a.5.5 0 0 0-.5-.5h-2a.5.5 0 0 0-.5.5v.5H2.5z"/>
                  </svg>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>

      <?php else: ?>
        <div class="empty-state">
          <div class="empty-state-icon">🔔</div>
          <div class="empty-state-text">You're all caught up!</div>
          <p style="color: var(--text-muted); margin-top: 8px;">No notifications at the moment</p>
        </div>
      <?php endif; ?>
    </div>

  </main>

  <script src="../post/script.js?v=20251103"></script>
  <script>
    // Handle notification deletion with confirmation
    document.querySelectorAll('.notification-delete-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        if (!confirm('Delete this notification?')) {
          e.preventDefault();
        }
      });
    });
  </script>

</body>

</html>
