<?php
// get_events.php
// Fetch events from database with filtering by user's learning interests
session_start();
require_once '../database/DatabaseConnection.php';
header('Content-Type: application/json; charset=utf-8');

$db = new DatabaseConnection();
$conn = $db->connection;

$user_id = $_SESSION['user_id'] ?? null;
$filter_mode = $_GET['filter'] ?? 'all'; // 'all', 'relevant', 'created'

// Base query to get all events with their subject and tag info
$baseSql = "
SELECT 
    e.events_id,
    e.events_title,
    e.events_description,
    e.events_date,
    e.events_img,
    e.events_location,
    e.events_capacity,
    e.events_organization,
    e.events_contact_email,
    e.events_visibility,
    e.events_deadline,
    e.events_start,
    e.events_end,
    e.events_create_date,
    e.host_user_id,
    GROUP_CONCAT(DISTINCT s.subject_name SEPARATOR ', ') as subjects,
    GROUP_CONCAT(DISTINCT es.es_subject_id SEPARATOR ',') as subject_ids,
    GROUP_CONCAT(DISTINCT t.tag_name SEPARATOR ', ') as tags,
    GROUP_CONCAT(DISTINCT t.tag_id SEPARATOR ',') as tag_ids
FROM events e
LEFT JOIN event_subjects es ON e.events_id = es.es_event_id
LEFT JOIN subjects s ON es.es_subject_id = s.subject_id
LEFT JOIN events_tags et ON e.events_id = et.et_events_id
LEFT JOIN tags t ON et.et_tags_id = t.tag_id
";

if ($filter_mode === 'created' && $user_id) {
    // Get events created by the current user
    $baseSql .= "WHERE e.host_user_id = ?";
    $stmt = $conn->prepare($baseSql . " GROUP BY e.events_id ORDER BY e.events_date ASC");
    $stmt->bind_param('i', $user_id);
} elseif ($filter_mode === 'relevant' && $user_id) {
    // Get events matching user's learning interests
    $baseSql .= "
    WHERE (
        es.es_subject_id IN (
            SELECT ui_subject_id FROM user_interests WHERE ui_user_id = ?
        )
        OR e.events_visibility = 'public'
    )
    ";
    $stmt = $conn->prepare($baseSql . " GROUP BY e.events_id ORDER BY e.events_date ASC");
    $stmt->bind_param('i', $user_id);
} else {
    // Get all public events
    $baseSql .= "WHERE e.events_visibility = 'public' OR e.events_visibility IS NULL";
    $stmt = $conn->prepare($baseSql . " GROUP BY e.events_id ORDER BY e.events_date ASC");
}

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Query failed', 'error' => $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$events = [];

while ($row = $result->fetch_assoc()) {
    // Format the event data
    $event = [
        'id' => (int)$row['events_id'],
        'title' => $row['events_title'],
        'description' => $row['events_description'],
        'date' => $row['events_date'],
        'image' => $row['events_img'],
        'location' => $row['events_location'],
        'capacity' => $row['events_capacity'],
        'organization' => $row['events_organization'],
        'contactEmail' => $row['events_contact_email'],
        'visibility' => $row['events_visibility'],
        'deadline' => $row['events_deadline'],
        'startTime' => $row['events_start'],
        'endTime' => $row['events_end'],
        'createdDate' => $row['events_create_date'],
        'hostUserId' => (int)$row['host_user_id'],
        'subjects' => $row['subjects'] ? array_map('trim', explode(',', $row['subjects'])) : [],
        'subjectIds' => $row['subject_ids'] ? array_map('intval', explode(',', $row['subject_ids'])) : [],
        'tags' => $row['tags'] ? array_map('trim', explode(',', $row['tags'])) : [],
        'tagIds' => $row['tag_ids'] ? array_map('intval', explode(',', $row['tag_ids'])) : [],
    ];
    $events[] = $event;
}

$stmt->close();
$db->closeConnection();

echo json_encode(['success' => true, 'events' => $events, 'count' => count($events)]);
exit;
?>
