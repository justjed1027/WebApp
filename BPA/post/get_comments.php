<?php
session_start();
require_once '../database/DatabaseConnection.php';

header('Content-Type: application/json');

// Validate input
if (!isset($_GET['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing post ID']);
    exit;
}

$post_id = intval($_GET['post_id']);

// Function to calculate time ago
function timeAgoShort($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'just now';
    } elseif ($difference < 3600) {
        $mins = floor($difference / 60);
        return $mins . 'm ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . 'h ago';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . 'd ago';
    } else {
        return date('M j', $timestamp);
    }
}

// Get comments for the post
try {
    $db = new DatabaseConnection();
    
    // Create the post_comments table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS post_comments (
        comment_id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        comment_text TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_post_id (post_id),
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->connection->query($createTableSQL);
    
    $stmt = $db->connection->prepare("
        SELECT 
            pc.comment_id,
            pc.comment_text,
            pc.created_at,
            u.user_id,
            u.user_username
        FROM post_comments pc
        LEFT JOIN user u ON pc.user_id = u.user_id
        WHERE pc.post_id = ?
        ORDER BY pc.created_at ASC
    ");
    
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = [
            'comment_id' => $row['comment_id'],
            'comment_text' => $row['comment_text'],
            'username' => $row['user_username'] ?? 'User #' . $row['user_id'],
            'time_ago' => timeAgoShort($row['created_at']),
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'comments' => $comments
    ]);
    
    $stmt->close();
    $db->closeConnection();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
