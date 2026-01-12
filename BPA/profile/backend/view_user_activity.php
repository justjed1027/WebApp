<?php
session_start();
require_once '../../database/DatabaseConnection.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (empty($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the user ID to check activity for
$data = json_decode(file_get_contents('php://input'), true);
$userIdToCheck = isset($data['user_id']) ? (int)$data['user_id'] : 0;

if ($userIdToCheck <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

try {
    $db = new DatabaseConnection();
    $conn = $db->connection;
    
    // Check if user exists
    $checkStmt = $conn->prepare("SELECT user_username FROM user WHERE user_id = ?");
    $checkStmt->bind_param("i", $userIdToCheck);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        $checkStmt->close();
        $db->closeConnection();
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $userRow = $result->fetch_assoc();
    $checkStmt->close();
    
    // Get user's posts
    $postsStmt = $conn->prepare("
        SELECT post_id, content, created_at, file_path 
        FROM posts 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $postsStmt->bind_param("i", $userIdToCheck);
    $postsStmt->execute();
    $postsResult = $postsStmt->get_result();
    
    $posts = [];
    while ($row = $postsResult->fetch_assoc()) {
        $posts[] = [
            'post_id' => $row['post_id'],
            'content' => substr($row['content'], 0, 100) . (strlen($row['content']) > 100 ? '...' : ''),
            'created_at' => $row['created_at'],
            'has_file' => !empty($row['file_path'])
        ];
    }
    $postsStmt->close();
    
    // Get user's comments count
    $commentsStmt = $conn->prepare("
        SELECT COUNT(*) as comment_count, MAX(created_at) as last_comment_at
        FROM post_comments 
        WHERE user_id = ?
    ");
    $commentsStmt->bind_param("i", $userIdToCheck);
    $commentsStmt->execute();
    $commentsResult = $commentsStmt->get_result();
    $commentsData = $commentsResult->fetch_assoc();
    $commentsStmt->close();
    
    $db->closeConnection();
    
    echo json_encode([
        'success' => true,
        'username' => $userRow['user_username'],
        'posts_count' => count($posts),
        'comments_count' => $commentsData['comment_count'] ?? 0,
        'posts' => $posts
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
