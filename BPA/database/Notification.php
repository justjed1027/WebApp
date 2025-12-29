<?php
require_once '../database/DatabaseConnection.php';

class Notification {
    private $connection;

    public function __construct($connection) {
        $this->connection = $connection;
    }

    /**
     * Create a notification
     */
    public function createNotification($userId, $type, $actorUserId, $title, $description = null, $referenceId = null, $referenceType = null) {
        $sql = "INSERT INTO notifications (user_id, type, actor_user_id, title, description, reference_id, reference_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("isssisi", $userId, $type, $actorUserId, $title, $description, $referenceId, $referenceType);
        return $stmt->execute();
    }

    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] ?? 0;
    }

    /**
     * Get recent notifications for a user
     */
    public function getRecentNotifications($userId, $limit = 20) {
        $sql = "
            SELECT 
                n.notification_id,
                n.type,
                n.title,
                n.description,
                n.is_read,
                n.created_at,
                n.actor_user_id,
                n.reference_id,
                n.reference_type,
                u.user_username,
                u.user_email
            FROM notifications n
            LEFT JOIN user u ON n.actor_user_id = u.user_id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ?
        ";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $notificationId);
        return $stmt->execute();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    /**
     * Delete a notification
     */
    public function deleteNotification($notificationId) {
        $sql = "DELETE FROM notifications WHERE notification_id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $notificationId);
        return $stmt->execute();
    }

    /**
     * Get notification count in human-readable format (0, 1, 2... 99, 99+)
     */
    public function getCountDisplay($userId) {
        $count = $this->getUnreadCount($userId);
        if ($count === 0) {
            return '0';
        } elseif ($count > 99) {
            return '99+';
        }
        return (string)$count;
    }
}
?>
