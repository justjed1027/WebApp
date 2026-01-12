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

if (empty($searchQuery)) {
    echo json_encode(['students' => []]);
    exit();
}

$db = new DatabaseConnection();
$con = $db->connection;

// Search for users matching the query
$searchParam = '%' . $searchQuery . '%';
$sql = "SELECT u.user_id, u.user_username, u.user_email, p.user_firstname, p.user_lastname 
        FROM user u
        LEFT JOIN profile p ON u.user_id = p.user_id
        WHERE u.user_id != ? 
        AND (u.user_username LIKE ? OR p.user_firstname LIKE ? OR p.user_lastname LIKE ? OR CONCAT(p.user_firstname, ' ', p.user_lastname) LIKE ?)
        ORDER BY u.user_username ASC
        LIMIT 100";

$stmt = $con->prepare($sql);
$stmt->bind_param("issss", $currentUserId, $searchParam, $searchParam, $searchParam, $searchParam);
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
    
    // Get connected users
    $connSql = "SELECT 
                    CASE 
                        WHEN requester_id = ? THEN receiver_id 
                        ELSE requester_id 
                    END as user_id,
                    status
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
        $connectionStatuses[$row['user_id']] = $row['status'];
    }
    $connStmt->close();
    
    // Get pending sent requests
    $pendingSql = "SELECT receiver_id FROM connections WHERE requester_id = ? AND status = 'pending' AND receiver_id IN ($placeholders)";
    $types = 'i' . str_repeat('i', count($userIds));
    $params = array_merge([$currentUserId], $userIds);
    
    $pendingStmt = $con->prepare($pendingSql);
    $pendingStmt->bind_param($types, ...$params);
    $pendingStmt->execute();
    $pendingResult = $pendingStmt->get_result();
    
    while ($row = $pendingResult->fetch_assoc()) {
        if (!isset($connectionStatuses[$row['receiver_id']])) {
            $connectionStatuses[$row['receiver_id']] = 'pending_sent';
        }
    }
    $pendingStmt->close();
    
    // Get pending received requests
    $pendingRecSql = "SELECT requester_id FROM connections WHERE receiver_id = ? AND status = 'pending' AND requester_id IN ($placeholders)";
    $pendingRecStmt = $con->prepare($pendingRecSql);
    $pendingRecStmt->bind_param($types, ...$params);
    $pendingRecStmt->execute();
    $pendingRecResult = $pendingRecStmt->get_result();
    
    while ($row = $pendingRecResult->fetch_assoc()) {
        if (!isset($connectionStatuses[$row['requester_id']])) {
            $connectionStatuses[$row['requester_id']] = 'pending_received';
        }
    }
    $pendingRecStmt->close();
}

// Add connection status to each student
foreach ($students as &$student) {
    $student['connection_status'] = $connectionStatuses[$student['user_id']] ?? 'none';
}

echo json_encode(['students' => $students, 'count' => count($students)]);
