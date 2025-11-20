<?php
session_start();
require_once "../database/DatabaseConnection.php";
require_once "../database/Connection.php";

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
} elseif ($action === 'decline') {
    $result = $connObj->declineRequest($connectionId, $receiverId);
} else {
    $result = 'invalid_action';
}

header("Location: connections.php?msg=" . urlencode($result));
exit;
