<?php
/**
 * Mark all messages in a conversation as read for the current user
 * 
 * POST Input (JSON):
 * - conversation_id: int
 * 
 * Output (JSON):
 * - success: bool
 * - updated_count: int (number of messages marked as read)
 */

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../../database/DatabaseConnection.php';

// Authenticate user
$currentUser = get_authenticated_user();
if (!$currentUser) {
    send_json(['success' => false, 'error' => 'Not authenticated'], 401);
}

// Validate POST parameters
$requiredParams = ['conversation_id'];
$params = validate_post_params($requiredParams);

$conversationId = intval($params['conversation_id']);

$dbConn = new DatabaseConnection();
$db = $dbConn->connection;

// Verify the user is part of this conversation
$checkSql = "SELECT user1_id, user2_id FROM conversations WHERE conversation_id = ?";
$checkStmt = $db->prepare($checkSql);

if (!$checkStmt) {
    send_json(['success' => false, 'error' => 'Database error: ' . $db->error], 500);
}

$checkStmt->bind_param("i", $conversationId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$conversation = $checkResult->fetch_assoc();
$checkStmt->close();

if (!$conversation) {
    send_json(['success' => false, 'error' => 'Conversation not found'], 404);
}

// Verify user is a participant
if ($conversation['user1_id'] != $currentUser && $conversation['user2_id'] != $currentUser) {
    send_json(['success' => false, 'error' => 'Access denied'], 403);
}

// Mark all messages as read where the current user is NOT the sender
$updateSql = "UPDATE messages 
              SET is_read = TRUE 
              WHERE conversation_id = ? 
              AND sender_id != ? 
              AND is_read = FALSE";

$updateStmt = $db->prepare($updateSql);

if (!$updateStmt) {
    send_json(['success' => false, 'error' => 'Database error: ' . $db->error], 500);
}

$updateStmt->bind_param("ii", $conversationId, $currentUser);
$updateStmt->execute();

$updatedCount = $updateStmt->affected_rows;
$updateStmt->close();

send_json([
    'success' => true,
    'updated_count' => $updatedCount
]);
