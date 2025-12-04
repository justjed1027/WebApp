<?php
// create_event.php
// Receives JSON POST to create an event and link to a subject (category)
session_start();
require_once '../database/DatabaseConnection.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// Validate required fields
$required = ['title', 'category', 'description', 'date', 'startTime', 'location', 'organizer'];
foreach ($required as $r) {
    if (empty($data[$r])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "$r is required"]);
        exit;
    }
}

$db = new DatabaseConnection();
$conn = $db->connection;

// Map and sanitize incoming values
$title = trim($data['title'] ?? '');
$desc = trim($data['description'] ?? '');
$events_date = $data['date'] ?? null;
$img = trim($data['image'] ?? '');
if (empty($img)) $img = null;
$location = trim($data['location'] ?? '');
$capacity = !empty($data['capacity']) && is_numeric($data['capacity']) ? (int)$data['capacity'] : null;
$organizer = trim($data['organizer'] ?? '');
$tags = trim($data['tags'] ?? '');
if (empty($tags)) $tags = null;
$visibility = $data['visibility'] ?? 'public';
$deadline = !empty($data['registrationDeadline']) ? $data['registrationDeadline'] : null;
$contactEmail = trim($data['contactEmail'] ?? '');
if (empty($contactEmail)) $contactEmail = null;
$startTime = $data['startTime'] ?? null;
$endTime = $data['endTime'] ?? null;
$subjectId = (int)($data['category'] ?? 0);

// Combine date and start time into DATETIME format if needed (events_start)
$startDateTime = null;
if ($events_date && $startTime) {
    $startDateTime = $events_date . ' ' . $startTime;
}

// Combine date and end time into DATETIME format if needed (events_end)
$endDateTime = null;
if ($events_date && $endTime) {
    $endDateTime = $events_date . ' ' . $endTime;
}

// Insert into events table
// Map to actual column names from your schema based on the screenshot
$insertSql = "INSERT INTO events (
    events_title, 
    events_description, 
    events_date, 
    events_create_date, 
    events_img, 
    events_location, 
    events_capacity, 
    events_organization, 
    events_contact_email, 
    events_visibility, 
    events_deadline, 
    events_start, 
    events_end,
    events_tags
) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($insertSql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB prepare failed', 'error' => $conn->error]);
    $db->closeConnection();
    exit;
}

// Bind parameters: 
// s = string, i = int
// Order: title(s), description(s), date(s), img(s), location(s), capacity(i), organization(s), contact_email(s), visibility(s), deadline(s), start(s), end(s), tags(s)
$stmt->bind_param(
    'sssssisssssss',
    $title,
    $desc,
    $events_date,
    $img,
    $location,
    $capacity,
    $organizer,
    $contactEmail,
    $visibility,
    $deadline,
    $startDateTime,
    $endDateTime,
    $tags
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Insert failed', 'error' => $stmt->error]);
    $stmt->close();
    $db->closeConnection();
    exit;
}

$eventId = $stmt->insert_id;
$stmt->close();

// Link event to subject in event_subjects table
if ($subjectId > 0) {
    $linkSql = "INSERT INTO event_subjects (es_event_id, es_subject_id) VALUES (?, ?)";
    $linkStmt = $conn->prepare($linkSql);
    if ($linkStmt) {
        $linkStmt->bind_param('ii', $eventId, $subjectId);
        $linkStmt->execute();
        $linkStmt->close();
    }
}

$db->closeConnection();

echo json_encode(['success' => true, 'event_id' => $eventId, 'message' => 'Event created successfully']);
exit;
