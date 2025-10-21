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

    // --- STEP 1: Validate password strength ---
    if (!$this->isValidPassword($this->user_password)) {
        $errors['password'] = "Password does not meet requirements!";
        return false;
    }

    // --- STEP 2: Check for existing username or email ---
    $checkSql = "SELECT user_id FROM bpa_skillswap.user WHERE user_username = ? OR user_email = ? LIMIT 1;";
    $checkStmt = $db->connection->prepare($checkSql);
    if ($checkStmt === false) {
        die("Error preparing check statement: " . $db->connection->error);
    }

    $checkStmt->bind_param("ss", $this->user_username, $this->user_email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $errors['fullName'&'email'] = "Username or email already exists!";
        $checkStmt->close();
        $db->closeConnection();
        return false;
    }
    $checkStmt->close();

    // --- STEP 3: Insert new user ---
    $sql = "INSERT INTO bpa_skillswap.user (user_username, user_password, user_email, user_is_admin, user_create_date)
            VALUES (?, ?, ?, ?, NOW());";
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

    private function isValidPassword($password) {
    // Check minimum length (8+ characters)
    if (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters long.<br>";
        return false;
    }

    // Require at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = "Password must contain at least one uppercase letter.<br>";
        return false;
    }

    // Require at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        $errors['password'] = "Password must contain at least one lowercase letter.<br>";
        return false;
    }

    // Require at least one digit
    if (!preg_match('/[0-9]/', $password)) {
        $errors['password'] = "Password must contain at least one number.<br>";
        return false;
    }

    // Require at least one special character
    if (!preg_match('/[\W_]/', $password)) {
        $errors['password'] = "Password must contain at least one special character.<br>";
        return false;
    }

    return true;
}

}


