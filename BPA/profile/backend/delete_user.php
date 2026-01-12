<?php
session_start();
require_once '../../database/DatabaseConnection.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (empty($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the user ID to delete
$data = json_decode(file_get_contents('php://input'), true);
$userIdToDelete = isset($data['user_id']) ? (int)$data['user_id'] : 0;

if ($userIdToDelete <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Prevent admin from deleting themselves
if ($userIdToDelete === $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete yourself']);
    exit;
}

try {
    $db = new DatabaseConnection();
    $conn = $db->connection;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if user exists
        $checkStmt = $conn->prepare("SELECT user_username FROM user WHERE user_id = ?");
        $checkStmt->bind_param("i", $userIdToDelete);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $conn->rollback();
            $checkStmt->close();
            $db->closeConnection();
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        $userRow = $result->fetch_assoc();
        $username = $userRow['user_username'];
        $checkStmt->close();
        
        // Delete user's posts and comments
        $deleteCommentsStmt = $conn->prepare("DELETE FROM post_comments WHERE user_id = ?");
        $deleteCommentsStmt->bind_param("i", $userIdToDelete);
        $deleteCommentsStmt->execute();
        $deleteCommentsStmt->close();
        
        $deletePostsStmt = $conn->prepare("DELETE FROM posts WHERE user_id = ?");
        $deletePostsStmt->bind_param("i", $userIdToDelete);
        $deletePostsStmt->execute();
        $deletePostsStmt->close();
        
        // Delete user's connections
        $deleteConnectionsStmt = $conn->prepare("DELETE FROM connections WHERE requester_id = ? OR receiver_id = ?");
        $deleteConnectionsStmt->bind_param("ii", $userIdToDelete, $userIdToDelete);
        $deleteConnectionsStmt->execute();
        $deleteConnectionsStmt->close();
        
        // Delete user's skills and interests
        $deleteSkillsStmt = $conn->prepare("DELETE FROM user_skills WHERE us_user_id = ?");
        $deleteSkillsStmt->bind_param("i", $userIdToDelete);
        $deleteSkillsStmt->execute();
        $deleteSkillsStmt->close();
        
        $deleteInterestsStmt = $conn->prepare("DELETE FROM user_interests WHERE ui_user_id = ?");
        $deleteInterestsStmt->bind_param("i", $userIdToDelete);
        $deleteInterestsStmt->execute();
        $deleteInterestsStmt->close();
        
        // Delete user's profile
        $deleteProfileStmt = $conn->prepare("DELETE FROM profile WHERE user_id = ?");
        $deleteProfileStmt->bind_param("i", $userIdToDelete);
        $deleteProfileStmt->execute();
        $deleteProfileStmt->close();
        
        // Delete the user itself
        $deleteUserStmt = $conn->prepare("DELETE FROM user WHERE user_id = ?");
        $deleteUserStmt->bind_param("i", $userIdToDelete);
        $deleteUserStmt->execute();
        $deleteUserStmt->close();
        
        // Commit transaction
        $conn->commit();
        $db->closeConnection();
        
        echo json_encode([
            'success' => true,
            'message' => "User '$username' and all associated data have been permanently deleted"
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
