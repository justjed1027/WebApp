<?php
    session_start();
    require_once '../database/User.php';
    
    $user = new User(); 

    //If userid exists in $_SESSION, then account is being updated. 
    //Otherwise, a new account is being created. 
    //We will use this page to insert and update user accounts. 
    if(!empty($_SESSION['user_id'])){

        $user->populate($_SESSION['user_id']);  
    }else{
        header ('location: ../landing/landing.php');
    }

    if($_SERVER["REQUEST_METHOD"] == "GET") {

        if(!empty($_GET['action']) && $_GET['action'] == 'logout'){

            $_SESSION = [];
            session_destroy();
            setcookie("PHPSESSID", "", time()-3600, "/");
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
          <button>📷 Photo</button>
          <button>🎥 Video</button>
          <button>📄 Document</button>
        </div>
      </div>

      <!-- Posts Feed -->
      <div id="posts-container"></div>
    </section>

    <!-- Right Panel -->
    <aside class="right-panel">
      <!-- Profile -->
      <div class="profile-card">
        <img src="profile.jpg" alt="User" class="profile-pic">
        <h3><?=$user->user_username?></h3>
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
