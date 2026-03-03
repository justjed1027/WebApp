<?php
/**
 * Get messages from a session room
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

$sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
$userId = intval($_SESSION['user_id']);

if (!$sessionId) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Session ID required']));
}

try {
    $db = DB::getInstance();
    $conn = $db->getConnection();
    
    // Verify user is part of this session and check end time
    $verifySql = "SELECT request_id, session_date, session_end_time FROM session_requests WHERE request_id = ? AND (requester_id = ? OR recipient_id = ?) AND status = 'accepted'";
    $verifyStmt = $conn->prepare($verifySql);
    
    if (!$verifyStmt) {
        throw new Exception('Verify prepare failed: ' . $conn->error);
    }
    
    $verifyStmt->bind_param('iii', $sessionId, $userId, $userId);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows === 0) {
        http_response_code(403);
        $verifyStmt->close();
        exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
    }
    
    $verifyRow = $verifyResult->fetch_assoc();
    $verifyStmt->close();

    if (!empty($verifyRow['session_date']) && !empty($verifyRow['session_end_time'])) {
        $expireCheckSql = "SELECT request_id FROM session_requests 
                           WHERE request_id = ? AND status = 'accepted'
                             AND session_date IS NOT NULL AND session_end_time IS NOT NULL
                             AND TIMESTAMP(session_date, session_end_time) <= DATE_SUB(NOW(), INTERVAL 12 HOUR)";
        $expireCheckStmt = $conn->prepare($expireCheckSql);
        if ($expireCheckStmt) {
            $expireCheckStmt->bind_param('i', $sessionId);
            $expireCheckStmt->execute();
            $expireCheckResult = $expireCheckStmt->get_result();
            $shouldExpire = ($expireCheckResult->num_rows > 0);
            $expireCheckStmt->close();

            if ($shouldExpire) {
                $expireSql = "UPDATE session_requests SET status = 'cancelled', responded_at = NOW(), response_message = 'Session ended' WHERE request_id = ?";
                $expireStmt = $conn->prepare($expireSql);
                if ($expireStmt) {
                    $expireStmt->bind_param('i', $sessionId);
                    $expireStmt->execute();
                    $expireStmt->close();
                }
                http_response_code(410);
                exit(json_encode(['success' => false, 'error' => 'Session ended']));
            }
        }
    }
    
    // Get messages
    $getSql = "SELECT sm.message_id, sm.session_id, sm.user_id, sm.message, sm.created_at, u.user_username as username
               FROM session_messages sm
               JOIN user u ON sm.user_id = u.user_id
               WHERE sm.session_id = ?
               ORDER BY sm.created_at ASC";
    
    $getStmt = $conn->prepare($getSql);
    
    if (!$getStmt) {
        throw new Exception('Get prepare failed: ' . $conn->error);
    }
    
    $getStmt->bind_param('i', $sessionId);
    $getStmt->execute();
    $getResult = $getStmt->get_result();
    
    $messages = [];
    while ($row = $getResult->fetch_assoc()) {
        $messages[] = $row;
    }
    
    $getStmt->close();
    
    exit(json_encode([
        'success' => true,
        'messages' => $messages,
        'count' => count($messages)
    ]));
    
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => $e->getMessage()]));
}
