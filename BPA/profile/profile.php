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

// Placeholder: fetch additional profile fields from setup/skills tables when they exist
$bio = "This is a placeholder bio. Will be populated from setup data.";
$skills = ["Skill 1", "Skill 2", "Skill 3"]; // Placeholder
$interests = ["Interest 1", "Interest 2"]; // Placeholder
$location = "City, Country"; // Placeholder
$website = "https://example.com"; // Placeholder

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — <?php echo htmlspecialchars($user->user_username); ?></title>
    <link rel="stylesheet" href="../post/style.css">
    <style>
        body { background: #0f0f0f; color: #fff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .profile-container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .profile-card { background: #fff; color: #111; border-radius: 12px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .profile-header { display: flex; gap: 20px; align-items: center; margin-bottom: 20px; }
        .profile-avatar-large { width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: 700; color: #fff; }
        .profile-info h1 { margin: 0 0 8px; font-size: 1.8rem; }
        .profile-info p { margin: 4px 0; color: #666; }
        .profile-section { margin-top: 20px; }
        .profile-section h2 { font-size: 1.3rem; margin-bottom: 12px; border-bottom: 2px solid #eee; padding-bottom: 8px; }
        .profile-field { margin: 10px 0; padding: 10px; background: #f9f9f9; border-radius: 6px; }
        .profile-field label { font-weight: 600; display: block; margin-bottom: 4px; color: #333; }
        .profile-field .value { color: #555; font-style: italic; }
        .skills-list, .interests-list { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .skill-tag, .interest-tag { background: #e3f2fd; color: #1976d2; padding: 6px 12px; border-radius: 20px; font-size: 0.9rem; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #667eea; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="profile-container">
        <a href="../post/post.php" class="back-link">← Back to Posts</a>
        
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar-large">
                    <?php echo htmlspecialchars(mb_strtoupper(mb_substr($user->user_username, 0, 1))); ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user->user_username); ?></h1>
                    <p><?php echo htmlspecialchars($user->user_email); ?></p>
                    <p style="color:#999; font-size:0.9rem;">Member since: <?php echo htmlspecialchars($user->user_create_date ?? 'N/A'); ?></p>
                </div>
            </div>

            <div class="profile-section">
                <h2>About</h2>
                <div class="profile-field">
                    <label>Bio</label>
                    <div class="value"><?php echo htmlspecialchars($bio); ?></div>
                </div>
                <div class="profile-field">
                    <label>Location</label>
                    <div class="value"><?php echo htmlspecialchars($location); ?></div>
                </div>
                <div class="profile-field">
                    <label>Website</label>
                    <div class="value"><?php echo htmlspecialchars($website); ?></div>
                </div>
            </div>

            <div class="profile-section">
                <h2>Skills</h2>
                <div class="skills-list">
                    <?php foreach ($skills as $skill): ?>
                        <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                    <?php endforeach; ?>
                </div>
                <p style="color:#999; font-size:0.9rem; margin-top:8px;">Skills will be populated from setup flow.</p>
            </div>

            <div class="profile-section">
                <h2>Interests</h2>
                <div class="interests-list">
                    <?php foreach ($interests as $interest): ?>
                        <span class="interest-tag"><?php echo htmlspecialchars($interest); ?></span>
                    <?php endforeach; ?>
                </div>
                <p style="color:#999; font-size:0.9rem; margin-top:8px;">Interests will be populated from setup flow.</p>
            </div>
        </div>
    </div>
</body>
</html>
