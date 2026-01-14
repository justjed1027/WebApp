<?php
session_start();
require_once '../database/DatabaseConnection.php';

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$user_id = $_SESSION['user_id'];
$selected_subjects = $_POST['subjects'] ?? []; // Get selected subject IDs
$nav = $_POST['nav'] ?? 'next';

// Enforce at least one skill when moving forward
if ($nav === 'next' && empty($selected_subjects)) {
    header("Location: page2.php?error=skills_required");
    exit;
}

$db = new DatabaseConnection();
$conn = $db->connection;

// Clear previous skills (if user revisits this page)
$conn->query("DELETE FROM user_skills WHERE us_user_id = $user_id");

// Insert new ones (if any)
if (!empty($selected_subjects)) {
    $stmt = $conn->prepare("INSERT INTO user_skills (us_user_id, us_subject_id) VALUES (?, ?)");
    foreach ($selected_subjects as $subject_id) {
        $stmt->bind_param("ii", $user_id, $subject_id);
        $stmt->execute();
    }
    $stmt->close();
}

$conn->close();

// Redirect based on nav intent
if ($nav === 'back') {
    header("Location: page1.php");
} else {
    header("Location: page3.php");
}
exit;
?>