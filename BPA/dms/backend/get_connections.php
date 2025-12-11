<?php
/**
 * Get all accepted connections for a user
 * This endpoint returns users the current user can message
 * 
 * Output (JSON):
 * - success: bool
 * - connections: array of objects with:
 *   - user_id: int
 *   - user_username: string
 *   - has_conversation: bool
 *   - conversation_id: int|null
 */

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../../database/DatabaseConnection.php';

// Authenticate user
$currentUser = get_authenticated_user();
if (!$currentUser) {
    send_json(['success' => false, 'error' => 'Not authenticated'], 401);
}

$dbConn = new DatabaseConnection();
$db = $dbConn->connection;

// Get all accepted connections (users they can message)
$connectionsSql = "SELECT DISTINCT
    CASE 
        WHEN conn.requester_id = ? THEN conn.receiver_id
        ELSE conn.requester_id
    END AS user_id,
    u.user_username
FROM connections conn
JOIN user u ON (
    CASE 
        WHEN conn.requester_id = ? THEN conn.receiver_id = u.user_id
        ELSE conn.requester_id = u.user_id
    END
)
WHERE (conn.requester_id = ? OR conn.receiver_id = ?)
AND conn.status = 'accepted'
ORDER BY u.user_username ASC";

$stmt = $db->prepare($connectionsSql);

if (!$stmt) {
    send_json(['success' => false, 'error' => 'Database error: ' . $db->error], 500);
}

$stmt->bind_param("iiii", $currentUser, $currentUser, $currentUser, $currentUser);
$stmt->execute();
$result = $stmt->get_result();

$connections = [];
while ($row = $result->fetch_assoc()) {
    $otherUserId = intval($row['user_id']);
    
    // Check if conversation already exists
    list($user1, $user2) = normalize_user_pair($currentUser, $otherUserId);
    
    $convStmt = $db->prepare("SELECT conversation_id FROM conversations WHERE user1_id = ? AND user2_id = ?");
    $convStmt->bind_param("ii", $user1, $user2);
    $convStmt->execute();
    $convResult = $convStmt->get_result();
    
    $conversationId = null;
    $hasConversation = false;
    
    if ($convRow = $convResult->fetch_assoc()) {
        $conversationId = intval($convRow['conversation_id']);
        $hasConversation = true;
    }
    $convStmt->close();
    
    $connections[] = [
        'user_id' => $otherUserId,
        'user_username' => $row['user_username'],
        'has_conversation' => $hasConversation,
        'conversation_id' => $conversationId
    ];
}

$stmt->close();

send_json([
    'success' => true,
    'connections' => $connections
]);
