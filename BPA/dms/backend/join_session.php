<?php
/**
 * Join a session room (participant waits for other user)
 */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once 'db.php';

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

error_log('Join session PHP file called');

if (!isset($_SESSION['user_id'])) {
    error_log('No user_id in session');
    http_response_code(401);
    exit(json_encode(['success' => false, 'error' => 'Not authenticated. Session user_id not set']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['session_id'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Session ID required']));
}

$sessionId = intval($data['session_id']);
$userId = intval($_SESSION['user_id']);

try {
    
    // Verify user is part of this session
    $verifySql = "SELECT request_id, requester_id, recipient_id, status FROM session_requests WHERE request_id = ?";
    $verifyStmt = $conn->prepare($verifySql);
    
    if (!$verifyStmt) {
        throw new Exception('Verify prepare failed: ' . $conn->error);
    }
    
    $verifyStmt->bind_param('i', $sessionId);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows === 0) {
        http_response_code(404);
        $verifyStmt->close();
        exit(json_encode(['success' => false, 'error' => 'Session not found']));
    }
    
    $sessionRow = $verifyResult->fetch_assoc();
    
    if ($sessionRow['status'] !== 'accepted') {
        http_response_code(403);
        $verifyStmt->close();
        exit(json_encode(['success' => false, 'error' => 'Session not accepted yet. Status: ' . $sessionRow['status']]));
    }
    
    $verifyStmt->close();
    
    // Check if user is requester or recipient
    if ($userId !== $sessionRow['requester_id'] && $userId !== $sessionRow['recipient_id']) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'error' => 'Unauthorized. User ' . $userId . ' is not part of session. Requester: ' . $sessionRow['requester_id'] . ', Recipient: ' . $sessionRow['recipient_id']]));
    }
    
    // Get other user
    $otherUserId = ($userId === $sessionRow['requester_id']) ? $sessionRow['recipient_id'] : $sessionRow['requester_id'];
    
    // Insert or update participant status
    $sql = "INSERT INTO session_participants (session_id, user_id, joined_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE last_seen = NOW()";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Insert prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('ii', $sessionId, $userId);
    
    if (!$stmt->execute()) {
        throw new Exception('Insert execute failed: ' . $stmt->error);
    }
    
    $stmt->close();
    
    // Check if other user is already in the session
    $checkSql = "SELECT user_id FROM session_participants WHERE session_id = ? AND user_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    
    if (!$checkStmt) {
        throw new Exception('Check prepare failed: ' . $conn->error);
    }
    
    $checkStmt->bind_param('ii', $sessionId, $otherUserId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $otherUserJoined = $checkResult->num_rows > 0;
    $checkStmt->close();
    
    exit(json_encode([
        'success' => true,
        'message' => 'Joined session',
        'other_user_joined' => $otherUserJoined,
        'other_user_id' => $otherUserId
    ]));
    
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => $e->getMessage()]));
}