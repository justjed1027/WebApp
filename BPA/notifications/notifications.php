<?php
session_start();
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';
require_once '../database/Notification.php';
require_once '../components/sidecontent.php';

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
  <title>Notifications — SkillSwap</title>
  <link rel="icon" type="image/png" href="../images/skillswaplogotrans.png">
  <link rel="stylesheet" href="../calendar/calendar.css">
  <link rel="stylesheet" href="../components/sidecontent.css">
  <style>
    .notification-item {
      background: var(--background-card);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 16px;
      display: flex;
      gap: 16px;
      align-items: flex-start;
      transition: all var(--transition-fast);
    }

    .notification-item:hover {
      background-color: var(--background-hover);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      transform: translateY(-2px);
    }

    .notification-item.unread {
      background: linear-gradient(135deg, rgba(31, 255, 147, 0.08) 0%, rgba(31, 255, 147, 0.03) 100%);
      border-left: 4px solid var(--primary-color);
    }

    .notification-icon {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      background: linear-gradient(135deg, rgba(31, 255, 147, 0.15) 0%, rgba(31, 255, 147, 0.05) 100%);
      transition: transform var(--transition-fast);
    }

    .notification-item:hover .notification-icon {
      transform: scale(1.08);
    }

    .notification-icon svg {
      width: 26px;
      height: 26px;
      color: var(--primary-color);
    }

    .notification-icon.friend-request svg { color: #4285f4; }
    .notification-icon.friend-accepted svg { color: #4caf50; }
    .notification-icon.event-created svg { color: #ff9800; }
    .notification-icon.welcome svg { color: #9c27b0; }

    .notification-content {
      flex: 1;
      min-width: 0;
    }

    .notification-title {
      font-size: 1rem;
      font-weight: 600;
      color: var(--text-primary);
      margin: 0 0 6px 0;
    }

    .notification-description {
      font-size: 0.95rem;
      color: var(--text-secondary);
      margin: 0 0 10px 0;
      line-height: 1.5;
    }

    .notification-meta {
      display: flex;
      gap: 12px;
      font-size: 0.85rem;
      color: var(--text-muted);
      margin: 0;
    }

    .notification-delete {
      background: none;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      padding: 8px;
      border-radius: 8px;
      transition: all var(--transition-fast);
      flex-shrink: 0;
    }

    .notification-delete:hover {
      background-color: rgba(244, 67, 54, 0.15);
      color: #f44336;
    }

    .empty-notifications {
      background: var(--background-card);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 60px 40px;
      text-align: center;
      color: var(--text-secondary);
    }

    .empty-notifications-icon {
      font-size: 4rem;
      margin-bottom: 20px;
      opacity: 0.5;
    }

    .empty-notifications-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: var(--text-primary);
      margin: 0 0 10px 0;
    }

    .page-header-content h1 {
      margin: 0;
    }
  </style>
</head>

<body class="has-side-content">

  <!-- Sidebar Navigation -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-top">
      <div class="sidebar-logo">
        <div class="logo-placeholder"><img src="../images/skillswaplogotrans.png" style="width:40px;"></div>
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
        <a href="../courses/courses.php" class="nav-link">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5" />
          </svg>
          <span>Dashboard</span>
        </a>
        <a href="../post/post.php" class="nav-link">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M6.428 1.151C6.708.591 7.213 0 8 0s1.292.592 1.572 1.151C9.861 1.73 10 2.431 10 3v3.691l5.17 2.585a1.5 1.5 0 0 1 .83 1.342V12a.5.5 0 0 1-.582.493l-5.507-.918-.375 2.253 1.318 1.318A.5.5 0 0 1 10.5 16h-5a.5.5 0 0 1-.354-.854l1.319-1.318-.376-2.253-5.507.918A.5.5 0 0 1 0 12v-1.382a1.5 1.5 0 0 1 .83-1.342L6 6.691V3c0-.568.14-1.271.428-1.849"/>
          </svg>
          <span>Posts</span>
        </a>
        <a href="../dms/dms.php" class="nav-link">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M16 8c0 3.866-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.584.296-1.925.864-4.181 1.234-.2.032-.352-.176-.273-.362.354-.836.674-1.95.77-2.966C.744 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7M5 8a1 1 0 1 0-2 0 1 1 0 0 0 2 0m4 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
          </svg>
          <span>DMs</span>
        </a>
        <a href="../connections/connections.php" class="nav-link">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5" />
          </svg>
          <span>Connections</span>
        </a>
        <a href="../calendar/calendar.php" class="nav-link">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z" />
          </svg>
          <span>Calendar</span>
        </a>
        <a href="../events/events.php" class="nav-link">
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

      <a href="../settings/settings.php" class="nav-link" data-tooltip="Edit User">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z" />
        </svg>
        <span>Edit User</span>
      </a>
      <a href="notifications.php?action=logout" class="nav-link">
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

  <main class="main-content">
    <div class="page-content">
      <div class="calendar-container">
        <div class="calendar-main">
          <!-- Header -->
          <div class="calendar-card">
            <div class="page-header-content">
              <h1 class="page-main-title">Notifications</h1>
              <p class="page-subtitle">Stay updated on activity from your connections and interactions</p>
            </div>
          </div>

          <!-- Notifications List -->
          <div class="calendar-card">
            <?php if (!empty($notifications)): ?>
              <?php foreach ($notifications as $notif): ?>
                <div class="notification-item <?php echo ($notif['is_read'] == 0) ? 'unread' : ''; ?>">
                  <div class="notification-icon <?php echo str_replace('_', '-', $notif['type']); ?>">
                    <?php
                    $iconSvg = '';
                    switch ($notif['type']) {
                      case 'friend_request':
                        $iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H1zM11 3a1 1 0 1 1 2 0v5h5v2h-5v5h-2v-5h-5v-2h5V3z"/></svg>';
                        break;
                      case 'friend_accepted':
                        $iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm9.854-3.146a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L8.5 11.793l6.646-6.647a.5.5 0 0 1 .708 0z"/></svg>';
                        break;
                      case 'event_created':
                        $iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/></svg>';
                        break;
                      case 'welcome':
                        $iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.646.327.646.3 0 .595-.249.873-.836l.89-1.893c.178-.336.276-.707.276-1.086 0-.646-.363-1.107-.948-1.107-.62 0-1.078.39-1.078.897 0 .34.135.664.353.997z"/></svg>';
                        break;
                      default:
                        $iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.646.327.646.3 0 .595-.249.873-.836l.89-1.893c.178-.336.276-.707.276-1.086 0-.646-.363-1.107-.948-1.107-.62 0-1.078.39-1.078.897 0 .34.135.664.353.997z"/></svg>';
                    }
                    echo $iconSvg;
                    ?>
                  </div>
                  <div class="notification-content">
                    <h4 class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></h4>
                    <?php if (!empty($notif['description'])): ?>
                      <p class="notification-description"><?php echo htmlspecialchars($notif['description']); ?></p>
                    <?php endif; ?>
                    <p class="notification-meta">
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
                        <span>•</span>
                        <span><?php echo htmlspecialchars($notif['user_username']); ?></span>
                      <?php endif; ?>
                    </p>
                  </div>
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="notification_id" value="<?php echo $notif['notification_id']; ?>">
                    <button type="submit" name="delete_notification" class="notification-delete" title="Delete">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 1a.5.5 0 0 0-.5.5v1h11v-1a.5.5 0 0 0-.5-.5h-3V1a.5.5 0 0 0-.5-.5h-2a.5.5 0 0 0-.5.5v.5H2.5z"/>
                      </svg>
                    </button>
                  </form>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-notifications">
                <div class="empty-notifications-icon">🔔</div>
                <h3 class="empty-notifications-title">You're all caught up!</h3>
                <p>No notifications at the moment</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Side Content -->
      <?php 
        renderSideContent('notifications', [
          'hide' => ['notifications'],
          'limit' => ['recent_dms' => 3, 'upcoming_events' => 3, 'suggested_collaborators' => 3]
        ]); 
      ?>
    </div>
  </main>

  <script src="../post/script.js?v=20251103"></script>
  <script>
    // Handle notification deletion with confirmation
    document.querySelectorAll('button[name="delete_notification"]').forEach(btn => {
      btn.addEventListener('click', function(e) {
        if (!confirm('Delete this notification?')) {
          e.preventDefault();
        }
      });
    });
  </script>

</body>

</html>
