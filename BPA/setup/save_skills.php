<?php
session_start();
require_once '../database/DatabaseConnection.php';

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$user_id = $_SESSION['user_id'];
$selected_subjects = $_POST['subjects'] ?? []; // Get selected subject IDs

if (empty($selected_subjects)) {
    header("Location: page3.php"); // Nothing selected, move on
    exit;
}

$db = new DatabaseConnection();
$conn = $db->connection;

// Optional: clear previous skills (if user revisits this page)
$conn->query("DELETE FROM bpa_skillswap.user_skills WHERE us_user_id = $user_id");

// Insert new ones
$stmt = $conn->prepare("INSERT INTO bpa_skillswap.user_skills (us_user_id, us_subject_id) VALUES (?, ?)");
foreach ($selected_subjects as $subject_id) {
    $stmt->bind_param("ii", $user_id, $subject_id);
    $stmt->execute();
}

$stmt->close();
$conn->close();

// Redirect to next page
header("Location: page3.php");
exit;
?>