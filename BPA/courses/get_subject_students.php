<?php
session_start();
require_once '../database/DatabaseConnection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$currentUserId = $_SESSION['user_id'];
$subjectId = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : ''; // 'learning' or 'fluent'
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

if (!$subjectId || !in_array($type, ['learning', 'fluent'])) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit();
}

$db = new DatabaseConnection();
$con = $db->connection;

// Determine which table to query based on type
$table = ($type === 'learning') ? 'user_interests' : 'user_skills';
$userIdColumn = ($type === 'learning') ? 'ui_user_id' : 'us_user_id';
$subjectIdColumn = ($type === 'learning') ? 'ui_subject_id' : 'us_subject_id';

// First, get total count
$countSql = "SELECT COUNT(DISTINCT t.$userIdColumn) as total
             FROM $table t
             WHERE t.$subjectIdColumn = ? AND t.$userIdColumn != ?";

$countStmt = $con->prepare($countSql);
$countStmt->bind_param("ii", $subjectId, $currentUserId);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalCount = $countResult->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalCount / $perPage);

// Get students with pagination
$sql = "SELECT u.user_id, u.user_username, u.user_email, p.user_firstname, p.user_lastname 
        FROM $table t
        JOIN user u ON t.$userIdColumn = u.user_id
        LEFT JOIN profile p ON u.user_id = p.user_id
        WHERE t.$subjectIdColumn = ? AND t.$userIdColumn != ?
        ORDER BY u.user_username ASC
        LIMIT ? OFFSET ?";

$stmt = $con->prepare($sql);
$stmt->bind_param("iiii", $subjectId, $currentUserId, $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

// Get connection statuses for all found users
$userIds = array_column($students, 'user_id');
$connectionStatuses = [];

if (!empty($userIds)) {
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    
    // Get all connections and determine their type
    $connSql = "SELECT 
                    CASE 
                        WHEN requester_id = ? THEN receiver_id 
                        ELSE requester_id 
                    END as user_id,
                    status,
                    requester_id,
                    receiver_id
                FROM connections 
                WHERE (requester_id = ? OR receiver_id = ?) 
                AND (requester_id IN ($placeholders) OR receiver_id IN ($placeholders))";
    
    $types = 'iii' . str_repeat('i', count($userIds) * 2);
    $params = array_merge([$currentUserId, $currentUserId, $currentUserId], $userIds, $userIds);
    
    $connStmt = $con->prepare($connSql);
    $connStmt->bind_param($types, ...$params);
    $connStmt->execute();
    $connResult = $connStmt->get_result();
    
    while ($row = $connResult->fetch_assoc()) {
        $otherUserId = ($row['requester_id'] == $currentUserId) ? $row['receiver_id'] : $row['requester_id'];
        
        if ($row['status'] === 'accepted') {
            $connectionStatuses[$otherUserId] = 'accepted';
        } else if ($row['status'] === 'pending') {
            // Determine if we sent or received the request
            if ($row['requester_id'] == $currentUserId) {
                $connectionStatuses[$otherUserId] = 'pending_sent';
            } else {
                $connectionStatuses[$otherUserId] = 'pending_received';
            }
        }
    }
    $connStmt->close();
}

// Add connection status to each student
foreach ($students as &$student) {
    $student['connection_status'] = $connectionStatuses[$student['user_id']] ?? 'none';
}

echo json_encode([
    'students' => $students, 
    'total' => $totalCount,
    'totalPages' => $totalPages,
    'currentPage' => $page,
    'perPage' => $perPage,
    'type' => $type
]);
