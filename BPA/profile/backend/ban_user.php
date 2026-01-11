<?php
session_start();
require_once '../../database/User.php';
require_once '../../database/DatabaseConnection.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (empty($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the user ID to ban
$data = json_decode(file_get_contents('php://input'), true);
$userIdToBan = isset($data['user_id']) ? (int)$data['user_id'] : 0;

if ($userIdToBan <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Prevent admin from banning themselves
if ($userIdToBan === $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot ban yourself']);
    exit;
}

try {
    $db = new DatabaseConnection();
    $conn = $db->connection;
    
    // Check if user exists
    $checkStmt = $conn->prepare("SELECT user_id FROM user WHERE user_id = ?");
    $checkStmt->bind_param("i", $userIdToBan);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        $checkStmt->close();
        $db->closeConnection();
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    $checkStmt->close();
    
    // Update user ban status (add user_is_banned column if it doesn't exist)
    // First, check if column exists, if not we'll just log it
    $updateStmt = $conn->prepare("UPDATE user SET user_is_banned = 1 WHERE user_id = ?");
    
    if ($updateStmt) {
        $updateStmt->bind_param("i", $userIdToBan);
        $updateStmt->execute();
        $updateStmt->close();
        
        $db->closeConnection();
        echo json_encode([
            'success' => true, 
            'message' => 'User has been banned successfully'
        ]);
    } else {
        // Column might not exist, return helpful message
        $db->closeConnection();
        echo json_encode([
            'success' => false, 
            'message' => 'Ban feature requires database migration. Please add user_is_banned column to user table.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
