<?php
/**
 * List all conversations for a user
 * 
 * GET Input:
 * - user_id: int (optional, defaults to current authenticated user)
 * 
 * Output (JSON):
 * - success: bool
 * - conversations: array of objects with:
 *   - conversation_id: int
 *   - other_user_id: int
 *   - other_user_username: string
 *   - last_message_time: string (timestamp or null)
 *   - unread_count: int
 */

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../../database/DatabaseConnection.php';

// Authenticate user
$currentUser = get_authenticated_user();
if (!$currentUser) {
    // Debug info
    $debugInfo = [
        'session_status' => session_status(),
        'session_id' => session_id(),
        'session_data' => isset($_SESSION) ? array_keys($_SESSION) : 'no session',
        'user_id_exists' => isset($_SESSION['user_id']) ? 'yes' : 'no'
    ];
    send_json(['success' => false, 'error' => 'Not authenticated', 'debug' => $debugInfo], 401);
}

// Use authenticated user
$userId = $currentUser;

// Use shared DatabaseConnection (ini-based)
$dbConn = new DatabaseConnection();
$db = $dbConn->connection;

// Get all conversations for this user, but only with accepted connections
$conversationsSql = "SELECT 
    c.conversation_id,
    c.user1_id,
    c.user2_id,
    c.last_message_time,
    CASE 
        WHEN c.user1_id = ? THEN c.user2_id
        ELSE c.user1_id
    END AS other_user_id
FROM conversations c
WHERE (c.user1_id = ? OR c.user2_id = ?)
AND EXISTS (
    SELECT 1 FROM connections conn
    WHERE conn.status = 'accepted'
    AND (
        (conn.requester_id = c.user1_id AND conn.receiver_id = c.user2_id)
        OR (conn.requester_id = c.user2_id AND conn.receiver_id = c.user1_id)
    )
)
ORDER BY c.last_message_time DESC";

$stmt = $db->prepare($conversationsSql);

if (!$stmt) {
    send_json(['success' => false, 'error' => 'Database error: ' . $db->error], 500);
}

$stmt->bind_param("iii", $userId, $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();

$conversations = [];
while ($row = $result->fetch_assoc()) {
    $conversationId = intval($row['conversation_id']);
    $otherUserId = intval($row['other_user_id']);
    
    // Get other user's username
    $userStmt = $db->prepare("SELECT user_username FROM user WHERE user_id = ?");
    $userStmt->bind_param("i", $otherUserId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $otherUsername = 'Unknown User';
    
    if ($userRow = $userResult->fetch_assoc()) {
        $otherUsername = $userRow['user_username'];
    }
    $userStmt->close();
    
    // Get unread count for this conversation
    $unreadStmt = $db->prepare("SELECT COUNT(*) as unread_count 
                                FROM messages 
                                WHERE conversation_id = ? 
                                AND sender_id != ? 
                                AND is_read = FALSE");
    $unreadStmt->bind_param("ii", $conversationId, $userId);
    $unreadStmt->execute();
    $unreadResult = $unreadStmt->get_result();
    $unreadCount = 0;
    
    if ($unreadRow = $unreadResult->fetch_assoc()) {
        $unreadCount = intval($unreadRow['unread_count']);
    }
    $unreadStmt->close();
    
    $conversations[] = [
        'conversation_id' => $conversationId,
        'other_user_id' => $otherUserId,
        'other_user_username' => $otherUsername,
        'last_message_time' => $row['last_message_time'],
        'unread_count' => $unreadCount
    ];
}

$stmt->close();

send_json([
    'success' => true,
    'conversations' => $conversations
]);
