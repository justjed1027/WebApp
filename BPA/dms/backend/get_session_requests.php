<?php
/**
 * Get pending session requests for the logged-in user
 */

error_reporting(0);
ini_set('display_errors', 0);

require_once 'db.php';
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    $db = DB::getInstance();
    $conn = $db->getConnection();
    $recipientId = $_SESSION['user_id'];
    
    // Get pending requests for this user
    $sql = "
    SELECT 
        sr.request_id,
        sr.requester_id,
        sr.session_type,
        sr.area_of_help,
        sr.description,
        sr.duration,
        sr.created_at,
        u.user_username,
        u.user_email
    FROM session_requests sr
    JOIN user u ON sr.requester_id = u.user_id
    WHERE sr.recipient_id = ? AND sr.status = 'pending'
    ORDER BY sr.created_at DESC
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $recipientId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'count' => count($requests)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching requests: ' . $e->getMessage()
    ]);
}
?>
