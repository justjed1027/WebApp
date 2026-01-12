<?php
/**
 * Check Connection Status between Current User and Another User
 * Returns: connected, pending, or not_connected
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'not_connected', 'error' => 'User not logged in']);
    exit;
}

$currentUserId = $_SESSION['user_id'];
$otherUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($otherUserId === 0) {
    echo json_encode(['status' => 'not_connected', 'error' => 'Invalid user ID']);
    exit;
}

require_once __DIR__ . '/../database/DatabaseConnection.php';

$db = new DatabaseConnection();
$conn = $db->connection;

try {
    // Check for existing connection in either direction
    $sql = "SELECT status FROM connections 
            WHERE (requester_id = ? AND receiver_id = ?) 
            OR (requester_id = ? AND receiver_id = ?)
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param('iiii', $currentUserId, $otherUserId, $otherUserId, $currentUserId);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $status = $row['status'] === 'accepted' ? 'connected' : 'pending';
    } else {
        $status = 'not_connected';
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode(['status' => $status]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]);
}
?>
