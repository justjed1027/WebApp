<?php
/**
 * Send a message in a session room
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['session_id']) || !isset($data['message'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Missing required fields']));
}

$sessionId = intval($data['session_id']);
$message = strval($data['message']);
$userId = intval($_SESSION['user_id']);

if (empty($message)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Message cannot be empty']));
}

try {
    $db = DB::getInstance();
    $conn = $db->getConnection();
    
    // Verify user is part of this session
    $verifySql = "SELECT request_id FROM session_requests WHERE request_id = ? AND (requester_id = ? OR recipient_id = ?) AND status = 'accepted'";
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
    
    $verifyStmt->close();
    
    // Insert message into session_messages table
    $insertSql = "INSERT INTO session_messages (session_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())";
    $insertStmt = $conn->prepare($insertSql);
    
    if (!$insertStmt) {
        throw new Exception('Insert prepare failed: ' . $conn->error);
    }
    
    $insertStmt->bind_param('iis', $sessionId, $userId, $message);
    
    if (!$insertStmt->execute()) {
        throw new Exception('Insert execute failed: ' . $insertStmt->error);
    }
    
    $messageId = $insertStmt->insert_id;
    $insertStmt->close();
    
    exit(json_encode([
        'success' => true,
        'message_id' => $messageId,
        'message' => 'Message sent successfully'
    ]));
    
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => $e->getMessage()]));
}
