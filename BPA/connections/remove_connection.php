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

$connectionId = (int) $_POST['connection_id'];
$userId = (int) $_SESSION['user_id'];

$db = new DatabaseConnection();
$connObj = new Connection($db->connection);
$result = $connObj->removeConnection($connectionId, $userId);

header("Location: connections.php?msg=" . urlencode($result));
exit;
