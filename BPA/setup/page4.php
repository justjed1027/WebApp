<?php
session_start();

// Handle logout FIRST, before any includes or output
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  if (!empty($_GET['action']) && $_GET['action'] == 'logout') {
    $_SESSION = [];
    session_destroy();
    setcookie("PHPSESSID", "", time() - 3600, "/");
    header('location: ../landing/landing.php');
    exit();
  }
}

require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';
require_once '../database/UserPreferences.php';





$user = new User();

//If userid exists in $_SESSION, then account is being updated. 
//Otherwise, a new account is being created. 
//We will use this page to insert and update user accounts. 
if (!empty($_SESSION['user_id'])) {

  $user->populate($_SESSION['user_id']);
} else {
  header('location: ../landing/landing.php');
}

$db = new DatabaseConnection();
$conn = $db->connection;
$prefs = UserPreferences::getForUser($conn, (int) $_SESSION['user_id']);
$db->closeConnection();

$selectedTheme = $prefs['theme'] ?? 'mixed';
$selectedColor = $prefs['primary_color'] ?? '#00D97E';
$selectedNavigationMode = $prefs['navigation_mode'] ?? 'sidebar';
$selectedHomePreference = $prefs['home_preference'] ?? 'dashboard2';
$allowedColors = UserPreferences::getAllowedColors();

// Page 4 — Choose Colors and Preferences
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SkillSwap — Creating your Account (4/4)</title>
  <link rel="icon" type="image/png" href="../images/skillswaplogotrans.png" />
  <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>" />
</head>
<body data-step="4">
  <div class="setup-shell">
    <div class="header"><div class="logo">SkillSwap</div></div>
    <h1 class="step-title">Creating your Account/Editing Account</h1>
    <p class="subtitle">Choose your theme and primary color</p>

    <div class="form-stack">
      <div class="input-card">
        <div class="colors-row" id="colorRow">
          <?php foreach ($allowedColors as $color): ?>
            <?php $hexColor = UserPreferences::toHexColor($color); ?>
            <button
              class="swatch<?php echo strcasecmp($selectedColor, $color) === 0 ? ' selected' : ''; ?>"
              data-color="<?php echo htmlspecialchars($color); ?>"
              style="background:<?php echo htmlspecialchars($hexColor); ?>"
              title="<?php echo htmlspecialchars($color); ?>"
              type="button"
            ></button>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="input-card">
        <div class="theme-previews">
          <button class="theme-card<?php echo $selectedTheme === 'mixed' ? ' selected' : ''; ?>" data-theme="mixed" type="button">
            <div class="preview mixed"></div>
            <div>Explore Preference</div>
            <small>Default mixed mode</small>
          </button>
          <button class="theme-card<?php echo $selectedTheme === 'light' ? ' selected' : ''; ?>" data-theme="light" type="button">
            <div class="preview light"></div>
            <div>Only Light Mode</div>
          </button>
          <button class="theme-card<?php echo $selectedTheme === 'dark' ? ' selected' : ''; ?>" data-theme="dark" type="button">
            <div class="preview dark"></div>
            <div>Only Dark Mode</div>
          </button>
        </div>
      </div>

      <div class="input-card">
        <h3 style="margin:0 0 14px 0; font-size:1.05rem; color:var(--text-primary, #0f172a);">Navigation Style</h3>
        <div class="theme-previews nav-previews">
          <button class="theme-card nav-mode-card<?php echo $selectedNavigationMode === 'sidebar' ? ' selected' : ''; ?>" data-navigation="sidebar" type="button">
            <div class="preview nav-sidebar"></div>
            <div>Sidebar Navigation</div>
            <small>Classic left sidebar</small>
          </button>
          <button class="theme-card nav-mode-card<?php echo $selectedNavigationMode === 'top' ? ' selected' : ''; ?>" data-navigation="top" type="button">
            <div class="preview nav-top"></div>
            <div>Top Navigation</div>
            <small>Shows your main menu across the top for quicker access</small>
          </button>
        </div>
      </div>

      <div class="input-card">
        <h3 style="margin:0 0 14px 0; font-size:1.05rem; color:var(--text-primary, #0f172a);">Home Dashboard</h3>
        <div class="theme-previews home-previews">
          <button class="theme-card home-mode-card<?php echo $selectedHomePreference === 'courses' ? ' selected' : ''; ?>" data-home="courses" type="button">
            <div class="preview home-courses"></div>
            <div>Original (Course Dashboard)</div>
            <small>Computer only. This was our original dashboard.</small>
          </button>
          <button class="theme-card home-mode-card<?php echo $selectedHomePreference === 'dashboard2' ? ' selected' : ''; ?>" data-home="dashboard2" type="button">
            <div class="preview home-universal"></div>
            <div>Universal</div>
            <small>Resizes best for phones.</small>
          </button>
        </div>
      </div>
    </div>

    <div class="nav-bar">
      <a class="btn btn-primary" href="page3.php">Back</a>
      <div class="spacer"></div>
      <button id="finish" class="btn btn-primary" type="button">Finish</button>
    </div>

    <div class="progress" aria-label="Progress">
      <span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="dot active"></span>
    </div>
  </div>
  <script src="script.js?v=<?php echo filemtime(__DIR__ . '/script.js'); ?>"></script>
</body>
</html>
