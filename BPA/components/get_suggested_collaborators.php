<?php
/**
 * Get Suggested Collaborators based on user interests and skills
 * 
 * Returns JSON array of suggested collaborators who share skills that match
 * the current user's marked interests
 */

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$currentUserId = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3;

require_once __DIR__ . '/../database/DatabaseConnection.php';

$db = new DatabaseConnection();
$conn = $db->connection;

try {
    /**
     * Find users who have skills that match the current user's interests
     * Logic:
     * 1. Get current user's interests
     * 2. Find other users whose skills match those interests
     * 3. Exclude current user and any already connected users
     * 4. Limit and return results
     */
    
    // First check if user has any interests
    $interestCheckSql = "SELECT COUNT(*) as count FROM user_interests WHERE ui_user_id = ?";
    $interestStmt = $conn->prepare($interestCheckSql);
    $interestStmt->bind_param('i', $currentUserId);
    $interestStmt->execute();
    $interestResult = $interestStmt->get_result();
    $interestRow = $interestResult->fetch_assoc();
    $interestStmt->close();
    
    $collaborators = [];
    
    // Try to find users whose skills match current user's interests
    if ($interestRow['count'] > 0) {
        $sql = "SELECT
                    u.user_id,
                    COALESCE(p.user_firstname, u.user_username) as user_firstname,
                    COALESCE(p.user_lastname, '') as user_lastname,
                    (
                        SELECT GROUP_CONCAT(DISTINCT s_match.subject_name ORDER BY s_match.subject_name SEPARATOR ', ')
                        FROM user_skills us_match
                        INNER JOIN subjects s_match ON us_match.us_subject_id = s_match.subject_id
                        WHERE us_match.us_user_id = u.user_id
                        AND us_match.us_subject_id IN (
                            SELECT ui.ui_subject_id
                            FROM user_interests ui
                            WHERE ui.ui_user_id = ?
                        )
                    ) as shared_skills,
                    (
                        SELECT GROUP_CONCAT(DISTINCT s_all.subject_name ORDER BY s_all.subject_name SEPARATOR ', ')
                        FROM user_skills us_all
                        INNER JOIN subjects s_all ON us_all.us_subject_id = s_all.subject_id
                        WHERE us_all.us_user_id = u.user_id
                    ) as all_skills,
                    (
                        SELECT COUNT(DISTINCT us_count.us_subject_id)
                        FROM user_skills us_count
                        WHERE us_count.us_user_id = u.user_id
                        AND us_count.us_subject_id IN (
                            SELECT ui.ui_subject_id
                            FROM user_interests ui
                            WHERE ui.ui_user_id = ?
                        )
                    ) as matching_skills_count
                FROM user u
                LEFT JOIN profile p ON u.user_id = p.user_id
                WHERE u.user_id != ?
                AND EXISTS (
                    SELECT 1
                    FROM user_skills us_exists
                    WHERE us_exists.us_user_id = u.user_id
                    AND us_exists.us_subject_id IN (
                        SELECT ui.ui_subject_id
                        FROM user_interests ui
                        WHERE ui.ui_user_id = ?
                    )
                )
                AND u.user_id NOT IN (
                    SELECT CASE
                        WHEN requester_id = ? THEN receiver_id
                        ELSE requester_id
                    END
                    FROM connections
                    WHERE (requester_id = ? OR receiver_id = ?)
                    AND status = 'accepted'
                )
                ORDER BY matching_skills_count DESC, u.user_id ASC
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param('iiiiiiii', $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $limit);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $collaborators[] = [
                'user_id' => $row['user_id'],
                'firstname' => htmlspecialchars($row['user_firstname'] ?? 'User'),
                'lastname' => htmlspecialchars($row['user_lastname'] ?? ''),
                'field' => htmlspecialchars($row['shared_skills'] ?? $row['all_skills'] ?? 'Various Skills'),
                'shared_skills' => htmlspecialchars($row['shared_skills'] ?? ''),
                'all_skills' => htmlspecialchars($row['all_skills'] ?? 'Various Skills'),
                'matching_count' => $row['matching_skills_count']
            ];
        }
        
        $stmt->close();
    }
    
    // If no interest-based matches found, show users with any skills (fallback)
    if (empty($collaborators)) {
        $fallbackSql = "SELECT DISTINCT 
                    u.user_id,
                    COALESCE(p.user_firstname, u.user_username) as user_firstname,
                    COALESCE(p.user_lastname, '') as user_lastname,
                    GROUP_CONCAT(DISTINCT s.subject_name SEPARATOR ', ') as skills,
                    COUNT(DISTINCT us.us_subject_id) as skill_count
                FROM user u
                LEFT JOIN profile p ON u.user_id = p.user_id
                INNER JOIN user_skills us ON u.user_id = us.us_user_id
                INNER JOIN subjects s ON us.us_subject_id = s.subject_id
                WHERE u.user_id != ?
                AND u.user_id NOT IN (
                    SELECT CASE 
                        WHEN requester_id = ? THEN receiver_id
                        ELSE requester_id
                    END
                    FROM connections
                    WHERE (requester_id = ? OR receiver_id = ?)
                    AND status = 'accepted'
                )
                GROUP BY u.user_id
                ORDER BY skill_count DESC, u.user_id ASC
                LIMIT ?";
        
        $fallbackStmt = $conn->prepare($fallbackSql);
        
        if ($fallbackStmt) {
            $fallbackStmt->bind_param('iiiii', $currentUserId, $currentUserId, $currentUserId, $currentUserId, $limit);
            $fallbackStmt->execute();
            $fallbackResult = $fallbackStmt->get_result();
            
            while ($row = $fallbackResult->fetch_assoc()) {
                $collaborators[] = [
                    'user_id' => $row['user_id'],
                    'firstname' => htmlspecialchars($row['user_firstname'] ?? 'User'),
                    'lastname' => htmlspecialchars($row['user_lastname'] ?? ''),
                    'field' => htmlspecialchars($row['skills'] ?? 'Various Skills'),
                    'shared_skills' => '',
                    'all_skills' => htmlspecialchars($row['skills'] ?? 'Various Skills'),
                    'matching_count' => $row['skill_count']
                ];
            }
            
            $fallbackStmt->close();
        }
    }
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'collaborators' => $collaborators
    ]);
    
} catch (Exception $e) {
    // Return error with detailed message
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
