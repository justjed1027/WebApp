<?php
session_start();
require_once '../database/DatabaseConnection.php';


$db = new DatabaseConnection();
$conn = $db->connection;

// Find the oldest post in the table
$sql = "SELECT post_id FROM posts ORDER BY created_at ASC, post_id ASC LIMIT 1";
$result = $conn->query($sql);

if ($row = $result->fetch_assoc()) {
    $oldest_post_id = $row['post_id'];
    // Delete the post
    $del_stmt = $conn->prepare("DELETE FROM posts WHERE post_id = ?");
    $del_stmt->bind_param("i", $oldest_post_id);
    $del_stmt->execute();
    $del_stmt->close();
}

$conn->close();

header("Location: post.php");
exit;
?>