<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Read optional type parameter
$type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '';
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Post</title>
</head>
<body>
  <h2>Write a New Post</h2>
  <form action="post_submit.php" method="POST">
    
    <textarea name="content" rows="5" cols="50" placeholder="Write your post here..." required></textarea>
    
    <br>
    <button type="submit">Post</button>
  </form>
  
</body>
</html>
