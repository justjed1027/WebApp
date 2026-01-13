<?php
/**
 * Check if both users are ready in a session
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

if (!$sessionId) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Session ID required']));
}

try {
    $db = DB::getInstance();
    $conn = $db->getConnection();
    
    // Get session request info
    $sql = "SELECT request_id, requester_id, recipient_id FROM session_requests WHERE request_id = ? AND status = 'accepted'";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        $stmt->close();
        exit(json_encode(['success' => false, 'error' => 'Session not found or not accepted']));
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    
    $requesterUserId = intval($row['requester_id']);
    $recipientUserId = intval($row['recipient_id']);
    
    // Check if requester is in participants
    $checkSql = "SELECT COUNT(*) as count FROM session_participants WHERE session_id = ? AND user_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    if (!$checkStmt) {
        throw new Exception('Check prepare failed: ' . $conn->error);
    }
    
    $checkStmt->bind_param('ii', $sessionId, $requesterUserId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $requesterData = $checkResult->fetch_assoc();
    $requesterJoined = $requesterData['count'] > 0;
    $checkStmt->close();
    
    // Check if recipient is in participants
    $checkStmt = $conn->prepare($checkSql);
    if (!$checkStmt) {
        throw new Exception('Check prepare failed: ' . $conn->error);
    }
    
    $checkStmt->bind_param('ii', $sessionId, $recipientUserId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $recipientData = $checkResult->fetch_assoc();
    $recipientJoined = $recipientData['count'] > 0;
    $checkStmt->close();
    
    $bothReady = $requesterJoined && $recipientJoined;
    
    exit(json_encode([
        'success' => true,
        'both_ready' => $bothReady,
        'requester_ready' => $requesterJoined,
        'recipient_ready' => $recipientJoined
    ]));
    
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => $e->getMessage()]));
}
