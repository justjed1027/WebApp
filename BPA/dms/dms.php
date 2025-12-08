<?php
require_once '../database/DatabaseConnection.php';
require_once '../database/User.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../landing/landing.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DMs</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="dm-container">
    <div class="dm-sidebar">
      <div class="dm-sidebar-header">
        <a href="../post/post.php" class="dm-back-btn" title="Back to Posts">← Posts</a>
        <h2 style="margin:0;font-size:1.2rem;color:#fff;">Messages</h2>
      </div>
      <div class="dm-search">
        <input type="text" id="searchInput" placeholder="Search messages...">
      </div>
      <div class="dm-list" id="conversationList">
        <!-- Conversations will be loaded here by JavaScript -->
        <div class="dm-empty-state">Loading conversations...</div>
      </div>
    </div>
    <div class="dm-main">
      <div class="dm-header" id="chatHeader" style="display: none;">
        <div class="dm-header-avatar" id="headerAvatar"></div>
        <div class="dm-header-info">
          <div class="dm-header-name" id="headerName"></div>
          <div class="dm-header-status">Online</div>
        </div>
        <div class="dm-header-actions">
          <button title="Call">📞</button>
          <button title="Video">🎥</button>
          <button title="Info">ℹ️</button>
          <button title="More">⋯</button>
        </div>
      </div>
      <div class="dm-messages" id="messagesContainer">
        <!-- Messages will be loaded here by JavaScript -->
        <div class="dm-empty-state">Select a conversation to start messaging</div>
      </div>
      <div class="dm-input-row" id="messageInput" style="display: none;">
        <input type="text" class="dm-input" id="messageText" placeholder="Type a message...">
        <button class="dm-send-btn" id="sendBtn" title="Send">&#10148;</button>
      </div>
    </div>
  </div>
  <script>
    // Check if coming from connections page with user_id
    const urlParams = new URLSearchParams(window.location.search);
    window.startUserId = urlParams.has('user_id') ? parseInt(urlParams.get('user_id')) : null;
    console.log('URL parameter user_id:', window.startUserId);
  </script>
  <script src="script.js"></script>
</body>
</html>