<?php
require_once 'DatabaseConnection.php';

class User
{
    //Properties
    public $user_id = 0;
    public $user_username = "";
    public $user_password = "";
    public $user_email = "";
    public $user_is_admin = 0;
    public $user_create_date;

    function populate($p_user_id)
    {

        //Create new connection. 
        $db = new DatabaseConnection();

        // Prepare the SELECT statement with a placeholder for the user ID
        $sql = "SELECT * FROM bpa_skillswap.user where user_id=?;";
        $stmt = $db->connection->prepare($sql);

        // Check if the statement preparation was successful
        if ($stmt === false) {
            die("Error preparing statement: " . $db->connection->error);
        }
        //Bind parameters to sql statement 
        $stmt->bind_param("i", $p_user_id);
        //Execute query
        $stmt->execute();
        // get the mysqli result
        $result = $stmt->get_result();


        if ($row = $result->fetch_assoc()) {
            $this->user_id = $p_user_id;
            $this->user_username = $row['user_username'];
            $this->user_password = $row['user_password'];
            $this->user_email = $row['user_email'];
            $this->user_is_admin = $row['user_is_admin'];
            $this->user_create_date = $row['user_create_date'];
        }
        //Clean up
        $stmt->close();
        $result->free_result();
        $db->closeConnection();
    }



    function insert()
    {
        // Create new connection
        $db = new DatabaseConnection();

        // STEP 1: Check if username or email already exists
        $checkSql = "SELECT user_id FROM bpa_skillswap.user WHERE user_username = ? OR user_email = ? LIMIT 1;";
        $checkStmt = $db->connection->prepare($checkSql);
        if ($checkStmt === false) {
            die("Error preparing check statement: " . $db->connection->error);
        }

        $checkStmt->bind_param("ss", $this->user_username, $this->user_email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $errors[] = "Username or email already exists!";
            $checkStmt->close();
            $db->closeConnection();
            return false; // Stop here — don’t insert duplicate
        }
        $checkStmt->close();

        // STEP 2: Proceed with insertion if no duplicate found
        $sql = "INSERT INTO bpa_skillswap.user (user_username, user_password, user_email, user_is_admin, user_create_date) VALUES (?, ?, ?, ?, NOW());";
        $stmt = $db->connection->prepare($sql);
        if ($stmt === false) {
            die("Error preparing insert statement: " . $db->connection->error);
        }

        $hashedPassword = password_hash($this->user_password, PASSWORD_DEFAULT);

        $stmt->bind_param("sssi", $this->user_username, $hashedPassword, $this->user_email, $this->user_is_admin);

        if ($stmt->execute()) {
            $this->user_id = $stmt->insert_id;
            echo "User registered successfully!";
        } else {
            echo "Insert failed: " . $stmt->error;
        }

        $stmt->close();
        $db->closeConnection();

        return true;
    }
    
    
    public static function validateUser($p_username, $p_password)
    {
        $userId = 0;

        //Create new connection. 
        $db = new DatabaseConnection();

        // Prepare the SELECT statement with a placeholder for the user ID
        $sql = "SELECT * FROM bpa_skillswap.user where user_username=?;";
        $stmt = $db->connection->prepare($sql);

        // Check if the statement preparation was successful
        if ($stmt === false) {
            die("Error preparing statement: " . $db->connection->error);
        }
        //Bind parameters to sql statement 
        $stmt->bind_param("s", $p_username);
        //Execute query
        $stmt->execute();
        // get the mysqli result
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $password = $row['user_password'];
            if (password_verify($p_password, $password)) {
                $userId = $row['user_id'];
            }
        }

        //Clean up
        $stmt->close();
        $result->free_result();
        $db->closeConnection();

        return $userId;
    }
}
