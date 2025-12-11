<?php
// register_event.php
// Registers the current user for an event (prevents duplicate registration)
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../database/DatabaseConnection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit;
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$eventId = isset($data['eventId']) ? (int)$data['eventId'] : 0;
if ($eventId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid event id']);
    exit;
}

$db = new DatabaseConnection();
$conn = $db->connection;

// Check if event exists and is open/visible (optional but safer)
$checkEvent = $conn->prepare('SELECT events_id FROM events WHERE events_id = ? LIMIT 1');
$checkEvent->bind_param('i', $eventId);
$checkEvent->execute();
$resE = $checkEvent->get_result();
if (!$resE || $resE->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Event not found']);
    $db->closeConnection();
    exit;
}

// Check existing registration
$stmt = $conn->prepare('SELECT participant_id FROM event_participants WHERE ep_event_id = ? AND ep_user_id = ? LIMIT 1');
$stmt->bind_param('ii', $eventId, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Already registered']);
    $stmt->close();
    $db->closeConnection();
    exit;
}

// Insert registration
$ins = $conn->prepare('INSERT INTO event_participants (ep_event_id, ep_user_id, signup_time) VALUES (?, ?, NOW())');
$ins->bind_param('ii', $eventId, $user_id);
try {
    $ins->execute();
    echo json_encode(['success' => true, 'message' => 'Registered', 'participant_id' => $ins->insert_id]);
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062 || stripos($e->getMessage(), 'Duplicate') !== false) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Already registered']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB error', 'error' => $e->getMessage()]);
    }
}

$ins->close();
$stmt->close();
$db->closeConnection();
exit;
