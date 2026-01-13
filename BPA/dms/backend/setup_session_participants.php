<?php
/**
 * Setup session_participants table
 */

require_once 'db.php';

header('Content-Type: application/json');

try {
    $db = DB::getInstance();
    $conn = $db->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS session_participants (
        participant_id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        user_id INT NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_session_user (session_id, user_id),
        FOREIGN KEY (session_id) REFERENCES session_requests(request_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
        INDEX idx_session_id (session_id),
        INDEX idx_user_id (user_id)
    )";
    
    if ($conn->query($sql)) {
        exit(json_encode(['success' => true, 'message' => 'session_participants table created']));
    } else {
        exit(json_encode(['success' => false, 'error' => $conn->error]));
    }
    
} catch (Exception $e) {
    exit(json_encode(['success' => false, 'error' => $e->getMessage()]));
}
