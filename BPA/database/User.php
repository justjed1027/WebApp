<?php
    require_once 'DatabaseConnection.php'; 

    class User{
        //Fields
        public $id = 0;
        public $username = "";
        public $password = "";
        public $email = "";
        public $is_admin = 0;
        public $create_date; 

        function populate($p_user_id){

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


            if($row = $result->fetch_assoc()){
                $this->id = $p_user_id; 
                $this->username = $row['user_username'];
                $this->password = $row['user_password'];
                $this->email = $row['user_email'];
                $this->is_admin = $row['user_is_admin'];
                $this->create_date = $row['user_create_date'];
            }
            //Clean up
            $stmt->close();
            $result -> free_result();
            $db->closeConnection();
        }

        function insert(){

            //Create new connection. 
            $db = new DatabaseConnection();

            // Prepare the SELECT statement with a placeholder for the user ID
            $sql = "INSERT INTO skillswap.user (user_username, user_password, user_email, user_is_admin, user_create_date) VALUES (?,?,?,?, NOW());";
            $stmt = $db->connection->prepare($sql);

            // Check if the statement preparation was successful
            if ($stmt === false) {
                die("Error preparing statement: " . $db->connection->error);
            }
            
            //Password hash
            $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);

            //Bind parameters to sql statement 
            $stmt->bind_param("sssi", $this->username, $hashedPassword, $this->email, $this->is_admin);
            //Execute query
            if($stmt->execute()){
                $this->id = $stmt->insert_id; 
                echo "Good insert";
            }else{
                echo "Insert failed!";
            }

            //Clean up
            $stmt->close();
            $db->closeConnection();
        }

    //Static method to validate user credentials AKA LOGIN
        public static function validateUser($p_username, $p_password){
            $userId = 0; 
                        
            //Create new connection. 
            $db = new DatabaseConnection();

            // Prepare the SELECT statement with a placeholder for the user ID
            $sql = "SELECT * FROM skillswap.user where user_username=?;";
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

            if($row = $result->fetch_assoc()){
                $password = $row['user_password'];
                if(password_verify($p_password, $password)){
                    $userId = $row['user_id'];
                }
            }

           //Clean up
            $stmt->close();
            $result -> free_result();
            $db->closeConnection();

            return $userId; 
        }
    }
?>
