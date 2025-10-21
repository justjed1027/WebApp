<?php
session_start();
require_once '..//database/DatabaseConnection.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to post.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $content = trim($_POST["content"]);

    if (!empty($content)) {
        $db = new DatabaseConnection();
        $conn = $db->connection;

        $user_id = $_SESSION["user_id"];
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $content);

        if ($stmt->execute()) {
            header("Location: post.php"); // redirect to the main forum
            exit;
        } else {
            echo "Error posting: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();
    } else {
        echo "Post content cannot be empty.";
    }
}
?>
