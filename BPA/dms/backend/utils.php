<?php
/**
 * Utility functions for DM system
 */

/**
 * Normalize user pair to ensure user1_id < user2_id
 * This ensures we don't create duplicate conversations
 * 
 * @param int $userA First user ID
 * @param int $userB Second user ID
 * @return array [user1_id, user2_id] where user1_id < user2_id
 */
function normalize_user_pair($userA, $userB) {
    $userA = intval($userA);
    $userB = intval($userB);
    
    if ($userA === $userB) {
        return null; // Can't have conversation with yourself
    }
    
    return $userA < $userB ? [$userA, $userB] : [$userB, $userA];
}

/**
 * Validate session and return user_id
 * 
 * @return int|null User ID if valid session, null otherwise
 */
function get_authenticated_user() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
}

/**
 * Send JSON response and exit
 * 
 * @param array $data Response data
 * @param int $statusCode HTTP status code
 */
function send_json($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Validate required POST parameters
 * 
 * @param array $required Array of required parameter names
 * @return array Sanitized parameters or sends error response
 */
function validate_post_params($required) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        send_json(['success' => false, 'error' => 'Invalid JSON input'], 400);
    }
    
    foreach ($required as $param) {
        if (!isset($input[$param])) {
            send_json(['success' => false, 'error' => "Missing required parameter: $param"], 400);
        }
    }
    
    return $input;
}

/**
 * Validate required GET parameters
 * 
 * @param array $required Array of required parameter names
 * @return array Sanitized parameters or sends error response
 */
function validate_get_params($required) {
    foreach ($required as $param) {
        if (!isset($_GET[$param])) {
            send_json(['success' => false, 'error' => "Missing required parameter: $param"], 400);
        }
    }
    
    return $_GET;
}
