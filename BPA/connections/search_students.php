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
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

if (empty($searchQuery)) {
    echo json_encode(['students' => [], 'total' => 0, 'totalPages' => 0, 'currentPage' => 1]);
    exit();
}

$db = new DatabaseConnection();
$con = $db->connection;

// First, get total count of matching users
$countSql = "SELECT COUNT(*) as total
        FROM user u
        LEFT JOIN profile p ON u.user_id = p.user_id
        WHERE u.user_id != ? 
        AND (u.user_username LIKE ? OR p.user_firstname LIKE ? OR p.user_lastname LIKE ? OR CONCAT(p.user_firstname, ' ', p.user_lastname) LIKE ?)";

$searchParam = '%' . $searchQuery . '%';
$countStmt = $con->prepare($countSql);
$countStmt->bind_param("issss", $currentUserId, $searchParam, $searchParam, $searchParam, $searchParam);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalCount = $countResult->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalCount / $perPage);

// Search for users matching the query with pagination
$searchParam = '%' . $searchQuery . '%';
$sql = "SELECT u.user_id, u.user_username, u.user_email, p.user_firstname, p.user_lastname 
        FROM user u
        LEFT JOIN profile p ON u.user_id = p.user_id
        WHERE u.user_id != ? 
        AND (u.user_username LIKE ? OR p.user_firstname LIKE ? OR p.user_lastname LIKE ? OR CONCAT(p.user_firstname, ' ', p.user_lastname) LIKE ?)
        ORDER BY u.user_username ASC
        LIMIT ? OFFSET ?";

$stmt = $con->prepare($sql);
$stmt->bind_param("isssiii", $currentUserId, $searchParam, $searchParam, $searchParam, $searchParam, $perPage, $offset);
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
    
    // Get all connections - determine the correct status based on direction
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
        
        if ($row['status'] == 'accepted') {
            $connectionStatuses[$otherUserId] = 'accepted';
        } elseif ($row['status'] == 'pending') {
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
    'perPage' => $perPage
]);
