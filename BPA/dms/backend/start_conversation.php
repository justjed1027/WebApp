<?php
/**
 * Start a conversation with another user (simplified endpoint)
 * 
 * POST Input (JSON):
 * - other_user_id: int (the user to start conversation with)
 * 
 * Output (JSON):
 * - success: bool
 * - conversation_id: int
 * - created: bool (true if newly created, false if existing)
 */

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../../database/DatabaseConnection.php';

// Authenticate user
$currentUser = get_authenticated_user();
if (!$currentUser) {
    send_json(['success' => false, 'error' => 'Not authenticated'], 401);
}

// Validate input
$input = validate_post_params(['other_user_id']);
$otherUserId = intval($input['other_user_id']);

// Normalize user pair
$normalized = normalize_user_pair($currentUser, $otherUserId);
if (!$normalized) {
    send_json(['success' => false, 'error' => 'Cannot create conversation with yourself'], 400);
}

list($user1_id, $user2_id) = $normalized;

$dbConn = new DatabaseConnection();
$db = $dbConn->connection;

// Check if conversation exists
$checkSql = "SELECT conversation_id FROM conversations WHERE user1_id = ? AND user2_id = ?";
$stmt = $db->prepare($checkSql);

if (!$stmt) {
    send_json(['success' => false, 'error' => 'Database error: ' . $db->error], 500);
}

$stmt->bind_param("ii", $user1_id, $user2_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Conversation exists
    $stmt->close();
    send_json([
        'success' => true,
        'conversation_id' => intval($row['conversation_id']),
        'created' => false
    ]);
}

$stmt->close();

// Create new conversation
$insertSql = "INSERT INTO conversations (user1_id, user2_id, created_at, last_message_time) 
              VALUES (?, ?, NOW(), NULL)";
$stmt = $db->prepare($insertSql);

if (!$stmt) {
    send_json(['success' => false, 'error' => 'Database error: ' . $db->error], 500);
}

$stmt->bind_param("ii", $user1_id, $user2_id);

if ($stmt->execute()) {
    $conversationId = $stmt->insert_id;
    $stmt->close();
    
    send_json([
        'success' => true,
        'conversation_id' => $conversationId,
        'created' => true
    ]);
} else {
    $error = $stmt->error;
    $stmt->close();
    send_json(['success' => false, 'error' => 'Failed to create conversation: ' . $error], 500);
}
