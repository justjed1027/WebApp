<?php
/**
 * Google OAuth 2.0 Callback Handler
 * This file receives the authorization code from Google after user selects their account
 * and exchanges it for user information to create/login the user
 */

session_start();
require_once '../database/DatabaseConnection.php';
require_once '../database/User.php';

// Google OAuth 2.0 Configuration
// IMPORTANT: Replace with your actual Client ID and Client Secret from Google Cloud Console
$clientId = 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com';
$clientSecret = 'YOUR_GOOGLE_CLIENT_SECRET'; // Get this from Google Cloud Console
$redirectUri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/WebApp/BPA/login/google-callback.php";

/**
 * Exchange authorization code for access token and user info
 */
function getGoogleUserInfo($code, $clientId, $clientSecret, $redirectUri) {
    // Exchange code for access token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    
    $postData = [
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("Google token exchange failed: " . $response);
        return false;
    }
    
    $tokenData = json_decode($response, true);
    
    if (!isset($tokenData['access_token'])) {
        error_log("No access token in response: " . $response);
        return false;
    }
    
    // Get user info using access token
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
    
    $ch = curl_init($userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $tokenData['access_token']
    ]);
    
    $userInfoResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("Failed to get user info: " . $userInfoResponse);
        return false;
    }
    
    return json_decode($userInfoResponse, true);
}

/**
 * Create or get user from Google data
 */
function getOrCreateGoogleUser($googleData) {
    try {
        $db = DatabaseConnection::getConnection();
        
        // Extract user information
        $email = $googleData['email'];
        $name = isset($googleData['name']) ? $googleData['name'] : '';
        $googleId = $googleData['id']; // Google user ID
        $picture = isset($googleData['picture']) ? $googleData['picture'] : '';
        
        // Check if user exists by email
        $stmt = $db->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // User exists, return their ID
            $user = $result->fetch_assoc();
            
            // Update Google ID and picture if not set
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
if (isset($_GET['code'])) {
    // Authorization code received from Google
    $code = $_GET['code'];
    
    // Exchange code for user info
    $googleData = getGoogleUserInfo($code, $clientId, $clientSecret, $redirectUri);
    
    if ($googleData === false) {
        // Failed to get user info
        $_SESSION['login_error'] = 'Failed to authenticate with Google. Please try again.';
        header("Location: login.php");
        exit;
    }
    
    // Get or create the user
    $userId = getOrCreateGoogleUser($googleData);
    
    if ($userId === false) {
        // Failed to create or retrieve user
        $_SESSION['login_error'] = 'Failed to create or retrieve user account.';
        header("Location: login.php");
        exit;
    }
    
    // Set session
    $_SESSION['user_id'] = $userId;
    $_SESSION['login_method'] = 'google';
    
    // Redirect to dashboard
    header("Location: ../post/post.php");
    exit;
    
} elseif (isset($_GET['error'])) {
    // User denied access or other error
    $error = $_GET['error'];
    $_SESSION['login_error'] = 'Google sign-in was cancelled or failed: ' . htmlspecialchars($error);
    header("Location: login.php");
    exit;
    
} else {
    // No code or error - redirect back to login
    header("Location: login.php");
    exit;
}
?>
