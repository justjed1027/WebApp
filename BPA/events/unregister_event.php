<?php
// unregister_event.php
// Unregisters the current user from an event
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

// Ensure registration exists
$check = $conn->prepare('SELECT participant_id FROM event_participants WHERE ep_event_id = ? AND ep_user_id = ? LIMIT 1');
$check->bind_param('ii', $eventId, $user_id);
$check->execute();
$res = $check->get_result();
if (!$res || $res->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Not registered']);
    $check->close();
    $db->closeConnection();
    exit;
}

$check->close();

// Delete registration
$del = $conn->prepare('DELETE FROM event_participants WHERE ep_event_id = ? AND ep_user_id = ? LIMIT 1');
$del->bind_param('ii', $eventId, $user_id);
try {
    $del->execute();
    if ($del->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Unregistered']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to unregister']);
    }
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error', 'error' => $e->getMessage()]);
}

$del->close();
$db->closeConnection();
exit;
