<?php
// update_event.php
// Update event tags or date (host only)
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
$updateType = isset($data['type']) ? $data['type'] : '';

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
    echo json_encode(['success' => false, 'message' => 'Only the host can edit this event']);
    $db->closeConnection();
    exit;
}

$checkHost->close();

// Handle different update types
try {
    if ($updateType === 'tags') {
        // Update tags
        $tagIds = isset($data['tagIds']) && is_array($data['tagIds']) ? $data['tagIds'] : [];
        
        // Delete existing tags
        $delTags = $conn->prepare('DELETE FROM events_tags WHERE et_events_id = ?');
        $delTags->bind_param('i', $eventId);
        $delTags->execute();
        $delTags->close();
        
        // Insert new tags
        if (!empty($tagIds)) {
            $insertTag = $conn->prepare('INSERT INTO events_tags (et_events_id, et_tags_id) VALUES (?, ?)');
            foreach ($tagIds as $tagId) {
                $tid = (int)$tagId;
                if ($tid > 0) {
                    $insertTag->bind_param('ii', $eventId, $tid);
                    $insertTag->execute();
                }
            }
            $insertTag->close();
        }
        
        echo json_encode(['success' => true, 'message' => 'Tags updated']);
        
    } elseif ($updateType === 'date') {
        // Update event date and times
        $date = isset($data['date']) ? $data['date'] : null;
        $startTime = isset($data['startTime']) ? $data['startTime'] : null;
        $endTime = isset($data['endTime']) ? $data['endTime'] : null;
        $deadline = isset($data['deadline']) ? $data['deadline'] : null;
        
        if (!$date) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Date is required']);
            exit;
        }
        
        $updateDate = $conn->prepare('UPDATE events SET events_date = ?, events_start = ?, events_end = ?, events_deadline = ? WHERE events_id = ?');
        $updateDate->bind_param('ssssi', $date, $startTime, $endTime, $deadline, $eventId);
        $updateDate->execute();
        $updateDate->close();
        
        echo json_encode(['success' => true, 'message' => 'Date updated']);
        
    } elseif ($updateType === 'close_registration') {
        // Set deadline to now to close registration
        $now = date('Y-m-d');
        $updateDeadline = $conn->prepare('UPDATE events SET events_deadline = ? WHERE events_id = ?');
        $updateDeadline->bind_param('si', $now, $eventId);
        $updateDeadline->execute();
        $updateDeadline->close();
        
        echo json_encode(['success' => true, 'message' => 'Registration closed']);
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid update type']);
    }
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error', 'error' => $e->getMessage()]);
}

$db->closeConnection();
exit;
