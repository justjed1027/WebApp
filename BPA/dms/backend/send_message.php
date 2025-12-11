<?php
/**
 * Send a message in a conversation
 * 
 * POST Input (JSON):
 * - conversation_id: int
 * - sender_id: int
 * - message_text: string
 * 
 * Output (JSON):
 * - success: bool
 * - message_id: int
 */

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../../database/DatabaseConnection.php';

// Authenticate user
$currentUser = get_authenticated_user();
if (!$currentUser) {
    send_json(['success' => false, 'error' => 'Not authenticated'], 401);
}

// Validate input
$input = validate_post_params(['conversation_id', 'message_text']);
$conversationId = intval($input['conversation_id']);
$messageText = trim($input['message_text']);

if (empty($messageText)) {
    send_json(['success' => false, 'error' => 'Message text cannot be empty'], 400);
}

if (strlen($messageText) > 10000) {
    send_json(['success' => false, 'error' => 'Message text too long (max 10000 characters)'], 400);
}

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

// Insert message
$insertSql = "INSERT INTO messages (conversation_id, sender_id, message_text, sent_at, is_read) 
              VALUES (?, ?, ?, NOW(), FALSE)";
$stmt = $db->prepare($insertSql);

if (!$stmt) {
    send_json(['success' => false, 'error' => 'Database error: ' . $db->error], 500);
}

$stmt->bind_param("iis", $conversationId, $currentUser, $messageText);

if (!$stmt->execute()) {
    $error = $stmt->error;
    $stmt->close();
    send_json(['success' => false, 'error' => 'Failed to send message: ' . $error], 500);
}

$messageId = $stmt->insert_id;
$stmt->close();

// Update last_message_time in conversations
$updateSql = "UPDATE conversations SET last_message_time = NOW() WHERE conversation_id = ?";
$stmt = $db->prepare($updateSql);

if ($stmt) {
    $stmt->bind_param("i", $conversationId);
    $stmt->execute();
    $stmt->close();
}

send_json([
    'success' => true,
    'message_id' => $messageId
]);
