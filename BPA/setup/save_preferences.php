<?php
session_start();
header('Content-Type: application/json');

require_once '../database/DatabaseConnection.php';
require_once '../database/UserPreferences.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to save preferences.'
    ]);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$theme = $data['theme'] ?? 'mixed';
$primaryColor = $data['primary_color'] ?? '#00D97E';
$navigationMode = $data['navigation_mode'] ?? 'sidebar';
$homePreference = $data['home_preference'] ?? 'dashboard2';

$db = new DatabaseConnection();
$conn = $db->connection;

$result = UserPreferences::saveForUser($conn, (int) $_SESSION['user_id'], $theme, $primaryColor, $navigationMode, $homePreference);
$db->closeConnection();

if (!$result['success']) {
    http_response_code(400);
}

echo json_encode($result);
exit;
