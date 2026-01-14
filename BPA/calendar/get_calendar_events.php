<?php
// get_calendar_events.php
// Fetch upcoming events that the user is registered for - for calendar view
session_start();
require_once '../database/DatabaseConnection.php';
header('Content-Type: application/json; charset=utf-8');

$db = new DatabaseConnection();
$conn = $db->connection;

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in', 'events' => []]);
    exit;
}

// Get all upcoming events that the user is registered for
// Events must have a future start time and the user must be a participant
$sql = "
SELECT 
    e.events_id,
    e.events_title,
    e.events_description,
    e.events_date,
    e.events_img,
    e.events_location,
    e.events_capacity,
    e.events_contact_email,
    e.events_visibility,
    e.events_deadline,
    e.events_start,
    e.events_end,
    e.events_create_date,
    e.host_user_id,
    u.user_username,
    p.user_firstname,
    p.user_lastname,
    p.profile_filepath,
    (SELECT COUNT(*) FROM event_participants ep WHERE ep.ep_event_id = e.events_id) AS registration_count,
    GROUP_CONCAT(DISTINCT s.subject_name SEPARATOR ', ') as subjects,
    GROUP_CONCAT(DISTINCT es.es_subject_id SEPARATOR ',') as subject_ids,
    GROUP_CONCAT(DISTINCT t.tag_name SEPARATOR ', ') as tags,
    GROUP_CONCAT(DISTINCT t.tag_id SEPARATOR ',') as tag_ids
FROM events e
INNER JOIN event_participants ep ON e.events_id = ep.ep_event_id
LEFT JOIN event_subjects es ON e.events_id = es.es_event_id
LEFT JOIN subjects s ON es.es_subject_id = s.subject_id
LEFT JOIN events_tags et ON e.events_id = et.et_events_id
LEFT JOIN tags t ON et.et_tags_id = t.tag_id
 LEFT JOIN user u ON e.host_user_id = u.user_id
 LEFT JOIN profile p ON e.host_user_id = p.user_id
WHERE 
    ep.ep_user_id = ?
    AND TIMESTAMP(e.events_date, COALESCE(e.events_start, '23:59:59')) > NOW()
    AND (e.events_deadline IS NULL OR TIMESTAMP(e.events_deadline, '23:59:59') > NOW())
GROUP BY 
    e.events_id,
    e.events_title,
    e.events_description,
    e.events_date,
    e.events_img,
    e.events_location,
    e.events_capacity,
    e.events_contact_email,
    e.events_visibility,
    e.events_deadline,
    e.events_start,
    e.events_end,
    e.events_create_date,
    e.host_user_id,
    u.user_username,
    p.user_firstname,
    p.user_lastname,
    p.profile_filepath
ORDER BY e.events_date ASC, e.events_start ASC
LIMIT 10
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error, 'events' => []]);
    exit;
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    // Determine if user is the host
    $row['is_host'] = ($row['host_user_id'] == $user_id);
    $row['is_registered'] = true; // Always true since we're filtering by registration
    // Format profile picture path
    if (!empty($row['profile_filepath'])) {
        $row['profile_filepath'] = (strpos($row['profile_filepath'], 'BPA/') === 0) 
            ? '../' . substr($row['profile_filepath'], 4) 
            : $row['profile_filepath'];
    }
    $events[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'events' => $events]);
