<?php
// delete_event.php
// Delete an event (host only)
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

// Verify user is the host
$checkHost = $conn->prepare('SELECT host_user_id FROM events WHERE events_id = ? LIMIT 1');
$checkHost->bind_param('i', $eventId);
$checkHost->execute();
$res = $checkHost->get_result();
if (!$res || $res->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Event not found']);
    $db->closeConnection();
    exit;
}

$row = $res->fetch_assoc();
if ((int)$row['host_user_id'] !== $user_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only the host can delete this event']);
    $db->closeConnection();
    exit;
}

$checkHost->close();

// Delete related records first (foreign key constraints)
try {
    // Delete participants
    $delParticipants = $conn->prepare('DELETE FROM event_participants WHERE ep_event_id = ?');
    $delParticipants->bind_param('i', $eventId);
    $delParticipants->execute();
    $delParticipants->close();
    
    // Delete tags
    $delTags = $conn->prepare('DELETE FROM events_tags WHERE et_events_id = ?');
    $delTags->bind_param('i', $eventId);
    $delTags->execute();
    $delTags->close();
    
    // Delete subjects
    $delSubjects = $conn->prepare('DELETE FROM event_subjects WHERE es_event_id = ?');
    $delSubjects->bind_param('i', $eventId);
    $delSubjects->execute();
    $delSubjects->close();
    
    // Delete event
    $delEvent = $conn->prepare('DELETE FROM events WHERE events_id = ?');
    $delEvent->bind_param('i', $eventId);
    $delEvent->execute();
    $delEvent->close();
    
    echo json_encode(['success' => true, 'message' => 'Event deleted']);
    
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage(), 'error' => $e->getMessage()]);
}

$db->closeConnection();
exit;
