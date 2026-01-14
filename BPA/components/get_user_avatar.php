<?php
/**
 * Get User Avatar HTML
 * Returns HTML for rendering a user's profile picture or initials
 */

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user_id is provided
if (!isset($_GET['user_id'])) {
    echo json_encode(['error' => 'No user_id provided']);
    exit;
}

$userId = (int)$_GET['user_id'];

require_once __DIR__ . '/../database/DatabaseConnection.php';

$db = new DatabaseConnection();
$conn = $db->connection;

// Fetch profile picture and username
$sql = "SELECT p.profile_filepath, u.user_username 
        FROM profile p 
        RIGHT JOIN user u ON p.user_id = u.user_id 
        WHERE u.user_id = ? 
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();
$db->closeConnection();

$profilePicture = $row['profile_filepath'] ?? null;
$username = $row['user_username'] ?? 'User';
$initials = strtoupper(substr($username, 0, 1));

$html = '';

if ($profilePicture) {
    // Convert BPA/post/uploads/file.jpg to ../post/uploads/file.jpg
    $imgSrc = (strpos($profilePicture, 'BPA/') === 0) ? '../' . substr($profilePicture, 4) : $profilePicture;
    $html = '<img src="' . htmlspecialchars($imgSrc) . '" alt="' . htmlspecialchars($username) . '" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;" />';
} else {
    // Display initials fallback
    $html = '<span style="display:flex; align-items:center; justify-content:center; width:100%; height:100%; font-weight:600; font-size:0.8rem; color:white;">' . htmlspecialchars($initials) . '</span>';
}

echo json_encode([
    'success' => true,
    'html' => $html
]);
?>
