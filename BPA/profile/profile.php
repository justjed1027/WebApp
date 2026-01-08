<?php
session_start();
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header('Location: ../landing/landing.php');
    exit;
}

$user = new User();
$user->populate($_SESSION['user_id']);

// Fetch profile data from database
$db = new DatabaseConnection();
$conn = $db->connection;

$profileSql = "SELECT user_firstname, user_lastname, phone, profile_summary, profile_filepath FROM profile WHERE user_id = ? LIMIT 1";
$profileStmt = $conn->prepare($profileSql);

$firstName = null;
$lastName = null;
$phone = null;
$bio = null;
$profilePicture = null;

if ($profileStmt) {
    $profileStmt->bind_param('i', $_SESSION['user_id']);
    $profileStmt->execute();
    $profileStmt->bind_result($firstName, $lastName, $phone, $bio, $profilePicture);
    $profileStmt->fetch();
    $profileStmt->close();
}

// Fetch skills from user_skills table 
$skillsSql = "SELECT s.subject_name 
              FROM user_skills us 
              JOIN subjects s ON us.us_subject_id = s.subject_id 
              WHERE us.us_user_id = ? 
              ORDER BY s.subject_name ASC";
$skillsStmt = $conn->prepare($skillsSql);
$skills = [];

if ($skillsStmt) {
    $skillsStmt->bind_param('i', $_SESSION['user_id']);
    $skillsStmt->execute();
    $skillsResult = $skillsStmt->get_result();
    while ($row = $skillsResult->fetch_assoc()) {
        $skills[] = $row['subject_name'];
    }
    $skillsStmt->close();
}

// Fetch interests from user_interests table 
$interestsSql = "SELECT s.subject_name 
                 FROM user_interests ui 
                 JOIN subjects s ON ui.ui_subject_id = s.subject_id 
                 WHERE ui.ui_user_id = ? 
                 ORDER BY s.subject_name ASC";
$interestsStmt = $conn->prepare($interestsSql);
$interests = [];

if ($interestsStmt) {
    $interestsStmt->bind_param('i', $_SESSION['user_id']);
    $interestsStmt->execute();
    $interestsResult = $interestsStmt->get_result();
    while ($row = $interestsResult->fetch_assoc()) {
        $interests[] = $row['subject_name'];
    }
    $interestsStmt->close();
}

$db->closeConnection();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — <?php echo htmlspecialchars($user->user_username); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="profile-container">
        <a href="../courses/courses.php" class="back-link">← Back to Dashboard</a>
        
        <div class="profile-card">
            <div class="profile-header">
                <?php if ($profilePicture): ?>
                    <img src="/WebApp/<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture" class="profile-avatar-large" />
                <?php else: ?>
                    <div class="profile-avatar-large">
                        <?php echo htmlspecialchars(mb_strtoupper(mb_substr($user->user_username, 0, 1))); ?>
                    </div>
                <?php endif; ?>
                <div class="profile-info">
                    <h1><?php 
                        $displayName = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
                        echo htmlspecialchars($displayName ?: $user->user_username); 
                    ?></h1>
                    <?php if ($displayName): ?>
                        <p style="color:#888; font-size:0.95rem;">Username:<?php echo htmlspecialchars($user->user_username); ?> </p>
                    <?php endif; ?>
                    <p><?php echo htmlspecialchars($user->user_email); ?></p>
                    <p style="color:#999; font-size:0.9rem;">Member since: <?php echo htmlspecialchars($user->user_create_date ?? 'N/A'); ?></p>
                </div>
            </div>

            <div class="profile-section">
                <h2>About</h2>
                <?php if ($bio): ?>
                    <div class="profile-field">
                        <label>Bio</label>
                        <div class="value"><?php echo nl2br(htmlspecialchars($bio)); ?></div>
                    </div>
                <?php else: ?>
                    <p style="color:#999; font-size:0.95rem; font-style:italic;">No bio added yet. Complete your profile in the setup flow.</p>
                <?php endif; ?>
                
                <?php if ($phone): ?>
                    <div class="profile-field">
                        <label>Phone Number</label>
                        <div class="value"><?php echo htmlspecialchars($phone); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($skills)): ?>
                <div class="profile-section">
                    <h2>Skills</h2>
                    <div class="skills-list">
                        <?php foreach ($skills as $skill): ?>
                            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($interests)): ?>
                <div class="profile-section">
                    <h2>Interests</h2>
                    <div class="interests-list">
                        <?php foreach ($interests as $interest): ?>
                            <span class="interest-tag"><?php echo htmlspecialchars($interest); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
