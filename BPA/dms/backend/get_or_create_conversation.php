<?php
/**
 * Get or create a conversation between two users
 * 
 * POST Input (JSON):
 * - userA: int (first user ID)
 * - userB: int (second user ID)
 * 
 * Output (JSON):
 * - success: bool
 * - conversation_id: int
 * - created: bool (true if newly created, false if existing)
 */

require_once 'db.php';
require_once 'utils.php';

// Authenticate user
$currentUser = get_authenticated_user();
if (!$currentUser) {
    send_json(['success' => false, 'error' => 'Not authenticated'], 401);
}

// Validate input
$input = validate_post_params(['userA', 'userB']);
$userA = intval($input['userA']);
$userB = intval($input['userB']);

// Normalize user pair
$normalized = normalize_user_pair($userA, $userB);
if (!$normalized) {
    send_json(['success' => false, 'error' => 'Cannot create conversation with yourself'], 400);
}

list($user1_id, $user2_id) = $normalized;

// Verify current user is part of this conversation
if ($currentUser !== $user1_id && $currentUser !== $user2_id) {
    send_json(['success' => false, 'error' => 'Unauthorized'], 403);
}

$db = DB::getInstance()->getConnection();

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
