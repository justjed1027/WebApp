<?php
require_once 'DatabaseConnection.php';
require_once 'User.php';
class Connection {
    private $connection;

    public function __construct($connection) {
        $this->connection = $connection;
    }

    public function sendConnectionRequest($requesterId, $receiverId)
    {
 // Prevent duplicates
    $checkSql = "
        SELECT * FROM connections 
        WHERE (requester_id = ? AND receiver_id = ?)
           OR (requester_id = ? AND receiver_id = ?)
    ";
    $check = $this->connection->prepare($checkSql);
    $check->bind_param("iiii", $requesterId, $receiverId, $receiverId, $requesterId);
    $check->execute();
    $existing = $check->get_result();

    if ($existing->num_rows > 0) {
        return "Request already exists.";
    }

    $sql = "INSERT INTO connections (requester_id, receiver_id, status) VALUES (?, ?, 'pending')";
    $stmt = $this->connection->prepare($sql);
    $stmt->bind_param("ii", $requesterId, $receiverId);

    if ($stmt->execute()) {
        return "success";
    } else {
        return "error";
    }
}







    public function getRecommendedUsers($userId)
    {
    $sql = "
        SELECT DISTINCT u.user_id, u.user_username
        FROM user u
        INNER JOIN user_skills us ON u.user_id = us.us_user_id
        WHERE us.us_subject_id IN (
            SELECT us_subject_id FROM user_skills WHERE us_user_id = ?
        )
        AND u.user_id != ?
        AND u.user_id NOT IN (
            SELECT requester_id FROM connections WHERE receiver_id = ?
            UNION
            SELECT receiver_id FROM connections WHERE requester_id = ?
        )
    ";
    $db = new DatabaseConnection();
    $stmt = $db->connection->prepare($sql);
    if ($stmt === false) {
        $db->closeConnection();
        return false;
    }

    $stmt->bind_param("iiii", $userId, $userId, $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $stmt->close();
    $db->closeConnection();

    return $result;
}


    public function acceptRequest($connectionId, $receiverId)
    {
        $sql = "UPDATE connections SET status = 'accepted' WHERE connection_id = ? AND receiver_id = ? AND status = 'pending'";
        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            return 'error_prepare';
        }
        $stmt->bind_param('ii', $connectionId, $receiverId);
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected > 0 ? 'accepted' : 'not_authorized_or_already_handled';
        } else {
            $err = $stmt->error;
            $stmt->close();
            return 'error:' . $err;
        }
    }

    public function declineRequest($connectionId, $receiverId)
    {
        $sql = "UPDATE connections SET status = 'declined' WHERE connection_id = ? AND receiver_id = ? AND status = 'pending'";
        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            return 'error_prepare';
        }
        $stmt->bind_param('ii', $connectionId, $receiverId);
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected > 0 ? 'declined' : 'not_authorized_or_already_handled';
        } else {
            $err = $stmt->error;
            $stmt->close();
            return 'error:' . $err;
        }
    }


    public function getConnections($userId)
    {
        $sql = "
            SELECT u.user_id, u.user_username, c.status
            FROM connections c
            JOIN user u ON (u.user_id = c.requester_id OR u.user_id = c.receiver_id)
            WHERE (c.requester_id = ? OR c.receiver_id = ?)
              AND u.user_id != ?
              AND c.status = 'accepted'
        ";
        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            return false;
        }
        $stmt->bind_param("iii", $userId, $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

}
