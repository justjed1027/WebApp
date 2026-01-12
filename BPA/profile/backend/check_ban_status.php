<?php
session_start();
require_once '../../database/DatabaseConnection.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (empty($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the user ID to check
$data = json_decode(file_get_contents('php://input'), true);
$userIdToCheck = isset($data['user_id']) ? (int)$data['user_id'] : 0;

if ($userIdToCheck <= 0) {
    echo json_encode(['user_is_banned' => 0]);
    exit;
}

try {
    $db = new DatabaseConnection();
    $conn = $db->connection;
    
    // Check user ban status
    $stmt = $conn->prepare("SELECT user_is_banned FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $userIdToCheck);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['user_is_banned' => 0]);
        $stmt->close();
        $db->closeConnection();
        exit;
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    $db->closeConnection();
    
    echo json_encode(['user_is_banned' => (int)$row['user_is_banned']]);
    
} catch (Exception $e) {
    echo json_encode(['user_is_banned' => 0, 'error' => $e->getMessage()]);
}
?>
