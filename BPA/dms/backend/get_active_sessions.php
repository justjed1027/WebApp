<?php
/**
 * Get active sessions for the logged-in user
 */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once 'db.php';

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$userId = intval($_SESSION['user_id']);

try {
    $db = DB::getInstance();
    $conn = $db->getConnection();
    
    // Get all accepted sessions for this user (either as requester or recipient)
    $sql = "SELECT sr.request_id, sr.requester_id, sr.recipient_id, sr.session_type, sr.area_of_help, 
            sr.description, sr.duration, sr.session_date, sr.session_start_time, sr.session_end_time,
            sr.session_notes, sr.status, sr.created_at, sr.responded_at,
            u1.username as requester_name, u2.username as recipient_name
            FROM session_requests sr
            JOIN user u1 ON sr.requester_id = u1.user_id
            JOIN user u2 ON sr.recipient_id = u2.user_id
            WHERE (sr.requester_id = ? OR sr.recipient_id = ?) AND sr.status = 'accepted'
            ORDER BY sr.session_date DESC, sr.session_start_time DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
    
    $stmt->close();
    
    exit(json_encode([
        'success' => true,
        'sessions' => $sessions,
        'count' => count($sessions)
    ]));
    
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => $e->getMessage()]));
}
