<?php
session_start();
require_once '..//database/DatabaseConnection.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to post.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $content = trim($_POST["content"]);
    
    // Count words in content
    $word_count = str_word_count($content);
    $max_words = 500;

    if (!empty($content) && $word_count <= $max_words) {
        $db = new DatabaseConnection();
        $conn = $db->connection;

        $user_id = $_SESSION["user_id"];
        $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $content);

        if ($stmt->execute()) {
            header("Location: post.php"); // redirect to the main post
            exit;
        } else {
            echo "Error posting: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();
    } elseif ($word_count > $max_words) {
        echo "Post content exceeds the maximum limit of 500 words. Current word count: " . $word_count;
    } else {
        echo "Post content cannot be empty.";
    }
}
?>
