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
    $userId = $_SESSION['user_id'];
    
    // Get all requests (pending and accepted) for this user (either as requester or recipient)
    $sql = "
    SELECT 
        sr.request_id,
        sr.requester_id,
        sr.recipient_id,
        sr.session_type,
        sr.area_of_help,
        sr.description,
        sr.duration,
        sr.status,
        sr.session_date,
        sr.session_start_time,
        sr.session_end_time,
        sr.created_at,
        u1.user_username as requester_name,
        u2.user_username as recipient_name,
        CASE 
            WHEN sr.requester_id = ? THEN u2.user_username 
            ELSE u1.user_username 
        END as user_username,
        CASE 
            WHEN sr.requester_id = ? THEN u2.user_email 
            ELSE u1.user_email 
        END as user_email
    FROM session_requests sr
    JOIN user u1 ON sr.requester_id = u1.user_id
    JOIN user u2 ON sr.recipient_id = u2.user_id
    WHERE (sr.recipient_id = ? OR sr.requester_id = ?) 
        AND (sr.status = 'pending' OR sr.status = 'accepted')
    ORDER BY sr.created_at DESC
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('iiii', $userId, $userId, $userId, $userId);
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
