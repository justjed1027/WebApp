<?php 
session_start();
require_once '../database/DatabaseConnection.php';
require_once '../database/User.php';
require_once '../database/Connection.php';
require_once 'send_request.php';


$pendingSql = "
    SELECT c.connection_id, u.name 
    FROM connections c
    JOIN users u ON u.user_id = c.requester_id
    WHERE c.receiver_id = ? AND c.status = 'pending'
";

$stmt = $con->prepare($pendingSql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pending = $stmt->get_result();

echo "<h2>Connection Requests</h2>";
while ($req = $pending->fetch_assoc()) {
    echo "<p>{$req['name']} wants to connect.</p>";
}
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
       <?php
$userId = $_SESSION['user_id'];
$recommended = $user->getRecommendedUsers($userId);
?>

<h2>People You May Know</h2>

<?php while ($row = $recommended->fetch_assoc()): ?>
    <div class="user-card">
        <p><strong><?= $row['name'] ?></strong></p>

        <form action="send_request.php" method="POST">
            <input type="hidden" name="receiver_id" value="<?= $row['user_id'] ?>">
            <button type="submit">Connect</button>
        </form>
    </div>
<?php endwhile; ?>
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