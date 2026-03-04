<?php
/**
 * Recipient responds to end session request
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
$action = isset($data['action']) ? strtolower(trim($data['action'])) : '';
$userId = intval($_SESSION['user_id']);

if ($sessionId <= 0 || !in_array($action, ['accept', 'reject'], true)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Invalid request']));
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

    $alterSql = "ALTER TABLE session_end_requests ADD COLUMN confirmed_by INT NULL AFTER responded_at";
    if (!$conn->query($alterSql) && intval($conn->errno) !== 1060) {
        throw new Exception('Alter table failed: ' . $conn->error);
    }

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

    if ($userId !== intval($sessionRow['recipient_id'])) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'error' => 'Only the recipient can respond']));
    }

    $status = $action === 'accept' ? 'accepted' : 'rejected';

    if ($action === 'accept') {
        $updateSql = "UPDATE session_end_requests SET status = ?, responded_at = NOW(), confirmed_by = ? WHERE session_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            throw new Exception('Update prepare failed: ' . $conn->error);
        }
        $updateStmt->bind_param('sii', $status, $userId, $sessionId);
    } else {
        $updateSql = "UPDATE session_end_requests SET status = ?, responded_at = NOW(), confirmed_by = NULL WHERE session_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            throw new Exception('Update prepare failed: ' . $conn->error);
        }
        $updateStmt->bind_param('si', $status, $sessionId);
    }

    if (!$updateStmt->execute()) {
        throw new Exception('Update execute failed: ' . $updateStmt->error);
    }
    $updateStmt->close();

    if ($action === 'accept') {
        $endSql = "UPDATE session_requests SET status = 'cancelled', responded_at = NOW(), response_message = 'Ended by host' WHERE request_id = ?";
        $endStmt = $conn->prepare($endSql);
        if ($endStmt) {
            $endStmt->bind_param('i', $sessionId);
            $endStmt->execute();
            $endStmt->close();
        }
    }

    exit(json_encode(['success' => true, 'status' => $status]));

} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => $e->getMessage()]));
}
