<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../database/DatabaseConnection.php';

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'error' => 'Not authenticated']);
  exit;
}

$dbConn = new DatabaseConnection();
$db = $dbConn->connection;

$userId = intval($_SESSION['user_id']);

// Count unread messages across all conversations where user is a participant
$sql = "SELECT COUNT(DISTINCT m.conversation_id) as unread_conversations
        FROM messages m
        INNER JOIN conversations c ON m.conversation_id = c.conversation_id
        WHERE (c.user1_id = ? OR c.user2_id = ?)
        AND m.sender_id != ?
        AND m.is_read = FALSE";

$stmt = $db->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'error' => 'Database error']);
  exit;
}

$stmt->bind_param("iii", $userId, $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();

$unreadCount = 0;
if ($row = $result->fetch_assoc()) {
  $unreadCount = intval($row['unread_conversations']);
}

$stmt->close();

echo json_encode([
  'success' => true,
  'unread_count' => $unreadCount
]);
