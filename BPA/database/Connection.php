<?php
require_once 'DatabaseConnection.php';
require_once 'User.php';
class Connection {
    private $db;

    public function __construct($connection) {
        $this->db = $connection;   // ← NAME MUST MATCH
    }

    public function sendConnectionRequest($requesterId, $receiverId)
    {
        if ($receiverId === null) {
            return "Receiver ID missing";
        }

        $sql = "INSERT INTO connections (requester_id, receiver_id, status)
                VALUES (?, ?, 'pending')";
        $stmt = $this->db->prepare($sql);     // ← This MUST BE VALID

        $stmt->bind_param("ii", $requesterId, $receiverId);
        $stmt->execute();

        return "success";
    }






    public function getRecommendedUsers($userId)
{
    $sql = "
        SELECT DISTINCT u.user_id, u.name
        FROM user u
        INNER JOIN user_skills us ON u.user_id = us.user_id
        WHERE us.skill_id IN (
            SELECT skill_id FROM user_skills WHERE user_id = ?
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


}
