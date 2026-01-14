<?php
session_start();
require_once "../database/DatabaseConnection.php";
require_once "../database/Connection.php";
require_once "../database/Notification.php";

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
	if ($isAjax) {
		header('Content-Type: application/json');
		echo json_encode(['success' => false, 'message' => 'not_logged_in']);
		exit;
	}
	header("Location: connections.php?msg=" . urlencode('not_logged_in'));
	exit;
}

// Validate POST
if (!isset($_POST['receiver_id']) || !is_numeric($_POST['receiver_id'])) {
	if ($isAjax) {
		header('Content-Type: application/json');
		echo json_encode(['success' => false, 'message' => 'invalid_request']);
		exit;
	}
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
	// Add receiver to excluded list in session so they don't show in recommendations
	if (!isset($_SESSION['recommendations_excluded'])) {
		$_SESSION['recommendations_excluded'] = [];
	}
	if (!isset($_SESSION['recommendations_excluded'][$requesterId])) {
		$_SESSION['recommendations_excluded'][$requesterId] = [];
	}
	if (!in_array($receiverId, $_SESSION['recommendations_excluded'][$requesterId])) {
		$_SESSION['recommendations_excluded'][$requesterId][] = $receiverId;
	}
	
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

// Return JSON for AJAX requests
if ($isAjax) {
	header('Content-Type: application/json');
	echo json_encode(['success' => ($result === 'success'), 'message' => $result]);
	exit;
}

// Regular redirect for form submissions
header("Location: connections.php?msg=" . urlencode($result));
exit;