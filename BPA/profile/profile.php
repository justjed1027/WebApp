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
    <script>
        // Apply theme immediately from localStorage
        const theme = localStorage.getItem('theme');
        if (theme === 'light') {
            document.body.classList.add('light-mode');
        }
    </script>
    <div class="profile-container">
        <a href="#" onclick="history.back(); return false;" class="back-link">← Back</a>
        
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
                    <h2 style="color: #1fff93; border-bottom: 2px solid #1fff93; padding-bottom: 12px;">Admin Controls</h2>
                    
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
                        <p id="userStatusDisplay" style="color: #1fff93; font-size: 0.95rem;">Active</p>
                    </div>
                </div>

                <style>
                    .admin-panel {
                        background: #1e1e1e;
                        border: 2px solid #1fff93;
                        border-radius: 14px;
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
                        background: #1fff93;
                        color: #0a0a0a;
                    }

                    .admin-btn-primary:hover {
                        background: #19cc75;
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(31, 255, 147, 0.3);
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
                        background: rgba(31, 255, 147, 0.1);
                        border-left: 4px solid #1fff93;
                        border-radius: 8px;
                    }

                    .admin-status p {
                        margin: 4px 0;
                    }

                    .status-active {
                        color: #1fff93 !important;
                    }

                    .status-banned {
                        color: #ef4444 !important;
                    }

                    .status-deleted {
                        color: #888 !important;
                    }
                </style>

                <div id="adminModal" class="admin-modal">
                    <div class="admin-modal-content">
                        <div class="admin-modal-header">
                            <h3 id="modalTitle">Confirm Action</h3>
                            <button class="admin-modal-close" onclick="closeAdminModal()">&times;</button>
                        </div>
                        <div class="admin-modal-body">
                            <p id="modalMessage">Are you sure?</p>
                            <div id="modalDetails" style="display: none; margin-top: 12px; padding: 12px; background: rgba(255,255,255,0.05); border-radius: 6px; max-height: 300px; overflow-y: auto;">
                                <!-- Activity details will go here -->
                            </div>
                        </div>
                        <div class="admin-modal-footer">
                            <button id="modalCancelBtn" class="admin-modal-btn admin-modal-btn-cancel" onclick="closeAdminModal()">Cancel</button>
                            <button id="modalConfirmBtn" class="admin-modal-btn admin-modal-btn-confirm" onclick="executeAdminAction()">Confirm</button>
                        </div>
                    </div>
                </div>

                <style>
                    .admin-modal {
                        display: none;
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.7);
                        z-index: 1000;
                        justify-content: center;
                        align-items: center;
                    }

                    .admin-modal.active {
                        display: flex;
                    }

                    .admin-modal-content {
                        background: #1e1e1e;
                        border: 2px solid #1fff93;
                        border-radius: 14px;
                        width: 90%;
                        max-width: 500px;
                        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
                    }

                    .admin-modal-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 20px;
                        border-bottom: 1px solid rgba(31, 255, 147, 0.3);
                    }

                    .admin-modal-header h3 {
                        margin: 0;
                        color: #1fff93;
                        font-size: 1.3rem;
                    }

                    .admin-modal-close {
                        background: none;
                        border: none;
                        color: #888;
                        font-size: 28px;
                        cursor: pointer;
                        padding: 0;
                        width: 32px;
                        height: 32px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 6px;
                        transition: all 0.2s ease;
                    }

                    .admin-modal-close:hover {
                        background: rgba(255, 255, 255, 0.1);
                        color: #fff;
                    }

                    .admin-modal-body {
                        padding: 20px;
                        color: #e0e0e0;
                    }

                    .admin-modal-body p {
                        margin: 0;
                        line-height: 1.5;
                    }

                    .admin-modal-footer {
                        display: flex;
                        gap: 12px;
                        padding: 20px;
                        border-top: 1px solid rgba(31, 255, 147, 0.3);
                        justify-content: flex-end;
                    }

                    .admin-modal-btn {
                        padding: 10px 20px;
                        border: none;
                        border-radius: 6px;
                        font-size: 0.95rem;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.3s ease;
                    }

                    .admin-modal-btn-cancel {
                        background: #2a2a2a;
                        color: #e0e0e0;
                    }

                    .admin-modal-btn-cancel:hover {
                        background: #3a3a3a;
                    }

                    .admin-modal-btn-confirm {
                        background: #1fff93;
                        color: #0a0a0a;
                    }

                    .admin-modal-btn-confirm:hover {
                        background: #19cc75;
                    }

                    .admin-modal-btn-confirm.danger {
                        background: #ef4444;
                        color: white;
                    }

                    .admin-modal-btn-confirm.danger:hover {
                        background: #dc2626;
                    }
                </style>

                <script>
                    let currentAdminAction = null;
                    let currentUserId = null;

                    function checkUserBanStatus(userId) {
                        fetch('backend/check_ban_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ user_id: userId })
                        })
                        .then(response => response.json())
                        .then(data => {
                            const statusDisplay = document.getElementById('userStatusDisplay');
                            if (data.user_is_banned === 1) {
                                statusDisplay.textContent = 'Banned';
                                statusDisplay.className = 'status-banned';
                            } else {
                                statusDisplay.textContent = 'Active';
                                statusDisplay.className = 'status-active';
                            }
                        })
                        .catch(error => console.error('Error checking ban status:', error));
                    }

                    function showAdminModal(title, message, confirmText = 'Confirm', isDanger = false) {
                        document.getElementById('modalTitle').textContent = title;
                        document.getElementById('modalMessage').textContent = message;
                        document.getElementById('modalDetails').style.display = 'none';
                        document.getElementById('modalDetails').innerHTML = '';
                        
                        const confirmBtn = document.getElementById('modalConfirmBtn');
                        const cancelBtn = document.getElementById('modalCancelBtn');
                        
                        confirmBtn.textContent = confirmText;
                        confirmBtn.style.display = 'block';
                        cancelBtn.textContent = 'Cancel';
                        
                        if (isDanger) {
                            confirmBtn.classList.add('danger');
                        } else {
                            confirmBtn.classList.remove('danger');
                        }
                        
                        document.getElementById('adminModal').classList.add('active');
                    }

                    function closeAdminModal() {
                        document.getElementById('adminModal').classList.remove('active');
                        currentAdminAction = null;
                        currentUserId = null;
                    }

                    function executeAdminAction() {
                        if (!currentAdminAction) return;
                        currentAdminAction();
                    }

                    function banUser(userId) {
                        currentUserId = userId;
                        currentAdminAction = () => {
                            fetch('backend/ban_user.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({ user_id: userId })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    showSuccessMessage('User Banned', '✓ ' + data.message);
                                    setTimeout(() => checkUserBanStatus(userId), 500);
                                } else {
                                    showErrorMessage('Error', '✗ ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showErrorMessage('Error', 'An error occurred: ' + error.message);
                            });
                        };

                        showAdminModal('Ban User', 'Are you sure you want to ban this user? They will no longer be able to log in.', 'Ban User', true);
                    }

                    function viewUserActivity(userId) {
                        fetch('backend/view_user_activity.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ user_id: userId })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('modalTitle').textContent = `Activity: ${data.username}`;
                                document.getElementById('modalMessage').textContent = 'User Activity Summary';
                                
                                let detailsHTML = `
                                    <div style="color: #e0e0e0;">
                                        <p><strong>Posts:</strong> ${data.posts_count}</p>
                                        <p><strong>Comments:</strong> ${data.comments_count}</p>
                                `;
                                
                                if (data.posts.length > 0) {
                                    detailsHTML += '<p style="margin-top: 12px; font-weight: 600; color: #22c55e;">Recent Posts:</p>';
                                    data.posts.slice(0, 5).forEach(post => {
                                        const shortContent = post.content.substring(0, 60) + (post.content.length > 60 ? '...' : '');
                                        detailsHTML += `<p style="font-size: 0.9rem; margin: 6px 0;"><span style="color: #888;">${post.created_at}</span><br>${escapeHtml(shortContent)}</p>`;
                                    });
                                }
                                
                                detailsHTML += '</div>';
                                
                                const detailsDiv = document.getElementById('modalDetails');
                                detailsDiv.innerHTML = detailsHTML;
                                detailsDiv.style.display = 'block';
                                
                                document.getElementById('adminModal').classList.add('active');
                                document.getElementById('modalConfirmBtn').style.display = 'none';
                                document.getElementById('modalCancelBtn').textContent = 'Close';
                                
                                currentAdminAction = null;
                            } else {
                                showErrorMessage('Error', '✗ ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showErrorMessage('Error', 'An error occurred: ' + error.message);
                        });
                    }

                    function deleteUser(userId) {
                        currentUserId = userId;
                        
                        showAdminModal(
                            'Delete User (Step 1 of 2)',
                            'WARNING: This will permanently delete this user and all their data. This cannot be undone. Are you absolutely sure?',
                            'Yes, Continue',
                            true
                        );
                        
                        currentAdminAction = () => {
                            // Show second confirmation (modal stays open)
                            showAdminModal(
                                'Delete User (Final Confirmation)',
                                'This is your final warning. Deleting this user will permanently remove all their posts, comments, connections, and profile data. Click "Delete Permanently" to proceed.',
                                'Delete Permanently',
                                true
                            );
                            
                            currentAdminAction = () => {
                                fetch('backend/delete_user.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({ user_id: userId })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        showSuccessMessage('User Deleted', '✓ ' + data.message + '\n\nRedirecting to dashboard in 2 seconds...');
                                        const statusDisplay = document.getElementById('userStatusDisplay');
                                        statusDisplay.textContent = 'Deleted';
                                        statusDisplay.className = 'status-deleted';
                                        
                                        setTimeout(() => {
                                            window.location.href = '../courses/courses.php';
                                        }, 2000);
                                    } else {
                                        showErrorMessage('Error', '✗ ' + data.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    showErrorMessage('Error', 'An error occurred: ' + error.message);
                                });
                            };
                        };
                    }

                    function showSuccessMessage(title, message) {
                        document.getElementById('modalTitle').textContent = title;
                        document.getElementById('modalMessage').textContent = message;
                        document.getElementById('modalDetails').style.display = 'none';
                        
                        const confirmBtn = document.getElementById('modalConfirmBtn');
                        confirmBtn.style.display = 'none';
                        document.getElementById('modalCancelBtn').textContent = 'Close';
                        
                        document.getElementById('adminModal').classList.add('active');
                        currentAdminAction = null;
                    }

                    function showErrorMessage(title, message) {
                        document.getElementById('modalTitle').textContent = title;
                        document.getElementById('modalMessage').textContent = message;
                        document.getElementById('modalDetails').style.display = 'none';
                        
                        const confirmBtn = document.getElementById('modalConfirmBtn');
                        confirmBtn.style.display = 'none';
                        document.getElementById('modalCancelBtn').textContent = 'Close';
                        
                        document.getElementById('adminModal').classList.add('active');
                        currentAdminAction = null;
                    }

                    function escapeHtml(text) {
                        const map = {
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#039;'
                        };
                        return text.replace(/[&<>"']/g, m => map[m]);
                    }

                    // Close modal when clicking outside of it
                    document.addEventListener('click', function(event) {
                        const modal = document.getElementById('adminModal');
                        if (event.target === modal) {
                            closeAdminModal();
                        }
                    });

                    // Check initial user status when page loads
                    document.addEventListener('DOMContentLoaded', function() {
                        checkUserBanStatus(<?php echo intval($viewingUserId); ?>);
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
