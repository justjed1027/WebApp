<?php
session_start();
require_once '../database/User.php';
require_once '../database/DatabaseConnection.php';
require_once '../database/UserPreferences.php';

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

$currentUserId = (int) $_SESSION['user_id'];
$prefs = UserPreferences::getForUser($conn, $currentUserId);
$preferredPrimaryColor = $prefs['primary_color_hex'] ?? $prefs['primary_color'] ?? null;
$hasPreferredPrimaryColor = is_string($preferredPrimaryColor) && preg_match('/^#[0-9a-fA-F]{6}$/', $preferredPrimaryColor);

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
<html lang="en"<?php echo $hasPreferredPrimaryColor ? ' style="--primary-color:' . htmlspecialchars($preferredPrimaryColor, ENT_QUOTES, 'UTF-8') . ';"' : ''; ?>>
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
                    <?php 
                        // Support both relative paths and full paths
                        $imgSrc = (strpos($profilePicture, 'BPA/') === 0) ? '../' . substr($profilePicture, 4) : $profilePicture;
                    ?>
                    <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="Profile Picture" class="profile-avatar-large" />
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
                        <p class="profile-meta-text" style="font-size:0.95rem;">Username:<?php echo htmlspecialchars($user->user_username); ?> </p>
                    <?php endif; ?>
                    <p><?php echo htmlspecialchars($user->user_email); ?></p>
                    <p class="profile-meta-text" style="font-size:0.9rem;">Member since: <?php echo htmlspecialchars($user->user_create_date ?? 'N/A'); ?></p>
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
                    <p class="profile-empty-text" style="font-size:0.95rem;">No bio added yet. Complete your profile in the setup flow.</p>
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
                    <h2 class="admin-panel-title">Admin Controls</h2>
                    
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
                        <p id="userStatusDisplay" class="status-active" style="font-size: 0.95rem;">Active</p>
                    </div>
                </div>

                <style>
                    .admin-panel {
                        background: var(--surface-bg);
                        border: 2px solid var(--primary-color);
                        border-radius: 14px;
                        padding: 20px;
                        margin-top: 24px;
                    }

                    .admin-panel-title {
                        color: var(--primary-color);
                        border-bottom: 2px solid var(--primary-color);
                        padding-bottom: 12px;
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
                        background: var(--primary-color);
                        color: var(--text-inverse);
                    }

                    .admin-btn-primary:hover {
                        background: var(--primary-hover);
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px color-mix(in srgb, var(--primary-color) 32%, transparent);
                    }

                    .admin-btn-secondary {
                        background: var(--color-info);
                        color: var(--text-inverse);
                    }

                    .admin-btn-secondary:hover {
                        background: var(--color-info-hover);
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px color-mix(in srgb, var(--color-info) 32%, transparent);
                    }

                    .admin-btn-danger {
                        background: var(--color-danger);
                        color: var(--text-inverse);
                    }

                    .admin-btn-danger:hover {
                        background: var(--color-danger-hover);
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px color-mix(in srgb, var(--color-danger) 32%, transparent);
                    }

                    .admin-status {
                        padding: 12px;
                        background: var(--accent-soft-bg);
                        border-left: 4px solid var(--primary-color);
                        border-radius: 8px;
                    }

                    .admin-status p {
                        margin: 4px 0;
                    }

                    .status-active {
                        color: var(--primary-color) !important;
                    }

                    .status-banned {
                        color: var(--color-danger) !important;
                    }

                    .status-deleted {
                        color: var(--text-muted) !important;
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
                            <div id="modalDetails" class="modal-details">
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
                        background: var(--overlay-strong);
                        z-index: 1000;
                        justify-content: center;
                        align-items: center;
                    }

                    .admin-modal.active {
                        display: flex;
                    }

                    .admin-modal-content {
                        --modal-accent: var(--primary-color);
                        --modal-accent-soft: color-mix(in srgb, var(--modal-accent) 32%, transparent);
                        background: var(--surface-bg);
                        border: 2px solid var(--modal-accent);
                        border-radius: 14px;
                        width: 90%;
                        max-width: 500px;
                        box-shadow: var(--shadow-elevated);
                    }

                    .admin-modal-content.tone-danger {
                        --modal-accent: var(--color-danger);
                        --modal-accent-soft: color-mix(in srgb, var(--color-danger) 32%, transparent);
                    }

                    .admin-modal-content.tone-success {
                        --modal-accent: var(--primary-color);
                        --modal-accent-soft: color-mix(in srgb, var(--modal-accent) 32%, transparent);
                    }

                    .admin-modal-content.tone-info {
                        --modal-accent: var(--primary-color);
                        --modal-accent-soft: color-mix(in srgb, var(--modal-accent) 32%, transparent);
                    }

                    .admin-modal-content.tone-error {
                        --modal-accent: var(--color-danger);
                        --modal-accent-soft: color-mix(in srgb, var(--color-danger) 32%, transparent);
                    }

                    .admin-modal-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 20px;
                        border-bottom: 1px solid var(--modal-accent-soft);
                    }

                    .admin-modal-header h3 {
                        margin: 0;
                        color: var(--modal-accent);
                        font-size: 1.3rem;
                    }

                    .admin-modal-close {
                        background: none;
                        border: none;
                        color: var(--text-muted);
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
                        background: var(--overlay-soft);
                        color: var(--text-primary);
                    }

                    .admin-modal-body {
                        padding: 20px;
                        color: var(--text-primary);
                    }

                    .modal-details {
                        display: none;
                        margin-top: 12px;
                        padding: 12px;
                        background: var(--surface-faint);
                        border-radius: 6px;
                        max-height: 300px;
                        overflow-y: auto;
                    }

                    .modal-activity-summary {
                        color: var(--text-primary);
                    }

                    .modal-activity-title {
                        margin-top: 12px;
                        font-weight: 600;
                        color: var(--primary-color);
                    }

                    .modal-activity-row {
                        font-size: 0.9rem;
                        margin: 6px 0;
                    }

                    .modal-activity-date {
                        color: var(--text-muted);
                    }

                    .admin-modal-body p {
                        margin: 0;
                        line-height: 1.5;
                    }

                    .admin-modal-footer {
                        display: flex;
                        gap: 12px;
                        padding: 20px;
                        border-top: 1px solid var(--modal-accent-soft);
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
                        background: var(--surface-alt);
                        color: var(--text-primary);
                    }

                    .admin-modal-btn-cancel:hover {
                        background: color-mix(in srgb, var(--surface-alt) 88%, var(--text-primary));
                    }

                    .admin-modal-btn-confirm {
                        background: var(--primary-color);
                        color: var(--text-inverse);
                    }

                    .admin-modal-btn-confirm:hover {
                        background: color-mix(in srgb, var(--primary-color) 84%, var(--page-bg));
                    }

                    .admin-modal-btn-confirm.danger {
                        background: var(--color-danger);
                        color: var(--text-inverse);
                    }

                    .admin-modal-btn-confirm.danger:hover {
                        background: var(--color-danger-hover);
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
                            setModalTone('danger');
                        } else {
                            confirmBtn.classList.remove('danger');
                            setModalTone('default');
                        }
                        
                        document.getElementById('adminModal').classList.add('active');
                    }

                    function closeAdminModal() {
                        document.getElementById('adminModal').classList.remove('active');
                        setModalTone('default');
                        currentAdminAction = null;
                        currentUserId = null;
                    }

                    function setModalTone(tone = 'default') {
                        const modalContent = document.querySelector('.admin-modal-content');
                        if (!modalContent) return;

                        modalContent.classList.remove('tone-danger', 'tone-success', 'tone-info', 'tone-error');
                        if (tone !== 'default') {
                            modalContent.classList.add(`tone-${tone}`);
                        }
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
                                    <div class="modal-activity-summary">
                                        <p><strong>Posts:</strong> ${data.posts_count}</p>
                                        <p><strong>Comments:</strong> ${data.comments_count}</p>
                                `;
                                
                                if (data.posts.length > 0) {
                                    detailsHTML += '<p class="modal-activity-title">Recent Posts:</p>';
                                    data.posts.slice(0, 5).forEach(post => {
                                        const shortContent = post.content.substring(0, 60) + (post.content.length > 60 ? '...' : '');
                                        detailsHTML += `<p class="modal-activity-row"><span class="modal-activity-date">${post.created_at}</span><br>${escapeHtml(shortContent)}</p>`;
                                    });
                                }
                                
                                detailsHTML += '</div>';
                                
                                const detailsDiv = document.getElementById('modalDetails');
                                detailsDiv.innerHTML = detailsHTML;
                                detailsDiv.style.display = 'block';
                                
                                document.getElementById('adminModal').classList.add('active');
                                setModalTone('info');
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
                        setModalTone('success');
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
                        setModalTone('error');
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
