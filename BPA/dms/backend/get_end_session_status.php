<?php
/**
 * Get end session request status for a session
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
                  WHERE request_id = ? AND (requester_id = ? OR recipient_id = ?)
                  LIMIT 1";
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

    $sessionRow = $verifyResult->fetch_assoc();
    $verifyStmt->close();

    $isEnded = ($sessionRow['status'] !== 'accepted');

    $status = 'none';
    $requestedBy = null;

    $getSql = "SELECT requester_id, status FROM session_end_requests WHERE session_id = ? LIMIT 1";
    $getStmt = $conn->prepare($getSql);
    if ($getStmt) {
        $getStmt->bind_param('i', $sessionId);
        $getStmt->execute();
        $getResult = $getStmt->get_result();
        if ($getResult->num_rows > 0) {
            $row = $getResult->fetch_assoc();
            $status = $row['status'];
            $requestedBy = intval($row['requester_id']);
        }
        $getStmt->close();
    }

    exit(json_encode([
        'success' => true,
        'status' => $status,
        'requested_by' => $requestedBy,
        'is_ended' => $isEnded
    ]));

} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => $e->getMessage()]));
}
