<?php
session_start();
require_once '../database/DatabaseConnection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to comment']);
    exit;
}

// Validate input
if (!isset($_POST['post_id']) || !isset($_POST['comment'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$post_id = intval($_POST['post_id']);
$user_id = intval($_SESSION['user_id']);
$comment_text = trim($_POST['comment']);

// Validate comment text
if (empty($comment_text)) {
    echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
    exit;
}

if (strlen($comment_text) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Comment is too long (max 1000 characters)']);
    exit;
}

// Insert comment into database
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
    
    // Check if post exists
    $checkStmt = $db->connection->prepare("SELECT post_id FROM posts WHERE post_id = ?");
    $checkStmt->bind_param("i", $post_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        $checkStmt->close();
        $db->closeConnection();
        exit;
    }
    $checkStmt->close();
    
    // Insert comment
    $stmt = $db->connection->prepare("INSERT INTO post_comments (post_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $post_id, $user_id, $comment_text);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Comment added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add comment: ' . $db->connection->error]);
    }
    
    $stmt->close();
    $db->closeConnection();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
