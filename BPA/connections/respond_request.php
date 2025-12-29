<?php
session_start();
require_once "../database/DatabaseConnection.php";
require_once "../database/Connection.php";
require_once "../database/Notification.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: connections.php?msg=" . urlencode('not_logged_in'));
    exit;
}

if (!isset($_POST['connection_id']) || !is_numeric($_POST['connection_id'])) {
    header("Location: connections.php?msg=" . urlencode('invalid_request'));
    exit;
}

$action = $_POST['action'] ?? '';
$connectionId = (int) $_POST['connection_id'];
$receiverId = (int) $_SESSION['user_id'];

$db = new DatabaseConnection();
$connObj = new Connection($db->connection);

if ($action === 'accept') {
    $result = $connObj->acceptRequest($connectionId, $receiverId);
    
    // Create notification when accepting a friend request
    if ($result === 'success') {
        $notif = new Notification($db->connection);
        
        // Get the requester's ID from the connections table
        $connQuery = $db->connection->prepare("SELECT requester_id FROM connections WHERE connection_id = ?");
        $connQuery->bind_param("i", $connectionId);
        $connQuery->execute();
        $connResult = $connQuery->get_result();
        $connData = $connResult->fetch_assoc();
        $requesterId = $connData['requester_id'] ?? null;
        
        if ($requesterId) {
            // Get the acceptor's username
            $userQuery = $db->connection->prepare("SELECT user_username FROM user WHERE user_id = ?");
            $userQuery->bind_param("i", $receiverId);
            $userQuery->execute();
            $userResult = $userQuery->get_result();
            $userData = $userResult->fetch_assoc();
            $username = $userData['user_username'] ?? 'Someone';
            
            // Create notification for the requester
            $notif->createNotification(
                $requesterId,
                'friend_accepted',
                $receiverId,
                $username . ' accepted your friend request',
                'You are now connected',
                $receiverId,
                'user'
            );
        }
    }
    $result = 'invalid_action';
}

header("Location: connections.php?msg=" . urlencode($result));
exit;
