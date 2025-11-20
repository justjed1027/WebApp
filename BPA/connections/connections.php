<?php 
session_start();
require_once '../database/DatabaseConnection.php';
require_once '../database/User.php';
require_once '../database/Connection.php';

$db = new DatabaseConnection();
$con = $db->connection;

$pendingSql = "
  SELECT c.connection_id, u.user_username 
  FROM connections c
  JOIN user u ON u.user_id = c.requester_id
  WHERE c.receiver_id = ? AND c.status = 'pending'
";

$stmt = $con->prepare($pendingSql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pending = $stmt->get_result();





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
  <title>Connections</title>
  <link rel="stylesheet" href="connections.css">
</head>
<body>
  <div class="connections-container">
    <div class="main-left">
      <input type="text" class="search-bar" placeholder="Search connections...">
      <div class="card">
        <h3>Connection Requests</h3>
        <?php
        if ($pending && $pending->num_rows > 0) {
            while ($req = $pending->fetch_assoc()) {
                ?>
                <div class="conn-request">
                  <div class="conn-avatar"></div>
                  <div class="conn-info">
                    <div class="conn-name"><?php echo htmlspecialchars($req['user_username']); ?></div>
                  </div>
                  <div class="conn-actions">
                    <form action="respond_request.php" method="POST" style="display:inline">
                      <input type="hidden" name="connection_id" value="<?php echo $req['connection_id']; ?>">
                      <input type="hidden" name="action" value="accept">
                      <button type="submit" class="btn-accept">✔</button>
                    </form>
                    <form action="respond_request.php" method="POST" style="display:inline; margin-left:6px;">
                      <input type="hidden" name="connection_id" value="<?php echo $req['connection_id']; ?>">
                      <input type="hidden" name="action" value="decline">
                      <button type="submit" class="btn-decline">✖</button>
                    </form>
                  </div>
                </div>
                <?php
            }
        } else {
            echo '<p>No pending requests.</p>';
        }
        ?>
      </div>
      <div class="card">
         <?php
        $userId = $_SESSION['user_id'];
        $connObj = new Connection($db->connection);
        $connections = $connObj->getConnections($userId);
        ?>

      <h3>My Connections</h3>

      <style>
        /* simple grid: 3 columns, responsive down to 2/1 */
        .my-connections {
          display: grid;
          grid-template-columns: repeat(3, 1fr);
          gap: 12px;
          align-items: start;
        }
        .conn-card {
          display: flex;
          align-items: center;
          padding: 10px;
          border-radius: 6px;
          background: #fff;
          box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .conn-avatar {
          width: 48px;
          height: 48px;
          border-radius: 50%;
          background: #ddd;
          margin-right: 12px;
          flex: 0 0 48px;
        }
        @media (max-width: 900px) {
          .my-connections { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 520px) {
          .my-connections { grid-template-columns: 1fr; }
        }
      </style>

      <?php if ($connections && $connections->num_rows > 0): ?>
        <div class="my-connections">
          <?php while ($row = $connections->fetch_assoc()): ?>
        <div class="conn-card">
          <div class="conn-avatar" aria-hidden="true"></div>
          <div>
            <div class="conn-name"><?php echo htmlspecialchars($row['user_username']); ?></div>
            <div class="conn-role">Role Placeholder</div>
          </div>
        </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <p>No connections yet.</p> 
      <?php endif; ?>
      </div>

      <div class="card">
         <?php
        $userId = $_SESSION['user_id'];
        $connObj = new Connection($db->connection);
        $recommended = $connObj->getRecommendedUsers($userId);
        ?>

      <h2>People You May Know</h2>

      <?php if ($recommended && $recommended->num_rows > 0): ?>
        <?php while ($row = $recommended->fetch_assoc()): ?>
          <div class="user-card">
            <p><strong><?php echo htmlspecialchars($row['user_username']); ?></strong></p>

            <form action="send_request.php" method="POST">
              <input type="hidden" name="receiver_id" value="<?php echo htmlspecialchars($row['user_id']); ?>">
              <button type="submit">Connect</button>
            </form>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No recommendations at this time.</p>
      <?php endif; ?>
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