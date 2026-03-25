<?php
// create_event.php
// Receives JSON POST to create an event and link to a subject (category)
session_start();
require_once '../database/DatabaseConnection.php';
require_once '../database/Notification.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST allowed']);
    exit;
}

$contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower($_SERVER['CONTENT_TYPE']) : '';
$isJsonRequest = strpos($contentType, 'application/json') !== false;

if ($isJsonRequest) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }
} else {
    $data = $_POST;
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid form submission']);
        exit;
    }
}

// Validate required fields
$required = ['title', 'category', 'description', 'date', 'startTime', 'location'];
foreach ($required as $r) {
    if (empty($data[$r])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "$r is required"]);
        exit;
    }
}

$db = new DatabaseConnection();
$conn = $db->connection;

// Get the current user ID from session
$hostUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
if (!$hostUserId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    $db->closeConnection();
    exit;
}

// Map and sanitize incoming values
$title = trim($data['title'] ?? '');
$desc = trim($data['description'] ?? '');
$events_date = $data['date'] ?? null;
$placeholderImage = 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1200&h=600&fit=crop';
$img = trim((string)($data['image'] ?? ''));
$organizer = trim((string)($data['organization'] ?? ''));
if ($organizer === '') {
    $organizer = null;
}
$location = trim($data['location'] ?? '');
$capacity = !empty($data['capacity']) && is_numeric($data['capacity']) ? (int)$data['capacity'] : null;

if (isset($_FILES['eventImageFile']) && (int)$_FILES['eventImageFile']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['eventImageFile'];
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Image upload failed']);
        $db->closeConnection();
        exit;
    }

    if ((int)$file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Image must be 5MB or smaller']);
        $db->closeConnection();
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;

    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    if (!$mime || !isset($allowedMime[$mime])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, WEBP, and GIF images are allowed']);
        $db->closeConnection();
        exit;
    }

    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        $db->closeConnection();
        exit;
    }

    $randomPart = bin2hex(random_bytes(8));
    $filename = 'event_' . time() . '_' . $randomPart . '.' . $allowedMime[$mime];
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded image']);
        $db->closeConnection();
        exit;
    }

    $img = 'uploads/' . $filename;
}

if ($img === '') {
    $img = $placeholderImage;
}

// tags may be sent as an array of tag IDs (preferred) or as a legacy string
$tagIds = [];
if (isset($data['tags'])) {
    if (is_array($data['tags'])) {
        $tagIds = array_map('intval', $data['tags']);
    } elseif (is_string($data['tags']) && strlen(trim($data['tags'])) > 0 && $data['tags'][0] === '[') {
        $decoded = json_decode($data['tags'], true);
        if (is_array($decoded)) {
            $tagIds = array_map('intval', $decoded);
        }
    } elseif (is_string($data['tags']) && strlen(trim($data['tags'])) > 0) {
        $parts = preg_split('/[\s,]+/', trim($data['tags']));
        foreach ($parts as $p) {
            if (is_numeric($p)) $tagIds[] = (int)$p;
        }
    }
}
$visibility = $data['visibility'] ?? 'public';
$deadline = !empty($data['registrationDeadline']) ? $data['registrationDeadline'] : null;
$contactEmail = trim($data['contactEmail'] ?? '');
if (empty($contactEmail)) $contactEmail = null;
$startTime = $data['startTime'] ?? null;
$endTime = $data['endTime'] ?? null;
$subjectId = (int)($data['category'] ?? 0);

if ($subjectId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid category is required']);
    $db->closeConnection();
    exit;
}

// Security check: users can only create events in subjects they marked as skills.
$skillCheckSql = 'SELECT 1 FROM user_skills WHERE us_user_id = ? AND us_subject_id = ? LIMIT 1';
$skillCheckStmt = $conn->prepare($skillCheckSql);
if (!$skillCheckStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB prepare failed', 'error' => $conn->error]);
    $db->closeConnection();
    exit;
}

$skillCheckStmt->bind_param('ii', $hostUserId, $subjectId);
$skillCheckStmt->execute();
$skillAllowed = $skillCheckStmt->get_result();
if (!$skillAllowed || $skillAllowed->num_rows === 0) {
    $skillCheckStmt->close();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You can only create events for subjects in your skills']);
    $db->closeConnection();
    exit;
}
$skillCheckStmt->close();

// Additional validations
try {
    // Validate event date not in the past (date-only comparison)
    if (empty($events_date)) {
        throw new Exception('Event date is required');
    }
    $eventDateObj = DateTime::createFromFormat('Y-m-d', $events_date);
    if (!$eventDateObj) {
        throw new Exception('Invalid event date format');
    }
    $today = new DateTime('today');
    if ($eventDateObj < $today) {
        throw new Exception('Event date cannot be in the past');
    }

    // Validate start and end times
    if (empty($startTime) || empty($endTime)) {
        throw new Exception('Start and end times are required');
    }
    $startDT = DateTime::createFromFormat('Y-m-d H:i', $events_date . ' ' . $startTime);
    $endDT = DateTime::createFromFormat('Y-m-d H:i', $events_date . ' ' . $endTime);
    if (!$startDT || !$endDT) {
        throw new Exception('Invalid start or end time');
    }
    if ($endDT <= $startDT) {
        throw new Exception('End time must be after start time');
    }
    // Normalize combined datetimes for insert
    $startDateTime = $startDT->format('Y-m-d H:i:s');
    $endDateTime = $endDT->format('Y-m-d H:i:s');

    // Validate capacity 1..500
    if ($capacity === null || !is_int($capacity)) {
        throw new Exception('Capacity is required and must be a number');
    }
    if ($capacity < 1 || $capacity > 500) {
        throw new Exception('Capacity must be between 1 and 500');
    }

    // Registration deadline is required, must be after today and before the event date
    if (empty($deadline)) {
        throw new Exception('Registration deadline is required');
    }
    // Try parsing as date first, then datetime
    $deadlineObj = DateTime::createFromFormat('Y-m-d', $deadline) ?: DateTime::createFromFormat('Y-m-d H:i', $deadline);
    if (!$deadlineObj) {
        throw new Exception('Invalid registration deadline');
    }
    // Compare dates only
    $deadlineDay = (clone $deadlineObj)->setTime(0, 0, 0);
    if ($deadlineDay <= $today) {
        throw new Exception('Registration deadline must be after today');
    }
    if ($deadlineDay >= $eventDateObj) {
        throw new Exception('Registration deadline must be before the event date');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    $db->closeConnection();
    exit;
}

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
    host_user_id
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
// Order: title(s), description(s), date(s), img(s), location(s), capacity(i), organization(s), contact_email(s), visibility(s), deadline(s), start(s), end(s), host_user_id(i)
$stmt->bind_param(
    'ssssssisssssi',
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
    $hostUserId
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

// Link event to tags in events_tags junction table (if any tag IDs provided)
if (!empty($tagIds)) {
    $tagLinkSql = "INSERT INTO events_tags (et_tags_id, et_events_id) VALUES (?, ?)";
    $tagStmt = $conn->prepare($tagLinkSql);
    if ($tagStmt) {
        foreach ($tagIds as $tId) {
            $tid = (int)$tId;
            if ($tid <= 0) continue;
            $tagStmt->bind_param('ii', $tid, $eventId);
            $tagStmt->execute();
        }
        $tagStmt->close();
    }
}

// Send notifications to users with matching interests
if ($subjectId > 0) {
    $notif = new Notification($conn);
    
    // Get all users who have skills matching this subject
    $userQuery = $conn->prepare("
        SELECT DISTINCT us.us_user_id
        FROM user_skills us
        WHERE us.us_subject_id = ?
        AND us.us_user_id != ?
        LIMIT 100
    ");
    
    if ($userQuery) {
        $userQuery->bind_param("ii", $subjectId, $hostUserId);
        $userQuery->execute();
        $userResult = $userQuery->get_result();
        
        // Get organizer username
        $orgQuery = $conn->prepare("SELECT user_username FROM user WHERE user_id = ?");
        $orgQuery->bind_param("i", $hostUserId);
        $orgQuery->execute();
        $orgResult = $orgQuery->get_result();
        $orgData = $orgResult->fetch_assoc();
        $orgName = $orgData['user_username'] ?? 'An organizer';
        
        // Create notification for each user with matching interests
        while ($userRow = $userResult->fetch_assoc()) {
            $userId = $userRow['us_user_id'];
            $notif->createNotification(
                $userId,
                'event_created',
                $hostUserId,
                'New event: ' . $title,
                $orgName . ' created an event matching your interests',
                $eventId,
                'event'
            );
        }
        
        $userQuery->close();
        $orgQuery->close();
    }
}

// Successful response
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Event created successfully',
    'eventId' => $eventId
]);
exit;

