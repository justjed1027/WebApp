<?php
session_start();
require_once '../database/DatabaseConnection.php';
require_once '../database/Notification.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$db = new DatabaseConnection();
$conn = $db->connection;
$notif = new Notification($conn);

$action = isset($_GET['action']) ? $_GET['action'] : '';
$userId = $_SESSION['user_id'];

switch ($action) {
    case 'get_count':
        $count = $notif->getUnreadCount($userId);
        $countDisplay = $notif->getCountDisplay($userId);
        echo json_encode([
            'success' => true,
            'count' => $count,
            'display' => $countDisplay
        ]);
        break;

    case 'get_recent':
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        $result = $notif->getRecentNotifications($userId, $limit);
        $notifications = [];
        
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'notification_id' => $row['notification_id'],
                'type' => $row['type'],
                'title' => $row['title'],
                'description' => $row['description'],
                'is_read' => $row['is_read'],
                'created_at' => $row['created_at'],
                'actor_username' => $row['user_username'],
                'time_ago' => timeAgo($row['created_at'])
            ];
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ]);
        break;

    case 'mark_read':
        if (isset($_POST['notification_id'])) {
            $notifId = intval($_POST['notification_id']);
            $success = $notif->markAsRead($notifId);
            echo json_encode(['success' => $success]);
        }
        break;

    case 'mark_all_read':
        $success = $notif->markAllAsRead($userId);
        echo json_encode(['success' => $success]);
        break;

    case 'delete':
        if (isset($_POST['notification_id'])) {
            $notifId = intval($_POST['notification_id']);
            $success = $notif->deleteNotification($notifId);
            echo json_encode(['success' => $success]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

function timeAgo($timestamp) {
    $datetime = new DateTime($timestamp);
    $now = new DateTime();
    $interval = $now->diff($datetime);

    if ($interval->y > 0) {
        return $interval->y . " year" . ($interval->y > 1 ? "s" : "");
    }
    if ($interval->m > 0) {
        return $interval->m . " month" . ($interval->m > 1 ? "s" : "");
    }
    if ($interval->d > 0) {
        return $interval->d . " day" . ($interval->d > 1 ? "s" : "");
    }
    if ($interval->h > 0) {
        return $interval->h . " hour" . ($interval->h > 1 ? "s" : "");
    }
    if ($interval->i > 0) {
        return $interval->i . " minute" . ($interval->i > 1 ? "s" : "");
    }
    return "just now";
}
?>
