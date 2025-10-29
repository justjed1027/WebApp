<?php
/**
 * Google OAuth 2.0 Handler
 * This file handles Google sign-in authentication and creates user sessions
 */

session_start();
require_once '../database/DatabaseConnection.php';
require_once '../database/User.php';

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');

/**
 * Verify Google ID Token
 * You'll need to validate the token with Google's servers
 */
function verifyGoogleToken($idToken) {
    // Google's token verification endpoint
    $verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $idToken;
    
    // Make request to Google
    $response = @file_get_contents($verifyUrl);
    
    if ($response === false) {
        return false;
    }
    
    $data = json_decode($response, true);
    
    // Verify that the token is valid and for your app
    // Replace YOUR_GOOGLE_CLIENT_ID with your actual client ID
    if (isset($data['aud']) && $data['aud'] === 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com') {
        return $data;
    }
    
    return false;
}

/**
 * Create or get user from Google data
 */
function getOrCreateGoogleUser($googleData) {
    try {
        $db = DatabaseConnection::getConnection();
        
        // Extract user information from Google data
        $email = $googleData['email'];
        $name = isset($googleData['name']) ? $googleData['name'] : '';
        $googleId = $googleData['sub']; // Google user ID
        $picture = isset($googleData['picture']) ? $googleData['picture'] : '';
        
        // Check if user exists by email
        $stmt = $db->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // User exists, return their ID
            $user = $result->fetch_assoc();
            
            // Update Google ID if not set
            $updateStmt = $db->prepare("UPDATE users SET google_id = ?, profile_picture = ? WHERE id = ?");
            $updateStmt->bind_param("ssi", $googleId, $picture, $user['id']);
            $updateStmt->execute();
            
            return $user['id'];
        } else {
            // Create new user
            // Generate a username from the email or name
            $username = strstr($email, '@', true); // Part before @
            
            // Check if username exists and make it unique
            $baseUsername = $username;
            $counter = 1;
            while (true) {
                $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                $checkStmt->bind_param("s", $username);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows === 0) {
                    break; // Username is unique
                }
                
                $username = $baseUsername . $counter;
                $counter++;
            }
            
            // Insert new user
            // Note: For Google auth users, we don't need a password
            // Set a random password hash that they can't use to login normally
            $randomPassword = bin2hex(random_bytes(32));
            $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);
            
            $insertStmt = $db->prepare("INSERT INTO users (username, email, password, google_id, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $insertStmt->bind_param("sssss", $username, $email, $passwordHash, $googleId, $picture);
            
            if ($insertStmt->execute()) {
                return $insertStmt->insert_id;
            } else {
                return false;
            }
        }
    } catch (Exception $e) {
        error_log("Google OAuth Error: " . $e->getMessage());
        return false;
    }
}

// Main execution
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the posted data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['credential'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No credential provided'
        ]);
        exit;
    }
    
    $idToken = $input['credential'];
    
    // Verify the Google token
    $googleData = verifyGoogleToken($idToken);
    
    if ($googleData === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid Google token'
        ]);
        exit;
    }
    
    // Get or create the user
    $userId = getOrCreateGoogleUser($googleData);
    
    if ($userId === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create or retrieve user'
        ]);
        exit;
    }
    
    // Set session
    $_SESSION['user_id'] = $userId;
    $_SESSION['login_method'] = 'google';
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => '../post/post.php'
    ]);
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
