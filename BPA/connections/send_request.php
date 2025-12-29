<?php
session_start();
require_once "../database/DatabaseConnection.php";
require_once "../database/Connection.php";
require_once "../database/Notification.php";

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
	header("Location: connections.php?msg=" . urlencode('not_logged_in'));
	exit;
}

// Validate POST
if (!isset($_POST['receiver_id']) || !is_numeric($_POST['receiver_id'])) {
	header("Location: connections.php?msg=" . urlencode('invalid_request'));
	exit;
}

$db = new DatabaseConnection();
$connObj = new Connection($db->connection);

$requesterId = (int) $_SESSION['user_id'];
$receiverId = (int) $_POST['receiver_id'];

$result = $connObj->sendConnectionRequest($requesterId, $receiverId);

// Create notification if request was successful
if ($result === 'success') {
	$notif = new Notification($db->connection);
	
	// Get the requester's username
	$userQuery = $db->connection->prepare("SELECT user_username FROM user WHERE user_id = ?");
	$userQuery->bind_param("i", $requesterId);
	$userQuery->execute();
	$userResult = $userQuery->get_result();
	$userData = $userResult->fetch_assoc();
	$username = $userData['user_username'] ?? 'Someone';
	
	// Create notification for the receiver
	$notif->createNotification(
		$receiverId,
		'friend_request',
		$requesterId,
		$username . ' sent you a friend request',
		'View their profile to accept or decline',
		$requesterId,
		'user'
	);
}

header("Location: connections.php?msg=" . urlencode($result));
exit;