<?php
/**
 * Update user online/offline status
 * 
 * GET Input:
 * - action: 'online' or 'offline'
 * 
 * Output (JSON):
 * - success: bool
 */

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../../database/DatabaseConnection.php';

// Authenticate user
$currentUser = get_authenticated_user();
if (!$currentUser) {
    send_json(['success' => false, 'error' => 'Not authenticated'], 401);
}

$action = $_GET['action'] ?? '';

if (!in_array($action, ['online', 'offline'])) {
    send_json(['success' => false, 'error' => 'Invalid action'], 400);
}

$dbConn = new DatabaseConnection();
$db = $dbConn->connection;

if ($action === 'online') {
    // User is online
    $stmt = $db->prepare("UPDATE user SET is_online = TRUE, last_seen = NOW() WHERE user_id = ?");
    $stmt->bind_param("i", $currentUser);
    
    if (!$stmt->execute()) {
        send_json(['success' => false, 'error' => 'Database error: ' . $db->error], 500);
    }
    $stmt->close();
    send_json(['success' => true]);
    
} elseif ($action === 'offline') {
    // User is going offline
    $stmt = $db->prepare("UPDATE user SET is_online = FALSE WHERE user_id = ?");
    $stmt->bind_param("i", $currentUser);
    
    if (!$stmt->execute()) {
        send_json(['success' => false, 'error' => 'Database error: ' . $db->error], 500);
    }
    $stmt->close();
    send_json(['success' => true]);
}
?>
