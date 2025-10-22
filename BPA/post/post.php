<?php
session_start();
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';

// Function to convert timestamp to "time ago" format
function timeAgo($timestamp) {
  $datetime = new DateTime($timestamp);
  $now = new DateTime();
  $interval = $now->diff($datetime);
    
  if ($interval->y > 0) {
    return $interval->y . " year" . ($interval->y > 1 ? "s" : "");
  }
  if ($interval->m > 0) {
    return $interval->m . " month" . ($interval->m > 1 ? "s" : "");
  }
  if ($interval->d > 0) {
    return $interval->d . " day" . ($interval->d > 1 ? "s" : "");
  }
  if ($interval->h > 0) {
    return $interval->h . " hour" . ($interval->h > 1 ? "s" : "");
  }
  if ($interval->i > 0) {
    return $interval->i . " minute" . ($interval->i > 1 ? "s" : "");
  }
  return "just now";
}

$db = new DatabaseConnection();
$conn = $db->connection;

$sql = "SELECT posts.post_id, posts.user_id, posts.content, posts.created_at, COALESCE(user.user_username, '') AS user_username 
  FROM posts 
  LEFT JOIN user ON posts.user_id = user.user_id
  ORDER BY posts.created_at DESC";
$result = $conn->query($sql);

// Debug helper: append ?debug=1 to URL to see query status
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
  if ($result === false) {
    echo '<pre>SQL Error: ' . htmlspecialchars($conn->error) . '</pre>';
  } else {
    echo '<pre>SQL OK — rows: ' . intval($result->num_rows) . "\n";
    // show first row sample
    if ($result->num_rows > 0) {
      $row = $result->fetch_assoc();
      echo 'First row: ' . htmlspecialchars(print_r($row, true));
      // rewind result pointer
      $result->data_seek(0);
    }
    echo '</pre>';
  }
}

$posts = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
  }
}

$user = new User();

//If userid exists in $_SESSION, then account is being updated. 
//Otherwise, a new account is being created. 
//We will use this page to insert and update user accounts. 
if (!empty($_SESSION['user_id'])) {

  $user->populate($_SESSION['user_id']);
} else {
  header('location: ../landing/landing.php');
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {

  if (!empty($_GET['action']) && $_GET['action'] == 'logout') {

    $_SESSION = [];
    session_destroy();
    setcookie("PHPSESSID", "", time() - 3600, "/");
    header('location: ../landing/landing.php');
  }
}



?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkillSwap</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <br><br>
  <a href="post.php?action=logout">Sign Out</a>
  <form action="delete_oldest_post.php" method="POST" style="display:inline;">
    <button type="submit" style="margin-left:16px;">Delete Oldest Post</button>
  </form>
  <!-- Header -->
  <header class="header">
    <div class="logo">SkillSwap</div>
    <input type="text" class="search-bar" placeholder="Search for skills, topics, or users...">
    <div class="header-icons">
      <button>🔔</button>
      <button>👥</button>
      <button>💬</button>
      <button>➕</button>
      <img src="profile.jpg" alt="Profile" class="profile-pic">
    </div>
  </header>

  <!-- Layout -->
  <main class="layout">
    <!-- Left Panel -->
    <section class="left-panel">
      <!-- Topics -->
      <div class="topics">
        <div class="topic">Topic 1</div>
        <div class="topic">Topic 2</div>
        <div class="topic">Topic 3</div>
        <div class="topic">Topic 4</div>
        <div class="topic">Topic 5</div>
        <div class="topic">Topic 6</div>
      </div>

      <!-- Create Post -->
      <div class="create-post">
        <img src="profile.jpg" alt="User" class="profile-pic">
        <input type="text" placeholder="Ask a question or share something helpful...">
        <div class="post-options">
          <button type="button" class="post-type-btn" data-type="photo">📷 Photo</button>
          <button type="button" class="post-type-btn" data-type="video">🎥 Video</button>
          <button type="button" class="post-type-btn" data-type="document">📄 Document</button>
          <a href="create-post.php">
            <button class="create-post-btn">Create New Post</button>
          </a>
        </div>
      </div>

      <!-- Posts Feed -->
      <div id="posts-container">
        <?php if (count($posts) === 0): ?>
          <div class="post" style="background:#fff3cd;border:1px solid #ffeeba;padding:16px;margin-bottom:16px;">No posts yet.</div>
        <?php else: ?>
          <?php foreach ($posts as $post): ?>
            <div class="post" style="background:#fff;border-radius:8px;padding:16px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
              <div class="post-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <?php $displayName = !empty($post['user_username']) ? $post['user_username'] : ('User #' . intval($post['user_id'])); ?>
                <span class="post-user" style="font-weight:bold;"><?= htmlspecialchars($displayName) ?></span>
                <?php $t = timeAgo($post['created_at']); ?>
                <span class="post-time" style="color:#666;font-size:0.9em;"><?= $t === 'just now' ? $t : htmlspecialchars($t . ' ago') ?></span>
              </div>
              <div class="post-content" style="color:#333;line-height:1.5;">
                <p style="margin:0;"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
   

    <!-- Right Panel -->
    <aside class="right-panel">
      <!-- Profile -->
      <div class="profile-card">
        <img src="profile.jpg" alt="User" class="profile-pic">
        <h3><?= $user->user_username ?></h3>
        <p class="field">Computer Science</p>
        <div class="stats">
          <span><strong>42</strong> Posts</span>
          <span><strong>128</strong> Followers</span>
          <span><strong>97</strong> Following</span>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="navigation">
        <a href="#">📄 Posts</a>
        <a href="#">📊 Dashboard</a>
        <a href="#">🎓 Courses</a>
        <a href="#">📅 Events</a>
        <a href="#">🗓️ Calendar</a>
      </nav>

      <!-- Suggested Collaborators -->
      <div class="suggested">
        <h4>Suggested Collaborators</h4>
        <ul>
          <li>Emily Chen <small>· Data Science</small></li>
          <li>Marcus Johnson <small>· Mechanical Eng.</small></li>
          <li>Sophia Williams <small>· Graphic Design</small></li>
        </ul>
      </div>
    </aside>
  </main>

  <script src="script.js"></script>
</body>

</html>