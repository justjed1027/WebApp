<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

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
$requestData = json_decode($input, true);

if (!isset($requestData['request_id']) || !isset($requestData['action'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Missing required parameters']));
}

$requestId = intval($requestData['request_id']);
$action = $requestData['action'];
$responseMessage = isset($requestData['message']) ? strval($requestData['message']) : '';
$sessionDate = isset($requestData['session_date']) ? strval($requestData['session_date']) : '';
$startTime = isset($requestData['start_time']) ? strval($requestData['start_time']) : '';
$endTime = isset($requestData['end_time']) ? strval($requestData['end_time']) : '';
$sessionNotes = isset($requestData['session_notes']) ? strval($requestData['session_notes']) : '';
$userId = intval($_SESSION['user_id']);

if (!in_array($action, ['accept', 'reject'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Invalid action']));
}

$status = ($action === 'accept') ? 'accepted' : 'rejected';

try {
    $db = DB::getInstance();
    $conn = $db->getConnection();
    
    $verifySQL = "SELECT request_id, recipient_id FROM session_requests WHERE request_id = ? LIMIT 1";
    $verifyStmt = $conn->prepare($verifySQL);
    
    if (!$verifyStmt) {
        throw new Exception('Verify prepare failed: ' . $conn->error);
    }
    
    $verifyStmt->bind_param('i', $requestId);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows === 0) {
        http_response_code(404);
        $verifyStmt->close();
        exit(json_encode(['success' => false, 'error' => 'Request not found']));
    }
    
    $requestRow = $verifyResult->fetch_assoc();
    $verifyStmt->close();
    
    if ($requestRow['recipient_id'] != $userId) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
    }
    
    if ($action === 'accept' && !empty($sessionDate) && !empty($startTime) && !empty($endTime)) {
        $updateSQL = "UPDATE session_requests SET status = ?, responded_at = NOW(), response_message = ?, session_date = ?, session_start_time = ?, session_end_time = ?, session_notes = ? WHERE request_id = ?";
        $updateStmt = $conn->prepare($updateSQL);
        
        if (!$updateStmt) {
            throw new Exception('Update prepare failed: ' . $conn->error);
        }
        
        $updateStmt->bind_param('ssssssi', $status, $responseMessage, $sessionDate, $startTime, $endTime, $sessionNotes, $requestId);
        
        if (!$updateStmt->execute()) {
            throw new Exception('Update execute failed: ' . $updateStmt->error);
        }
        
        $updateStmt->close();
    } else {
        $updateSQL = "UPDATE session_requests SET status = ?, responded_at = NOW(), response_message = ? WHERE request_id = ?";
        $updateStmt = $conn->prepare($updateSQL);
        
        if (!$updateStmt) {
            throw new Exception('Update prepare failed: ' . $conn->error);
        }
        
        $updateStmt->bind_param('ssi', $status, $responseMessage, $requestId);
        
        if (!$updateStmt->execute()) {
            throw new Exception('Update execute failed: ' . $updateStmt->error);
        }
        
        $updateStmt->close();
    }
    
    exit(json_encode(['success' => true, 'message' => 'Request ' . $status . ' successfully', 'status' => $status]));
    
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => $e->getMessage()]));
} catch (Throwable $e) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => $e->getMessage()]));
}