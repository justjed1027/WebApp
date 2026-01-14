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
// Also include whether the current user is registered for each event (is_registered)
// and count total number of registered participants
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
    u.user_username,
    p.user_firstname,
    p.user_lastname,
    p.profile_filepath,
    -- is the current user registered for this event? (0/1)
    (EXISTS(SELECT 1 FROM event_participants ep WHERE ep.ep_event_id = e.events_id AND ep.ep_user_id = ?)) AS is_registered,
    -- count total number of participants registered for this event
    (SELECT COUNT(*) FROM event_participants ep WHERE ep.ep_event_id = e.events_id) AS registration_count,
    GROUP_CONCAT(DISTINCT s.subject_name SEPARATOR ', ') as subjects,
    GROUP_CONCAT(DISTINCT es.es_subject_id SEPARATOR ',') as subject_ids,
    GROUP_CONCAT(DISTINCT t.tag_name SEPARATOR ', ') as tags,
    GROUP_CONCAT(DISTINCT t.tag_id SEPARATOR ',') as tag_ids
FROM events e
LEFT JOIN user u ON e.host_user_id = u.user_id
LEFT JOIN profile p ON e.host_user_id = p.user_id
LEFT JOIN event_subjects es ON e.events_id = es.es_event_id
LEFT JOIN subjects s ON es.es_subject_id = s.subject_id
LEFT JOIN events_tags et ON e.events_id = et.et_events_id
LEFT JOIN tags t ON et.et_tags_id = t.tag_id
";

// We'll always bind the current user id (or 0) for the is_registered check
$uid_param = $user_id ? (int)$user_id : 0;

// Build WHERE clauses depending on filter mode, and always exclude events
// whose start datetime is now or in the past. If `events_start` is NULL,
// treat the event as ending at the end of that day so it remains visible
// until 23:59:59 of its date.
$timeFilter = "(TIMESTAMP(e.events_date, COALESCE(e.events_start, '23:59:59')) > NOW())";

// Accept optional search and category filters from query params
// Query params: search text, category id, and status (upcoming|past)
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : 'upcoming';

// Build dynamic WHERE clauses and bind parameters safely
$whereClauses = [];
$bindTypes = '';
$bindValues = [];

// Deadline passed expression: treat a date-only deadline as end of day
$deadlinePassedExpr = "(e.events_deadline IS NOT NULL AND TIMESTAMP(e.events_deadline, '23:59:59') <= NOW())";

// For 'past' status we select events whose deadline has passed.
// For 'upcoming' (default) we select events that start in the future and
// whose deadline has NOT passed (if a deadline exists).
if ($status === 'past') {
    // Only include past events whose deadline has passed AND the current user is registered
    $whereClauses[] = $deadlinePassedExpr . " AND (EXISTS(SELECT 1 FROM event_participants ep2 WHERE ep2.ep_event_id = e.events_id AND ep2.ep_user_id = ?))";
    $bindTypes .= 'i';
    $bindValues[] = (int)$user_id;
} else {
    // upcoming: event start is in the future AND deadline has NOT passed
    // Registered users will NOT see deadline-passed events in the upcoming list;
    // those will only appear in the 'past' (Coming) registered list.
    $whereClauses[] = $timeFilter;
    $whereClauses[] = "NOT (" . $deadlinePassedExpr . ")";
    
    // Hide full capacity events from users who are NOT registered
    // Show event if: (capacity is null) OR (spots available) OR (user is registered)
    $capacityFilter = "(e.events_capacity IS NULL OR 
        (SELECT COUNT(*) FROM event_participants ep3 WHERE ep3.ep_event_id = e.events_id) < e.events_capacity OR
        EXISTS(SELECT 1 FROM event_participants ep4 WHERE ep4.ep_event_id = e.events_id AND ep4.ep_user_id = ?))";
    $whereClauses[] = $capacityFilter;
    $bindTypes .= 'i';
    $bindValues[] = (int)$user_id;
}

// Base visibility / mode filters
if ($filter_mode === 'created' && $user_id) {
    $whereClauses[] = 'e.host_user_id = ?';
    $bindTypes .= 'i';
    $bindValues[] = (int)$user_id;
} elseif ($filter_mode === 'relevant' && $user_id) {
    $whereClauses[] = "(es.es_subject_id IN (SELECT ui_subject_id FROM user_interests WHERE ui_user_id = ?) OR e.events_visibility = 'public')";
    $bindTypes .= 'i';
    $bindValues[] = (int)$user_id;
} else {
    $whereClauses[] = "(e.events_visibility = 'public' OR e.events_visibility IS NULL)";
}

// Category filter: subject id
if ($category !== '') {
    // only accept numeric category ids
    if (ctype_digit($category)) {
        $whereClauses[] = 'es.es_subject_id = ?';
        $bindTypes .= 'i';
        $bindValues[] = (int)$category;
    }
}

// Search filter: match title, description, location, organization, subject name, or tag name
if ($search !== '') {
    $like = '%' . $search . '%';
    $whereClauses[] = "(
        e.events_title LIKE ? OR
        e.events_description LIKE ? OR
        e.events_location LIKE ? OR
        e.events_organization LIKE ? OR
        s.subject_name LIKE ? OR
        t.tag_name LIKE ?
    )";
    // add six string params
    $bindTypes .= 'ssssss';
    $bindValues[] = $like;
    $bindValues[] = $like;
    $bindValues[] = $like;
    $bindValues[] = $like;
    $bindValues[] = $like;
    $bindValues[] = $like;
}

// Prepare final SQL with WHERE clauses
$finalSql = $baseSql . ' WHERE ' . implode(' AND ', $whereClauses) . ' GROUP BY e.events_id ORDER BY e.events_date ASC';
$stmt = $conn->prepare($finalSql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed', 'error' => $conn->error]);
    exit;
}

// Bind parameters: first the uid used in is_registered subquery, then dynamic ones
$allTypes = 'i' . $bindTypes;
$allValues = array_merge([$uid_param], $bindValues);
if ($allValues) {
    // mysqli_stmt::bind_param requires references
    $refs = [];
    $refs[] = &$allTypes;
    foreach ($allValues as $k => $v) {
        $refs[] = &$allValues[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
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
        'hostUsername' => $row['user_username'],
        'hostFirstName' => $row['user_firstname'],
        'hostLastName' => $row['user_lastname'],
        'hostProfilePicture' => !empty($row['profile_filepath']) 
            ? (strpos($row['profile_filepath'], 'BPA/') === 0 ? '../' . substr($row['profile_filepath'], 4) : $row['profile_filepath'])
            : null,
        'isRegistered' => !empty($row['is_registered']) && $row['is_registered'] ? true : false,
        'registrationCount' => (int)($row['registration_count'] ?? 0),
        'subjects' => $row['subjects'] ? array_map('trim', explode(',', $row['subjects'])) : [],
        'subjectIds' => $row['subject_ids'] ? array_map('intval', explode(',', $row['subject_ids'])) : [],
        'tags' => $row['tags'] ? array_map('trim', explode(',', $row['tags'])) : [],
        'tagIds' => $row['tag_ids'] ? array_map('intval', explode(',', $row['tag_ids'])) : [],
    ];
    $events[] = $event;
}

$stmt->close();
$db->closeConnection();

echo json_encode([
    'success' => true, 
    'events' => $events, 
    'count' => count($events)
]);
exit;
?>
