<?php
/**
 * Create a private session request
 * POST request with session_type parameter
 */

error_reporting(0);
ini_set('display_errors', 0);

require_once 'db.php';
require_once '../../database/User.php';
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$requestData = json_decode(file_get_contents('php://input'), true);

if (!isset($requestData['recipient_user_id']) || !isset($requestData['session_type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$requesterId = $_SESSION['user_id'];
$recipientId = (int)$requestData['recipient_user_id'];
$sessionType = $requestData['session_type'];
$areaOfHelp = isset($requestData['area_of_help']) ? $requestData['area_of_help'] : null;
$description = isset($requestData['description']) ? $requestData['description'] : null;
$duration = isset($requestData['duration']) ? $requestData['duration'] : null;

// Validate recipient ID
if ($recipientId === $requesterId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Cannot request session with yourself']);
    exit;
}

try {
    $db = DB::getInstance();
    $conn = $db->getConnection();
    
    // Create session_requests table if it doesn't exist
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS session_requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        requester_id INT NOT NULL,
        recipient_id INT NOT NULL,
        session_type VARCHAR(50),
        area_of_help VARCHAR(100),
        description TEXT,
        duration VARCHAR(50),
        session_date DATE NULL,
        session_start_time TIME NULL,
        session_end_time TIME NULL,
        session_notes TEXT,
        status ENUM('pending', 'accepted', 'rejected', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        responded_at TIMESTAMP NULL,
        response_message TEXT,
        FOREIGN KEY (requester_id) REFERENCES user(user_id),
        FOREIGN KEY (recipient_id) REFERENCES user(user_id),
        UNIQUE KEY unique_pending (requester_id, recipient_id, status),
        INDEX idx_recipient (recipient_id, status),
        INDEX idx_requester (requester_id, status)
    )
    ";
    
    $conn->query($createTableSQL);
    
    // Add missing columns if they don't exist
    $alterTableSQL = "ALTER TABLE session_requests 
                      ADD COLUMN IF NOT EXISTS session_date DATE NULL,
                      ADD COLUMN IF NOT EXISTS session_start_time TIME NULL,
                      ADD COLUMN IF NOT EXISTS session_end_time TIME NULL,
                      ADD COLUMN IF NOT EXISTS session_notes TEXT";
    
    $conn->query($alterTableSQL);
    
    // Check if there's already a pending request from this requester to this recipient
    $checkSQL = "SELECT request_id FROM session_requests 
                 WHERE requester_id = ? AND recipient_id = ? AND status = 'pending' 
                 LIMIT 1";
    
    $checkStmt = $conn->prepare($checkSQL);
    $checkStmt->bind_param('ii', $requesterId, $recipientId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Request already pending with this user']);
        exit;
    }
    
    $checkStmt->close();
    
    // Insert new session request
    $insertSQL = "INSERT INTO session_requests (requester_id, recipient_id, session_type, area_of_help, description, duration) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    
    $insertStmt = $conn->prepare($insertSQL);
    if (!$insertStmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $insertStmt->bind_param('iissss', $requesterId, $recipientId, $sessionType, $areaOfHelp, $description, $duration);
    
    if (!$insertStmt->execute()) {
        throw new Exception('Execute failed: ' . $insertStmt->error);
    }
    
    $requestId = $insertStmt->insert_id;
    $insertStmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Session request sent successfully',
        'request_id' => $requestId
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error creating session request: ' . $e->getMessage()
    ]);
}
?>