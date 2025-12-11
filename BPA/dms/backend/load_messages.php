<?php
/**
 * Load all messages for a conversation
 * 
 * GET Input:
 * - conversation_id: int
 * 
 * Output (JSON):
 * - success: bool
 * - messages: array of objects with:
 *   - message_id: int
 *   - sender_id: int
 *   - message_text: string
 *   - sent_at: string (timestamp)
 *   - is_read: bool
 */

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../../database/DatabaseConnection.php';

// Authenticate user
$currentUser = get_authenticated_user();
if (!$currentUser) {
    send_json(['success' => false, 'error' => 'Not authenticated'], 401);
}

// Validate input
$params = validate_get_params(['conversation_id']);
$conversationId = intval($params['conversation_id']);

$dbConn = new DatabaseConnection();
$db = $dbConn->connection;

// Verify user is part of this conversation
$verifySql = "SELECT conversation_id FROM conversations 
              WHERE conversation_id = ? AND (user1_id = ? OR user2_id = ?)";
$stmt = $db->prepare($verifySql);

if (!$stmt) {
    send_json(['success' => false, 'error' => 'Database error: ' . $db->error], 500);
}

$stmt->bind_param("iii", $conversationId, $currentUser, $currentUser);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    send_json(['success' => false, 'error' => 'Conversation not found or access denied'], 403);
}

$stmt->close();

// Load messages
$messagesSql = "SELECT message_id, sender_id, message_text, sent_at, is_read 
                FROM messages 
                WHERE conversation_id = ? 
                ORDER BY sent_at ASC";
$stmt = $db->prepare($messagesSql);

if (!$stmt) {
    send_json(['success' => false, 'error' => 'Database error: ' . $db->error], 500);
}

$stmt->bind_param("i", $conversationId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'message_id' => intval($row['message_id']),
        'sender_id' => intval($row['sender_id']),
        'message_text' => $row['message_text'],
        'sent_at' => $row['sent_at'],
        'is_read' => (bool)$row['is_read']
    ];
}

$stmt->close();

send_json([
    'success' => true,
    'messages' => $messages
]);
