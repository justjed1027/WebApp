<?php
require_once "../database/DatabaseConnection.php";
require_once "../database/Connection.php";

$db = new DatabaseConnection();
$connObj = new Connection($db->connection);

$requesterId = $_SESSION['user_id'];
$receiverId = $_POST['receiver_id'] ?? null;

$result = $connObj->sendConnectionRequest($requesterId, $receiverId);

header("Location: connections.php?msg=" . $result);
exit;