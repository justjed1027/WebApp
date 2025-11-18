<?php
session_start();
require_once "../database/DatabaseConnection.php";
require_once "../database/Connection.php";

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

header("Location: connections.php?msg=" . urlencode($result));
exit;