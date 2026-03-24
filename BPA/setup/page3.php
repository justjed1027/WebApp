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

require_once '../database/DatabaseConnection.php';
require_once '../database/User.php';

$error = isset($_GET['error']) ? $_GET['error'] : '';

$db = new DatabaseConnection();
$conn = $db->connection;

// Fetch categories except the skipped one
$categories_query = "
    SELECT category_id, category_name
    FROM subjectcategories
    WHERE category_id != 3
    ORDER BY category_name ASC
";
$categories_result = $conn->query($categories_query);

$categories = [];
while ($row = $categories_result->fetch_assoc()) {
  $categories[] = $row;
}

// Fetch subjects grouped by category
$subjects_query = "
    SELECT subject_id, subject_name, category_id
    FROM subjects
    WHERE category_id != 3
    ORDER BY category_id, subject_name ASC
";
$subjects_result = $conn->query($subjects_query);

// Group subjects by category_id for easy display
$subjects_by_category = [];
while ($row = $subjects_result->fetch_assoc()) {
    $subjects_by_category[$row['category_id']][] = $row;
}

// Fetch previously selected interests for this user
$user_id = $_SESSION['user_id'] ?? null;
$selected_interests = [];
if ($user_id) {
    $interests_query = "SELECT ui_subject_id FROM user_interests WHERE ui_user_id = ?";
    $stmt = $conn->prepare($interests_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $interests_result = $stmt->get_result();
    while ($row = $interests_result->fetch_assoc()) {
        $selected_interests[] = $row['ui_subject_id'];
    }
    $stmt->close();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SkillSwap — Creating your Account (3/4)</title>
  <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>" />
</head>
<body data-step="3">
  <div class="setup-shell">
  <div class="header"><div class="logo">SkillSwap</div></div>
  <h1 class="step-title">Skills Sought After</h1>
  <p class="subtitle">What skills interest you?</p>

  <div
    class="setup-alert<?php echo $error === 'interests_required' ? ' is-visible' : ''; ?>"
    id="interestsAlert"
    role="alert"
    aria-live="polite"
  >
    Please select at least one interest before continuing.
  </div>

  <!-- Form starts here -->
  <form action="learn_skills.php" method="POST" id="learnSkillsForm">
    <div class="form-stack">
      <div class="input-card">
        <div class="panel-wrap">
          <div class="courses-panel" id="coursesPanel" tabindex="0">
            <div class="search-row">
              <input id="courseSearch" type="text" placeholder="Search skills by name..." aria-label="Search skills" />
              <div class="filters">
                <select id="topicFilter" aria-label="Filter by topic">
                  <option value="all">All Topics</option>
                  <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category['category_name']); ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                  <?php endforeach; ?>
                </select>
                <select id="sortBy" aria-label="Sort skills">
                  <option value="popular">Most Popular</option>
                  <option value="alpha">Alphabetical</option>
                  <option value="new">Newest</option>
                </select>
              </div>
            </div>

            <div id="categories" class="grid">
              <?php
                foreach ($categories as $cat):
                  $cat_id = $cat['category_id'];
                  $cat_name = htmlspecialchars($cat['category_name']);
                  echo "<div class='category' data-topic='{$cat_name}'>";
                  echo "<h3>{$cat_name}</h3>";

                  if (isset($subjects_by_category[$cat_id])) {
                      foreach ($subjects_by_category[$cat_id] as $subject) {
                          $subject_id = $subject['subject_id'];
                          $subject_name = htmlspecialchars($subject['subject_name']);
                          $is_checked = in_array($subject_id, $selected_interests) ? 'checked' : '';
                          $courseClasses = 'course' . ($is_checked ? ' selected' : '');
                          echo "<label class='{$courseClasses}' data-name='{$subject_name}'>";
                          echo "<input type='checkbox' name='subjects[]' value='{$subject_id}' {$is_checked}>";
                          echo "<span class='course-label'>{$subject_name}</span>";
                          echo "</label>";
                      }
                  } else {
                      echo "<p>No subjects available for this category.</p>";
                  }

                  echo "</div>";
              endforeach;
              ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Only the "Next" button should be inside the form -->
    <div class="nav-bar">
      <button type="submit" name="nav" value="back" class="btn btn-ghost">Back</button>
      <div class="spacer"></div>
      <button type="submit" name="nav" value="next" class="btn btn-primary" id="interestsNext">Next</button>
    </div>
  </form>
  <!-- Form ends here -->

  <div class="progress" aria-label="Progress">
    <span class="dot"></span><span class="dot"></span><span class="dot active"></span><span class="dot"></span>
  </div>
</div>
  <script src="script.js?v=<?php echo filemtime(__DIR__ . '/script.js'); ?>"></script>
</body>
</html>
