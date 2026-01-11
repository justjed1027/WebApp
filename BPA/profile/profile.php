<?php
session_start();
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header('Location: ../landing/landing.php');
    exit;
}

// Determine which user profile to view
$viewingUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $_SESSION['user_id'];

$user = new User();
$user->populate($viewingUserId);

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
    $profileStmt->bind_param('i', $viewingUserId);
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
    $skillsStmt->bind_param('i', $viewingUserId);
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
    $interestsStmt->bind_param('i', $viewingUserId);
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

            <!-- Admin Panel (only visible to admins viewing other users) -->
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === 1 && $viewingUserId !== $_SESSION['user_id']): ?>
                <div class="admin-panel">
                    <h2 style="color: #22c55e; border-bottom: 2px solid #22c55e; padding-bottom: 12px;">Admin Controls</h2>
                    
                    <div class="admin-actions">
                        <button class="admin-btn admin-btn-primary" onclick="banUser(<?php echo intval($viewingUserId); ?>)" title="Ban this user from the platform">
                            🚫 Ban User
                        </button>
                        
                        <button class="admin-btn admin-btn-secondary" onclick="viewUserActivity(<?php echo intval($viewingUserId); ?>)" title="View this user's posts and activity">
                            📊 View Activity
                        </button>
                        
                        <button class="admin-btn admin-btn-danger" onclick="deleteUser(<?php echo intval($viewingUserId); ?>)" title="Permanently delete this user account">
                            🗑️ Delete User
                        </button>
                    </div>

                    <div class="admin-status">
                        <p><strong>User Status:</strong></p>
                        <p id="userStatusDisplay" style="color: #10b981; font-size: 0.95rem;">Active</p>
                    </div>
                </div>

                <style>
                    .admin-panel {
                        background: #1a1a1a;
                        border: 2px solid #22c55e;
                        border-radius: 12px;
                        padding: 20px;
                        margin-top: 24px;
                    }

                    .admin-actions {
                        display: flex;
                        flex-direction: column;
                        gap: 10px;
                        margin-bottom: 20px;
                    }

                    .admin-btn {
                        padding: 12px 16px;
                        border: none;
                        border-radius: 8px;
                        font-size: 0.95rem;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        text-align: left;
                    }

                    .admin-btn-primary {
                        background: #22c55e;
                        color: #0b0b0b;
                    }

                    .admin-btn-primary:hover {
                        background: #16a34a;
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
                    }

                    .admin-btn-secondary {
                        background: #3b82f6;
                        color: white;
                    }

                    .admin-btn-secondary:hover {
                        background: #2563eb;
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
                    }

                    .admin-btn-danger {
                        background: #ef4444;
                        color: white;
                    }

                    .admin-btn-danger:hover {
                        background: #dc2626;
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
                    }

                    .admin-status {
                        padding: 12px;
                        background: rgba(34, 197, 94, 0.1);
                        border-left: 4px solid #22c55e;
                        border-radius: 6px;
                    }

                    .admin-status p {
                        margin: 4px 0;
                    }
                </style>

                <script>
                    function banUser(userId) {
                        if (confirm('Are you sure you want to ban this user? They will no longer be able to log in.')) {
                            alert('Ban functionality coming soon! User ID: ' + userId);
                            // TODO: Implement ban user endpoint
                        }
                    }

                    function viewUserActivity(userId) {
                        alert('View activity functionality coming soon! User ID: ' + userId);
                        // TODO: Implement view user activity endpoint
                    }

                    function deleteUser(userId) {
                        if (confirm('WARNING: This will permanently delete this user and all their data. This cannot be undone. Are you sure?')) {
                            if (confirm('This is a final confirmation. Delete this user permanently?')) {
                                alert('Delete functionality coming soon! User ID: ' + userId);
                                // TODO: Implement delete user endpoint
                            }
                        }
                    }
                </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
