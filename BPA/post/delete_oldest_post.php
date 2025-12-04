<?php
session_start();
require_once '../database/DatabaseConnection.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$db = new DatabaseConnection();
$conn = $db->connection;

try {
    // Get post_id from JSON body if provided, otherwise delete oldest
    $post_id = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (isset($data['post_id'])) {
            $post_id = intval($data['post_id']);
        }
    }
    
    // If no specific post_id provided, find and delete the oldest
    if ($post_id === null) {
        $sql = "SELECT post_id FROM posts ORDER BY created_at ASC, post_id ASC LIMIT 1";
        $result = $conn->query($sql);

        if (!$result || $result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'No posts found to delete']);
            exit;
        }

        if ($row = $result->fetch_assoc()) {
            $post_id = $row['post_id'];
        }
    }
    
    if ($post_id === null) {
        echo json_encode(['success' => false, 'error' => 'No post to delete']);
        exit;
    }
    
    // Delete the post using prepared statement
    $del_stmt = $conn->prepare("DELETE FROM posts WHERE post_id = ?");
    
    if (!$del_stmt) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit;
    }
    
    $del_stmt->bind_param("i", $post_id);
    
    if ($del_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Post deleted successfully',
            'post_id' => $post_id
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete post']);
    }
    
    $del_stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    error_log('Delete post error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

$db->closeConnection();
exit;
?>