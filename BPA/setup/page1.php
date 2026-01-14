<?php
session_start();
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';

$db = new DatabaseConnection();
$conn = $db->connection;

$user = new User();

$profileData = [
  'firstName' => '',
  'lastName' => '',
  'phone' => '',
  'bio' => '',
  'avatarUrl' => ''
];

//If userid exists in $_SESSION, then account is being updated. 
//Otherwise, a new account is being created. 
//We will use this page to insert and update user accounts. 
if (!empty($_SESSION['user_id'])) {

  $user->populate($_SESSION['user_id']);

  $profileStmt = $conn->prepare("SELECT user_firstname, user_lastname, profile_filepath, profile_summary, phone FROM profile WHERE user_id = ? LIMIT 1");
  if ($profileStmt) {
    $profileStmt->bind_param('i', $_SESSION['user_id']);
    $profileStmt->execute();
    $result = $profileStmt->get_result();
    if ($row = $result->fetch_assoc()) {
      $profileData['firstName'] = $row['user_firstname'] ?? '';
      $profileData['lastName'] = $row['user_lastname'] ?? '';
      $profileData['phone'] = $row['phone'] ?? '';
      $profileData['bio'] = $row['profile_summary'] ?? '';
      if (!empty($row['profile_filepath'])) {
        // Convert BPA/post/uploads/file.jpg to ../post/uploads/file.jpg
        $path = $row['profile_filepath'];
        $profileData['avatarUrl'] = (strpos($path, 'BPA/') === 0) ? '../' . substr($path, 4) : $path;
      }
    }
    $profileStmt->close();
  }
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
// Page 1 — Basic Info Input
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SkillSwap — Creating your Account (1/4)</title>
  <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>" />
</head>
<body data-step="1">
  <div class="setup-shell">
    <div class="header">
      <div class="logo">SkillSwap</div>
    </div>

    <h1 class="step-title">Creating your Account/Editing Account</h1>
    <p class="subtitle">The Basics and Profile</p>

    <form id="basicInfoForm" action="save_profile.php" method="post" enctype="multipart/form-data" novalidate>
      <div class="form-stack">
        <div class="two-cols">
          <div class="input-card">
            <label class="input-label" for="firstName">First Name <span class="optional">optional</span></label>
            <input id="firstName" name="firstName" type="text" placeholder="Enter your first name" value="<?php echo htmlspecialchars($profileData['firstName'], ENT_QUOTES); ?>" />
            <div class="hint">Add your first name if you want it shown on your profile.</div>
          </div>
          <div class="input-card">
            <label class="input-label" for="lastName">Last Name <span class="optional">optional</span></label>
            <input id="lastName" name="lastName" type="text" placeholder="Enter your last name" value="<?php echo htmlspecialchars($profileData['lastName'], ENT_QUOTES); ?>" />
            <div class="hint">Add your last name if you want it shown on your profile.</div>
          </div>
        </div>

        <div class="two-cols">
          <div class="input-card">
            <span class="input-label">Profile Picture <span class="optional">optional</span></span>
            <div class="avatar-upload">
              <div class="avatar" id="avatarPreview" aria-label="Profile preview" data-existing-avatar="<?php echo htmlspecialchars($profileData['avatarUrl'], ENT_QUOTES); ?>">👤</div>
              <input type="file" id="avatar" name="avatar" accept="image/*" />
            </div>
            <div class="hint">Add a clear image of yourself.</div>
          </div>
          <div class="input-card">
            <label class="input-label" for="phone">Phone Number <span class="optional">optional</span></label>
            <input id="phone" name="phone" type="tel" placeholder="e.g., (555) 123-4567" value="<?php echo htmlspecialchars($profileData['phone'], ENT_QUOTES); ?>" />
            <div class="hint">We may use this to receive messages from mentors.</div>
          </div>
        </div>

        <div class="input-card">
          <label class="input-label" for="bio">Biography / Summary <span class="optional">optional</span></label>
          <textarea id="bio" name="bio" placeholder="Introduce yourself to fellow learners. Add a few sentences about your background, interests, or goals."><?php echo htmlspecialchars($profileData['bio'], ENT_QUOTES); ?></textarea>
          <div class="hint">Share a short intro so others can get to know you.</div>
        </div>
      </div>

      <div class="nav-bar">
        <div class="spacer"></div>
        <button class="btn btn-primary" id="next-1" type="submit">Next</button>
      </div>

      <div class="progress" aria-label="Progress">
        <span class="dot active"></span><span class="dot"></span><span class="dot"></span><span class="dot"></span>
      </div>
    </form>
  </div>
  <script src="script.js?v=<?php echo filemtime(__DIR__ . '/script.js'); ?>"></script>
</body>
</html>
