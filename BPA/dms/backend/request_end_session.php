<?php
/**
 * Host requests to end a session
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

$sessionId = isset($data['session_id']) ? intval($data['session_id']) : 0;
$userId = intval($_SESSION['user_id']);

if ($sessionId <= 0) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Session ID required']));
}

try {
    $db = DB::getInstance();
    $conn = $db->getConnection();

    $createSql = "CREATE TABLE IF NOT EXISTS session_end_requests (
        session_id INT PRIMARY KEY,
        requester_id INT NOT NULL,
        recipient_id INT NOT NULL,
        status ENUM('pending','accepted','rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        responded_at TIMESTAMP NULL,
        INDEX idx_recipient (recipient_id, status)
    )";
    $conn->query($createSql);

    $verifySql = "SELECT request_id, requester_id, recipient_id, status
                  FROM session_requests
                  WHERE request_id = ? AND status = 'accepted'
                  LIMIT 1";
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
        exit(json_encode(['success' => false, 'error' => 'Session not active']));
    }

    $sessionRow = $verifyResult->fetch_assoc();
    $verifyStmt->close();

    if ($userId !== intval($sessionRow['requester_id'])) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'error' => 'Only the host can end the session']));
    }

    $recipientId = intval($sessionRow['recipient_id']);

    $upsertSql = "INSERT INTO session_end_requests (session_id, requester_id, recipient_id, status, created_at)
                  VALUES (?, ?, ?, 'pending', NOW())
                  ON DUPLICATE KEY UPDATE status = 'pending', created_at = NOW(), responded_at = NULL";
    $upsertStmt = $conn->prepare($upsertSql);
    if (!$upsertStmt) {
        throw new Exception('Upsert prepare failed: ' . $conn->error);
    }

    $upsertStmt->bind_param('iii', $sessionId, $userId, $recipientId);
    if (!$upsertStmt->execute()) {
        throw new Exception('Upsert execute failed: ' . $upsertStmt->error);
    }
    $upsertStmt->close();

    exit(json_encode(['success' => true, 'message' => 'End request sent']));

} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => $e->getMessage()]));
}
