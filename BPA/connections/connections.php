<?php 
session_start();
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';




?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connections</title>
  <link rel="stylesheet" href="connections.css">
</head>
<body>
  <div class="connections-container">
    <div class="main-left">
      <input type="text" class="search-bar" placeholder="Search connections...">
      <div class="card">
        <h3>Connection Requests (2)</h3>
        <div class="conn-request">
          <div class="conn-avatar avatar1"></div>
          <div class="conn-info">
            <div class="conn-name">Jamie Rivera</div>
            <div class="conn-role">Web Development</div>
            <div class="conn-mutual">3 mutual connections</div>
          </div>
          <div class="conn-actions">
            <button class="btn-accept">✔</button>
            <button class="btn-decline">✖</button>
          </div>
        </div>
        <div class="conn-request">
          <div class="conn-avatar avatar2"></div>
          <div class="conn-info">
            <div class="conn-name">Casey Thompson</div>
            <div class="conn-role">Marketing</div>
            <div class="conn-mutual">7 mutual connections</div>
          </div>
          <div class="conn-actions">
            <button class="btn-accept">✔</button>
            <button class="btn-decline">✖</button>
          </div>
        </div>
      </div>
      <div class="card">
        <h3>My Connections (3)</h3>
        <div class="my-connections">
          <div class="conn-card">
            <div class="conn-avatar avatar3"></div>
            <div>
              <div class="conn-name">Alex Johnson</div>
              <div class="conn-role">Computer Science</div>
            </div>
          </div>
          <div class="conn-card">
            <div class="conn-avatar avatar4"></div>
            <div>
              <div class="conn-name">Morgan Lee</div>
              <div class="conn-role">UX Design</div>
            </div>
          </div>
          <div class="conn-card">
            <div class="conn-avatar avatar5"></div>
            <div>
              <div class="conn-name">Taylor Wilson</div>
              <div class="conn-role">Physics</div>
            </div>
          </div>
        </div>
        <a href="#" class="view-link">View All Connections</a>
      </div>
      <div class="card">
        <h3>People You May Know</h3>
        <div class="suggested-list">
          <div class="suggested-item">
            <div class="conn-avatar avatar6"></div>
            <div>
              <div class="conn-name">Emily Chen</div>
              <div class="conn-role">Data Science</div>
              <div class="conn-mutual">4 mutual connections</div>
            </div>
            <button class="btn-connect">Connect</button>
          </div>
          <div class="suggested-item">
            <div class="conn-avatar avatar7"></div>
            <div>
              <div class="conn-name">Marcus Johnson</div>
              <div class="conn-role">Mechanical Engineering</div>
              <div class="conn-mutual">2 mutual connections</div>
            </div>
            <button class="btn-connect">Connect</button>
          </div>
          <div class="suggested-item">
            <div class="conn-avatar avatar8"></div>
            <div>
              <div class="conn-name">Sophia Williams</div>
              <div class="conn-role">Graphic Design</div>
              <div class="conn-mutual">6 mutual connections</div>
            </div>
            <button class="btn-connect">Connect</button>
          </div>
          <div class="suggested-item">
            <div class="conn-avatar avatar9"></div>
            <div>
              <div class="conn-name">Jordan Smith</div>
              <div class="conn-role">Business Administration</div>
              <div class="conn-mutual">1 mutual connection</div>
            </div>
            <button class="btn-connect">Connect</button>
          </div>
        </div>
        <a href="#" class="view-link">View More Suggestions</a>
      </div>
    </div>
    <div class="main-right">
      <div class="profile-card">
        <div class="profile-avatar avatar-main"></div>
        <div class="profile-name">Your Name</div>
        <div class="profile-role">Computer Science</div>
        <div class="profile-stats">
          <div><span>42</span><br>Posts</div>
          <div><span>128</span><br>Followers</div>
          <div><span>97</span><br>Following</div>
        </div>
      </div>
      <div class="card nav-card">
        <div class="nav-title">Navigation</div>
        <ul class="nav-list">
          <li>Posts</li>
          <li>Dashboard</li>
          <li>Courses</li>
          <li>Events</li>
          <li>Calendar</li>
        </ul>
      </div>
      <div class="card">
        <div class="side-title-row">
          <span>Suggested Collaborators</span>
          <a href="#" class="side-link">See All</a>
        </div>
        <div class="side-collab">
          <div class="side-collab-item">
            <div class="conn-avatar avatar6"></div>
            <div>
              <div class="conn-name">Emily Chen</div>
              <div class="conn-role">Data Science</div>
            </div>
            <a href="#" class="side-follow">Follow</a>
          </div>
          <div class="side-collab-item">
            <div class="conn-avatar avatar7"></div>
            <div>
              <div class="conn-name">Marcus Johnson</div>
              <div class="conn-role">Mechanical Engineering</div>
            </div>
            <a href="#" class="side-follow">Follow</a>
          </div>
          <div class="side-collab-item">
            <div class="conn-avatar avatar8"></div>
            <div>
              <div class="conn-name">Sophia Williams</div>
              <div class="conn-role">Graphic Design</div>
            </div>
            <a href="#" class="side-follow">Follow</a>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="side-title-row">
          <span>Trending Topics</span>
          <a href="#" class="side-link">See All</a>
        </div>
        <ul class="trending-list">
          <li><a href="#">#machinelearning</a> <span>1243 posts</span></li>
          <li><a href="#">#reactjs</a> <span>892 posts</span></li>
          <li><a href="#">#finalexams</a> <span>754 posts</span></li>
          <li><a href="#">#capstoneprojects</a> <span>621 posts</span></li>
          <li><a href="#">#internships</a> <span>543 posts</span></li>
        </ul>
      </div>
    </div>
  </div>
  <script src="connections.js"></script>
</body>
</html>