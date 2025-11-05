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
  <title>SkillSwap - Create Post</title>
  <link rel="stylesheet" href="style.css?v=nav-20251022">
</head>
<body>
  <h2>Write a New Post</h2>
      <div class="create-post-card">
      <div class="create-post-header">
        <div class="user-avatar-small">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
          </svg>
        </div>
            <form id="inline-post-form" action="create-post.php" method="POST">
              <textarea name="content" class="create-post-input" placeholder="Ask a question or share something helpful..." rows="3"></textarea>
      </div>
          <div class="create-post-actions">
            <button type="submit" class="create-post-btn">Post</button>
            </form>
          </div>
    </div>
</body>
</html>
