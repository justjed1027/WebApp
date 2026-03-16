<?php
session_start();
header('Content-Type: application/json');

require_once '../database/DatabaseConnection.php';
require_once '../database/UserPreferences.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in.'
    ]);
    exit;
}

$db = new DatabaseConnection();
$conn = $db->connection;
$prefs = UserPreferences::getForUser($conn, (int) $_SESSION['user_id']);
$db->closeConnection();

echo json_encode([
    'success' => true,
    'theme' => $prefs['theme'],
    'primary_color' => $prefs['primary_color'],
    'primary_color_hex' => $prefs['primary_color_hex'],
    'navigation_mode' => $prefs['navigation_mode']
]);
exit;
