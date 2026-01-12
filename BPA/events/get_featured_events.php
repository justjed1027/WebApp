<?php
// get_featured_events.php - Get personalized featured events based on user interests
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../database/DatabaseConnection.php';

$db = new DatabaseConnection();
$conn = $db->connection;

// Check connection
if (!$conn) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed',
        'error' => 'Could not establish connection to database'
    ]);
    exit;
}

// Get current user ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Determine current time slot for caching (6:00, 12:00, 18:00, 24:00)
// This creates a deterministic "slot" that changes 4 times per day
$currentHour = (int)date('H');
if ($currentHour >= 0 && $currentHour < 6) {
    $timeSlot = '00:00';
} elseif ($currentHour >= 6 && $currentHour < 12) {
    $timeSlot = '06:00';
} elseif ($currentHour >= 12 && $currentHour < 18) {
    $timeSlot = '12:00';
} else {
    $timeSlot = '18:00';
}

try {
    // Build SQL query to get featured events
    // 1. Match events to user's interested subjects
    // 2. Exclude events user is already registered for
    // 3. Only upcoming events (not past deadline)
    // 4. Order by registration deadline (closest first)
    // 5. Limit to 10 events for carousel

    $sql = "
    SELECT DISTINCT
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
        (SELECT COUNT(*) FROM event_participants ep WHERE ep.ep_event_id = e.events_id) AS registration_count,
        GROUP_CONCAT(DISTINCT s.subject_name SEPARATOR ', ') as subjects,
        GROUP_CONCAT(DISTINCT t.tag_name SEPARATOR ', ') as tags
    FROM events e
    INNER JOIN event_subjects es ON e.events_id = es.es_event_id
    INNER JOIN user_interests ui ON es.es_subject_id = ui.ui_subject_id AND ui.ui_user_id = ?
    LEFT JOIN subjects s ON es.es_subject_id = s.subject_id
    LEFT JOIN events_tags et ON e.events_id = et.et_events_id
    LEFT JOIN tags t ON et.et_tags_id = t.tag_id
    WHERE 
        -- Only upcoming events (start time is in future)
        TIMESTAMP(e.events_date, COALESCE(e.events_start, '23:59:59')) > NOW()
        -- Registration deadline has not passed
        AND (e.events_deadline IS NULL OR TIMESTAMP(e.events_deadline, '23:59:59') > NOW())
        -- User is NOT already registered
        AND NOT EXISTS(
            SELECT 1 FROM event_participants ep 
            WHERE ep.ep_event_id = e.events_id AND ep.ep_user_id = ?
        )
        -- Event is visible
        AND e.events_visibility = 'public'
    GROUP BY e.events_id
    ORDER BY 
        -- Prioritize events with closer registration deadlines
        CASE 
            WHEN e.events_deadline IS NOT NULL THEN TIMESTAMP(e.events_deadline, '23:59:59')
            ELSE TIMESTAMP(e.events_date, COALESCE(e.events_start, '23:59:59'))
        END ASC
    LIMIT 5
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }

    $stmt->bind_param('ii', $user_id, $user_id);

    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $featuredEvents = [];

    while ($row = $result->fetch_assoc()) {
        $event = [
            'id' => (int)$row['events_id'],
            'title' => $row['events_title'],
            'description' => $row['events_description'],
            'date' => $row['events_date'],
            'image' => $row['events_img'] ?: 'https://via.placeholder.com/600x400?text=Event+Image',
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
            'registrationCount' => (int)($row['registration_count'] ?? 0),
            'subjects' => $row['subjects'] ? array_map('trim', explode(',', $row['subjects'])) : [],
            'tags' => $row['tags'] ? array_map('trim', explode(',', $row['tags'])) : [],
        ];
        $featuredEvents[] = $event;
    }

    $stmt->close();
    $db->closeConnection();

    echo json_encode([
        'success' => true, 
        'events' => $featuredEvents, 
        'count' => count($featuredEvents),
        'timeSlot' => $timeSlot,
        'refreshTime' => date('Y-m-d H:i:s')
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading featured events',
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
    exit;
}
?>

